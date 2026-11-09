<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

$del = (int)dPgetParam($_POST, 'del', 0);
$obj = new CHumanResource;
$msg = '';
// Absent when the form did not send the roles: they are then left as they are.
$roles_ids = dPgetParam($_POST, 'roles_ids', null);

if (! $obj->bind($_POST)) {
  $AppUI->setMsg($obj->getError(), UI_MSG_ERROR);
  $AppUI->redirect();
}

$human_resource_id = (int)$obj->human_resource_id;
dPrequireWritePermission('human_resources', $human_resource_id, $del);

if ($human_resource_id) {
	// An existing record stays with its user.
	$existing = new CHumanResource;
	if (!$existing->load($human_resource_id)) {
		$AppUI->setMsg('Human Resource');
		$AppUI->setMsg('invalidID', UI_MSG_ERROR, true);
		$AppUI->redirect();
	}
	$obj->human_resource_user_id = (int)$existing->human_resource_user_id;
} else {
	$obj->human_resource_user_id = (int)$obj->human_resource_user_id;
}

// The user must exist and belong to a company this user may see, as the company's users
// list (view_company_users) the form is opened from.
$q = new DBQuery;
$q->addTable('users');
$q->addQuery('user_company');
$q->addWhere('user_id = ' . (int)$obj->human_resource_user_id);
$hr_company = $q->loadResult();
if ($hr_company === null || $hr_company === false
		|| ($hr_company && !getPermission('companies', 'view', (int)$hr_company))) {
	$AppUI->setMsg('Access denied.', UI_MSG_ERROR);
	$AppUI->redirect('m=public&a=access_denied');
}
$hr_company = (int)$hr_company;

// Roles: ids of roles defined for the user's company, as the form offers.
$role_list = array();
foreach (explode(',', (string)$roles_ids) as $role_id) {
	$role_id = (int)$role_id;
	if ($role_id > 0) {
		$role_list[$role_id] = $role_id;
	}
}
if ($role_list && !dPrecordsBelongTo($hr_company, dPrecordList('human_resources_role',
		'human_resources_role_id', 'human_resources_role_company_id', $role_list))) {
	$AppUI->setMsg('Access denied.', UI_MSG_ERROR);
	$AppUI->redirect('m=public&a=access_denied');
}

$AppUI->setMsg('Human Resource');
if ($del) {
	$human_resource_roles = new CHumanResourceRoles;
	$human_resource_roles->deleteAll($human_resource_id);
  if (($msg = $obj->delete())) {
    $AppUI->setMsg($msg, UI_MSG_ERROR);
    $AppUI->redirect();
  }
  else {
	$AppUI->setMsg('deleted', UI_MSG_ALERT, true);
    $AppUI->redirect('', -1);
  }
} else {
  if (($msg = $obj->store())) {
	$AppUI->setMsg($msg, UI_MSG_ERROR);
  } else {
	if ($roles_ids !== null) {
		$human_resource_roles = new CHumanResourceRoles;
		$human_resource_roles->deleteAll($obj->human_resource_id);
		foreach ($role_list as $role_id) {
			$human_resource_roles->storeRoles($role_id, (int)$obj->human_resource_id);
		}
	}
    $AppUI->setMsg($human_resource_id ? 'updated' : 'added', UI_MSG_OK, true);
  }
  $AppUI->redirect();
}
?>
