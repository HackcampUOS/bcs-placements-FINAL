<?php
/**
 * Login Controller
 * Handles user authentication and logout for both students and employers
 */

require_once('Models/UserAuthentication.php');

// Initialize view object
$view = new stdClass();
$view->pageTitle = 'Login';
$view->user = new UserAuthentication();
$view->errorMessage = '';
$view->authMessage = '';
$view->selectedRole = $_GET['role'] ?? 'student'; // Default to student

// Handle logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $view->user->logout();
    $view->authMessage = 'You have been logged out successfully.';
}

// If user is already logged in and not logging out, redirect to homepage
if ($view->user->isLoggedIn() && !isset($_GET['action'])) {
    header('Location: index.php');
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['loginBtn'])) {

    $role = $_POST['role'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validation
    if (empty($email) || empty($password)) {
        $view->errorMessage = 'Please enter your email and password.';
    }
    elseif (!isset($_POST['botcheck'])) {
        $view->errorMessage = 'Please confirm you are not a robot.';
    }
    else {
        // Attempt login
        if ($view->user->login($email, $password, $role)) {
            // Login successful - redirect based on role
            if ($role === 'student') {
                header('Location: dashboard.php');
            } elseif ($role === 'employer') {
                header('Location: employers.php');
            } else {
                header('Location: index.php');
            }
            exit();
        } else {
            $view->errorMessage = 'Invalid email or password. Please try again.';
        }
    }

    // Keep the selected role for the form
    $view->selectedRole = $role;
}

// Load the view
require_once('Views/login.phtml');