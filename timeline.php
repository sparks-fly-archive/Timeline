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
add_breadcrumb('Stadtgedächtnis', "timeline.php");

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

	$eventdate = strtotime($mybb->get_input('year')."-".$mybb->get_input('month')."-".$mybb->get_input('day'));

	// data to insert into database
	$new_record = array(
		"title" => $db->escape_string($mybb->get_input('name')),
		"date" => $eventdate,
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

// show specific event
if($action == "view") {
	$eid = $mybb->input['id'];
	
	// get event matching id
	$query = $db->query("SELECT * FROM ".TABLE_PREFIX."timeline WHERE eid = '$eid'");
	$event = $db->fetch_array($query);
	
	// format date
	$event['date'] = date("d.m.Y", $event['date']);
	
	// format author username
	$user = get_user($event['uid']);
	// if author doesn't exist, make Snorre author ;)
	if(empty($user)) {
		$user = get_user("1");
	}
	$user['profile_link'] = build_profile_link($user['username'], $event['uid']);
	
	// format tagged members
	$members = explode(",", $event['tagged']);
	foreach($members as $member) {
		$member = get_user($member);
		$member['profile_link'] = build_profile_link($member['username'], $member['uid']);
		eval("\$member_bit .= \"".$templates->get("timeline_view_member_bit")."\";");
	}
	eval("\$member = \"".$templates->get("timeline_view_member")."\";");
	
	// set show specific event-template
	eval("\$page = \"".$templates->get("timeline_view")."\";");
	output_page($page);
	
}

// show forums' timeline
if($action == "history") {

	$year = $db->escape_string($mybb->input['year']);
	if(empty($year)) {
		$year = "%";
	}

	// get years
	$years_bit = "";
	$query = $db->query("SELECT DISTINCT from_unixtime(date, '%Y') AS year FROM ".TABLE_PREFIX."timeline ORDER by date DESC");
	while($years = $db->fetch_array($query)) {
		$years_bit .= "<div class=\"nav-year trow2\"><a href=\"timeline.php?action=history&year={$years['year']}\">{$years['year']}</a></div>";
	}
	
	// get events 
	$query = $db->query("SELECT * FROM ".TABLE_PREFIX."timeline
	WHERE from_unixtime(date, '%Y') = '$year' ORDER BY date ASC");
	while($event = $db->fetch_array($query)) {
		$member_bit = "";
		$months_translate = array(
			"January" => "Januar",
			"February" => "Februar",
			"March" => "März",
			"April" => "April",
			"May" => "Mai",
			"June" => "Juni",
			"July" => "Juli",
			"August" => "August",
			"September" => "September",
			"October" => "Oktober",
			"November" => "November",
			"December" => "Dezember"
		);
		
		$end_day = date("d", $event['date']);
		$end_month = date("F", $event['date']);
		$end_year = date("Y", $event['date']);
		$end_month = $months_translate[$end_month];
		
		// format tagged members
		$members = explode(",", $event['tagged']);
		foreach($members as $member) {
			$member = get_user($member);
			$member['profile_link'] = build_profile_link($member['username'], $member['uid']);
			$member_bit .= "<div class=\"member-bit trow2\">{$member['profile_link']}</div>";
		}

		if(my_strlen($event['description']) > 250)
		{
			$event['description'] = my_substr($event['description'], 0, 250)."...";
			$event['description'] .= "<a href=\"timeline.php?action=view&id={$event['eid']}\" target=\"_blank\">[ Weiterlesen ]</a>";
		}

		eval("\$history_bit .= \"".$templates->get("timeline_history_bit")."\";");
		
	}
	
	// set show forums timeline-template
	eval("\$page = \"".$templates->get("timeline_history")."\";");
	output_page($page);	
}

// User-Timeline
if($mybb->input['action'] == "utimeline") {
	
	$archiv = $mybb->settings['inplaytracker_archiv'];
	$ipforum = $mybb->settings['inplaytracker_forum'];
	
	$szenen_bit = "";
	$query = $db->query("SELECT * , ".TABLE_PREFIX."threads.tid, ".TABLE_PREFIX."threads.partners, ".TABLE_PREFIX."timeline_users.description,  ".TABLE_PREFIX."threads.subject FROM ".TABLE_PREFIX."threads
	LEFT JOIN ".TABLE_PREFIX."forums ON ".TABLE_PREFIX."forums.fid = ".TABLE_PREFIX."threads.fid
	LEFT JOIN ".TABLE_PREFIX."timeline_users ON ".TABLE_PREFIX."timeline_users.tid = ".TABLE_PREFIX."threads.tid
	WHERE (".TABLE_PREFIX."forums.parentlist LIKE '%$ipforum%' OR ".TABLE_PREFIX."forums.parentlist LIKE '%,$archiv%')
	AND ".TABLE_PREFIX."threads.visible = '1'
	GROUP by ".TABLE_PREFIX."threads.tid
	ORDER by ".TABLE_PREFIX."threads.ipdate ASC
	");
	while($szenen = $db->fetch_array($query)) {
		$ownuid = $mybb->user['uid'];
		$szenen_partner = ",".$szenen['partners'].",";
		if(preg_match("/,$ownuid,/i", $szenen_partner)) {
			eval("\$szenen_bit .= \"".$templates->get("characp_timeline_bit")."\";");
		}	
	}
	
    eval("\$page = \"".$templates->get("timeline_utimeline")."\";");
    output_page($page);
}

if($mybb->input['action'] == "do_timeline") {
	$new_record = array(
		"description" => $db->escape_string($mybb->input['description']),
		"tid" => (int)$mybb->input['tid'],
		"uid" => $uid
	);
	if(!empty($mybb->input['eid'])) {
		$db->update_query("timeline_users", $new_record, "eid = '{$mybb->input['eid']}'");
	}
	else {
		$db->insert_query("timeline_users", $new_record);
	}
	redirect("timeline.php?action=utimeline", "Die Beschreibung wurde hinzugefügt!");
	
}

?>