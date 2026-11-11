<?php
/* FINANCES setup.php, v 0.1.0 2012/07/20 */
/*
* Copyright (c) 2012 Region Poitou-Charentes (France)
*
* Description:	Setup page of the Finances module.
*
* Author:		Simon BENUREAU, <simon.benureau@gmail.com>
*
* License:		GNU/GPL
*
* CHANGE LOG
*
* version 0.1.0
* 	Creation
*
*/
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

/**
 *  Name: Finances
 *  Directory: finances
 *  Version 1.0
 *  Type: user
 *  UI Name: finances
 *  UI Icon: ?
 */

$config = array();
$config['mod_name'] = 'Finances';
$config['mod_version'] = '1.2';
$config['mod_directory'] = 'finances';
$config['mod_setup_class'] = 'CSetupFinances';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'Finances';
$config['mod_ui_icon'] = 'folder5.png';
$config['mod_description'] = 'This module add budget improvements';
$config['mod_config'] = false;


if (@$a == 'setup') {
	echo dPshowModuleConfig($config);
}


class CSetupFinances {

  function configure() { return true; }

  function remove() {
		$dbprefix = dPgetConfig('dbprefix', '');
		$q = new DBQuery();
		$q -> dropTable('budget');
		// exec() returns a record set object, not a number.
		return $q -> exec() ? null : db_error();
	}
  
	function upgrade($old_version) {
		// 1.1: when the costs module was installed first, 'budget' was its table
		// and ours was never created (see install()).
		if (version_compare($old_version, '1.1', '<') && $this->install() !== null) {
			return false;
		}
		// 1.2: the budget table in utf8mb4 like the rest (it was latin1).
		return dPconvertTablesToUtf8mb4(array('budget'));
	}

	function install() { 
		$dbprefix = dPgetConfig('dbprefix', '');
		// The costs module used to create a 'budget' table too (project budgets,
		// other columns); move it to its new name, costs_budget, first.
		$exists = function ($table) use ($dbprefix) {
			return (bool)db_loadResult("SHOW TABLES LIKE '" . db_escape($dbprefix . $table) . "'");
		};
		if ($exists('budget') && !$exists('costs_budget')
			&& db_loadResult("SHOW COLUMNS FROM `" . $dbprefix . "budget` LIKE 'budget_project_id'")) {
			db_exec("RENAME TABLE `" . $dbprefix . "budget` TO `" . $dbprefix . "costs_budget`");
		}
		if ($exists('budget')) {
			return null;
		}
		$success = true;
		$q = new DBQuery();
		$q -> createTable('budget');
		$q -> createDefinition("(
				  `budget_id` int(11) NOT NULL AUTO_INCREMENT,
				  `task_id` int(11) NOT NULL DEFAULT '0',
				  `Tax` decimal(4,2) NOT NULL DEFAULT '0',
				  `display_tax` tinyint(1) NOT NULL DEFAULT '0',
				  `only_financial` tinyint(1) NOT NULL DEFAULT '0',
				  `equipment_investment` decimal(15,2) DEFAULT '0',
				  `intangible_investment` decimal(15,2) DEFAULT '0',
				  `service_investment` decimal(15,2) DEFAULT '0',
				  `equipment_operation` decimal(15,2) DEFAULT '0',
				  `intangible_operation` decimal(15,2) DEFAULT '0',
				  `service_operation` decimal(15,2) DEFAULT '0',
				  PRIMARY KEY (`budget_id`)
				) ENGINE=MyISAM AUTO_INCREMENT=7 ;");
		if ($q -> exec()) {
			return null;
		}
		return db_error();
	}
}
