<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
##
## holiday module - A dotProject module for keeping track of holidays
##
## Sensorlink AS (c) 2006
## Vegard Fiksdal (fiksdal@sensorlink.no)
##


// MODULE CONFIGURATION DEFINITION
$config = array();
$config['mod_name'] = 'Holiday';
$config['mod_version'] = '0.3';
$config['mod_directory'] = 'holiday';
$config['mod_setup_class'] = 'CSetupHoliday';
$config['mod_type'] = 'admin';
$config['mod_ui_name'] = 'Holiday';
$config['mod_ui_icon'] = 'notepad.gif';
$config['mod_description'] = 'A module for registering non-working days';

if (@$a == 'setup') {
	echo dPshowModuleConfig( $config );
}

class CSetupHoliday {

	function install() {
		$ok=true;
		// Create whilelist/blacklist database
		$q = new DBQuery();
		$q -> createTable('holiday');
		$q -> createDefinition("( " .
		"holiday_id int(10) unsigned NOT NULL auto_increment," .
		"holiday_user int(10) NOT NULL default '0'," .
		"holiday_white int(10) NOT NULL default '0'," .
		"holiday_annual int(10) NOT NULL default '0'," .
		"holiday_start_date datetime NOT NULL default '0000-00-00 00:00:00'," .
		"holiday_end_date datetime NOT NULL default '0000-00-00 00:00:00'," .
		"holiday_description text," .
		"holiday_calendar int(10) NOT NULL default '0'," .
		"PRIMARY KEY  (holiday_id)," .
		"UNIQUE KEY holiday_id (holiday_id)," .
		"KEY holiday_calendar (holiday_calendar)" .
		")");
		$ok = $ok && $q -> exec();	
		$ok = $ok && $this -> createCalendars();

		// Create settings database
		$q = new DBQuery();
		$q -> createTable('holiday_settings');
		$q -> createDefinition("( " .
                "holiday_manual int(10) NOT NULL default '0'," .
                "holiday_auto int(10) NOT NULL default '0'," .
                "holiday_country varchar(8) NOT NULL default ''," .
                "UNIQUE KEY holiday_manual (holiday_manual)," .
                "UNIQUE KEY holiday_auto (holiday_auto)" .
                ")");                
        
		$ok = $ok && $q -> exec();
		
		// Set default settings
		$holiday_manual = 1;
		$holiday_auto = 0;

		$q = new DBQuery();
		$q -> addTable('holiday_settings');
		$q -> addInsert('holiday_manual', $holiday_manual);
		$q -> addInsert('holiday_auto', $holiday_auto);
		$ok = $ok && $q->exec();
		if($ok){
			return null;
		}
		return $ok;
	}
	
	function remove() {
		$ok = true;
		$q = new DBQuery();
		$q -> dropTable('holiday');
		$ok = $ok && $q -> exec();
		$q -> clear();
		$q -> dropTable('holiday_settings');
		$ok = $ok && $q -> exec();
		$q -> clear();
		$q -> dropTable('holiday_calendars');
		$ok = $ok && $q -> exec();
		$q -> clear();
		$q -> dropTable('holiday_calendar_links');
		$ok = $ok && $q -> exec();
		if($ok){
			return null;
		}
		return $ok;
	}
	
	function upgrade($old_version) {
		switch ($old_version) {
			case '0.1':
				// 0.2: public holidays by country instead of the PEAR Date_Holidays
				// drivers, whose index is meaningless now; pick the country again.
				$q = new DBQuery();
				$q -> alterTable('holiday_settings');
				$q -> addField('holiday_country', "varchar(8) NOT NULL default ''");
				$q -> dropField('holiday_driver');
				if (!$q -> exec()) {
					return false;
				}
				// no break
			case '0.2':
				// 0.3: holiday calendars for workplaces. The existing days stay
				// shared by every calendar (holiday_calendar 0).
				$q = new DBQuery();
				$q -> alterTable('holiday');
				$q -> addField('holiday_calendar', "int(10) NOT NULL default '0'");
				if (!$q -> exec() || !$this -> createCalendars()) {
					return false;
				}
		}
		return true;
	}

	/**
	 * The calendars (a workplace's holidays: a country's national holidays and the
	 * calendar's own days, added to those of the calendar it builds on) and whom
	 * they are for: a user, a department or a company.
	 */
	function createCalendars() {
		$q = new DBQuery();
		$q -> createTable('holiday_calendars');
		$q -> createDefinition("( " .
		"calendar_id int(10) unsigned NOT NULL auto_increment," .
		"calendar_name varchar(100) NOT NULL default ''," .
		"calendar_parent int(10) NOT NULL default '0'," .
		"calendar_country varchar(8) NOT NULL default ''," .
		"calendar_description text," .
		"PRIMARY KEY (calendar_id)" .
		")");
		if (!$q -> exec()) {
			return false;
		}
		$q = new DBQuery();
		$q -> createTable('holiday_calendar_links');
		$q -> createDefinition("( " .
		"link_type varchar(16) NOT NULL default ''," .
		"link_id int(10) NOT NULL default '0'," .
		"link_calendar int(10) NOT NULL default '0'," .
		"PRIMARY KEY (link_type, link_id)," .
		"KEY link_calendar (link_calendar)" .
		")");
		return (bool)$q -> exec();
	}
}

?>

