<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.");
}


function timeline_info()
{
	return array(
		"name"			=> "Stadtgedächtnis",
		"description"	=> "Erstellt Tabellen, Templates und Verknüpfungen für die <em>Stadtgedächtnis</em>-Erweiterung.",
		"website"		=> "http://dirty-paws.de",
		"author"		=> "sparks fly",
		"authorsite"	=> "http://github.com/user/its-sparks-fly",
		"version"		=> "1.0",
		"compatibility" => "*"
	);
}

function timeline_install()
{
	global $db;

	if(!$db->table_exists("timeline")) {
		$db->query("CREATE TABLE `mybb_timeline` (
  				`eid` int(11) NOT NULL AUTO_INCREMENT,
  				`uid` int(11) NOT NULL,
  				`date` varchar(20) NOT NULL,
  				`title` text NOT NULL,
  				`description` text NOT NULL,
  				`tagged` text NOT NULL,
  				`accepted` int(1) NOT NULL,
  				PRIMARY KEY (`eid`)
			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");
	}

	if(!$db->table_exists("timeline_users")) {
		$db->query("CREATE TABLE `mybb_timeline_users` (
			  	`eid` int(11) NOT NULL AUTO_INCREMENT,
  				`uid` int(11) NOT NULL,
  				`tid` int(11) NOT NULL,
  				`description` text NOT NULL,
  				PRIMARY KEY (`eid`)
  			) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;");
	}

	if($db->table_exists("characters")) {
		$db->query("ALTER TABLE `".TABLE_PREFIX."characters` ADD `rumor` text NOT NULL AFTER `fact`;");
	}
}

function timeline_is_installed()
{
    global $db;

    if($db->table_exists("timeline")) {
        return true;
    }
    return false;
}

function timeline_uninstall()
{
	global $db;

	if($db->table_exists("timeline")) {
		$db->query("DROP TABLE `mybb_timeline`");
	}

	if($db->table_exists("timeline_users")) {
		$db->query("DROP TABLE `mybb_timeline_users`");
	}

  	if($db->field_exists("rumor", "characters"))
 	{
  	  $db->drop_column("characters", "rumor");
  	}
}

function timeline_activate()
{
	global $db, $mybb;

	// CSS	
	$css = array(
		'name' => 'timeline.css',
		'tid' => 1,
		"stylesheet" =>	'.nav-year {
	float: left;
	margin: 3px;
	width: 12%;
	padding: 10px;
	text-align: center;
	letter-spacing: 2px;
	font-size: 9px;
}

.member-bit {
	display: inline-block;
	padding: 5px;
	margin: 3px;
	text-align: center;
	letter-spacing: 1px;
	font-size: 8px;
	text-transform: uppercase;
}

.end_date_cal {
	display: block;
	background: rgba(255,255,255,.6);
	text-align: center;
	height: 75px;
}

.end_date_top {
	background: #E04343;
	padding: 1px;
	font-size: 9px;
	text-transform: uppercase;
	letter-spacing: 1px;
	color: rgba(255,255,255,.8) !Important;
}

.end_date_day {
	font-size: 35px;
	font-style: italic;
	color: rgba(0,0,0,.7);
	margin: 3px;
	margin-top: 10px;
}

.end_date_month {
	font-size: 9px;
	text-transform: uppercase;
	letter-spacing: 1px;
	margin: 3px;
}',
		'cachefile' => $db->escape_string(str_replace('/', '', timeline.css)),
		'lastmodified' => time()
	);

	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";

	$sid = $db->insert_query("themestylesheets", $css);
	$db->update_query("themestylesheets", array("cachefile" => "css.php?stylesheet=".$sid), "sid = '".$sid."'", 1);

	$tids = $db->simple_select("themes", "tid");
	while($theme = $db->fetch_array($tids)) {
		update_theme_stylesheet_list($theme['tid']);
	}

	  include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("characp_facts", "#".preg_quote('<div align="center">')."#i", '{$facts_rumor} <div align="center">');

	  $insert_array = array(
		'title'		=> 'timeline',
		'template'	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->timeline}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
<td width="23%" valign="top">
{$timeline_nav}
</td>
<td valign="top">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="{$colspan}"><strong>{$lang->timeline}</strong></td>
</tr>
<tr>
<td class="trow2" style="padding: 10px; text-align: justify;">
<div style="width: 95%; margin: auto; padding: 8px;  font-size: 12px; line-height: 1.5em;" class="trow1">
	<i class="fab fa-pied-piper-alt" aria-hidden="true" style="float: left; font-size: 50px; margin: 5px; margin-right: 15px;"></i> {$lang->timeline_welcome}

</div>
</td>
</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

		  $insert_array = array(
		'title'		=> 'timeline_add',
		'template'	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->timeline_send}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
<td width="23%" valign="top">
{$timeline_nav}
</td>
<td valign="top">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="{$colspan}"><strong>{$lang->timeline_send}</strong></td>
</tr>
<tr>
<td class="trow2" style="padding: 10px; text-align: justify;">
<div style="width: 95%; margin: auto; padding: 8px;  font-size: 12px; line-height: 1.5em;" class="trow1">
	<i class="fas fa-pencil-alt" aria-hidden="true" style="float: left; font-size: 50px; margin: 5px; margin-right: 15px;"></i> {$lang->timeline_add_desc}
<br /><br/>
	<form method="post" action="timeline.php" id="add_event">
	<table cellspacing="3" cellpadding="3" class="tborder" style="width: 90%";>
		<tr>
			<td class="tcat" colspan="2">
				{$lang->timeline_send}
			</td>
		</tr>
		<tr>
			<td class="trow1">
				<strong>{$lang->timeline_event_name}:</strong>
			</td>
			<td class="trow1">
				<input type="text" class="textbox" name="name" id="name" size="40" maxlength="1155" style="width: 340px;" />
			</td>
		</tr>
		<tr>
			<td class="trow2">
				<strong>{$lang->timeline_event_date}:</strong>
			</td>
			<td class="trow2">
					<select name="day">
						{$day_bit}
					</select> 
					<select name="month">
						{$month_bit}
					</select>
					<select name="year">
						{$year_bit}
					</select> 
			</td>
		</tr>
		<tr>
			<td class="trow1">
				<strong>{$lang->timeline_event_desc}:</strong>
			</td>
			<td class="trow1">
				<textarea name="desc" id="desc" style="height: 100px; width: 340px;"></textarea>
			</td>
		</tr>
		<tr>
			<td class="trow2">
				<strong>{$lang->timeline_event_members}:</strong>
			</td>
			<td class="trow2">
				<input type="text" class="textbox" name="members" id="members" size="40" maxlength="1155" style="width: 350px;" />
			</td>
		</tr>
		<tr>
			<td class="trow1" colspan="2" align="center">
				<input type="hidden" name="action" value="do_add" />
				<input type="submit" name="submit" id="submit" class="button" value="{$lang->timeline_send}" />
			</td>
		</tr>
	</table>
	</form>
	<br />
</div>
</td>
</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>
<link rel="stylesheet" href="{$mybb->asset_url}/jscripts/select2/select2.css?ver=1807">
<script type="text/javascript" src="{$mybb->asset_url}/jscripts/select2/select2.min.js?ver=1806"></script>
<script type="text/javascript">
<!--
if(use_xmlhttprequest == "1")
{
    MyBB.select2();
    $("#members").select2({
        placeholder: "{$lang->search_user}",
        minimumInputLength: 2,
        maximumSelectionSize: \'\',
        multiple: true,
        ajax: { // instead of writing the function to execute the request we use Select2\'s convenient helper
            url: "xmlhttp.php?action=get_users",
            dataType: \'json\',
            data: function (term, page) {
                return {
                    query: term, // search term
                };
            },
            results: function (data, page) { // parse the results into the format expected by Select2.
                // since we are using custom formatting functions we do not need to alter remote JSON data
                return {results: data};
            }
        },
        initSelection: function(element, callback) {
            var query = $(element).val();
            if (query !== "") {
                var newqueries = [];
                exp_queries = query.split(",");
                $.each(exp_queries, function(index, value ){
                    if(value.replace(/\s/g, \'\') != "")
                    {
                        var newquery = {
                            id: value.replace(/,\s?/g, ","),
                            text: value.replace(/,\s?/g, ",")
                        };
                        newqueries.push(newquery);
                    }
                });
                callback(newqueries);
            }
        }
    })
}
// -->
</script>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

  $insert_array = array(
		'title'		=> 'timeline_faq',
		'template'	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->timeline}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
<td width="23%" valign="top">
{$timeline_nav}
</td>
<td valign="top">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="{$colspan}"><strong>{$lang->timeline_faq}</strong></td>
</tr>
<tr>
<td class="trow2" style="padding: 10px; text-align: justify;">
<div style="width: 95%; margin: auto; padding: 8px;  font-size: 12px; line-height: 1.5em;" class="trow1">

	<table border="0" cellspacing="3" cellpadding="5" class="tborder">
		<tr>
			<td class="tcat">Wer darf Ereignisse einstellen?</td>
		</tr>
		<tr>
			<td class="smalltext trow1" style="line-height: 1.5em;">Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. </td>
		</tr>
		<tr>
			<td class="tcat">Was für Ereignisse dürfen das sein?</td>
		</tr>
		<tr>
			<td class="smalltext trow2" style="line-height: 1.5em;">Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. </td>
		</tr>
		<tr>
			<td class="tcat">Was passiert mit meinen Ereignissen, wenn ich das Forum verlasse?</td>
		</tr>
		<tr>
			<td class="smalltext trow1" style="line-height: 1.5em;">Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. </td>
		</tr>
		<tr>
			<td class="tcat">Für wen ist die Timeline relevant?</td>
		</tr>
		<tr>
			<td class="smalltext trow2" style="line-height: 1.5em;">Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. </td>
		</tr>
		<tr>
			<td class="tcat">Warum kann ich eigene Ereignisse nicht bearbeiten/löschen?</td>
		</tr>
		<tr>
			<td class="smalltext trow1" style="line-height: 1.5em;">Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. </td>
		</tr>
	</table>

</div>
</td>
</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

  $insert_array = array(
		'title'		=> 'timeline_history',
		'template'	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->timeline}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
<td width="23%" valign="top">
{$timeline_nav}
</td>
<td valign="top">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="{$colspan}"><strong>{$lang->timeline}</strong></td>
</tr>
<tr>
<td class="trow2" style="padding: 10px; text-align: justify;">
<div style="width: 95%; margin: auto; padding: 8px;  font-size: 12px; line-height: 1.5em;" class="trow1">
	<i class="fab fa-pied-piper-alt" aria-hidden="true" style="float: left; font-size: 50px; margin: 5px; margin-right: 15px;"></i> Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Aenean commodo ligula eget dolor. Aenean massa. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Donec quam felis, ultricies nec, pellentesque eu, pretium quis, sem. Nulla consequat massa quis enim. Donec pede justo, fringilla vel, aliquet nec, vulputate eget, arcu. In enim justo, rhoncus ut, imperdiet a, venenatis vitae, justo. Nullam dictum felis eu pede mollis pretium. Integer tincidunt. Cras dapibus. Vivamus elementum semper nisi. Aenean vulputate eleifend tellus. Aenean leo ligula, porttitor eu, consequat vitae, eleifend ac, enim. Aliquam lorem ante, dapibus in, viverra quis, feugiat a, tellus. Phasellus viverra nulla ut metus varius laoreet. Quisque rutrum. Aenean imperdiet.<div style="clear: both;"></div><br />
	<hr />
	{$years_bit}
	<div style="clear: both;"></div>
	<hr /><br />
	{$history_bit}
</div>
</td>
</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

  $insert_array = array(
		'title'		=> 'timeline_history_bit',
		'template'	=> $db->escape_string('<table class="tborder" cellspacing="5" cellpadding="5">
	<tr>
		<td class="thead" colspan="3">
			&bull; {$event[\'title\']}
		</td>
	</tr>
	<tr>
		<td class="trow1" width="15%">
						<div class="end_date_cal">
				<div class="end_date_top">{$end_year}
				</div>
				<div class="end_date_day">
					{$end_day}.
				</div>
				<div class="end_date_month">
					{$end_month}
				</div>
			</div>
		</td>
		<td class="trow1" width="10%">
			<img src="images/events/event-{$event[\'eid\']}.png" style="opacity: .3;" width="45px" />
		</td>
		<td class="trow1">
			{$event[\'description\']}
		</td>
	</tr>
	<tr>
		<td class="tcat" colspan="3">
			&bull; {$lang->timeline_event_members}
		</td>
	</tr>
	<tr>
		<td class="trow1" colspan="3">
			{$member_bit}
		</td>
	</tr>
</table>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

  $insert_array = array(
		'title'		=> 'timeline_navigation',
		'template'	=> $db->escape_string('<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder con-nav">
<tbody>
	<tr>
		<td class="thead"><strong>{$lang->timeline_navigation}</strong></td>
	</tr>
	<tr>
		<td class="trow2 smalltext"><i class="fa fa-home" aria-hidden="true"></i> <a href="timeline.php">{$lang->timeline_start}</a></td>
	</tr>
	<tr>
		<td class="trow1 smalltext"><i class="fas fa-question"></i> <a href="timeline.php?action=faq">{$lang->timeline_faq}</a></td>
	</tr>
	<tr>
		<td class="trow2 smalltext"><i class="fas fa-book"></i> <a href="timeline.php?action=history">{$lang->timeline}</a></td>
	</tr>
	<tr>
		<td class="trow2 smalltext"><i class="fa fa-user"></i> <a href="timeline.php?action=user">{$lang->timeline_character_chronicles}</a></td>
	</tr>
	<tr>
		<td class="tcat"><strong>{$lang->timeline_control}</strong></td>
	</tr>
	<tr>
		<td class="trow2 smalltext"><i class="fas fa-pencil-alt"></i> <a href="timeline.php?action=add">{$lang->timeline_send}</a></td>
	</tr>
	<tr>
		<td class="trow1 smalltext"><i class="fas fa-pencil-alt"></i> <a href="timeline.php?action=utimeline">{$lang->timeline_character_nav}</a></td>
	</tr>
	{$timeline_nav_team}
</tbody>
</table>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

  $insert_array = array(
		'title'		=> 'timeline_view',
		'template'	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->timeline} - {$event[\'title\']}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
<td width="23%" valign="top">
{$timeline_nav}
</td>
<td valign="top">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="{$colspan}"><strong>{$lang->timeline} - {$event[\'title\']}</strong></td>
</tr>
<tr>
<td class="trow2" style="padding: 10px; text-align: justify;">
<div style="width: 95%; margin: auto; padding: 8px;  font-size: 12px; line-height: 1.5em;" class="trow1">
<table border="0" cellspacing="5" cellpadding="5" class="tborder">
	<tr>
		<td class="trow2" align="center" valign="top">
			<img src="{$user[\'avatar\']}" width="150px" />
		</td>
		<td class="trow2" align="justify" valign="top">
			<h1>{$event[\'date\']}: {$event[\'title\']}</h1>
			
			{$event[\'description\']}
			<h2>{$lang->timeline_event_members}</h2>
			{$member}
		</td>
	</tr>
</table>
	<br />
</div>
</td>
</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

  $insert_array = array(
		'title'		=> 'timeline_view_member',
		'template'	=> $db->escape_string('<ul>
	{$member_bit}
</ul>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

  $insert_array = array(
		'title'		=> 'timeline_view_member',
		'template'	=> $db->escape_string('<ul>
	{$member_bit}
</ul>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

  $insert_array = array(
		'title'		=> 'timeline_view_member_bit',
		'template'	=> $db->escape_string('<li>{$member[\'profile_link\']}'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

	  $insert_array = array(
		'title'		=> 'timeline_user',
		'template'	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->timeline} - {$user[\'username\']}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
<td width="23%" valign="top">
{$timeline_nav}
</td>
<td valign="top">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="{$colspan}"><strong>{$lang->timeline} - {$user[\'username\']}</strong></td>
</tr>
<tr>
<td class="trow2" style="padding: 10px; text-align: justify;">
<div style="width: 95%; margin: auto; padding: 8px;  font-size: 12px; line-height: 1.5em;" class="trow1">
<table border="0" cellspacing="5" cellpadding="5" class="tborder">
	<tr>
		<td class="trow2" align="justify" valign="top">
			<table class="tborder"cellspacing="4" cellpadding="4">
				<tr>
					<td colspan="2">
						<div class="thead">&bull; {$user[\'username\']}</div>
					</td>
				</tr>
				<tr>
					<td width="190px">			
						<img src="{$user[\'avatar\']}" width="190px" /><br />
					</td>
					<td>
						<div class="tcat">&bull; {$lang->timeline_rumor}</div>
						<div style="padding: 5px; text-align: justify; line-height: 1.3em; max-height: 130px; overflow: auto;">{$rumor}</div>
					</td>
				</tr>
			</table>
			{$events_bit}
			<table class="tborder" cellspacing="5" cellpadding="5">
				<tr>
					<td class="tcat">&bull; {$lang->timeline_character}</td>
				</tr>
					{$inplay_bit}
			</table>
		</td>
	</tr>
</table>
	<br />
</div>
</td>
</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

  $insert_array = array(
		'title'		=> 'timeline_user_inplay_bit',
		'template'	=> $db->escape_string('<tr>
	<td class="trow1 smalltext">
		{$uevent[\'link\']}
	</td>
</tr>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

  $insert_array = array(
		'title'		=> 'timeline_utimeline',
		'template'	=> $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->timeline}</title>
{$headerinclude}
</head>
<body>
{$header}
<table width="100%" border="0" align="center">
<tr>
<td width="23%" valign="top">
{$timeline_nav}
</td>
<td valign="top">
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="thead" colspan="{$colspan}"><strong>Charakter Control Panel</strong></td>
</tr>
<tr>
<td class="trow2" style="padding: 10px; text-align: justify;">
<div style="width: 95%; margin: auto; padding: 8px;  font-size: 12px; line-height: 1.5em;" class="trow1"> 
	{$lang->timeline_description_desc} &raquo; <a href="timeline.php?action=user&uid={$uid}" target="blank_"><strong>{$lang->timeline_character_chronicles}</strong></a><br /><br />
{$szenen_bit}
</div>
</td>
</tr>
</table>
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

  $insert_array = array(
		'title'		=> 'timeline_utimeline_bit',
		'template'	=> $db->escape_string('<form method="post" id="timeline_user">
<table class="tborder" cellspacing="3" cellpadding="3">
	<tr>
		<td class="tcat">{$szenen[\'subject\']}</td>
	</tr>
	<tr>
		<td class="trow1" align="center">
			<input type="text" value="{$szenen[\'description\']}" name="description" id="description" style="width: 90%;" /><br />
		</td>
	</tr>
	<tr>
		<td class="trow1" align="center">
			<input type="hidden" value="{$szenen[\'eid\']}" id="eid" name="eid" />
			<input type="hidden" value="{$szenen[\'tid\']}" id="tid" name="tid" />
			<input type="hidden" name="action" id="action" value="do_timeline" />
			<input type="submit" name="submit" id="submit" value="{$lang->timeline_send_description}" />
		</td>
	</tr>
</table>
</form>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

  $insert_array = array(
		'title'		=> 'characp_facts_rumor',
		'template'	=> $db->escape_string('		
			<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder smalltext" style="text-align: center;">
			<tr>
				<td class="thead"><strong>{$lang->timeline_rumor}</strong></td>
			</tr>
			<tr>
				<td class="smalltext trow1" align="justify">{$lang->timeline_rumor_desc}</td> 
			</tr>
			<tr>
				<td class="smalltext trow2"><textarea name="rumor" rows="7" cols="90">{$chara[\'rumor\']}</textarea></td> 
			</tr>
		</table><br />'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);

}

function timeline_deactivate()
{
	global $db, $mybb;

	// drop css
	require_once MYBB_ADMIN_DIR."inc/functions_themes.php";
	$db->delete_query("themestylesheets", "name = 'timeline.css'");
	$query = $db->simple_select("themes", "tid");
	while($theme = $db->fetch_array($query)) {
		update_theme_stylesheet_list($theme['tid']);
	}

	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("characp_facts", "#".preg_quote('{$facts_rumor}')."#i", '', 0);

	 $db->delete_query("templates", "title LIKE '%timeline%'");

}

?>