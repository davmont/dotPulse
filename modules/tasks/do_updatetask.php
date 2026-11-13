<?php /* TASKS $Id: do_updatetask.php 6149 2012-01-09 11:58:40Z ajdonnison $ */
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

/*
 * The cost code column is VARCHAR(8). It used to be stored in a single-byte
 * charset, where an accented letter typed as UTF-8 took two of the 8 places,
 * so this function folded accents (REDACIÓN -> REDACION). The table of
 * accented letters was lost in a charset conversion and folded nothing, and
 * utf8_decode()/utf8_encode() (deprecated in PHP 8.2) only turned every letter
 * outside Latin-1 into '?'. The table is utf8mb4 now: VARCHAR(8) counts
 * characters, so accents fit as they are. What is left is to keep the code
 * valid UTF-8 and within the column.
*/
function cleanText($text) {
	return mb_substr(mb_scrub((string)$text, 'UTF-8'), 0, 8, 'UTF-8');
}

$notify_owner = ((isset($_POST['task_log_notify_owner'])) ? $_POST['task_log_notify_owner'] : 0);

$del = (int)dPgetParam($_POST, 'del', 0);

$obj = new CTaskLog();

if (!($obj->bind($_POST))) {
	$AppUI->setMsg($obj->getError(), UI_MSG_ERROR);
	$AppUI->redirect();
}

// An existing log stays on its task and author; the user must be allowed to change it.
$obj->prepareWrite($del);

if ($obj->task_log_date) {
	$date = new CDate($obj->task_log_date);
	$obj->task_log_date = $date->format(FMT_DATETIME_MYSQL);
}
$dot = mb_strpos($obj->task_log_hours, ':');
if ($dot > 0) {
	$log_duration_minutes = sprintf('%.3F', mb_substr($obj->task_log_hours, $dot + 1)/60.0);
	$obj->task_log_hours = sprintf('%.3F', floor($obj->task_log_hours) + $log_duration_minutes);
}
// An empty hours field means no hours (round('') throws on PHP 8).
$obj->task_log_hours = sprintf('%.3F', round((float)$obj->task_log_hours, 3));

// Modules with task tabs can act on task logs: their tasks_dosql.updatetask.php may add
// functions to $post_log_store (called with the stored log) and $post_log_delete
// (called with the deleted log).
$post_log_store = array();
$post_log_delete = array();
foreach (findTabModules('tasks', 'view') as $mod) {
	$fname = DP_BASE_DIR . '/modules/' . $AppUI->checkFileName($mod) . '/tasks_dosql.updatetask.php';
	if (file_exists($fname)) {
		require_once $fname;
	}
}

// prepare (and translate) the module name ready for the suffix
$AppUI->setMsg('Task Log');
if ($del) {
	if (($msg = $obj->delete())) {
		$AppUI->setMsg($msg, UI_MSG_ERROR);
	} else {
		foreach ($post_log_delete as $post_log_function) {
			$post_log_function($obj);
		}
		$AppUI->setMsg('deleted', UI_MSG_ALERT);
	}
	$AppUI->redirect();
} else {
	$obj->task_log_costcode = cleanText($obj->task_log_costcode);
	if (($msg = $obj->store())) {
		$AppUI->setMsg($msg, UI_MSG_ERROR);
		$AppUI->redirect();
	} else {
		foreach ($post_log_store as $post_log_function) {
			$post_log_function($obj);
		}
		$AppUI->setMsg(@$_POST['task_log_id'] ? 'updated' : 'inserted', UI_MSG_OK, true);
	}
}
$task = new CTask();
$task->load($obj->task_log_task);
$task->htmlDecode();
$task->check();
$task_end_date = new CDate($task->task_end_date);
$task->task_percent_complete = dPgetCleanParam($_POST, 'task_percent_complete', null);

if (dPgetCleanParam($_POST, 'task_end_date', '') != '') {
	$new_date = new CDate($_POST['task_end_date']);
	$new_date->setTime($task_end_date->hour, $task_end_date->minute, $task_end_date->second);
	$task->task_end_date = $new_date->format(FMT_DATETIME_MYSQL);
}

if ($task->task_percent_complete >= 100 && (!($task->task_end_date) 
                                            || $task->task_end_date == '0000-00-00 00:00:00')) {
	$task->task_end_date = $obj->task_log_date;
}

if (($msg = $task->store())) {
	$AppUI->setMsg($msg, UI_MSG_ERROR, true);
}

$new_task_end = new CDate($task->task_end_date);
if ($new_task_end->dateDiff($task_end_date)) {
	$task->addReminder();
}
if ($notify_owner && $msg = $task->notifyOwner($obj->task_log_description)) {
	$AppUI->setMsg($msg, UI_MSG_ERROR);
}

// Check if we need to email the task log to anyone.
$email_assignees = dPgetCleanParam($_POST, 'email_assignees', null);
$email_task_contacts = dPgetCleanParam($_POST, 'email_task_contacts', null);
$email_project_contacts = dPgetCleanParam($_POST, 'email_project_contacts', null);
$email_others = dPgetCleanParam($_POST, 'email_others', '');
$email_extras = dPgetCleanParam($_POST, 'email_extras', null);

if ($task->email_log($obj, $email_assignees, $email_task_contacts, $email_project_contacts, 
    $email_others, $email_extras)) {
	$obj->store(); // Save the updated message. It is not an error if this fails.
}

$AppUI->redirect('m=tasks&a=view&task_id=' . $obj->task_log_task . '&tab=0#tasklog' 
                 . $obj->task_log_id);
?>
