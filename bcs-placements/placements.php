<?php
/**
 * Placements Controller
 * Displays all available placement opportunities with search, filter, and sort
 */

require_once('Models/UserAuthentication.php');
require_once('Models/PlacementDataSet.php');
require_once('Models/ShortlistDataSet.php');
require_once('Models/StudentDataSet.php');

// Initialize view object
$view = new stdClass();
$view->pageTitle = 'Latest Opportunities';
$view->user = new UserAuthentication();
$view->message = '';

// Get search, filter, and sort parameters
$view->searchTerm = $_GET['search'] ?? '';
$view->filterLocation = $_GET['location'] ?? '';
$view->filterEmployer = $_GET['employer'] ?? '';
$view->filterSalary = $_GET['salary_min'] ?? '';
$view->filterDuration = $_GET['duration'] ?? '';
$view->sortBy = $_GET['sort'] ?? 'latest';

// Pagination settings
$view->itemsPerPage = 10;
$view->currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($view->currentPage - 1) * $view->itemsPerPage;

// Build filters array
$filters = [];
if (!empty($view->filterLocation)) {
    $filters['location'] = $view->filterLocation;
}
if (!empty($view->filterEmployer)) {
    $filters['employer'] = $view->filterEmployer;
}
if (!empty($view->filterSalary)) {
    $filters['salary_min'] = $view->filterSalary;
}
if (!empty($view->filterDuration)) {
    $filters['duration'] = $view->filterDuration;
}

// Fetch placements
$placementDS = new PlacementDataSet();
$view->placements = $placementDS->fetchPlacements(
    $view->searchTerm,
    $filters,
    $view->sortBy,
    $view->itemsPerPage,
    $offset
);

// ----- Match percentage calculation for logged-in students -----
$view->showMatch = false;
$view->isStudent = $view->user->isLoggedIn() && $view->user->getUserType() === 'student';

if ($view->isStudent && !empty($view->placements)) {

    $studentDS = new StudentDataSet();
    $student = $studentDS->getStudentByUserId($view->user->getUserID());

    if ($student) {
        $studentId = $student->getStudentID();
        $studentSkills = $studentDS->getStudentSkills($studentId);

        // Build quick lookup: skill_code => level
        $studentSkillMap = [];
        foreach ($studentSkills as $ss) {
            $studentSkillMap[$ss['skill_code']] = (int)$ss['proficiency_level'];
        }

        // Helper to compute match %
        $computeMatch = function (array $placementSkills) use ($studentSkillMap): ?int {
            if (empty($placementSkills)) {
                return null; // No required skills -> no match shown
            }

            $totalRatio = 0;
            $count = 0;

            foreach ($placementSkills as $req) {
                $code = $req['skill_code'];
                $requiredLevel = (int)$req['required_proficiency'];
                if ($requiredLevel <= 0) {
                    continue;
                }

                $studentLevel = $studentSkillMap[$code] ?? 0;

                if ($studentLevel > 0) {
                    // Cap at required level so 7/5 doesn't exceed 100%
                    $ratio = min($studentLevel, $requiredLevel) / $requiredLevel;
                } else {
                    $ratio = 0;
                }

                $totalRatio += $ratio;
                $count++;
            }

            if ($count === 0) {
                return null;
            }

            return (int) round(($totalRatio / $count) * 100);
        };

        // Attach match % to each placement
        foreach ($view->placements as $placement) {
            $skills = $placement->getSkills();
            $match = $computeMatch($skills);
            $placement->setMatchPercentage($match);
            if ($match !== null) {
                $view->showMatch = true;
            }
        }

        // Sort by match percentage if requested
        $sort = $_GET['sort'] ?? 'latest';
        $view->sort = $sort;

        if ($sort === 'match_desc') {
            usort($view->placements, function ($a, $b) {
                return ($b->getMatchPercentage() ?? -1) <=> ($a->getMatchPercentage() ?? -1);
            });
        }
    }
}

// Get total count for pagination
$view->totalPlacements = $placementDS->countPlacements($view->searchTerm, $filters);
$view->totalPages = ceil($view->totalPlacements / $view->itemsPerPage);

// Get filter options
$view->availableLocations = $placementDS->getUniqueLocations();
$view->availableEmployers = $placementDS->getUniqueEmployers();

// Get student's shortlisted placements (if logged in as student)
$view->shortlistedIds = [];
if ($view->user->isLoggedIn() && $view->user->getUserType() === 'student') {
    $studentDS = new StudentDataSet();
    $student = $studentDS->getStudentByUserId($view->user->getUserID());

    if ($student) {
        $shortlistDS = new ShortlistDataSet();
        $view->shortlistedIds = $shortlistDS->getShortlistedPlacements($student->getStudentID());
    }
}

// Handle AJAX shortlist toggle
if (isset($_POST['toggle_shortlist']) && $view->user->isLoggedIn() && $view->user->getUserType() === 'student') {
    $placementId = $_POST['placement_id'] ?? 0;
    $action = $_POST['action'] ?? ''; // 'add' or 'remove'

    $studentDS = new StudentDataSet();
    $student = $studentDS->getStudentByUserId($view->user->getUserID());

    if ($student && $placementId) {
        $shortlistDS = new ShortlistDataSet();

        if ($action === 'add') {
            $shortlistDS->addToShortlist($student->getStudentID(), $placementId);
            echo json_encode(['success' => true, 'action' => 'added']);
        } elseif ($action === 'remove') {
            $shortlistDS->removeFromShortlist($student->getStudentID(), $placementId);
            echo json_encode(['success' => true, 'action' => 'removed']);
        }
    }
    exit();
}

// Load the view
require_once('Views/placements.phtml');