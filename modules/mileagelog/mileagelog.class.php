<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
require_once( $AppUI->getSystemClass( 'dp' ) );
//require_once( $AppUI->getSystemClass( 'libmail' ) );

// Function to build a where clause to be appended to any sql that will narrow
// down the returned data to only permitted entities

function getPermsWhereClause($mod, $mod_id_field){
	GLOBAL $AppUI, $perms;
	$dbprefix = dPgetConfig('dbprefix', '');

  // Figure out the module and field
	switch($mod){
		case "companies":
			$id_field = "company_id";
			break;
		case "users":
			$id_field = "user_id";
			break;
		case "projects":
			$id_field = "project_id";
			break;
		case "tasks":
			$id_field = "task_id";
			break;
		case "helpdesk_items":
			$id_field = "item_id";
			break;
		default:
			return null;
	}
	if(
		(isset($perms[$mod]['-1']) && ($perms[$mod]['-1']=='1' || $perms[$mod]['-1']=='-1')) || 
     		(isset($perms["all"]['-1']) && ($perms["all"]['-1']=='1' || $perms["all"]['-1']=='-1'))
     	) {
		$sql = "SELECT $id_field FROM {$dbprefix}$mod";
		$list = db_loadColumn( $sql );
	} else {
		$list = array();
	}

	$list[] = "''";
	$list[] = "0";

	if(isset($perms[$mod])){
		foreach($perms[$mod] as $key => $value){
			//-1 is all perms, so not a specific one

			if($key=='-1')
				continue;

			switch($value){
				case '-1': //edit
					$list[] = $key;
					break;
				case '0'://deny
					unset($list[array_search($key, $list)]);
					break;
				case '1'://read
					$list[] = $key;
					break;
				default:
					break;
			}
		}
	}

	$list = array_unique($list);

	return " $mod_id_field in (".implode(",",$list).")";
}

class CMileageLog {

	var $mileage_log_id = NULL;
	var $mileage_log_user_id = NULL;
	var $mileage_log_date = NULL;
	var $mileage_log_od_start = NULL;
	var $mileage_log_od_end = NULL;
	var $mileage_log_miles = NULL;
	
	function __construct () {
		// empty constructor
	}

	function load( $mileage_log_id = null, $strip = true ) {
		$q = new DBQuery;
		$q->addTable('mileage_log');
		$q->addWhere('mileage_log_id = ' . intval($mileage_log_id));
		return $q->loadObject($this);
	}

	function bind( $hash ) {
		if (!is_array( $hash )) {
			return get_class( $this )."::bind failed";
		} else {
			bindHashToObject( $hash, $this );
			// Ids are integers: the raw request value must not reach the SQL that uses them.
			if (isset($this->mileage_log_id) && $this->mileage_log_id !== '') {
				$this->mileage_log_id = (int)$this->mileage_log_id;
			}
			if (isset($this->mileage_log_user_id) && $this->mileage_log_user_id !== '') {
				$this->mileage_log_user_id = (int)$this->mileage_log_user_id;
			}
			return NULL;
		}
	}

	function check() {
		if ($this->mileage_log_id === NULL) {
			return 'mileage log id is NULL';
		}
		// TODO MORE
		return NULL; // object is ok
	}

	function store($updateNulls = false) {
		if( !$this ) {
			return get_class( $this )."::store-check failed";
		}
		if( $this->mileage_log_id ) {
			$ret = db_updateObject( 'mileage_log', $this, 'mileage_log_id');
		} else {
			$ret = db_insertObject( 'mileage_log', $this, 'mileage_log_id');
		}
		if( !$ret ) {
			return get_class( $this )."::store failed <br>" . db_error();
		} else {
			return NULL;
		}
	}
	function delete($oid = null, $history_desc = '', $history_proj = 0) {
		$q = new DBQuery;
		$q->setDelete('mileage_log');
		$q->addWhere('mileage_log_id = ' . intval($this->mileage_log_id));
		if (!$q->exec()) {
			return db_error();
		} else {
			return NULL;
		}
	}
}

class CMileageLogPurpose {

	var $mileage_log_purpose_id = NULL;
	var $mileage_log_id = NULL;
	var $mileage_log_purpose_relation_id = NULL;
	var $mileage_log_purpose_relation_type = NULL;
	var $mileage_log_purpose_note = NULL;
	
	function __construct () {
		// empty constructor
	}

	function load( $mileage_log_purpose_id = null, $strip = true ) {
		$q = new DBQuery;
		$q->addTable('mileage_log_purpose');
		$q->addWhere('mileage_log_purpose_id = ' . intval($mileage_log_purpose_id));
		return $q->loadObject($this);
	}

	function bind( $hash ) {
		if (!is_array( $hash )) {
			return get_class( $this )."::bind failed";
		} else {
			bindHashToObject( $hash, $this );
			// Ids are integers: the raw request value must not reach the SQL that uses them.
			if (isset($this->mileage_log_purpose_id) && $this->mileage_log_purpose_id !== '') {
				$this->mileage_log_purpose_id = (int)$this->mileage_log_purpose_id;
			}
			if (isset($this->mileage_log_id) && $this->mileage_log_id !== '') {
				$this->mileage_log_id = (int)$this->mileage_log_id;
			}
			return NULL;
		}
	}

	function check() {
		if ($this->mileage_log_purpose_id === NULL) {
			return 'mileage log purpose id is NULL';
		}
		// TODO MORE
		return NULL; // object is ok
	}

	function store($updateNulls = false) {
		if( !$this ) {
			return get_class( $this )."::store-check failed";
		}
		if( $this->mileage_log_purpose_id ) {
			$ret = db_updateObject( 'mileage_log_purpose', $this, 'mileage_log_purpose_id');
		} else {
			$ret = db_insertObject( 'mileage_log_purpose', $this, 'mileage_log_purpose_id');
		}
		if( !$ret ) {
			return get_class( $this )."::store failed <br>" . db_error();
		} else {
			return NULL;
		}
	}
	function delete($oid = null, $history_desc = '', $history_proj = 0) {
		$q = new DBQuery;
		$q->setDelete('mileage_log_purpose');
		$q->addWhere('mileage_log_purpose_id = ' . intval($this->mileage_log_purpose_id));
		if (!$q->exec()) {
			return db_error();
		} else {
			return NULL;
		}
	}
}
?>
