<?php
/**
 * About Us Controller
 * Displays information about the BCS Placement Portal
 */

require_once('Models/UserAuthentication.php');

$view = new stdClass();
$view->pageTitle = 'About Us';
$view->user = new UserAuthentication();

require_once('Views/about.phtml');
