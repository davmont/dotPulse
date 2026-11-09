<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

//addinitiating sql
$initiating_id = intval(dPgetParam($_POST, 'initiating_id', 0));
$del = intval(dPgetParam($_POST, 'del', 0));
$completed = intval(dPgetParam($_POST, 'initiating_completed', 0));
$approved = intval(dPgetParam($_POST, 'initiating_approved', 0));
$authorized = intval(dPgetParam($_POST, 'initiating_authorized', 0));
global $db;

$not = dPgetParam($_POST, 'notify', '0');
if ($not!='0') $not='1';

// Permission on this record (module level for charters, as index.php checks).
dPrequireWritePermission('initiating', $initiating_id, $del);

$obj = new CInitiating();
$current = null;
// A charter of a project the user cannot see is neither changed nor deleted.
if ($initiating_id) {
	$current = new CInitiating();
	if (!$current->load($initiating_id) || !CInitiating::projectAllowed((int)$current->initiating_project)) {
		$AppUI->redirect('m=public&a=access_denied');
	}
}

if (!$obj->bind($_POST)) {
	$AppUI->setMsg($obj->getError(), UI_MSG_ERROR);
	$AppUI->redirect();
}
$obj->initiating_id = $initiating_id;

// convert dates to SQL format first
if ($obj->initiating_start_date) {
	$date = new CDate($obj->initiating_start_date);
	$obj->initiating_start_date = $date->format(FMT_DATETIME_MYSQL);
}
if ($obj->initiating_end_date) {
	$date = new CDate($obj->initiating_end_date);
	$obj->initiating_end_date = $date->format(FMT_DATETIME_MYSQL);
}

// Who wrote the charter and when stay as stored; a new one is the current user's, now.
if ($current) {
	$obj->initiating_create_by = $current->initiating_create_by;
	$obj->initiating_date_create = $current->initiating_date_create;
} else {
	$obj->initiating_date_create = str_replace("'", '', $db->DBTimeStamp(time()));
	$obj->initiating_create_by = $AppUI->user_id;
	$obj->initiating_completed = $completed ? 1 : 0;
	$obj->initiating_approved = $approved ? 1 : 0;
	$obj->initiating_authorized = $authorized ? 1 : 0;
}

// A charter is part of its project: changing or deleting one needs edit on the project
// it belongs to, and on the project it is moved to.
$initiating_projects = array();
if ($current && $current->initiating_project) {
	$initiating_projects[] = (int)$current->initiating_project;
}
if (!$del && (int)$obj->initiating_project) {
	$initiating_projects[] = (int)$obj->initiating_project;
}
foreach (array_unique($initiating_projects) as $initiating_project) {
	if (!getPermission('projects', 'edit', $initiating_project)) {
		$AppUI->setMsg('Access denied.', UI_MSG_ERROR);
		$AppUI->redirect('m=public&a=access_denied');
	}
}

// prepare (and translate) the module name ready for the suffix
$AppUI->setMsg('Initiating');
// delete the item
if ($del) {
	$obj->load($initiating_id);
	if (($msg = $obj->delete())) {
		$AppUI->setMsg($msg, UI_MSG_ERROR);
		$AppUI->redirect();
	} else {
		if ($not=='1') $obj->notify();
		$AppUI->setMsg("deleted", UI_MSG_ALERT, true);
		$AppUI->redirect("m=initiating");
	}
}

if (($msg = $obj->store())) {
        $AppUI->setMsg($msg, UI_MSG_ERROR);
} else {
        $obj->load($obj->initiating_id);
        if ($not=='1') $obj->notify();
        $AppUI->setMsg($initiating_id ? 'updated' : 'added', UI_MSG_OK, true);
}

$AppUI->redirect();