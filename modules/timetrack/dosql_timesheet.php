<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
$timesheet = new CTimeSheet();
if (($msg = $timesheet->bind( $_POST ))) {
	$AppUI->setMsg( $msg, UI_MSG_ERROR );
	$AppUI->redirect();
}
// A timesheet is its user's. An existing one keeps its user and approvals, and only that
// user may change it; the request could set tt_user_id and the approval fields, so anyone
// approved their own timesheet. (The approve/reject branches that used to follow relied on
// register_globals and never ran; they concatenated request values into SQL.)
$tt_id = (int)$timesheet->tt_id;
if ($tt_id) {
	$loader = new CTimeSheet();
	$existing = $loader->load($tt_id); // returns the row as an object
	if (!$existing || (int)$existing->tt_user_id != (int)$AppUI->user_id) {
		$AppUI->setMsg('Access denied.', UI_MSG_ERROR);
		$AppUI->redirect('m=public&a=access_denied');
	}
	foreach (array('tt_user_id', 'tt_supervisor_approval', 'tt_pm_approval', 'tt_approve_note',
			'tt_approve_note_date') as $field) {
		$timesheet->$field = $existing->$field;
	}
} else {
	$timesheet->tt_user_id = $AppUI->user_id;
	$timesheet->tt_supervisor_approval = null;
	$timesheet->tt_pm_approval = null;
	$timesheet->tt_approve_note = null;
	$timesheet->tt_approve_note_date = null;
}
// convert dates to SQL format first
$timesheet->tt_start_date = db_unix2DateTime( $timesheet->tt_start_date );
$timesheet->tt_end_date = db_unix2DateTime( $timesheet->tt_end_date );

if (($msg = $timesheet->store())) {
	$AppUI->setMsg( $msg, UI_MSG_ERROR );
} else {
	$AppUI->setMsg( "Timesheet ".($tt_id ? 'updated' : 'inserted'), UI_MSG_OK );
}
$AppUI->redirect();
?>
