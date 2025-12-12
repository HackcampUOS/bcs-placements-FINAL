<?php
/**
 * Employer Portal Controller
 * Manages employer profile, placements, and matched students
 */

require_once('Models/UserAuthentication.php');
require_once('Models/EmployerDataSet.php');
require_once('Models/PlacementDataSet.php');
require_once('Models/StudentDataSet.php');
require_once('Models/Database.php');

// Initialize
$view = new stdClass();
$view->pageTitle = 'Employer Portal';
$view->user = new UserAuthentication();
$view->message = '';
$view->errorMessage = '';

// Redirect if not logged in or not employer
if (!$view->user->isLoggedIn() || $view->user->getUserType() !== 'employer') {
    header('Location: login.php');
    exit();
}

// Get employer data
$employerDS = new EmployerDataSet();
$view->employer = $employerDS->getEmployerByUserId($view->user->getUserID());

if (!$view->employer) {
    $view->errorMessage = 'Employer profile not found.';
    require_once('Views/employers.phtml');
    exit();
}

// Get employer stats
$db = Database::getInstance();
$dbHandle = $db->getdbConnection();
$employerId = $view->employer->getEmployerID();

// Placements stats
$sql = "SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active
        FROM placements 
        WHERE employer_id = :employer_id";
$stmt = $dbHandle->prepare($sql);
$stmt->bindParam(':employer_id', $employerId, PDO::PARAM_INT);
$stmt->execute();
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$view->totalPlacements   = $stats['total'] ?? 0;
$view->activePlacements  = $stats['active'] ?? 0;

// Applications received (all matches)
$sql = "SELECT COUNT(*) 
        FROM matches m
        INNER JOIN placements p ON m.placement_id = p.placement_id
        WHERE p.employer_id = :employer_id";
$stmt = $dbHandle->prepare($sql);
$stmt->bindParam(':employer_id', $employerId, PDO::PARAM_INT);
$stmt->execute();
$view->applicationsReceived = (int)$stmt->fetchColumn();

// Confirmed matches (status = 'interested' used as 'confirmed')
$sql = "SELECT COUNT(*) 
        FROM matches m
        INNER JOIN placements p ON m.placement_id = p.placement_id
        WHERE p.employer_id = :employer_id
          AND m.status = 'interested'";
$stmt = $dbHandle->prepare($sql);
$stmt->bindParam(':employer_id', $employerId, PDO::PARAM_INT);
$stmt->execute();
$view->confirmedMatches = (int)$stmt->fetchColumn();

// Get all placements
$placementDS = new PlacementDataSet();
$view->placements = [];
$sql = "SELECT p.*, e.company_logo AS company_logo
        FROM placements p
        INNER JOIN employers e ON p.employer_id = e.employer_id
        WHERE p.employer_id = :employer_id
        ORDER BY p.created_at DESC";
$stmt = $dbHandle->prepare($sql);
$stmt->bindParam(':employer_id', $employerId, PDO::PARAM_INT);
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $placement = new PlacementData($row);
    $skills = $placementDS->fetchPlacementSkills($placement->getPlacementId());
    $placement->setSkills($skills);
    $view->placements[] = $placement;
}

// Get all SFIA skills
$studentDS = new StudentDataSet();
$view->allSkills = $studentDS->getAllSkills();

// Get received applications (matches for this employer)
$view->receivedApplications = [];
try {
    $sql = "SELECT m.student_id, m.placement_id, m.status, m.match_score, m.created_at,
                   s.first_name, s.last_name, u.email AS student_email,
                   p.title
            FROM matches m
            INNER JOIN students s ON m.student_id = s.student_id
            INNER JOIN users u ON s.user_id = u.user_id
            INNER JOIN placements p ON m.placement_id = p.placement_id
            WHERE p.employer_id = :employer_id
            ORDER BY m.created_at DESC";

    $stmt = $dbHandle->prepare($sql);
    $stmt->bindParam(':employer_id', $employerId, PDO::PARAM_INT);
    $stmt->execute();

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $placement = $placementDS->fetchPlacementById($row['placement_id']);
        if (!$placement) {
            continue;
        }

        // Fetch skills
        $placementSkills = $placementDS->fetchPlacementSkills($row['placement_id']);
        $studentSkills   = $studentDS->getStudentSkills($row['student_id']);

        /**
         * Level-based matching:
         * For each required skill:
         *   skill_score = min(student_level, required_level) / required_level
         * Overall match = average of all skill_score values * 100
         */

        // Build [skill_code => required_level] for placement
        $requiredLevelsByCode = [];
        foreach ($placementSkills as $ps) {
            if (!empty($ps['skill_code'])) {
                $code = $ps['skill_code'];
                $requiredLevelsByCode[$code] = (int)($ps['required_proficiency'] ?? 0);
            }
        }

        // Build [skill_code => student_level] for student
        $studentLevelsByCode = [];
        foreach ($studentSkills as $ss) {
            if (!empty($ss['skill_code'])) {
                $code = $ss['skill_code'];
                $studentLevelsByCode[$code] = (int)($ss['proficiency_level'] ?? 0);
            }
        }

        $matchPercentage = 0;
        $requiredCount   = count($requiredLevelsByCode);

        if ($requiredCount > 0) {
            $totalScore = 0.0;

            foreach ($requiredLevelsByCode as $code => $requiredLevel) {
                if ($requiredLevel <= 0) {
                    continue; // safety check
                }

                $studentLevel = $studentLevelsByCode[$code] ?? 0;

                // Per-skill score in [0,1]
                $skillScore = min($studentLevel, $requiredLevel) / $requiredLevel;

                $totalScore += $skillScore;
            }

            // Average across required skills -> %
            $matchPercentage = (int) round(($totalScore / $requiredCount) * 100);
        }

        $view->receivedApplications[] = [
            'student_id'        => (int)$row['student_id'],
            'student_name'      => trim($row['first_name'] . ' ' . $row['last_name']),
            'student_email'     => $row['student_email'],
            'placement'         => $placement,
            'placement_skills'  => $placementSkills,
            'student_skills'    => $studentSkills,
            'status'            => $row['status'] ?? 'applied',
            'match_percentage'  => $matchPercentage,
        ];
    }
} catch (PDOException $e) {
    $view->receivedApplications = [];
}

// Handle profile update (phone, address, website, description, contact name, logo)
if (isset($_POST['update_profile'])) {
    $contactName = trim($_POST['contact_name'] ?? $view->employer->getContactName());
    $logoPath = $view->employer->getCompanyLogo();

    // Handle logo upload (optional)
    if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['company_logo'];

        if ($file['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

            if (in_array($ext, $allowed, true)) {
                $uploadDir = __DIR__ . '/uploads/logos/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                $filename = 'logo_' . $view->employer->getEmployerID() . '_' . time() . '.' . $ext;
                $filepath = $uploadDir . $filename;

                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    // Store web path in DB (used directly in img src)
                    $logoPath = '/uploads/logos/' . $filename;
                } else {
                    $view->errorMessage = 'Failed to upload company logo.';
                }
            } else {
                $view->errorMessage = 'Invalid logo file type. Please upload an image file (JPG, PNG, GIF, WEBP).';
            }
        } else {
            $view->errorMessage = 'Error uploading company logo.';
        }
    }

    $data = [
        'phone'               => trim($_POST['phone'] ?? ''),
        'address'             => trim($_POST['address'] ?? ''),
        'company_description' => trim($_POST['company_description'] ?? ''),
        'website'             => trim($_POST['website'] ?? ''),
        'contact_name'        => $contactName,
        'company_logo'        => $logoPath
    ];

    if ($employerDS->updateProfile($view->user->getUserID(), $data)) {
        $view->message = 'Profile updated successfully!';
        $_SESSION['user_name'] = $contactName;
        $view->employer = $employerDS->getEmployerByUserId($view->user->getUserID());
    } else {
        $view->errorMessage = 'Failed to update profile.';
    }
}

// Handle employer password update
if (isset($_POST['update_password'])) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $view->errorMessage = 'Please fill in all password fields.';
    } elseif ($newPassword !== $confirmPassword) {
        $view->errorMessage = 'New passwords do not match.';
    } elseif (strlen($newPassword) < 6) {
        $view->errorMessage = 'New password must be at least 6 characters.';
    } else {
        if ($employerDS->updatePassword($view->user->getUserID(), $currentPassword, $newPassword)) {
            $view->message = 'Password updated successfully!';
        } else {
            $view->errorMessage = 'Failed to update password. Please check your current password.';
        }
    }
}

// Handle post placement
if (isset($_POST['post_placement'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $salaryMin = $_POST['salary_min'] ?? 0;
    $salaryMax = $_POST['salary_max'] ?? 0;
    $startDate = $_POST['start_date'] ?? '';
    $endDate = $_POST['end_date'] ?? '';
    $deadline = $_POST['deadline'] ?? '';
    $deadline = $deadline === '' ? null : $deadline;
    $duration = $_POST['duration'] ?? 12;
    $appUrl = trim($_POST['application_url'] ?? '');

    if (empty($title) || empty($description) || empty($location)) {
        $view->errorMessage = 'Please fill in all required fields.';
    } else {
        $sql = "INSERT INTO placements (employer_id, title, description, location, salary_min, salary_max, 
                start_date, end_date, deadline, duration_months, application_url, status, created_at) 
                VALUES (:emp_id, :title, :desc, :loc, :sal_min, :sal_max, :start, :end, :dead, :dur, :url, 'active', CURRENT_TIMESTAMP)";
        $stmt = $dbHandle->prepare($sql);
        $stmt->execute([
            ':emp_id' => $employerId,
            ':title' => $title,
            ':desc' => $description,
            ':loc' => $location,
            ':sal_min' => $salaryMin,
            ':sal_max' => $salaryMax,
            ':start' => $startDate,
            ':end' => $endDate,
            ':dead' => $deadline,
            ':dur' => $duration,
            ':url' => $appUrl
        ]);

        $placementId = $dbHandle->lastInsertId();

        // Add skills (skills[skill_id] = required_level)
        if (!empty($_POST['skills']) && is_array($_POST['skills'])) {
            foreach ($_POST['skills'] as $skillId => $proficiency) {
                $skillId = (int)$skillId;
                $proficiency = (int)$proficiency;

                if ($skillId > 0 && $proficiency > 0) {
                    $sql = "INSERT INTO placement_skills (placement_id, skill_id, required_proficiency) 
                            VALUES (?, ?, ?)";
                    $stmt = $dbHandle->prepare($sql);
                    $stmt->execute([$placementId, $skillId, $proficiency]);
                }
            }
        }

        $view->message = 'Placement posted successfully!';
        header('Location: employers.php');
        exit();
    }
}

// Toggle placement status (active <-> inactive)
if (isset($_POST['toggle_status'])) {
    $placementId = isset($_POST['placement_id']) ? (int)$_POST['placement_id'] : 0;

    if ($placementId) {
        $sql = "UPDATE placements
                SET status = CASE 
                             WHEN status = 'active' THEN 'inactive'
                             WHEN status = 'inactive' THEN 'active'
                             ELSE status
                             END
                             WHERE placement_id = :id AND employer_id = :emp_id";
        $stmt = $dbHandle->prepare($sql);
        $stmt->execute([':id' => $placementId, ':emp_id' => $employerId]);

        $view->message = 'Placement status updated.';
        header('Location: employers.php');
        exit();
    }
}

// Confirm match for an application
if (isset($_POST['confirm_match'])) {
    $studentId       = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
    $placementId     = isset($_POST['placement_id']) ? (int)$_POST['placement_id'] : 0;
    $matchPercentage = isset($_POST['match_percentage']) ? (int)$_POST['match_percentage'] : 0;

    if ($studentId && $placementId) {
        // Update match record – NOTE: matches table has NO updated_at column
        $sql = "UPDATE matches
                SET status = 'interested',
                    match_percentage = :match_percentage
                WHERE student_id = :student_id
                  AND placement_id = :placement_id";

        $stmt = $dbHandle->prepare($sql);
        $stmt->execute([
            ':match_percentage' => $matchPercentage,
            ':student_id'       => $studentId,
            ':placement_id'     => $placementId
        ]);

        // Do NOT mark placement as filled anymore (per your requirements)
        // If you still have an UPDATE placements ... here, remove it.

        $view->message = 'Match confirmed and an email has been sent to the student!';
        header('Location: employers.php');
        exit();
    } else {
        $view->errorMessage = 'Invalid match selection.';
    }
}

// Delete placement
if (isset($_POST['delete_placement'])) {
    $placementId = $_POST['placement_id'] ?? 0;
    if ($placementId) {
        $sql = "DELETE FROM placements WHERE placement_id = :id AND employer_id = :emp_id";
        $stmt = $dbHandle->prepare($sql);
        $stmt->execute([':id' => $placementId, ':emp_id' => $employerId]);
        $view->message = 'Placement deleted successfully.';
        header('Location: employers.php');
        exit();
    }
}

// Delete Employer Account
if (isset($_POST['delete_account'])) {
    $password = $_POST['delete_password'] ?? '';

    if (empty($password)) {
        $view->errorMessage = 'Please enter your password to confirm account deletion.';
    } else {
        if ($employerDS->deleteAccount($view->user->getUserID(), $password)) {
            // Log the user out and redirect to login page
            $view->user->logout();
            header('Location: login.php');
            exit();
        } else {
            $view->errorMessage = 'Account deletion failed. Please check your password and try again.';
        }
    }
}

require_once('Views/employers.phtml');
