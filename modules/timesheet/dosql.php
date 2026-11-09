<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
$del = isset($_POST['del']) ? $_POST['del'] : 0;
$punchIn = isset($_POST['punchin']) ? 1 : 0;
$punchOut = isset($_POST['punchout']) ? 1 : 0;
$break = isset($_POST['break']) ? 1 : 0;
$update = isset($_POST['update']) ? 1 : 0;

$this_post = array();
$timesheet_id = $_POST['timesheet_id'];
$timesheet_date = $_POST['timesheet_date'];
$timesheet_time_in = $_POST['timesheet_time_in'];
$timesheet_time_out = $_POST['timesheet_time_out'];
$timesheet_time_break = $_POST['timesheet_time_break'];
$q = new DBQuery();
if ($punchIn) {
	$timesheet = new Timesheet();
	$q->addTable('timesheet');
	$q->addQuery('*');
	$q->addWhere("user_id = " . (int)$AppUI->user_id . " and timesheet_date = '" . db_escape($_POST['timesheet_date']) . "'");

	if (!$q->loadObject($timesheet)) { // timesheet doesn't exist yet.  Create it.
		$timesheet->timesheet_id = "";
		$timesheet->user_id = $AppUI->user_id;
		$timesheet->timesheet_date = $_POST['timesheet_date'];

		if (($msg = $timesheet->store())) {
			$AppUI->setMsg($msg, UI_MSG_ERROR);
			$AppUI->redirect();
		}
	}

	// set time_in
	$curTime = new CDate();
	$timesheet->timesheet_time_in = $curTime->format("%H:%M");
	if (($msg = $timesheet->store())) {
		$AppUI->setMsg($msg, UI_MSG_ERROR);
	} else {
		$AppUI->setMsg("Punched in at " . $curTime->format("%H:%M"), UI_MSG_OK);
	}
} else if ($punchOut) {
	$timesheet = new Timesheet();
	$q->addTable('timesheet');
	$q->addQuery('*');
	$q->addWhere("user_id = " . (int)$AppUI->user_id . " and timesheet_date = '" . db_escape($_POST['timesheet_date']) . "'");

	if (!$q->loadObject($timesheet)) { // timesheet doesn't exist yet.  Create it.
		$timesheet->timesheet_id = "";
		$timesheet->user_id = $AppUI->user_id;
		$timesheet->timesheet_date = $_POST['timesheet_date'];

		if (($msg = $timesheet->store())) {
			$AppUI->setMsg($msg, UI_MSG_ERROR);
			$AppUI->redirect();
		}
	}

	// set time_out
	$curTime = new CDate();
	$timesheet->timesheet_time_out = $curTime->format("%H:%M");
	if (($msg = $timesheet->store())) {
		$AppUI->setMsg($msg, UI_MSG_ERROR);
	} else {
		$AppUI->setMsg("Punched out at " . $curTime->format("%H:%M"), UI_MSG_OK);
	}
} else if ($break) {
	$timesheet = new Timesheet();
	$q->addTable('timesheet');
	$q->addQuery('*');
	$q->addWhere("user_id = " . (int)$AppUI->user_id . " and timesheet_date = '" . db_escape($_POST['timesheet_date']) . "'");

	if (!$q->loadObject($timesheet)) { // timesheet doesn't exist yet.  Create it.
		$timesheet->timesheet_id = "";
		$timesheet->user_id = $AppUI->user_id;
		$timesheet->timesheet_date = $_POST['timesheet_date'];

		if (($msg = $timesheet->store())) {
			$AppUI->setMsg($msg, UI_MSG_ERROR);
			$AppUI->redirect();
		}
	}

	// set current time
	$curTime = new CDate();

	if ($_POST['break'] == 'Back for more Fun') {

		$myMsg = "Break ended on " . $curTime->format("%H:%M");

		// calculate break time
		$prevBreak = new CDate('0000-00-00 ' . $timesheet->timesheet_time_break);
		$startTime = new CDate('0000-00-00 ' . $timesheet->timesheet_time_break_start);
		$curTime->addSeconds($prevBreak->hour * 60 * 60 + $prevBreak->minute * 60);
		$curTime->subtractSeconds($startTime->hour * 60 * 60 + $startTime->minute * 60);
		// set time_break_start
		$timesheet->timesheet_time_break = $curTime->format("%H:%M");

		// reset time_break_start
		$timesheet->timesheet_time_break_start = '00:00:00';
	} else {
		// set time_break_start
		$timesheet->timesheet_time_break_start = $curTime->format("%H:%M");
	}

	if (($msg = $timesheet->store())) {
		$AppUI->setMsg($msg, UI_MSG_ERROR);
	} else {
		$AppUI->setMsg(($myMsg ? $myMsg : "Break started on " . $curTime->format("%H:%M")), UI_MSG_OK);
	}
} else if ($update) {
	for ($i = 0; $i < count($_POST['timesheet_id']); $i++) {

		$timesheet = new Timesheet();

		// An existing row must be the user's own: any id could be posted and taken over.
		$this_post['timesheet_id'] = (int)$timesheet_id[$i];
		if ($this_post['timesheet_id']) {
			$q = new DBQuery();
			$q->addTable('timesheet');
			$q->addQuery('user_id');
			$q->addWhere('timesheet_id = ' . $this_post['timesheet_id']);
			if ((int)$q->loadResult() != (int)$AppUI->user_id) {
				continue;
			}
		}
		$this_post['timesheet_date'] = $timesheet_date[$i];
		$this_post['timesheet_time_in'] = $timesheet_time_in[$i];
		$this_post['timesheet_time_out'] = $timesheet_time_out[$i];
		$this_post['timesheet_time_break'] = $timesheet_time_break[$i];
		$this_post['user_id'] = $AppUI->user_id;


		if (($this_post['timesheet_id']) or ($this_post['timesheet_time_in'] or $this_post['timesheet_time_out'] or $this_post['timesheet_time_break'])) {

			if (($msg = $timesheet->bind($this_post))) {
				$AppUI->setMsg($msg, UI_MSG_ERROR);
				$AppUI->redirect();
			}

			// $isNotNew = @$this_post['timesheet_id'];
			if (($msg = $timesheet->store())) {
				$AppUI->setMsg($msg, UI_MSG_ERROR);
				$AppUI->redirect();
			}
		}
	}
	$AppUI->setMsg("Timesheet entries updated", UI_MSG_OK);
}
$AppUI->redirect();
?>