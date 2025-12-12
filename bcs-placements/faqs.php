<?php
/**
 * FAQ Controller
 * Frequently Asked Questions about the BCS Placement Portal
 */

require_once('Models/UserAuthentication.php');

$view = new stdClass();
$view->pageTitle = 'Frequently Asked Questions';
$view->user = new UserAuthentication();

// Load the view
require_once('Views/faqs.phtml');
