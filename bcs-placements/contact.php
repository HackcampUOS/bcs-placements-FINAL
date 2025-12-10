<?php
/**
 * Contact Controller
 * Displays contact page and handles contact form submissions
 */

require_once('Models/UserAuthentication.php');

$view = new stdClass();
$view->pageTitle = 'Contact Us';
$view->user = new UserAuthentication();
$view->message = '';
$view->errorMessage = '';

// Pre-fill name & email if logged in
$view->contactName  = '';
$view->contactEmail = '';
$view->contactRole  = '';
$view->contactSubject = '';
$view->contactMessage = '';

if ($view->user->isLoggedIn()) {
    $view->contactName  = $view->user->getUserName();
    $view->contactEmail = $view->user->getUserEmail();
    $view->contactRole  = $view->user->getUserType(); // 'student' or 'employer'
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact_submit'])) {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $role    = trim($_POST['role'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Preserve user input on error
    $view->contactName    = $name;
    $view->contactEmail   = $email;
    $view->contactRole    = $role;
    $view->contactSubject = $subject;
    $view->contactMessage = $message;

    // Basic validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $view->errorMessage = 'Please fill in all required fields (Name, Email, Subject, and Message).';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $view->errorMessage = 'Please enter a valid email address.';
    } else {
        // For HackCamp, we'll just "fake send" and show a success message.
        // If you ever want to wire this to a real inbox, you could do e.g.:
        //
        // $to      = 'placements@example.ac.uk';
        // $headers = 'From: ' . $email . "\r\n" .
        //            'Reply-To: ' . $email . "\r\n" .
        //            'X-Mailer: PHP/' . phpversion();
        // @mail($to, "[BCS Portal] $subject", "From: $name ($role)\n\n$message", $headers);
        //
        // But for now, we just show feedback to the user.

        $view->message = 'Thanks for reaching out! Your message has been received. 
                          A member of the team will review it and get back to you as soon as possible.';

        // Optionally clear the form after success
        $view->contactSubject = '';
        $view->contactMessage = '';
    }
}

require_once('Views/contact.phtml');
