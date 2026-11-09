<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

$del = dPgetIntParam($_POST, 'del', 0);
$task_id = dPgetIntParam($_POST, 'task_id', 0);
$user_id = dPgetIntParam($_POST, 'user_id', 0);
$obj = new CHumanResourceAllocation();
$msg = '';

if (! $obj->bind($_POST)) {
  $AppUI->setMsg($obj->getError(), UI_MSG_ERROR);
  $AppUI->redirect();
}

// The task's project must be one the user can edit, and the estimated role (and an existing
// allocation's) must be of that task: nothing was checked beyond the module permission.
$q = new DBQuery();
$q->addTable('tasks');
$q->addQuery('task_project');
$q->addWhere('task_id = ' . $task_id);
$task_project = (int)$q->loadResult();
$records = array(array('project_tasks_estimated_roles', 'id', 'task_id', (int)$obj->project_tasks_estimated_roles_id));
if ($obj->human_resource_allocation_id) {
  $q = new DBQuery();
  $q->addTable('human_resource_allocation');
  $q->addQuery('project_tasks_estimated_roles_id');
  $q->addWhere('human_resource_allocation_id = ' . (int)$obj->human_resource_allocation_id);
  $records[] = array('project_tasks_estimated_roles', 'id', 'task_id', (int)$q->loadResult());
}
if (!$task_id || !dPrecordsBelongTo($task_id, $records)) {
  $AppUI->setMsg('Access denied.', UI_MSG_ERROR);
  $AppUI->redirect('m=public&a=access_denied');
}
dPrequireProjectEdit($task_project);

$AppUI->setMsg('Human Resource Allocation');
if ($del) {
  // deleteAllocation(): this allocation and the user's assignment to the task (delete($task_id)
  // deleted the allocation whose id was the task's).
  if (($msg = $obj->deleteAllocation($task_id, $user_id))) {
    $AppUI->setMsg($msg, UI_MSG_ERROR);
    $AppUI->redirect();
  } else {
    $AppUI->setMsg('deleted', UI_MSG_ALERT, true);
    $AppUI->redirect();
  }
} else {
  if (($msg = $obj->storeAllocation($task_id, $user_id))) {
    $AppUI->setMsg($msg, UI_MSG_ERROR);
  } else {
    $AppUI->setMsg($obj->human_resource_allocation_id ? 'updated' : 'added', UI_MSG_OK, true);
  }
  $AppUI->redirect();
}
?>
