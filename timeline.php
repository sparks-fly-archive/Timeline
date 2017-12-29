<?php

// Set some useful constants that the core may require or use
define("IN_MYBB", 1);
define('THIS_SCRIPT', 'timeline.php');

// Including global.php gives us access to a bunch of MyBB functions and variables
require_once "./global.php";

// Load Language-Settings
$lang->load('timeline');

// Shorten variables
$action = $mybb->input['action'];
$uid = $mybb->user['uid'];

// Add a breadcrumb
add_breadcrumb('Stadtgeschichte', "timeline.php");

// Set navigation
eval("\$timeline_nav = \"".$templates->get("timeline_navigation")."\";");

// Set navigation so only team members can see certain options
$timeline_nav_team = "";
if($mybb->usergroup['cancp'] == "1") {
	eval("\$timeline_nav_team = \"".$templates->get("timeline_navigation_team")."\";");	
}

// Landing Page
if(empty($action)) {
	
	// Set Landing Page-Template
	eval("\$page = \"".$templates->get("timeline")."\";");
	output_page($page);
}

// FAQ-Page
if($action == "faq") {
	
	// Set FAQ Page-Template
	eval("\$page = \"".$templates->get("timeline_faq")."\";");
	output_page($page);
}

?>