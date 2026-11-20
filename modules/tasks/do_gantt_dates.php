<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// Saves a task bar dragged or resized on a Gantt chart (js/dp-gantt.js), or its progress
// handle. index.php has already checked POST, the CSRF token and write permission on the
// tasks module; this checks the task itself. The dates are days (YYYY-MM-DD, the end
// inclusive); the task keeps its times of day. Saving goes through CTask::store(), as the
// task form does: task groups follow, and tasks that track this one's dependencies move.
// Answers JSON: {"status": "ok", "reload": bool} (reload when other tasks may have moved)
// or {"status": "error", "message": "..."}.
header('Content-Type: application/json; charset=utf-8');

function ganttDatesFail($status, $message)
{
	global $AppUI;
	http_response_code($status);
	echo json_encode(array('status' => 'error', 'message' => $AppUI->_($message, UI_OUTPUT_RAW)));
	exit;
}

$task_id = (int)dPgetParam($_POST, 'task_id', 0);
$task = new CTask;
if ($task_id <= 0 || !$task->load($task_id) || !$task->canAccess($AppUI->user_id)
	|| !getPermission('tasks', 'view', $task_id)) {
	ganttDatesFail(404, 'Task not found.');
}
if (!getPermission('tasks', 'edit', $task_id)) {
	ganttDatesFail(403, 'You may not edit this task.');
}
if ($task->task_dynamic == 1) {
	ganttDatesFail(400, 'The dates and progress of a task group come from its tasks.');
}

$task->htmlDecode();
$old_end = $task->task_end_date;
$changes = array();
$start = (string)dPgetParam($_POST, 'start', '');
$end = (string)dPgetParam($_POST, 'end', '');
if ($start !== '' || $end !== '') {
	$day = '/^\d{4}-\d{2}-\d{2}$/';
	if (!preg_match($day, $start) || !preg_match($day, $end) || !strtotime($start) || !strtotime($end)
		|| $end < $start) {
		ganttDatesFail(400, 'Invalid dates.');
	}
	$start_time = substr((string)$task->task_start_date, 11, 8);
	$end_time = substr((string)$task->task_end_date, 11, 8);
	$start_time = preg_match('/^\d\d:\d\d:\d\d$/', $start_time) ? $start_time : '08:00:00';
	$end_time = preg_match('/^\d\d:\d\d:\d\d$/', $end_time) ? $end_time : '17:00:00';
	if ($start === $end && $end_time < $start_time) {
		$end_time = $start_time;
	}
	$task->task_start_date = $start . ' ' . $start_time;
	$task->task_end_date = $end . ' ' . $end_time;
	$changes[] = $start . ' - ' . $end;
}
$progress = dPgetParam($_POST, 'progress', null);
if ($progress !== null && $progress !== '') {
	$task->task_percent_complete = max(0, min(100, (int)$progress));
	$changes[] = $task->task_percent_complete . '%';
}
if (!$changes) {
	ganttDatesFail(400, 'Nothing to save.');
}
if (($msg = $task->store())) {
	ganttDatesFail(422, is_array($msg) ? implode(' ', $msg) : (string)$msg);
}
addHistory('tasks', $task_id, 'update', $task->task_name . ': ' . implode(', ', $changes) . ' (Gantt)',
	$task->task_project);

// Moving the end date moves the tasks that track this one, and a task group follows its
// tasks: the chart is out of date then.
$reload = ($task->task_end_date != $old_end && $task->dependentTasks('', '', false) !== '')
	|| ($task->task_parent && $task->task_parent != $task->task_id);
echo json_encode(array('status' => 'ok', 'reload' => (bool)$reload));
exit;
