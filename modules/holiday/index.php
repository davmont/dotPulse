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

// DotProject and PHP stuff
$AppUI->savePlace();

// Get current tab 
if (isset( $_GET['tab'] ))
{
	$AppUI->setState( 'ProjIdxTab', $_GET['tab'] );
}
$tab = $AppUI->getState( 'ProjIdxTab' ) !== NULL ? $AppUI->getState( 'ProjIdxTab' ) : 0;

// Create module header

$titleBlock = new CTitleBlock( 'Holidays', 'calendar.gif', $m, "$m.$a" );
$titleBlock ->show();
// tabbed information boxes
$tabBox = new CTabBox( "?m=holiday", "{$dPconfig['root_dir']}/modules/holiday/", $tab );
$tabBox->add('holiday_white','Whitelist');
$tabBox->add('holiday_black','Blacklist');
$tabBox->add('holiday_calendars','Calendars');
$tabBox->add('holiday_options','Settings');
$tabBox->show();

?>


