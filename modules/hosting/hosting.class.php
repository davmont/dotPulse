<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
require_once $AppUI->getSystemClass('date');
/* Hosting module -> $Id: hosting.class.php,v 1.1 2007/04/16 05:36:02 mbelos Exp $ */

require_once( $AppUI->getSystemClass ('dp' ) );
require_once( $AppUI->getModuleClass( 'companies' ) );
require_once( $AppUI->getModuleClass('departments') );
/**
 * The Project Class
 */
class CHosting extends CDpObject {
	var $domain_id = null;
	var $company_id = null;
	var $domain_name = null;
	var $domain_expiry_date = null;
	var $domain_registrar = null;
	var $domain_status = null;
	var $domain_notes = null;

	var $hosting_id = null;
	var $hosting_package_name = null;
	var $hosting_expiry_date = null;
	var $hosting_status = null;
	var $hosting_notes = null;

	function __construct() {
		parent::__construct( 'hosting', 'domain_id' );
	}

	function canDelete(&$message, $domain_id = null, $joins = null){
		return true;
	}

	function load($domain_id = null, $strip = true){
		$q = new DBQuery;
		$q->addTable('domains','dom');
		$q->addQuery("dom.domain_id, company_id, domain_name, domain_expiry_date, domain_registrar, domain_status,
			domain_notes, hosting_id, hosting_package_name, hosting_expiry_date, hosting_status, hosting_notes");
		$q->addJoin('hosting', 'h', 'h.domain_id = dom.domain_id');
		$q->addWhere('dom.domain_id = '.(int)$domain_id);
		$results = $q->exec();

		$row = $results ? db_fetch_assoc($results) : false;
		if (!$row) {
			return false;
		}

		$this->domain_id = $row['domain_id'];
		$this->company_id = $row['company_id'];
		$this->domain_name = $row['domain_name'];
		$this->domain_expiry_date = $row['domain_expiry_date'];
		$this->domain_registrar = $row['domain_registrar'];
		$this->domain_status = $row['domain_status'];
		$this->domain_notes = $row['domain_notes'];

		$this->hosting_id = $row['hosting_id'];
		$this->hosting_package_name = $row['hosting_package_name'];
		$this->hosting_expiry_date = $row['hosting_expiry_date'];
		$this->hosting_status = $row['hosting_status'];
		$this->hosting_notes = $row['hosting_notes'];
		return true;
	}

	function delete($oid = null, $history_desc = '', $history_proj = 0){
		$q = new DBQuery;
		$q->setDelete('domains');
		$q->addWhere('domain_id='.(int)$this->domain_id);
		$q->exec();
		$q->clear();

		$q->setDelete('hosting');
		$q->addWhere('domain_id='.(int)$this->domain_id);
		$q->exec();
		$q->clear();
	}

	function store($updateNulls = false){
		$domain_id = (int)$this->domain_id;
		$domain = array(
			'company_id' => (int)$this->company_id,
			'domain_name' => $this->domain_name,
			'domain_expiry_date' => $this->domain_expiry_date,
			'domain_registrar' => $this->domain_registrar,
			'domain_status' => $this->domain_status,
			'domain_notes' => $this->domain_notes,
		);
		$hosting = array(
			'hosting_package_name' => $this->hosting_package_name,
			'hosting_expiry_date' => $this->hosting_expiry_date,
			'hosting_status' => $this->hosting_status,
			'hosting_notes' => $this->hosting_notes,
		);

		$q = new DBQuery;
		$q->addTable('domains');
		if ($domain_id) {
			// An existing domain is updated in place, so its id (and custom fields) stay.
			foreach ($domain as $field => $value) {
				$q->addUpdate($field, $value);
			}
			$q->addWhere('domain_id = ' . $domain_id);
			$q->exec();
			$q->clear();

			$q->addTable('hosting');
			$q->addQuery('COUNT(*)');
			$q->addWhere('domain_id = ' . $domain_id);
			$has_hosting = (int)$q->loadResult();
			$q->clear();
		} else {
			foreach ($domain as $field => $value) {
				$q->addInsert($field, $value);
			}
			$q->exec();
			$domain_id = (int)db_insert_id();
			$this->domain_id = $domain_id;
			$q->clear();
			$has_hosting = 0;
		}

		$q->addTable('hosting');
		if ($has_hosting) {
			foreach ($hosting as $field => $value) {
				$q->addUpdate($field, $value);
			}
			$q->addWhere('domain_id = ' . $domain_id);
		} else {
			$q->addInsert('domain_id', $domain_id);
			foreach ($hosting as $field => $value) {
				$q->addInsert($field, $value);
			}
		}
		$q->exec();
		$q->clear();
		return null;
	}
}

function getCompanies(){
	// retrieve list of records
	// modified for speed
	// by Pablo Roca (pabloroca@mvps.org)
	// 16 August 2003
	// get the list of permitted companies
	global $AppUI, $buffer, $company, $company_id, $company_prefix, $deny, $department, $dept_ids, $dPconfig, $orderby, $orderdir, $projects, $tasks_critical, $tasks_problems, $tasks_sum, $tasks_summy;

	$obj = new CCompany();

	$companies = $obj->getAllowedRecords( $AppUI->user_id, 'company_id,company_name', 'company_name' );
	if(count($companies) == 0) $companies = array(0);

	// get the list of permitted companies
	$companies = arrayMerge( array( '0'=>$AppUI->_('All') ), $companies );

	//get list of all departments, filtered by the list of permitted companies.
	$q  = new DBQuery;
	$q->clear();
	$q->addTable('companies','companies');
	$q->addQuery('company_id, company_name, dep.*');
	$q->addJoin('departments', 'dep', 'companies.company_id = dep.dept_company');
	$q->addOrder('company_name,dept_parent,dept_name');
	$obj->setAllowedSQL($AppUI->user_id, $q);
	$rows = $q->loadList();

	//display the select list
	$buffer = '<select name="company_id"' . dPaction(null, array(), null, array('submit' => 'pickCompany')) . ' class="text">';
	$buffer .= '<option value="company_0" style="font-weight:bold;">'.$AppUI->_('All').'</option>'."\n";
	$company = '';
	foreach ($rows as $row) {
		if ($row["dept_parent"] == 0) {
			if($company!=$row['company_id']){
				$buffer .= '<option value="'.$company_prefix.$row['company_id'].'" style="font-weight:bold;"'.($company_id==$row['company_id']?'selected="selected"':'').'>'.dPhtml($row['company_name']).'</option>'."\n";
				$company=$row['company_id'];
			}
			if($row["dept_parent"]!=null){
				showchilddept( $row );
				findchilddept( $rows, $row["dept_id"] );
			}
		}
	}
	$buffer .= '</select>';
}
?>