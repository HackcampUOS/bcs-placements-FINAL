<?php
/**
 * Homepage Controller
 *
 * Displays the main landing page for the BCS Placements application.
 * Provides an overview of the platform's features including;
 * placement browsing, student dashboard, and employer portal.
 * Adapts content based on user authentication and role.
 */

require_once('Models/UserAuthentication.php');

$view = new stdClass();
$view->pageTitle = 'Homepage';
$view->authMessage = '';
$view->user = new UserAuthentication();
require_once('Views/index.phtml');