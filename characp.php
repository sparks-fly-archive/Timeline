<?php

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'characp.php');

require_once "./global.php";
require_once MYBB_ROOT."inc/class_parser.php";
$parser = new postParser;
$uid = $mybb->user['uid'];

$lang->load('timeline');


if(!empty($mybb->input['user'])) {
	$uid = $mybb->input['user'];
	$query = $db->query("SELECT * FROM mybb_characters
		LEFT JOIN mybb_userjobs ON mybb_characters.uid = mybb_userjobs.uid
		WHERE mybb_characters.uid = '$uid'");
	$chara = $db->fetch_array($query);
	$url_user = "&user={$uid}";
}

add_breadcrumb("Charakter Control Panel", "characp.php");


// Charakter einsenden
if($chara['done'] == "0") {
  $menu_send = "<tr>
		<td class=\"trow1 smalltext\"><i class=\"fa fa-sign-in\" aria-hidden=\"true\"></i> <a href=\"characp.php?action=send_chara\"><span style=\"color: #dd0000; font-weight: bold;\">Charakter einsenden</span></a></td>
	</tr>";
}
elseif($chara['done'] == "1" && $chara['accepted'] == "0") {
  $menu_send = "<tr>
		<td class=\"trow1 smalltext\"><i class=\"fa fa-sign-in\" aria-hidden=\"true\"></i> <a href=\"characp.php?action=withdraw_chara&uid={$uid}\"><span style=\"color: #dd0000; font-weight: bold;\">Charakter zurückziehen</span></a></td>
	</tr>";
}

// Navigation
eval("\$menu .= \"".$templates->get("characp_nav")."\";");

// Charakter lesen
if($mybb->input['action'] == "read_chara") {
  if($mybb->usergroup['cancp']) {
    $cid = $mybb->get_input('cid');
    $new_record = array(
      "read" => $db->escape_string($mybb->user['fid1'])
    );
    $db->update_query("characters", $new_record, "cid = '$cid'");
    $auid = $db->fetch_field($db->query("SELECT uid FROM mybb_characters WHERE cid = '$cid'"), "uid");
    $new_alert = array(
      "uid" => $uid,
      "auid" => $auid,
      "type" => "2",
      "dateline" => TIME_NOW
    );
    $db->insert_query("alerts", $new_alert);
    redirect("index.php", "Dieser Charakter wurde aktualisiert!");
  }
}

// Charakter woben
if($mybb->input['action'] == "accept_chara") {
  if($mybb->usergroup['cancp']) {
    $auid = $mybb->get_input('uid');
    $group = "8";
    $new_record = array(
      "accepted" => "1"
    );
    $db->update_query("characters", $new_record, "uid = '$auid'");
    $new_record = array(
      "usergroup" => $group
    );
    $db->update_query("users", $new_record, "uid = '$auid'");
    $new_alert = array(
      "uid" => $uid,
      "auid" => $auid,
      "type" => "3",
      "dateline" => TIME_NOW
    );
    $db->insert_query("alerts", $new_alert);
    redirect("index.php", "Dieser Charakter wurde aktualisiert!");
  }
}

// Charakter einsenden
if($mybb->input['action'] == "send_chara") {
  $new_record = array(
    "done" => (int)"1"
  );
  $db->update_query("characters", $new_record, "uid = '$uid'");
  redirect("characp.php", "Dein Charakter wurde aktualisiert!");
}

// Charakter einsenden
if($mybb->input['action'] == "withdraw_chara") {
	$auid = $mybb->get_input('uid');
  $new_record = array(
    "done" => (int)"0"
  );
  $db->update_query("characters", $new_record, "uid = '$auid'");
  redirect("index.php", "Der Charakter wurde aktualisiert!");
}

// Charakter-File erstellen
if($mybb->input['action'] == "do_create" && $mybb->request_method == "post") {
  $new_record = array(
    "uid" => (int)$uid
  );
  $db->insert_query("characters", $new_record);
  redirect("characp.php", "Dein Charakter wurde aktualisiert!");
}

// Charakter CP-Startseite
if(!$mybb->input['action'])
{

  if(!$chara['cid']) {
    eval("\$page = \"".$templates->get("characp_start")."\";");
    output_page($page);
  }

  else {
    eval("\$page = \"".$templates->get("characp")."\";");
    output_page($page);
  }
}

// Aussehen-Profilfelder einfügen:
if($mybb->input['action'] == "do_look" && $mybb->request_method == "post") {
  $new_record = array(
    "avatar" => $db->escape_string($mybb->get_input('avatarperson')),
    "icon" => $db->escape_string($mybb->get_input('icon')),
    "height" => $db->escape_string($mybb->get_input('koerpergroesse')),
    "sex" => $db->escape_string($mybb->get_input('sex'))
  );
  $db->update_query("characters", $new_record, "uid = '$uid'");
  redirect("characp.php?action=look", "Dein Charakter wurde aktualisiert!");
}

// Avatar hochladen
if($mybb->input['action'] == "do_avatar" && $mybb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($mybb->get_input('my_post_key'));

	$plugins->run_hooks("usercp_do_avatar_start");
	require_once MYBB_ROOT."inc/functions_upload.php";

	$avatar_error = "";

	if(!empty($mybb->input['remove'])) // remove avatar
	{
		$updated_avatar = array(
			"avatar" => "",
			"avatardimensions" => "",
			"avatartype" => ""
		);
		$db->update_query("users", $updated_avatar, "uid='".$mybb->user['uid']."'");
		remove_avatars($mybb->user['uid']);
	}
	elseif($_FILES['avatarupload']['name']) // upload avatar
	{
		if($mybb->usergroup['canuploadavatars'] == 0)
		{
			error_no_permission();
		}
		$avatar = upload_avatar();
		if($avatar['error'])
		{
			$avatar_error = $avatar['error'];
		}
		else
		{
			if($avatar['width'] > 0 && $avatar['height'] > 0)
			{
				$avatar_dimensions = $avatar['width']."|".$avatar['height'];
			}
			$updated_avatar = array(
				"avatar" => $avatar['avatar'].'?dateline='.TIME_NOW,
				"avatardimensions" => $avatar_dimensions,
				"avatartype" => "upload"
			);
			$db->update_query("users", $updated_avatar, "uid='".$mybb->user['uid']."'");
		}
	}
	elseif($mybb->settings['allowremoteavatars']) // remote avatar
	{
		$mybb->input['avatarurl'] = trim($mybb->get_input('avatarurl'));
		if(validate_email_format($mybb->input['avatarurl']) != false)
		{
			// Gravatar
			$mybb->input['avatarurl'] = my_strtolower($mybb->input['avatarurl']);

			// If user image does not exist, or is a higher rating, use the mystery man
			$email = md5($mybb->input['avatarurl']);

			$s = '';
			if(!$mybb->settings['maxavatardims'])
			{
				$mybb->settings['maxavatardims'] = '100x100'; // Hard limit of 100 if there are no limits
			}

			// Because Gravatars are square, hijack the width
			list($maxwidth, $maxheight) = explode("x", my_strtolower($mybb->settings['maxavatardims']));
			$maxheight = (int)$maxwidth;

			// Rating?
			$types = array('g', 'pg', 'r', 'x');
			$rating = $mybb->settings['useravatarrating'];

			if(!in_array($rating, $types))
			{
				$rating = 'g';
			}

			$s = "?s={$maxheight}&r={$rating}&d=mm";

			$updated_avatar = array(
				"avatar" => "https://www.gravatar.com/avatar/{$email}{$s}",
				"avatardimensions" => "{$maxheight}|{$maxheight}",
				"avatartype" => "gravatar"
			);

			$db->update_query("users", $updated_avatar, "uid = '{$mybb->user['uid']}'");
		}
		else
		{
			$mybb->input['avatarurl'] = preg_replace("#script:#i", "", $mybb->get_input('avatarurl'));
			$ext = get_extension($mybb->input['avatarurl']);

			// Copy the avatar to the local server (work around remote URL access disabled for getimagesize)
			$file = fetch_remote_file($mybb->input['avatarurl']);
			if(!$file)
			{
				$avatar_error = $lang->error_invalidavatarurl;
			}
			else
			{
				$tmp_name = $mybb->settings['avataruploadpath']."/remote_".md5(random_str());
				$fp = @fopen($tmp_name, "wb");
				if(!$fp)
				{
					$avatar_error = $lang->error_invalidavatarurl;
				}
				else
				{
					fwrite($fp, $file);
					fclose($fp);
					list($width, $height, $type) = @getimagesize($tmp_name);
					@unlink($tmp_name);
					if(!$type)
					{
						$avatar_error = $lang->error_invalidavatarurl;
					}
				}
			}

			if(empty($avatar_error))
			{
				if($width && $height && $mybb->settings['maxavatardims'] != "")
				{
					list($maxwidth, $maxheight) = explode("x", my_strtolower($mybb->settings['maxavatardims']));
					if(($maxwidth && $width > $maxwidth) || ($maxheight && $height > $maxheight))
					{
						$lang->error_avatartoobig = $lang->sprintf($lang->error_avatartoobig, $maxwidth, $maxheight);
						$avatar_error = $lang->error_avatartoobig;
					}
				}
			}

			if(empty($avatar_error))
			{
				if($width > 0 && $height > 0)
				{
					$avatar_dimensions = (int)$width."|".(int)$height;
				}
				$updated_avatar = array(
					"avatar" => $db->escape_string($mybb->input['avatarurl'].'?dateline='.TIME_NOW),
					"avatardimensions" => $avatar_dimensions,
					"avatartype" => "remote"
				);
				$db->update_query("users", $updated_avatar, "uid='".$mybb->user['uid']."'");
				remove_avatars($mybb->user['uid']);
			}
		}
	}
	else // remote avatar, but remote avatars are not allowed
	{
		$avatar_error = $lang->error_remote_avatar_not_allowed;
	}

	if(empty($avatar_error))
	{
		$plugins->run_hooks("usercp_do_avatar_end");
		redirect("characp.php?action=look", $lang->redirect_avatarupdated);
	}
	else
	{
		$mybb->input['action'] = "avatar";
		$avatar_error = inline_error($avatar_error);
	}
}

// Signatur hochladen
if($mybb->input['action'] == "do_editsig" && $mybb->request_method == "post")
{
	// Verify incoming POST request
	verify_post_check($mybb->get_input('my_post_key'));

	$plugins->run_hooks("usercp_do_editsig_start");

	// User currently has a suspended signature
	if($mybb->user['suspendsignature'] == 1 && $mybb->user['suspendsigtime'] > TIME_NOW)
	{
		error_no_permission();
	}

	if($mybb->get_input('updateposts') == "enable")
	{
		$update_signature = array(
			"includesig" => 1
		);
		$db->update_query("posts", $update_signature, "uid='".$mybb->user['uid']."'");
	}
	elseif($mybb->get_input('updateposts') == "disable")
	{
		$update_signature = array(
			"includesig" => 0
		);
		$db->update_query("posts", $update_signature, "uid='".$mybb->user['uid']."'");
	}
	$new_signature = array(
		"signature" => $db->escape_string($mybb->get_input('signature'))
	);
	$plugins->run_hooks("usercp_do_editsig_process");
	$db->update_query("users", $new_signature, "uid='".$mybb->user['uid']."'");
	$plugins->run_hooks("usercp_do_editsig_end");
	redirect("characp.php?action=look", $lang->redirect_sigupdated);
}

if($mybb->input['action']=="look")
{

  // Load global language phrases
  $lang->load("usercp");

  // Avatar
  $avatarmsg = $avatarurl = '';

	if($mybb->user['avatartype'] == "upload" || stristr($mybb->user['avatar'], $mybb->settings['avataruploadpath']))
	{
		$avatarmsg = "<br /><strong>".$lang->already_uploaded_avatar."</strong>";
	}
	elseif($mybb->user['avatartype'] == "remote" || my_validate_url($mybb->user['avatar']))
	{
		$avatarmsg = "<br /><strong>".$lang->using_remote_avatar."</strong>";
		$avatarurl = htmlspecialchars_uni($mybb->user['avatar']);
	}

	$useravatar = format_avatar($mybb->user['avatar'], $mybb->user['avatardimensions'], '100x100');
	eval("\$currentavatar = \"".$templates->get("usercp_avatar_current")."\";");

	if($mybb->settings['maxavatardims'] != "")
	{
		list($maxwidth, $maxheight) = explode("x", my_strtolower($mybb->settings['maxavatardims']));
		$lang->avatar_note .= "<br />".$lang->sprintf($lang->avatar_note_dimensions, $maxwidth, $maxheight);
	}

	if($mybb->settings['avatarsize'])
	{
		$maxsize = get_friendly_size($mybb->settings['avatarsize']*1024);
		$lang->avatar_note .= "<br />".$lang->sprintf($lang->avatar_note_size, $maxsize);
	}

	$plugins->run_hooks("usercp_avatar_intermediate");

	$auto_resize = '';
	if($mybb->settings['avatarresizing'] == "auto")
	{
		eval("\$auto_resize = \"".$templates->get("usercp_avatar_auto_resize_auto")."\";");
	}
	else if($mybb->settings['avatarresizing'] == "user")
	{
		eval("\$auto_resize = \"".$templates->get("usercp_avatar_auto_resize_user")."\";");
	}

	$avatarupload = '';
	if($mybb->usergroup['canuploadavatars'] == 1)
	{
		eval("\$avatarupload = \"".$templates->get("usercp_avatar_upload")."\";");
	}

	$avatar_remote = '';
	if($mybb->settings['allowremoteavatars'] == 1)
	{
		eval("\$avatar_remote = \"".$templates->get("usercp_avatar_remote")."\";");
	}

	$removeavatar = '';
	if(!empty($mybb->user['avatar']))
	{
		eval("\$removeavatar = \"".$templates->get("usercp_avatar_remove")."\";");
	}

	$plugins->run_hooks("usercp_avatar_end");

	if(!isset($avatar_error))
	{
		$avatar_error = '';
	}

  // Signatur
  $plugins->run_hooks("usercp_editsig_start");
	if(!empty($mybb->input['preview']) && empty($error))
	{
		$sig = $mybb->get_input('signature');
		$template = "usercp_editsig_preview";
	}
	elseif(empty($error))
	{
		$sig = $mybb->user['signature'];
		$template = "usercp_editsig_current";
	}
	else
	{
		$sig = $mybb->get_input('signature');
		$template = false;
	}

	if(!isset($error))
	{
		$error = '';
	}

	if($mybb->user['suspendsignature'] && ($mybb->user['suspendsigtime'] == 0 || $mybb->user['suspendsigtime'] > 0 && $mybb->user['suspendsigtime'] > TIME_NOW))
	{
		// User currently has no signature and they're suspended
		error($lang->sig_suspended);
	}

	if($mybb->usergroup['canusesig'] != 1)
	{
		// Usergroup has no permission to use this facility
		error_no_permission();
	}
	else if($mybb->usergroup['canusesig'] == 1 && $mybb->usergroup['canusesigxposts'] > 0 && $mybb->user['postnum'] < $mybb->usergroup['canusesigxposts'])
	{
		// Usergroup can use this facility, but only after x posts
		error($lang->sprintf($lang->sig_suspended_posts, $mybb->usergroup['canusesigxposts']));
	}

	$signature = '';
	if($sig && $template)
	{
		$sig_parser = array(
			"allow_html" => $mybb->settings['sightml'],
			"allow_mycode" => $mybb->settings['sigmycode'],
			"allow_smilies" => $mybb->settings['sigsmilies'],
			"allow_imgcode" => $mybb->settings['sigimgcode'],
			"me_username" => $mybb->user['username'],
			"filter_badwords" => 1
		);

		if($mybb->user['showimages'] != 1)
		{
			$sig_parser['allow_imgcode'] = 0;
		}

		$sigpreview = $parser->parse_message($sig, $sig_parser);
		eval("\$signature = \"".$templates->get($template)."\";");
	}

	// User has a current signature, so let's display it (but show an error message)
	if($mybb->user['suspendsignature'] && $mybb->user['suspendsigtime'] > TIME_NOW)
	{
		$plugins->run_hooks("usercp_editsig_end");

		// User either doesn't have permission, or has their signature suspended
		eval("\$editsig = \"".$templates->get("usercp_editsig_suspended")."\";");
	}
	else
	{
		// User is allowed to edit their signature
		if($mybb->settings['sigsmilies'] == 1)
		{
			$sigsmilies = $lang->on;
			$smilieinserter = build_clickable_smilies();
		}
		else
		{
			$sigsmilies = $lang->off;
		}
		if($mybb->settings['sigmycode'] == 1)
		{
			$sigmycode = $lang->on;
		}
		else
		{
			$sigmycode = $lang->off;
		}
		if($mybb->settings['sightml'] == 1)
		{
			$sightml = $lang->on;
		}
		else
		{
			$sightml = $lang->off;
		}
		if($mybb->settings['sigimgcode'] == 1)
		{
			$sigimgcode = $lang->on;
		}
		else
		{
			$sigimgcode = $lang->off;
		}
		$sig = htmlspecialchars_uni($sig);
		$lang->edit_sig_note2 = $lang->sprintf($lang->edit_sig_note2, $sigsmilies, $sigmycode, $sigimgcode, $sightml, $mybb->settings['siglength']);

		if($mybb->settings['bbcodeinserter'] != 0 || $mybb->user['showcodebuttons'] != 0)
		{
			$codebuttons = build_mycode_inserter("signature");
		}

		$plugins->run_hooks("usercp_editsig_end");
	}

  $genders = array("m" => "männlich", "w" => "weiblich");
  foreach($genders as $key => $value) {
    $checked = "";
    if($chara['sex'] == $key) { $checked = "selected"; }
    $gender_bit .= "<option value=\"$key\" {$checked}>$value</option>";
  }

  eval("\$page = \"".$templates->get("characp_look")."\";");
  output_page($page);
}

// Listen-Einträge bearbeiten
if($mybb->input['action'] == "do_listen" && $mybb->request_method == "post") {
  $new_record = array(
    "age" => (int)$mybb->get_input('age'),
    "birthday" => $db->escape_string($mybb->get_input('day').".".$mybb->get_input('month').".".$mybb->get_input('year')),
  );
  $db->update_query("characters", $new_record, "uid = '$uid'");
  
  $new_record = array(
	  "uid" => (int)$uid,
      "position" => $db->escape_string($mybb->get_input('job')),
      "jid" => (int)$mybb->get_input('jid')
  );
  
  $check = $db->fetch_field($db->query("SELECT COUNT(*) as counted FROM mybb_userjobs WHERE uid = '$uid'"), "counted");

  if(!$check) {
	$db->insert_query('userjobs', $new_record);
  }

  else {
  	$db->update_query('userjobs', $new_record, "uid = '$uid'");
  	}

  redirect("characp.php?action=listen", "Dein Charakter wurde aktualisiert!");
}

if($mybb->input['action']=="listen") {
  $birthday = explode(".", $chara['birthday']);
  for($i=1 ; $i <=31; $i++) {
    $checked = "";
    if($birthday['0'] == $i) { $checked = "selected"; }
    $day_bit .= "<option value=\"{$i}\" {$checked}>{$i}</option>";
  }

  $months = array("01" => "Januar", "02" => "Februar", "03" => "März", "04" => "April", "05" => "Mai", "06" => "Juni", "07" => "Juli", "08" => "August", "09" => "September", "10" => "Oktober", "11" => "November", "12" => "Dezember");
  foreach($months as $key => $value) {
    $checked = "";
    if($birthday['1'] == $key) { $checked = "selected"; }
    $month_bit .= "<option value=\"{$key}\" {$checked}>{$value}</option>";
  }

  for($i=1917 ; $i <=2017; $i++) {
    $checked = "";
    if($birthday['2'] == $i) { $checked = "selected"; }
    $year_bit .= "<option value=\"{$i}\" {$checked}>{$i}</option>";
  }

  $query = $db->query("SELECT jid, name FROM mybb_jobs
  ORDER BY name ASC");
  while($jobs = $db->fetch_array($query)) {
    $checked = "";
    if($chara['jid'] == $jobs['jid']) { $checked = "selected"; }
    $job_bit .= "<option value=\"{$jobs['jid']}\" {$checked}>{$jobs['name']}</option>";
  }
  

  eval("\$page = \"".$templates->get("characp_listen")."\";");
  output_page($page);
}

// Fakten bearbeiten
if($mybb->input['action'] == "do_facts" && $mybb->request_method == "post") {
  $new_record = array(
    "description" => $db->escape_string($mybb->get_input('description')),
    "strength" => $db->escape_string($mybb->get_input('strength1').", ".$mybb->get_input('strength2').", ".$mybb->get_input('strength3').", ".$mybb->get_input('strength4').", ".$mybb->get_input('strength5')),
    "weakness" => $db->escape_string($mybb->get_input('weak1').", ".$mybb->get_input('weak2').", ".$mybb->get_input('weak3').", ".$mybb->get_input('weak4').", ".$mybb->get_input('weak5')),
    "positive" => $db->escape_string($mybb->get_input('love1').", ".$mybb->get_input('love2').", ".$mybb->get_input('love3').", ".$mybb->get_input('love4').", ".$mybb->get_input('love5')),
    "negative" => $db->escape_string($mybb->get_input('hate1').", ".$mybb->get_input('hate2').", ".$mybb->get_input('hate3').", ".$mybb->get_input('hate4').", ".$mybb->get_input('hate5')),
    "fact" => $db->escape_string($mybb->get_input('fact')),
    "rumor" => $db->escape_string($mybb->get_input('rumor'))
  );
  $db->update_query("characters", $new_record, "uid = '$uid'");
  redirect("characp.php?action=facts", "Dein Charakter wurde aktualisiert!");
}
if($mybb->input['action']=="facts") {
  $weakness = explode(", ", $chara['weakness']);
  $strength = explode(", ", $chara['strength']);
  $love = explode(", ", $chara['positive']);
  $hate = explode(", ", $chara['negative']);
  eval("\$facts_rumor = \"".$templates->get("characp_facts_rumor")."\";");
  eval("\$page = \"".$templates->get("characp_facts")."\";");
  output_page($page);
}

// Steckbrief bearbeiten
if($mybb->input['action'] == "do_steckbrief" && $mybb->request_method == "post") {
    $new_record = array(
      "application" => $db->escape_string($mybb->get_input('application'))
    );
    $db->update_query("characters", $new_record, "uid = '$uid'");
    redirect("characp.php?action=steckbrief", "Dein Charakter wurde aktualisiert!");
}

if($mybb->input['action']=="steckbrief") {
	
	$raw_chara['application'] = $chara['application'];
	
	$options = array(
		"allow_html" => 1,
		"allow_mycode" => 1,
		"allow_smilies" => 1,
		"allow_imgcode" => 1,
		"filter_badwords" => 0,
		"nl2br" => 1,
		"allow_videocode" => 1,
	);
	
	$chara['application'] = $parser->parse_message($chara['application'], $options);

  eval("\$page = \"".$templates->get("characp_application")."\";");
  output_page($page);
}

// Postpartnersuche

if($mybb->input['action'] == "do_postpartner" && $mybb->request_method == "post") {
  $new_record = array(
    "psactive" => (int)$mybb->get_input('psactive'),
    "pslength" => $mybb->get_input('length'),
    "psintval" => $mybb->get_input('intval')
  );
  $db->update_query("characters", $new_record, "uid = '$uid'");
  redirect("characp.php?action=postpartner", "Dein Charakter wurde aktualisiert!");
}

if($mybb->input['action']=="postpartner") {

  $psactive_opt = array("0" => "Nein", "1" => "Ja");
  foreach($psactive_opt as $key => $value) {
    $checked = "";
    if($chara['psactive'] == $key) { $checked = "selected"; }
    $psactive_bit .= "<option value=\"$key\" {$checked}>{$value}</option>";
  }

  $pslength_opt = array("1" => "bis 2000 Zeichen", "2" => "2.000 Zeichen bis 5.000 Zeichen", "3" => "5.000 bis 8.000 Zeichen", "4" => "ab 8.000 Zeichen", "0" => "variabel");
  foreach($pslength_opt as $key => $value) {
    $checked = "";
    if($chara['pslength'] == $key) { $checked = "selected"; }
    $pslength_bit .= "<option value=\"$key\" {$checked}>{$value}</option>";
  }

  $psintval_opt = array("1" => "Schnell", "2" => "Gemütlich", "0" => "Variabel");
  foreach($psintval_opt as $key => $value) {
    $checked = "";
    if($chara['psintval'] == $key) { $checked = "selected"; }
    $psintval_bit .= "<option value=\"$key\" {$checked}>{$value}</option>";
  }

  $query = $db->query("SELECT * FROM mybb_characters LEFT JOIN mybb_users ON mybb_characters.uid = mybb_users.uid WHERE psactive = '1' AND(pslength LIKE '%$chara[pslength]%' OR pslength = '') AND(psintval LIKE '%$chara[psintval]%' OR psintval = '') AND mybb_characters.uid IN(SELECT uid FROM mybb_users) AND mybb_characters.uid != '$uid' ORDER BY username ASC");
  while($ps_chara = $db->fetch_array($query)) {
    $formattedname = format_name($ps_chara['username'], $ps_chara['usergroup'], $ps_chara['displaygroup']);
    $ps_chara['profilelink'] = build_profile_link($formattedname, $ps_chara['uid']);
    $intval = $ps_chara['psintval'];
    $length = $ps_chara['pslength'];
    eval("\$postpartner_bit .= \"".$templates->get("characp_postpartner_bit")."\";");
  }

  eval("\$page = \"".$templates->get("characp_postpartner")."\";");
  output_page($page);
}

if($mybb->input['action'] == "do_delrel") {
  $rid = $mybb->get_input('rid');
  $suid = $db->fetch_field($db->query("SELECT suid FROM mybb_rprelations WHERE rid = '$rid'"), "suid");
  $ruid = $db->fetch_field($db->query("SELECT ruid FROM mybb_rprelations WHERE rid = '$rid'"), "ruid");
  if($uid == $ruid || $uid == $suid) {
    $db->delete_query("rprelations", "rid = '$rid'");
  }
  redirect("characp.php?action=relations", "Dein Charakter wurde aktualisiert!");
}

// Relations
if($mybb->input['action'] == "relations") {

  $query = $db->query("SELECT * FROM mybb_rprelations
  LEFT JOIN mybb_users ON mybb_users.uid = mybb_rprelations.ruid
  WHERE suid = '$uid'
   AND ruid IN(SELECT uid FROM mybb_users)
  ORDER BY mybb_users.username ASC");
  while($sent = $db->fetch_array($query)) {
    $formattedname = format_name($sent['username'], $sent['usergroup'], $sent['displaygroup']);
    $sent['profilelink'] = build_profile_link($formattedname, $sent['ruid']);
    eval("\$sent_bit .= \"".$templates->get("characp_relations_sent")."\";");
  }

  $query = $db->query("SELECT * FROM mybb_rprelations
  LEFT JOIN mybb_users ON mybb_users.uid = mybb_rprelations.suid
  WHERE ruid = '$uid'
   AND suid IN (SELECT uid FROM mybb_users)
  ORDER BY mybb_users.username ASC");
  while($received = $db->fetch_array($query)) {
    $formattedname = format_name($received['username'], $received['usergroup'], $received['displaygroup']);
    $received['profilelink'] = build_profile_link($formattedname, $received['ruid']);
    eval("\$received_bit .= \"".$templates->get("characp_relations_received")."\";");
  }

  eval("\$page = \"".$templates->get("characp_relations")."\";");
  output_page($page);
}
?>
