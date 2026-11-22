<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

if (! $AppUI->acl()->checkModule($m, 'edit')) {
	$AppUI->redirect('m=public&a=access_denied');
}

$user_id = (int)dPgetParam($_POST, 'user');
$projects = dPgetParam($_POST, 'project', array());
$from_user = (int)dPgetParam($_POST, 'from_user');
$projects = is_array($projects) ? array_values(array_filter(array_map('intval', $projects))) : array();

if (!$projects || !$user_id || !$from_user || $user_id == $from_user) {
	$AppUI->setMsg('Select the projects and the user who takes them over.', UI_MSG_ERROR);
	$AppUI->redirect();
}
$project_where = ' IN (' . implode(',', $projects) . ')';

// Project and task contacts are contacts, not users: those of the two users.
$q = new DBQuery();
$q->addTable('users');
$q->addQuery('user_id, user_contact');
$q->addWhere('user_id IN (' . $from_user . ',' . $user_id . ')');
$contact_of = $q->loadHashList();
$q->clear();
$from_contact = (int)($contact_of[$from_user] ?? 0);
$to_contact = (int)($contact_of[$user_id] ?? 0);

$q->addQuery('task_id');
$q->addTable('tasks');
$q->addWhere('task_project' . $project_where);
$tasks = array_map('intval', $q->loadColumn());
$q->clear();
$task_where = ' IN (' . ($tasks ? implode(',', $tasks) : '0') . ')';

// Where the new person is already there, the old user's row goes: the new person keeps theirs
// (a second assignment to the same task broke the transfer halfway).
$steps = array(
	array('project_contacts', 'contact_id', $from_contact, $to_contact, 'project_id', $project_where, 'failed to update project contacts'),
	array('task_contacts', 'contact_id', $from_contact, $to_contact, 'task_id', $task_where, 'failed to update task contacts'),
	array('user_tasks', 'user_id', $from_user, $user_id, 'task_id', $task_where, 'failed to update task assignments'),
);
foreach ($steps as $step) {
	list($table, $column, $from, $to, $key, $where, $error) = $step;
	if (!$from || !$to) {
		continue;
	}
	$q->addQuery($key);
	$q->addTable($table);
	$q->addWhere($column . ' = ' . $to);
	$q->addWhere($key . $where);
	$both = array_map('intval', $q->loadColumn());
	$q->clear();
	if ($both) {
		$q->setDelete($table);
		$q->addWhere($column . ' = ' . $from);
		$q->addWhere($key . ' IN (' . implode(',', $both) . ')');
		$q->exec();
		$q->clear();
	}
	$q->addUpdate($column, $to);
	$q->addTable($table);
	$q->addWhere($column . ' = ' . $from);
	$q->addWhere($key . $where);
	if (!$q->exec()) {
		$AppUI->setMsg($error, UI_MSG_ERROR);
		$AppUI->redirect();
	}
	$q->clear();
}

// Owners last, so that a failure above leaves them as they were.
$q->addUpdate('task_owner', $user_id);
$q->addTable('tasks');
$q->addWhere('task_owner = ' . $from_user);
$q->addWhere('task_project' . $project_where);
if (!$q->exec()) {
	$AppUI->setMsg('failed to update task owner', UI_MSG_ERROR);
	$AppUI->redirect();
}
$q->clear();
$q->addUpdate('project_owner', $user_id);
$q->addTable('projects');
$q->addWhere('project_owner = ' . $from_user);
$q->addWhere('project_id' . $project_where);
if (!$q->exec()) {
	$AppUI->setMsg('failed to update project owner', UI_MSG_ERROR);
	$AppUI->redirect();
}
$q->clear();
require_once DP_BASE_DIR . '/classes/auditlog.class.php';
dPaudit('user.transferred', 'from ' . CAuditLog::username($from_user) . ' to ' . CAuditLog::username($user_id)
	. ', projects ' . implode(',', $projects), 'users', $from_user);
$AppUI->setMsg('updated', UI_MSG_OK);
