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

}

function timeline_deactivate()
{

}

?>