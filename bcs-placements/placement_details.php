<?php
/**
 * Placement Details Controller
 * Displays full details of a single placement opportunity
 */

require_once('Models/UserAuthentication.php');
require_once('Models/PlacementDataSet.php');
require_once('Models/ShortlistDataSet.php');
require_once('Models/StudentDataSet.php');
require_once('Models/Database.php');

// Initialize view object
$view = new stdClass();
$view->user = new UserAuthentication();
$view->message = '';
$view->errorMessage = '';

// Get placement ID from URL
$placementId = $_GET['id'] ?? 0;

if (empty($placementId)) {
    header('Location: placements.php');
    exit();
}

// Fetch placement details
$placementDS = new PlacementDataSet();
$view->placement = $placementDS->fetchPlacementById($placementId);

if (!$view->placement) {
    header('Location: placements.php');
    exit();
}

// Redirect if placement has expired
if ($view->placement->hasExpired()) {
    header('Location: placements.php');
    exit();
}

// Set page title
$view->pageTitle = $view->placement->getTitle();

// Check if shortlisted (for students)
$view->isShortlisted = false;
$view->studentId = null;

if ($view->user->isLoggedIn() && $view->user->getUserType() === 'student') {
    $studentDS = new StudentDataSet();
    $student = $studentDS->getStudentByUserId($view->user->getUserID());

    if ($student) {
        $view->studentId = $student->getStudentID();
        $shortlistDS = new ShortlistDataSet();
        $view->isShortlisted = $shortlistDS->isShortlisted($view->studentId, $placementId);
    }
}

// Match percentage for this placement (for logged-in student)
$view->matchPercentage = null;

if ($view->studentId) {
    $studentDS = $studentDS ?? new StudentDataSet(); // reuse if already created
    $studentSkills = $studentDS->getStudentSkills($view->studentId);
    $placementSkills = $view->placement->getSkills();

    // Build map: skill_code => level
    $studentSkillMap = [];
    foreach ($studentSkills as $ss) {
        $studentSkillMap[$ss['skill_code']] = (int)$ss['proficiency_level'];
    }

    $totalRatio = 0;
    $count = 0;

    foreach ($placementSkills as $req) {
        $code = $req['skill_code'];
        $requiredLevel = (int)$req['required_proficiency'];
        if ($requiredLevel <= 0) continue;

        $studentLevel = $studentSkillMap[$code] ?? 0;
        if ($studentLevel > 0) {
            $ratio = min($studentLevel, $requiredLevel) / $requiredLevel;
        } else {
            $ratio = 0;
        }

        $totalRatio += $ratio;
        $count++;
    }

    if ($count > 0) {
        $view->matchPercentage = (int) round(($totalRatio / $count) * 100);
    }
}

// Handle Apply button (create match and redirect)
if (isset($_POST['apply_btn']) && $view->user->isLoggedIn() && $view->user->getUserType() === 'student') {
    if ($view->studentId) {
        try {
            $db = Database::getInstance();
            $dbHandle = $db->getdbConnection();

            // ----------------------------
            // Level-based matching:
            // For each required skill:
            //   skill_score = min(student_level, required_level) / required_level
            // Overall match_score = average(skill_score) * 100
            // ----------------------------

            // Get placement skills (required)
            $placementSkills = $placementDS->fetchPlacementSkills($placementId);

            // Get student skills (owned)
            $studentDS = new StudentDataSet();
            $studentSkills = $studentDS->getStudentSkills($view->studentId);

            // Build [skill_code => required_level]
            $requiredLevelsByCode = [];
            foreach ($placementSkills as $ps) {
                if (!empty($ps['skill_code'])) {
                    $code = $ps['skill_code'];
                    $requiredLevelsByCode[$code] = (int)($ps['required_proficiency'] ?? 0);
                }
            }

            // Build [skill_code => student_level]
            $studentLevelsByCode = [];
            foreach ($studentSkills as $ss) {
                if (!empty($ss['skill_code'])) {
                    $code = $ss['skill_code'];
                    $studentLevelsByCode[$code] = (int)($ss['proficiency_level'] ?? 0);
                }
            }

            $matchScore = 0;
            $requiredCount = count($requiredLevelsByCode);

            if ($requiredCount > 0) {
                $totalScore = 0.0;

                foreach ($requiredLevelsByCode as $code => $requiredLevel) {
                    if ($requiredLevel <= 0) {
                        continue;
                    }

                    $studentLevel = $studentLevelsByCode[$code] ?? 0;
                    $skillScore = min($studentLevel, $requiredLevel) / $requiredLevel; // 0–1

                    $totalScore += $skillScore;
                }

                $matchScore = (int) round(($totalScore / $requiredCount) * 100);
            } else {
                // If no required skills defined, treat as neutral → 50%
                $matchScore = 50;
            }

            // Insert match (or error if already exists)
            $sql = "INSERT INTO matches (student_id, placement_id, match_score, status) 
                    VALUES (:student_id, :placement_id, :match_score, 'pending')";

            $statement = $dbHandle->prepare($sql);
            $statement->bindParam(':student_id', $view->studentId, PDO::PARAM_INT);
            $statement->bindParam(':placement_id', $placementId, PDO::PARAM_INT);
            $statement->bindParam(':match_score', $matchScore, PDO::PARAM_INT);
            $statement->execute();

            // Redirect to external application URL if present
            $applicationUrl = $view->placement->getApplicationUrl();
            if (!empty($applicationUrl)) {
                header('Location: ' . $applicationUrl);
                exit();
            } else {
                $view->message = 'Application submitted! The employer will be notified.';
            }

        } catch (PDOException $e) {
            // If duplicate (already applied), just redirect if URL exists
            if (strpos($e->getMessage(), 'UNIQUE constraint failed') !== false) {
                $applicationUrl = $view->placement->getApplicationUrl();
                if (!empty($applicationUrl)) {
                    header('Location: ' . $applicationUrl);
                    exit();
                } else {
                    $view->errorMessage = 'You have already applied to this placement.';
                }
            } else {
                $view->errorMessage = 'Error submitting application. Please try again.';
            }
        }
    }
}

// Handle shortlist toggle
if (isset($_POST['toggle_shortlist']) && $view->user->isLoggedIn() && $view->user->getUserType() === 'student') {
    if ($view->studentId) {
        $shortlistDS = new ShortlistDataSet();

        if ($view->isShortlisted) {
            $shortlistDS->removeFromShortlist($view->studentId, $placementId);
            $view->isShortlisted = false;
            $view->message = 'Removed from <a href="dashboard.php#shortlist-section" class="fw-bold text-decoration-underline shortlist-toggle-text">shortlist</a>.';

        } else {
            $shortlistDS->addToShortlist($view->studentId, $placementId);
            $view->isShortlisted = true;
            $view->message = 'Added to <a href="dashboard.php#shortlist-section" class="fw-bold text-decoration-underline shortlist-toggle-text">shortlist</a>!';

        }
    }
}

// Load the view
require_once('Views/placement_details.phtml');