<?php

// set some useful constants that the core may require or use
define("IN_MYBB", 1);
define('THIS_SCRIPT', 'timeline.php');

// including global.php gives us access to a bunch of MyBB functions and variables
require_once "./global.php";

// load language-settings
$lang->load('timeline');

// shorten variables
$action = $mybb->input['action'];
$uid = $mybb->user['uid'];

// add a breadcrumb
add_breadcrumb('Stadtgeschichte', "timeline.php");

// set navigation
eval("\$timeline_nav = \"".$templates->get("timeline_navigation")."\";");

// set navigation so only team members can see certain options
$timeline_nav_team = "";
if($mybb->usergroup['cancp'] == "1") {
	eval("\$timeline_nav_team = \"".$templates->get("timeline_navigation_team")."\";");	
}

// landing page
if(empty($action)) {
	
	// set landing page-template
	eval("\$page = \"".$templates->get("timeline")."\";");
	output_page($page);
}

// FAQ-page
if($action == "faq") {
	
	// set FAQ-page-template
	eval("\$page = \"".$templates->get("timeline_faq")."\";");
	output_page($page);
}

// add event-page
if($action == "add") {

	// format date dropdowns
	for($i=1 ; $i <=31; $i++) {
    	$day_bit .= "<option value=\"{$i}\">{$i}</option>";
  	}

  	$months = array("01" => "Januar", "02" => "Februar", "03" => "März", "04" => "April", "05" => "Mai", "06" => "Juni", "07" => "Juli", "08" => "August", "09" => "September", "10" => "Oktober", "11" => "November", "12" => "Dezember");
  	foreach($months as $key => $value) {
    	$month_bit .= "<option value=\"{$key}\">{$value}</option>";
  	}

 	for($i=1970 ; $i <=2017; $i++) {
    	$year_bit .= "<option value=\"{$i}\">{$i}</option>";
  	}

	// set add event-page-template
	eval("\$page = \"".$templates->get("timeline_add")."\";");
	output_page($page);
}

// add event backend
if($action == "do_add") {

	$members_new = explode(",", $mybb->get_input('members'));
	$members_new = array_map("trim", $members_new);
	$members_uids = array();
	foreach($members_new as $member) {
		$db->escape_string($member);
		$member_uid = $db->fetch_field($db->query("SELECT uid FROM ".TABLE_PREFIX."users WHERE username = '$member'"), "uid");
		$members_uids[] = $member_uid;
	}
	$members_uids = implode(",", $members_uids);

	$ipdate = strtotime($mybb->get_input('year')."-".$mybb->get_input('month')."-".$mybb->get_input('day'));

	// data to insert into database
	$new_record = array(
		"title" => $db->escape_string($mybb->get_input('name')),
		"date" => $ipdate,
		"description" => $db->escape_string($mybb->get_input('desc')),
		"tagged" => $members_uids,
		"uid" => (int)$uid,
		"accepted" => (int)"1"
	);

	// insert entry
	$db->insert_query("timeline", $new_record);

	// stuff is done, redirect to landing page
	redirect("timeline.php", "{$lang->timeline_added}");
}

?>