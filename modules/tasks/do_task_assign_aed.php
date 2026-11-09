<?php //$Id: do_task_assign_aed.php 6149 2012-01-09 11:58:40Z ajdonnison $
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

$del = dPgetIntParam($_POST, 'del', 0);
$rm = dPgetIntParam($_POST, 'rm', 0);
$chUTP = (int)dPgetParam($_POST, 'chUTP', 0);
$percentage_assignment = dPgetCleanParam($_POST, 'percentage_assignment');
$user_task_priority = dPgetCleanParam($_POST, 'user_task_priority');
$user_id = dPgetIntParam($_POST, 'user_id', 0);

// The users to (un)assign and the percentage of assignment of each, as CTask::updateAssigned()
// needs them; ids only.
$hperc_assign_ar = array();
foreach (explode(',', (string)dPgetParam($_POST, 'hassign', '')) as $uid) {
	if ((int)$uid > 0) {
		$hperc_assign_ar[(int)$uid] = $percentage_assignment;
	}
}
$hassign = isset($_POST['hassign']) ? implode(',', array_keys($hperc_assign_ar)) : null;

// The tasks to process.
$htasks_ar = array();
foreach (explode(',', (string)dPgetParam($_POST, 'htasks', '')) as $tid) {
	if ((int)$tid > 0) {
		$htasks_ar[] = (int)$tid;
	}
}

foreach ($htasks_ar as $task_id) {
	// Assigning users changes the task: the user must be allowed to edit it. The task is
	// loaded, not built from the request, so no other field of it can be set here.
	dPrequireWritePermission('tasks', $task_id);
	$obj = new CTask();
	if (!$obj->load($task_id)) {
		$AppUI->setMsg('Task', UI_MSG_ERROR);
		$AppUI->setMsg('invalidID', UI_MSG_ERROR, true);
		$AppUI->redirect();
	}
	if ($rm && $del) {
		$overAssignment = $obj->updateAssigned($hassign , $hperc_assign_ar, true, true);
		if ($overAssignment) {
			$AppUI->setMsg('Some Users could not be unassigned from Task', UI_MSG_ERROR);
		} else {
			// Don't do anything because we might have other tasks to change
			// $AppUI->setMsg('User(s) unassigned from Task', UI_MSG_OK);
			// $AppUI->redirect();
		}
	} else if (($rm || $del)) {
		if (($msg = $obj->removeAssigned($user_id))) {
			$AppUI->setMsg($msg, UI_MSG_ERROR);
			
		} else {
			$AppUI->setMsg('User unassigned from Task', UI_MSG_OK);
		}
	}
	if (isset($hassign) && ! $del == 1) {
		$overAssignment = $obj->updateAssigned($hassign , $hperc_assign_ar, false, false);
		//check if OverAssignment occured, database has not been updated in this case
		if ($overAssignment) {
			$AppUI->setMsg(('The following Users have not been assigned in order to prevent' 
			                . ' from Over-Assignment:'), UI_MSG_ERROR);
			$AppUI->setMsg('<br />'.$overAssignment, UI_MSG_ERROR, true);
		} else {
			$AppUI->setMsg('User(s) assigned to Task', UI_MSG_OK);
		}
	}
	// process the user specific task priority
	if ($chUTP == 1) {
		$obj->updateUserSpecificTaskPriority($user_task_priority, $user_id);
		$AppUI->setMsg('User specific Task Priority updated', UI_MSG_OK, true);
	}
}
if ($rm && $del) {
	$AppUI->setMsg('User(s) unassigned from Task', UI_MSG_OK);
}
$AppUI->redirect();
?>
