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

$placementDS = new PlacementDataSet();

// Is the viewer a logged-in student?
$view->isStudent = $view->user->isLoggedIn() && $view->user->getUserType() === 'student';
$view->showMatch = false;

// Preload student skills if needed
$studentSkillMap = [];
if ($view->isStudent) {
    $studentDS = new StudentDataSet();
    $student = $studentDS->getStudentByUserId($view->user->getUserID());

    if ($student) {
        $studentId = $student->getStudentID();
        $studentSkills = $studentDS->getStudentSkills($studentId);

        foreach ($studentSkills as $ss) {
            $studentSkillMap[$ss['skill_code']] = (int)$ss['proficiency_level'];
        }
    }
}

// Helper to compute match %, reused in both paths
$computeMatch = function (array $placementSkills) use ($studentSkillMap): ?int {
    if (empty($placementSkills) || empty($studentSkillMap)) {
        return null; // No required or no student skills
    }

    $totalRatio = 0;
    $count = 0;

    foreach ($placementSkills as $req) {
        $code          = $req['skill_code'];
        $requiredLevel = (int)$req['required_proficiency'];

        if ($requiredLevel <= 0) {
            continue;
        }

        $studentLevel = $studentSkillMap[$code] ?? 0;

        if ($studentLevel > 0) {
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

// ----- PATH A: Sort by match (global, across all pages) -----
if ($view->sortBy === 'match_desc' && $view->isStudent && !empty($studentSkillMap)) {

    // 1) Fetch ALL matching placements (no pagination in SQL)
    $allPlacements = $placementDS->fetchAllPlacements(
        $view->searchTerm,
        $filters
    );

    // 2) Compute match % for each placement
    foreach ($allPlacements as $placement) {
        $skills = $placement->getSkills();
        $match  = $computeMatch($skills);
        $placement->setMatchPercentage($match);
        if ($match !== null) {
            $view->showMatch = true;
        }
    }

    // 3) Sort globally by match (desc), nulls at the bottom
    usort($allPlacements, function ($a, $b) {
        return ($b->getMatchPercentage() ?? -1) <=> ($a->getMatchPercentage() ?? -1);
    });

    // 4) Now paginate on the sorted array
    $view->totalPlacements = count($allPlacements);
    $view->totalPages      = max(1, ceil($view->totalPlacements / $view->itemsPerPage));

    $view->placements = array_slice(
        $allPlacements,
        $offset,
        $view->itemsPerPage
    );

// ----- PATH B: All other sorts use SQL pagination as before -----
} else {
    // Fetch page from DB using chosen sort
    $view->placements = $placementDS->fetchPlacements(
        $view->searchTerm,
        $filters,
        $view->sortBy,
        $view->itemsPerPage,
        $offset
    );

    // Compute match % just for display (no reordering)
    if ($view->isStudent && !empty($view->placements) && !empty($studentSkillMap)) {
        foreach ($view->placements as $placement) {
            $skills = $placement->getSkills();
            $match  = $computeMatch($skills);
            $placement->setMatchPercentage($match);
            if ($match !== null) {
                $view->showMatch = true;
            }
        }
    }

    // Total count for pagination (SQL count, as before)
    $view->totalPlacements = $placementDS->countPlacements($view->searchTerm, $filters);
    $view->totalPages      = max(1, ceil($view->totalPlacements / $view->itemsPerPage));
}

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