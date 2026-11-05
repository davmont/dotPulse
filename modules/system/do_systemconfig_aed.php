<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}
// Saves the settings of System Configuration: only those the page showed (one
// section, or a search), so an unticked checkbox turns off only its own setting.

$obj = new CConfig();

$q = new DBQuery;
$q->addTable('config');
$q->addQuery('config_name, config_id, config_type, config_value');
$config = $q->loadHashList('config_name');
$q->clear();
// Values before the save, so the audit log can list the settings that changed.
$audit_before = array();
foreach ($config as $name => $row) {
	$audit_before[$name] = $row['config_value'];
}

$shown = dPgetParam($_POST, 'dPcfgShown', array());
$values = dPgetParam($_POST, 'dPcfg', array());
if (!is_array($shown)) {
	$shown = array();
}
if (!is_array($values)) {
	$values = array();
}

$AppUI->setMsg('System Configuration');
$errors = 0;
foreach (array_unique($shown) as $name) {
	if (!is_string($name) || !isset($config[$name])) {
		continue;
	}
	$row = $config[$name];
	$value = isset($values[$name]) ? $values[$name] : null;
	if ($row['config_type'] == 'checkbox') {
		$value = ($value === 'true') ? 'true' : 'false';
	} else if ($row['config_type'] == 'password' && ($value === null || $value === '')) {
		// Left empty: the saved password stays.
		continue;
	} else if (is_array($value)) {
		// Working days: one checkbox per day.
		$value = implode(',', array_filter(array_map('strval', $value), 'strlen'));
	} else if ($value === null) {
		continue;
	}
	if ((string)$value === (string)$row['config_value']) {
		continue;
	}
	$obj->config_id = (int)$row['config_id'];
	$obj->config_name = $name;
	$obj->config_value = (string)$value;
	if (($msg = $obj->store())) {
		$AppUI->setMsg($msg, UI_MSG_ERROR, true);
		$errors++;
	}
}
if (!$errors) {
	$AppUI->setMsg('updated', UI_MSG_OK, true);
}

$q = new DBQuery;
$q->addTable('config');
$q->addQuery('config_name, config_value');
$audit_changed = array_keys(array_diff_assoc($q->loadHashList(), $audit_before));
if ($audit_changed) {
	// Names only: values can be passwords (mail_pass, ldap_pass).
	sort($audit_changed);
	dPaudit('config.changed', implode(', ', $audit_changed), 'config');
}
$AppUI->redirect();
?>
