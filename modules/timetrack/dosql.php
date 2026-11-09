<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
$del = dPgetIntParam($_POST, 'del', 0);

$timedata = new CTimeData();

if (($msg = $timedata->bind( $_POST ))) {
	$AppUI->setMsg( $msg, UI_MSG_ERROR );
	$AppUI->redirect();
}
// Entries are only added to, moved between or deleted from the user's own timesheets while
// they are open (tt_active): any entry of any timesheet could be changed.
$own_open_timesheet = function ($tt_id) {
	global $AppUI;
	$q = new DBQuery();
	$q->addTable('timetrack_idx');
	$q->addQuery('tt_user_id, tt_active');
	$q->addWhere('tt_id = ' . (int)$tt_id);
	$row = $q->loadHash();
	return $row && (int)$row['tt_user_id'] == (int)$AppUI->user_id && (int)$row['tt_active'] == 1;
};
$timesheets = array((int)$timedata->tt_data_timesheet_id);
if ($timedata->tt_data_id) {
	$q = new DBQuery();
	$q->addTable('timetrack_data');
	$q->addQuery('tt_data_timesheet_id');
	$q->addWhere('tt_data_id = ' . (int)$timedata->tt_data_id);
	$stored_timesheet = $q->loadResult();
	if ($stored_timesheet === null || $stored_timesheet === false) {
		$AppUI->setMsg('invalidID', UI_MSG_ERROR, true);
		$AppUI->redirect();
	}
	$timesheets[] = (int)$stored_timesheet;
	if ($del) {
		$timesheets = array((int)$stored_timesheet);
	}
}
foreach ($timesheets as $tt_id) {
	if (!$own_open_timesheet($tt_id)) {
		$AppUI->setMsg('Access denied.', UI_MSG_ERROR);
		$AppUI->redirect('m=public&a=access_denied');
	}
}
// convert dates to SQL format first
// (the form sends YYYYMMDD, as the public calendar returns it)
$tt_date = new CDate(preg_match('/^\d{8}$/', (string)$timedata->tt_data_date) ? $timedata->tt_data_date : null);
$timedata->tt_data_date = $tt_date->format(FMT_DATETIME_MYSQL);

if ($del) {
	if (($msg = $timedata->delete())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect();
	} else {
		$AppUI->setMsg( "Timesheet entry deleted", UI_MSG_ALERT );
		$AppUI->redirect( "m=timesheets" );
	}
} else {
	$isNotNew = @$_POST['tt_data_id'];
	if (($msg = $timedata->store())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	} else {
		$AppUI->setMsg( "Timesheet entry ".($isNotNew ? 'updated' : 'inserted'), UI_MSG_OK );
	}
	$AppUI->redirect();
}
?>