<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

require_once $AppUI->getSystemClass('dp');
require_once $AppUI->getSystemClass('query');

class CHumanResource extends CDpObject
{
	var $human_resource_id = null;
	var $human_resource_user_id = null;
	var $human_resource_lattes_url = null;
	var $human_resource_company_role = null;
	var $human_resource_mon = null;
	var $human_resource_tue = null;
	var $human_resource_wed = null;
	var $human_resource_thu = null;
	var $human_resource_fri = null;
	var $human_resource_sat = null;
	var $human_resource_sun = null;

	function __construct()
	{

		parent::__construct('human_resource', 'human_resource_id', 'human_resources');
	}

	function canDelete(&$msg, $oid = null, $joins = null)
	{
		$query = new DBQuery;
		$query->addTable('human_resource_allocation', 'a');
		$query->addQuery('human_resource_allocation_id');
		$id = $oid ? $oid : $this->human_resource_id;
		$query->addWhere('a.human_resource_id = ' . (int) $id);
		$sql = $query->prepare();
		$query->clear();
		return count(db_loadList($sql)) == 0;
	}
}

class CHumanResourceAllocation extends CDpObject
{
	var $human_resource_allocation_id = null;
	var $project_tasks_estimated_roles_id = null;
	var $human_resource_id = null;

	function __construct()
	{
		parent::__construct('human_resource_allocation', 'human_resource_allocation_id', 'human_resources');
	}

	function canDelete(&$msg, $oid = null, $joins = null)
	{
		// Permission check (human_resources delete) and dependencies, as for other records.
		return parent::canDelete($msg, $oid, $joins);
	}

	function store($updateNulls = false)
	{
		return parent::store($updateNulls);
	}

	function storeAllocation($task_id, $user_id)
	{

		$q = new DBQuery;
		$q->addTable('user_tasks');
		$q->addQuery('user_id');
		$q->addWhere('task_id = ' . (int)$task_id . ' and user_id = ' . (int)$user_id);
		$sql = $q->prepare();
		$user = db_loadList($sql);
		$q->clear();

		if (count($user) == 0) {
			$q->addTable('user_tasks');
			$q->addInsert('user_id', $user_id);
			$q->addInsert('task_id', (int)$task_id);
			$q->addInsert('perc_assignment', '100');
			$q->exec();
			$q->clear();
		}
		return $this->store();
	}

	function delete($oid = null, $history_desc = '', $history_proj = 0)
	{
		return parent::delete($oid, $history_desc, $history_proj);
	}

	function deleteAllocation($task_id, $user_id)
	{
		$q = new DBQuery;
		$q->setDelete('user_tasks');
		$q->addWhere('task_id = ' . (int)$task_id . ' AND user_id = ' . (int)$user_id);
		$q->exec();
		$q->clear();

		return $this->delete();
	}
}

class CCompaniesPolicies extends CDpObject
{
	var $company_policies_id = null;
	var $company_policies_recognition = null;
	var $company_policies_policy = null;
	var $company_policies_safety = null;
	var $company_policies_company_id = null;

	function __construct()
	{
		parent::__construct('company_policies', 'company_policies_id', 'human_resources');
	}

	function canDelete(&$msg, $oid = null, $joins = null)
	{
		// Permission check (human_resources delete) and dependencies, as for other records.
		return parent::canDelete($msg, $oid, $joins);
	}
}

class CHumanResourcesRole extends CDpObject
{
	var $human_resources_role_id = null;
	var $human_resources_role_name = null;
	var $human_resources_role_company_id = null;
	var $human_resources_role_responsability = null;
	var $human_resources_role_authority = null;
	var $human_resources_role_competence = null;

	function __construct()
	{
		parent::__construct('human_resources_role', 'human_resources_role_id', 'human_resources');
	}

	function canDelete(&$msg, $oid = null, $joins = null)
	{
		// Permission check (human_resources delete) and dependencies, as for other records.
		return parent::canDelete($msg, $oid, $joins);
	}
}

class CHumanResourceRoles extends CDpObject
{
	var $human_resource_roles_id = null;
	var $human_resources_role_id = null;
	var $human_resource_id = null;

	function __construct()
	{
		parent::__construct('human_resource_roles', 'human_resource_roles_id', 'human_resources');
	}

	function deleteAll($human_resource_id)
	{
		$q = new DBQuery;
		$q->setDelete('human_resource_roles');
		$q->addWhere('human_resource_id = ' . (int)$human_resource_id);
		$q->exec();
		$q->clear();
	}

	function store($updateNulls = false)
	{
		return parent::store($updateNulls);
	}

	function storeRoles($role_id, $human_resource_id)
	{
		$q = new DBQuery;
		$q->addTable('human_resource_roles');
		$q->addInsert('human_resources_role_id', $role_id);
		$q->addInsert('human_resource_id', $human_resource_id);
		$q->exec();
		$q->clear();
	}

	function canDelete(&$msg, $oid = null, $joins = null)
	{
		// Permission check (human_resources delete) and dependencies, as for other records.
		return parent::canDelete($msg, $oid, $joins);
	}
}
?>