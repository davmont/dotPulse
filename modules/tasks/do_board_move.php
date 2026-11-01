<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// Moves a task between the columns of the project board (projects/vw_board.php,
// js/dp-board.js) by setting its progress. index.php has already checked POST, the CSRF
// token and write permission on the tasks module; this checks the task itself.
// Answers JSON: {"status": "ok", "percent": n} or {"status": "error", "message": "..."}.
header('Content-Type: application/json; charset=utf-8');

function boardMoveFail($status, $message)
{
	global $AppUI;
	http_response_code($status);
	echo json_encode(array('status' => 'error', 'message' => $AppUI->_($message, UI_OUTPUT_RAW)));
	exit;
}

$task_id = (int)dPgetParam($_POST, 'task_id', 0);
$column = (string)dPgetParam($_POST, 'column', '');
if (!in_array($column, array('todo', 'doing', 'done'), true)) {
	boardMoveFail(400, 'Unknown column.');
}
$task = new CTask;
if ($task_id <= 0 || !$task->load($task_id) || !$task->canAccess($AppUI->user_id)
	|| !getPermission('tasks', 'view', $task_id)) {
	boardMoveFail(404, 'Task not found.');
}
if (!getPermission('tasks', 'edit', $task_id)) {
	boardMoveFail(403, 'You may not edit this task.');
}
if ($task->task_dynamic == 1) {
	boardMoveFail(400, 'The progress of a task group comes from its tasks.');
}

$percent = CTask::boardPercent($column, (int)$task->task_percent_complete);
if ($percent != (int)$task->task_percent_complete) {
	$task->htmlDecode();
	$task->task_percent_complete = $percent;
	if ($percent >= 100 && (!$task->task_end_date || $task->task_end_date == '0000-00-00 00:00:00')) {
		$task->task_end_date = date('Y-m-d H:i:s');
	}
	if (($msg = $task->store())) {
		boardMoveFail(422, is_array($msg) ? implode(' ', $msg) : (string)$msg);
	}
	addHistory('tasks', $task_id, 'update', $task->task_name . ': ' . $percent . '% (board)', $task->task_project);
}
echo json_encode(array('status' => 'ok', 'percent' => $percent));
exit;
