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
require_once DP_BASE_DIR . '/modules/holiday/holiday_calendar.class.php';

/** The module settings (one row), read once per request. */
function holidaySettings() {
	static $settings = null;
	if ($settings === null) {
		$q = new DBQuery();
		$q -> addTable('holiday_settings');
		$q -> addQuery('*');
		$rows = $q -> loadList();
		$settings = $rows ? $rows[0] : array();
	}
	return $settings;
}

function isHoliday( $date=0 ){	
	$settings = holidaySettings();
	$holiday_manual = intval(isset($settings['holiday_manual']) ? $settings['holiday_manual'] : 0);
	$holiday_auto =   intval(isset($settings['holiday_auto']) ? $settings['holiday_auto'] : 0);
	// Until the module is upgraded to 0.2 there is no country.
	$holiday_country = isset($settings['holiday_country']) ? $settings['holiday_country'] : '';
	$q = new DBQuery();
	if(!$date)
	{
		$date=new CDate;
	}
	
	if($holiday_manual)
	{
		// Check whether the date is blacklisted
		$q -> clear();
		$q -> addTable('holiday');
		$q -> addQuery('*');
		$q -> addWhere("( date(holiday_start_date) <= '".$date->format( '%Y-%m-%d' )
						."' AND date(holiday_end_date) >= '".$date->format( '%Y-%m-%d' )
						."' AND holiday_white=0 ) "
						."OR ( DATE_FORMAT(holiday_start_date, '%m-%d') <= '".$date->format( '%m-%d' )
						."' AND DATE_FORMAT(holiday_end_date, '%m-%d') >= '".$date->format( '%m-%d' ) 
						."' AND holiday_annual=1 AND holiday_white=0 ) ");
				
		if($q -> loadResult())
		{
			return 0;
		}

        // Check if we have a whitelist item for this date
		$q -> clear();
		$q -> addTable('holiday');
		$q -> addQuery('*');
		$q -> addWhere("( date(holiday_start_date) <= '".$date->format( '%Y-%m-%d' )
						."' AND date(holiday_end_date) >= '".$date->format( '%Y-%m-%d' )
						."' AND holiday_white=1 ) "
						."OR ( DATE_FORMAT(holiday_start_date, '%m-%d') <= '".$date->format( '%m-%d' )
						."' AND DATE_FORMAT(holiday_end_date, '%m-%d') >= '".$date->format( '%m-%d' ) 
						."' AND holiday_annual=1 AND holiday_white=1 ) ");
				
		if($q -> loadResult())
		{
			return 1;
		}
	}

	if($holiday_auto && $holiday_country !== '')
	{
		// Still here? Then the public holidays of the country decide.
		if (CHolidayCalendar::holidayName($holiday_country, $date->format('%Y-%m-%d')) !== null) {
			return 1;
		}
	}

	// No hits, must be a working day
	return 0;
}
?>
