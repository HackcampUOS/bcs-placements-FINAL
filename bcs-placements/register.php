<?php
/**
 * Register Controller
 * Handles user registration for both students and employers
 */

require_once('Models/UserAuthentication.php');
require_once('Models/StudentDataSet.php');
require_once('Models/EmployerDataSet.php');

// Initialize view object
$view = new stdClass();
$view->pageTitle = 'Register';
$view->user = new UserAuthentication();
$view->errorMessage = '';
$view->successMessage = '';
$view->selectedRole = $_GET['role'] ?? 'student'; // Default to student

// If user is already logged in, redirect to homepage
if ($view->user->isLoggedIn()) {
    header('Location: index.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registerBtn'])) {

    $role = $_POST['role'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($email) || empty($password) || empty($confirmPassword)) {
        $view->errorMessage = 'Please fill in all required fields.';
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $view->errorMessage = 'Please enter a valid email address.';
    }
    elseif ($password !== $confirmPassword) {
        $view->errorMessage = 'Passwords do not match.';
    }
    elseif (strlen($password) < 6) {
        $view->errorMessage = 'Password must be at least 6 characters long.';
    }
    elseif (!isset($_POST['botcheck'])) {
        $view->errorMessage = 'Please confirm you are not a robot.';
    }
    else {
        // Check if email already exists
        if ($role === 'student') {
            $studentDS = new StudentDataSet();
            if ($studentDS->emailExists($email)) {
                $view->errorMessage = 'This email is already registered.';
            } else {
                // Get additional student fields
                $firstName = trim($_POST['first_name'] ?? '');
                $lastName = trim($_POST['last_name'] ?? '');

                if (empty($firstName) || empty($lastName)) {
                    $view->errorMessage = 'Please enter your first and last name.';
                } else {
                    // Register student
                    if ($studentDS->registerStudent($email, $password, $firstName, $lastName)) {
                        $view->successMessage = 'Registration successful! You can now login.';
                        // Redirect to login page after 2 seconds
                        header('refresh:2;url=login.php?role=student');
                    } else {
                        $view->errorMessage = 'Registration failed. Please try again.';
                    }
                }
            }
        }
        elseif ($role === 'employer') {
            $employerDS = new EmployerDataSet();
            if ($employerDS->emailExists($email)) {
                $view->errorMessage = 'This email is already registered.';
            } else {
                // Get additional employer fields
                $companyName = trim($_POST['company_name'] ?? '');
                $contactName = trim($_POST['contact_name'] ?? '');

                if (empty($companyName) || empty($contactName)) {
                    $view->errorMessage = 'Please enter your company name and contact name.';
                } else {
                    // Register employer
                    if ($employerDS->registerEmployer($email, $password, $companyName, $contactName)) {
                        $view->successMessage = 'Registration successful! You can now login.';
                        // Redirect to login page after 2 seconds
                        header('refresh:2;url=login.php?role=employer');
                    } else {
                        $view->errorMessage = 'Registration failed. Please try again.';
                    }
                }
            }
        }
        else {
            $view->errorMessage = 'Please select a valid role.';
        }
    }

    // Keep the selected role for the form
    $view->selectedRole = $role;
}

// Load the view
require_once('Views/register.phtml');