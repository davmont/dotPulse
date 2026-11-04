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
require_once 'PEAR/Holidays.php';

function isHoliday( $date=0 ){	
	// Query database for settings
	$q = new DBQuery();
	$q -> addTable('holiday_settings');
	$q -> addQuery('holiday_manual');
	$q -> addQuery('holiday_auto');
	$q -> addQuery('holiday_driver');
	
	$settings = $q -> loadList()[0];
	
	$holiday_manual = intval($settings['holiday_manual']);
	$holiday_auto =   intval($settings['holiday_auto']);
	$holiday_driver = intval($settings['holiday_driver']);
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

	if($holiday_auto)
	{
		// Still here? Ok, lets poll the automatic system. Its PEAR Date_Holidays
		// drivers fail on PHP 8 (and need PEAR Date, gone since dotPulse 3.1.1):
		// a failure counts as no holiday rather than stopping the page.
		try {
			$drivers_alloc = Date_Holidays::getInstalledDrivers();
			$driver_object = Date_Holidays::factory($drivers_alloc[$holiday_driver]['title'],$date->getYear(),'en_EN');
			if (is_object($driver_object) && !Date_Holidays::isError($driver_object)) {
				if($driver_object->getHolidayForDate($date)){
					return 1;
				}
			}
		} catch (Throwable $e) {
			error_log('holiday: automatic holidays unavailable: ' . $e->getMessage());
		}
	}

	// No hits, must be a working day
	return 0;
}
?>
