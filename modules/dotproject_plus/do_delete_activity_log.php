<?php

if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}

require_once (DP_BASE_DIR . '/modules/tasks/tasks.class.php');

$taskLogId = dPgetIntParam($_POST, 'task_log_id', 0);
$project_id = dPgetIntParam($_POST, 'project_id', 0);
$q = new DBQuery();
$q->addTable('task_log', 'tl');
$q->addJoin('tasks', 't', 't.task_id = tl.task_log_task', 'inner');
$q->addJoin('projects', 'p', 'p.project_id = t.task_project', 'inner');
$q->addQuery('tl.task_log_creator, t.task_project, p.project_owner');
$q->addWhere('tl.task_log_id = ' . $taskLogId);
$logRow = $q->loadHash();
// A log of a task of this project, which the user must be able to edit; and, as the
// execution tab intends, only its author, the project owner or an administrator may delete it.
if (!$logRow || (int)$logRow['task_project'] !== $project_id) {
    $AppUI->setMsg('Access denied.', UI_MSG_ERROR);
    $AppUI->redirect('m=public&a=access_denied');
}
dPrequireProjectEdit($project_id);
if ((int)$logRow['task_log_creator'] !== (int)$AppUI->user_id
        && (int)$logRow['project_owner'] !== (int)$AppUI->user_id
        && !getPermission('admin', 'edit')) {
    $AppUI->setMsg('Access denied.', UI_MSG_ERROR);
    $AppUI->redirect('m=public&a=access_denied');
}

$taskLog = new CTaskLog();
$taskLog->load($taskLogId);
$taskLog->delete();


$AppUI->setMsg($AppUI->_("LBL_ACTIVITY_TASK_LOG_DELETED",UI_OUTPUT_HTML), UI_MSG_OK, true);

$AppUI->redirect("m=projects&a=view&project_id=" . $project_id . "&tab=" . dPgetIntParam($_POST, 'tab', 0));
?>