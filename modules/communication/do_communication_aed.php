<?php
if (!defined('DP_BASE_DIR')) {
die('You should not access this file directly.');
}

$communication_id = intval(dPgetParam($_POST, 'communication_id',0));
$del = intval(dPgetParam($_POST, 'del', 0));

global $db;

$not = dPgetParam($_POST, 'notify', '0');
if ($not!='0') {
    $not='1';
}
$obj = new CCommunication();
$obj->communication_project_id = dPgetParam($_POST, 'project');
$obj->communication_frequency_id = dPgetParam($_POST, 'frequency');
$obj->communication_channel_id = dPgetParam($_POST, 'channel');
$obj->communication_responsible_authorization = dPgetParam($_POST, 'responsible');


if (!$obj->bind($_POST)) {
    $AppUI->setMsg($obj->getError(), UI_MSG_ERROR);
    $AppUI->redirect();
}
// A communication of a project is changed by those who can edit the project: the one it is
// stored in and the one the request names (only the module permission was checked).
$obj->communication_project_id = (int)$obj->communication_project_id;
$communication_projects = array($obj->communication_project_id);
if ($communication_id) {
    $q = new DBQuery();
    $q->addTable('communication');
    $q->addQuery('communication_project_id');
    $q->addWhere('communication_id = ' . (int)$communication_id);
    $stored_project = $q->loadResult();
    if ($stored_project === null || $stored_project === false) {
        $AppUI->setMsg('invalidID', UI_MSG_ERROR, true);
        $AppUI->redirect();
    }
    $communication_projects[] = (int)$stored_project;
}
foreach ($communication_projects as $communication_project) {
    if ($communication_project > 0 && !getPermission('projects', 'edit', $communication_project)) {
        $AppUI->setMsg('Access denied.', UI_MSG_ERROR);
        $AppUI->redirect('m=public&a=access_denied');
    }
}
// prepare (and translate) the module name ready for the suffix
$AppUI->setMsg('Communication');
// delete the item
if ($del) {
    
    $obj->load($communication_id);

    if (($msg = $obj->delete())) {
    $AppUI->setMsg($msg, UI_MSG_ERROR);
    $AppUI->redirect();
    } else {
        $q = new DBQuery();
        $q->setDelete('communication_receptor');
        $q->addWhere('communication_id=' . (int)$communication_id);
        $q->exec();
        
        $q = new DBQuery();
        $q->setDelete('communication_issuing');
        $q->addWhere('communication_id=' . (int)$communication_id);
        $q->exec();
        
        $AppUI->setMsg("deleted", UI_MSG_ALERT, true);
        $AppUI->redirect("m=communication");
      }
}

if (($msg = $obj->store())) {
    $AppUI->setMsg($msg, UI_MSG_ERROR);
} else {    
    if (isset($_SESSION['receptors'])) {
        $dbprefix = dPgetConfig('dbprefix', '');
        $receptors_values = array();
        foreach($_SESSION['receptors'] as $value){
            $receptors_values[] = '(' . (int)$obj->communication_id . ', ' . (int)$value . ')';
        }
        if (count($receptors_values) > 0) {
            $sql = "INSERT INTO {$dbprefix}communication_receptor (communication_id, communication_stakeholder_id) VALUES " . implode(', ', $receptors_values);
            $db->Execute($sql);
        }
        unset($_SESSION['receptors']);
    }
    
    if (isset($_SESSION['emitters'])) {
        $dbprefix = dPgetConfig('dbprefix', '');
        $emitters_values = array();
        foreach($_SESSION['emitters'] as $value){
            $emitters_values[] = '(' . (int)$obj->communication_id . ', ' . (int)$value . ')';
        }
        if (count($emitters_values) > 0) {
            $sql = "INSERT INTO {$dbprefix}communication_issuing (communication_id, communication_stakeholder_id) VALUES " . implode(', ', $emitters_values);
            $db->Execute($sql);
        }
        unset($_SESSION['emitters']);
    }
    
    $obj->load($obj->communication_id);
    $AppUI->setMsg($communication_id ? 'updated' : 'added', UI_MSG_OK, true);
  }

  $AppUI->redirect();

?>