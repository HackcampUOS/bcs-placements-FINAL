<?php
/**
 * Student Dashboard Controller
 * Manages student profile, CV, skills, shortlisted placements, and applications
 */

require_once('Models/UserAuthentication.php');
require_once('Models/StudentDataSet.php');
require_once('Models/PlacementDataSet.php');
require_once('Models/ShortlistDataSet.php');
require_once('Models/Database.php');

// Initialize view
$view = new stdClass();
$view->pageTitle = 'My Dashboard';
$view->user = new UserAuthentication();
$view->message = '';
$view->errorMessage = '';

// Redirect if not logged in or not a student
if (!$view->user->isLoggedIn() || $view->user->getUserType() !== 'student') {
    header('Location: login.php');
    exit();
}

// Get student data
$studentDS = new StudentDataSet();
$view->student = $studentDS->getStudentByUserId($view->user->getUserID());

if (!$view->student) {
    $view->errorMessage = 'Student profile not found.';
    require_once('Views/dashboard.phtml');
    exit();
}

// Get student skills
$view->skills = $studentDS->getStudentSkills($view->student->getStudentID());

// Get all SFIA skills for modal dropdown
$view->allSkills = $studentDS->getAllSkills();

// Get shortlisted placements
$shortlistDS = new ShortlistDataSet();
$shortlistedIds = $shortlistDS->getShortlistedPlacements($view->student->getStudentID());
$view->shortlistedCount = count($shortlistedIds);

// Fetch shortlisted placement details (first 3)
$view->shortlistedPlacements = [];
$placementDS = new PlacementDataSet();
foreach ($shortlistedIds as $placementId) {
    $placement = $placementDS->fetchPlacementById($placementId);
    if ($placement) {
        $view->shortlistedPlacements[] = $placement;
    }
}

// Get applications (matches) - all placements that still exist
try {
    $db = Database::getInstance();
    $dbHandle = $db->getdbConnection();

    $studentId = $view->student->getStudentID();

    $sql = "SELECT m.status, m.match_score, p.placement_id
            FROM matches m
            INNER JOIN placements p ON m.placement_id = p.placement_id
            WHERE m.student_id = :student_id
            ORDER BY m.created_at DESC";

    $stmt = $dbHandle->prepare($sql);
    $stmt->bindParam(':student_id', $studentId, PDO::PARAM_INT);
    $stmt->execute();

    $view->applications = [];
    $matchedCount = 0;

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $placement = $placementDS->fetchPlacementById($row['placement_id']);
        if (!$placement) {
            // Placement was deleted – skip
            continue;
        }

        $status       = $row['status'];
        $matchPercent = (int) round($row['match_score']);

        if ($status === 'interested') {   // "interested" = confirmed/matched
            $matchedCount++;
        }

        $view->applications[] = [
            'placement'        => $placement,
            'status'           => $status,
            'match_percentage' => $matchPercent,
        ];
    }

    // Counts now match exactly what the page shows
    $view->applicationsCount = count($view->applications);
    $view->matchedCount      = $matchedCount;

} catch (PDOException $e) {
    $view->applications      = [];
    $view->applicationsCount = 0;
    $view->matchedCount      = 0;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Update Name
    if (isset($_POST['update_name'])) {
        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');

        if (empty($firstName) || empty($lastName)) {
            $view->errorMessage = 'Please enter both first and last name.';
        } else {
            if ($studentDS->updateName($view->user->getUserID(), $firstName, $lastName)) {
                $view->message = 'Name updated successfully!';
                // Refresh student data
                $view->student = $studentDS->getStudentByUserId($view->user->getUserID());
                // Update session
                $_SESSION['user_name'] = $firstName . ' ' . $lastName;
            } else {
                $view->errorMessage = 'Failed to update name.';
            }
        }
    }

    // Update Email
    if (isset($_POST['update_email'])) {
        $newEmail = trim($_POST['new_email'] ?? '');
        $password = $_POST['email_password'] ?? '';

        if (empty($newEmail) || empty($password)) {
            $view->errorMessage = 'Please enter new email and password.';
        } elseif (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $view->errorMessage = 'Please enter a valid email address.';
        } else {
            if ($studentDS->updateEmail($view->user->getUserID(), $newEmail, $password)) {
                $view->message = 'Email updated successfully! Please use your new email to login next time.';
                // Update session
                $_SESSION['user_email'] = $newEmail;
            } else {
                $view->errorMessage = 'Failed to update email. Please check your password.';
            }
        }
    }

    // Update Password
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
            if ($studentDS->updatePassword($view->user->getUserID(), $currentPassword, $newPassword)) {
                $view->message = 'Password updated successfully!';
            } else {
                $view->errorMessage = 'Failed to update password. Please check your current password.';
            }
        }
    }

    // Save/Update Profile Details (Phone, Address, University, Course)
    if (isset($_POST['update_profile'])) {
        $data = [
            'phone' => trim($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'university' => trim($_POST['university'] ?? ''),
            'course' => trim($_POST['course'] ?? '')
        ];

        if ($studentDS->updateProfile($view->user->getUserID(), $data)) {
            $view->message = 'Profile details saved successfully!';
            $view->student = $studentDS->getStudentByUserId($view->user->getUserID());
        } else {
            $view->errorMessage = 'Failed to save profile details.';
        }
    }

    // Upload CV
    if (isset($_POST['upload_cv']) && isset($_FILES['cv_file'])) {
        $file = $_FILES['cv_file'];

        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $view->errorMessage = 'File upload error.';
        } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB max
            $view->errorMessage = 'File size must be less than 5MB.';
        } elseif (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'pdf') {
            $view->errorMessage = 'Only PDF files are allowed.';
        } else {
            // Create upload directory if not exists
            $uploadDir = __DIR__ . '/uploads/cvs/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $filename = 'cv_' . $view->student->getStudentID() . '_' . time() . '.pdf';
            $filepath = $uploadDir . $filename;

            // Delete old CV if exists
            if ($view->student->hasCv() && file_exists($view->student->getCvFilepath())) {
                unlink($view->student->getCvFilepath());
            }

            // Move uploaded file
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                if ($studentDS->uploadCV($view->user->getUserID(), $filename, $filepath)) {
                    $view->message = 'CV uploaded successfully!';
                    $view->student = $studentDS->getStudentByUserId($view->user->getUserID());
                } else {
                    $view->errorMessage = 'Failed to save CV to database.';
                }
            } else {
                $view->errorMessage = 'Failed to upload file.';
            }
        }
    }

    // Delete CV
    if (isset($_POST['delete_cv'])) {
        if ($studentDS->deleteCV($view->user->getUserID())) {
            $view->message = 'CV deleted successfully.';
            $view->student = $studentDS->getStudentByUserId($view->user->getUserID());
        } else {
            $view->errorMessage = 'Failed to delete CV.';
        }
    }

    // Remove from shortlist
    if (isset($_POST['remove_shortlist'])) {
        $placementId = $_POST['placement_id'] ?? 0;
        if ($placementId) {
            if ($shortlistDS->removeFromShortlist($view->student->getStudentID(), $placementId)) {
                $view->message = 'Placement removed from shortlist.';
                // Refresh page to update shortlist
                header('Location: dashboard.php');
                exit();
            } else {
                $view->errorMessage = 'Failed to remove from shortlist.';
            }
        }
    }

    // Delete Account
    if (isset($_POST['delete_account'])) {
        $password = $_POST['delete_password'] ?? '';

        if (empty($password)) {
            $view->errorMessage = 'Please enter your password to confirm account deletion.';
        } else {
            if ($studentDS->deleteAccount($view->user->getUserID(), $password)) {
                // Log the user out and redirect to login page
                $view->user->logout();
                header('Location: login.php');
                exit();
            } else {
                $view->errorMessage = 'Account deletion failed. Please check your password and try again.';
            }
        }
    }
}

// Handle AJAX requests for skills and remove shortlist
if (isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');

    if ($_POST['ajax_action'] === 'add_skill') {
        $skillId = $_POST['skill_id'] ?? 0;
        $proficiency = $_POST['proficiency'] ?? 0;

        if ($skillId && $proficiency >= 1 && $proficiency <= 7) {
            if ($studentDS->addSkill($view->student->getStudentID(), $skillId, $proficiency)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to add skill']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid data']);
        }

    } elseif ($_POST['ajax_action'] === 'remove_skill') {
        $skillId = $_POST['skill_id'] ?? 0;

        if ($skillId) {
            if ($studentDS->removeSkill($view->student->getStudentID(), $skillId)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to remove skill']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid data']);
        }

    } elseif ($_POST['ajax_action'] === 'remove_shortlisted') {

        $placementId = isset($_POST['placement_id']) ? (int) $_POST['placement_id'] : 0;
        $studentId   = $view->student->getStudentID();

        if ($placementId && $studentId) {
            $shortlistDS = new ShortlistDataSet();
            if ($shortlistDS->removeFromShortlist($studentId, $placementId)) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to remove shortlist entry']);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid placement or student']);
        }
    }

    exit;
}

// Load view
require_once('Views/dashboard.phtml');