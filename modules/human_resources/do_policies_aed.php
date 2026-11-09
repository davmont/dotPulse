<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

$del = (int)dPgetParam($_POST, 'del', 0);
$obj = new CCompaniesPolicies;
$msg = '';

if (! $obj->bind($_POST)) {
  $AppUI->setMsg($obj->getError(), UI_MSG_ERROR);
  $AppUI->redirect();
}

$record_id = (int)$obj->company_policies_id;
// Permission on this record, as its addedit page checks.
dPrequireWritePermission('human_resources', $record_id, $del);
if ($record_id) {
  $existing = new CCompaniesPolicies();
  if (!$existing->load($record_id)) {
    $AppUI->setMsg('Company Policies');
    $AppUI->setMsg('invalidID', UI_MSG_ERROR, true);
    $AppUI->redirect();
  }
  // A record stays with its company.
  $obj->company_policies_company_id = $existing->company_policies_company_id;
}
$obj->company_policies_company_id = (int)$obj->company_policies_company_id;
if (!getPermission('companies', 'view', $obj->company_policies_company_id)) {
  $AppUI->setMsg('Access denied.', UI_MSG_ERROR);
  $AppUI->redirect('m=public&a=access_denied');
}

$AppUI->setMsg('Company Policies');
if ($del) {
//if (! $obj->canDelete($msg)) {
//    $AppUI->setMsg($msg, UI_MSG_ERROR);
//    $AppUI->redirect();
//  }
  if (($msg = $obj->delete())) {
    $AppUI->setMsg($msg, UI_MSG_ERROR);
    $AppUI->redirect();
  } else {
    $AppUI->setMsg('deleted', UI_MSG_ALERT, true);
    $AppUI->redirect('', -1);
  }
} else {
  if (($msg = $obj->store())) {
    $AppUI->setMsg($msg, UI_MSG_ERROR);
  } else {
    $AppUI->setMsg($record_id ? 'updated' : 'added', UI_MSG_OK, true);
  }
  $AppUI->redirect();
}
?>
