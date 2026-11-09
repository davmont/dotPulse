<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* $Id: timetrack.class.php,v 1.2 2003/09/04 03:07:12 kripper Exp $ */
##
## TimeTrack Classes
##

class CTimeSheet {
	var $tt_id = NULL;
	var $tt_user_id = NULL;
	var $tt_week = NULL;
	var $tt_active = NULL;
	var $tt_note_id = NULL;
	var $tt_year = NULL;
	var $tt_submitted = NULL;
	var $tt_start_date = NULL;
	var $tt_end_date = NULL;
	var $tt_supervisor_approval = NULL;
	var $tt_pm_approval = NULL;
	var $tt_approve_note = NULL;
	var $tt_approve_note_date = NULL;

	function __construct() {
		// empty constructor
	}

	function load( $oid = null, $strip = true ) {
                $obj=null;
                $q = new DBQuery();
                $q -> addTable('timetrack_idx');
                $q -> addQuery('*');
                $q -> addWhere('tt_id = ' . (int)$oid);
                $q -> loadObject($obj);
		return $obj;
	}

	function bind( $hash ) {
		if (!is_array( $hash )) {
			return get_class( $this )."::bind failed";
		} else {
			bindHashToObject( $hash, $this );
			// Ids are integers: the raw request value must not reach the SQL that uses them.
			if (isset($this->tt_id) && $this->tt_id !== '') {
				$this->tt_id = (int)$this->tt_id;
			}
			if (isset($this->tt_user_id) && $this->tt_user_id !== '') {
				$this->tt_user_id = (int)$this->tt_user_id;
			}
			return NULL;
		}
	}

	function check() {
		if ($this->tt_id === NULL) {
			return 'timsheet id is NULL';
		}
		// TODO MORE
		return NULL; // object is ok
	}

	function store($updateNulls = false) {
		$msg = $this->check();
		if( $msg ) {
			return get_class( $this )."::store-check failed";
		}
		if( $this->tt_id ) {
			$ret = db_updateObject( 'timetrack_idx', $this, 'tt_id' );
		} else {
			$ret = db_insertObject( 'timetrack_idx', $this, 'tt_id' );
		}
		if( !$ret ) {
			return get_class( $this )."::store failed <br>" . db_error();
		} else {
			return NULL;
		}
	}
	function delete($oid = null, $history_desc = '', $history_proj = 0) {
                $q = new DBQuery();
                $q -> addTable('timetrack_data');
                $q -> addQuery('tt_data_timesheet_id');
                $q -> addWhere('tt_data_timesheet_id = '.(int)$this->tt_id);
                $q -> exec();
		if ($q -> foundRows()) {
			return "You cannot delete a timesheet that has entries associated with it.";
		} else{
                      $q -> clear();
                      $q -> setDelete('timetrack_data');
                      $q -> addWhere('tt_id = ' . (int)$this->tt_id);
			if (!$q->exec()) {
				return db_error();
			} else {
				return NULL;
			}
		}
	}
}

class CTimeData {
	var $tt_data_id = NULL;
	var $tt_data_timesheet_id = NULL;
	var $tt_data_date = NULL;
	var $tt_data_client_id = NULL;
	var $tt_data_project_id = NULL;
	var $tt_data_task_id = NULL;
	var $tt_data_description = NULL;
	var $tt_data_hours = NULL;
	var $tt_data_change_date = NULL;
	var $tt_data_note = NULL;

	function __construct() {
		// empty constructor
	}

	function load( $oid = null, $strip = true ) {
                $q = new DBQuery();
                $q -> addTable('timetrack_data');
                $q -> addQuery('*');
                $q -> addWhere('tt_data_id = '.(int)$oid);
                $q -> loadObject($this);
		return $this;
	}

	function bind( $hash ) {
		if (!is_array( $hash )) {
			return get_class( $this )."::bind failed";
		} else {
			bindHashToObject( $hash, $this );
			// Ids are integers: the raw request value must not reach the SQL that uses them.
			if (isset($this->tt_data_id) && $this->tt_data_id !== '') {
				$this->tt_data_id = (int)$this->tt_data_id;
			}
			if (isset($this->tt_data_timesheet_id) && $this->tt_data_timesheet_id !== '') {
				$this->tt_data_timesheet_id = (int)$this->tt_data_timesheet_id;
			}
			return NULL;
		}
	}

	function check() {
		if ($this->tt_data_id === NULL) {
			return 'tt_data id is NULL';
		}
		// TODO MORE
		return NULL; // object is ok
	}

	function store($updateNulls = false) {
		$msg = $this->check();
		if( $msg ) {
			return get_class( $this )."::store-check failed";
		}
		if( $this->tt_data_id ) {
			$ret = db_updateObject( 'timetrack_data', $this, 'tt_data_id', false );
		} else {
			$ret = db_insertObject( 'timetrack_data', $this, 'tt_data_id' );
		}
		if( !$ret ) {
			return get_class( $this )."::store failed <br>" . db_error();
		} else {
			return NULL;
		}
	}
	function delete($oid = null, $history_desc = '', $history_proj = 0) {
                $ok=1;
                $q = new DBQuery();
                $q -> setDelete('timetrack_data');
                $q -> addWhere('tt_data_id = '.(int)$this->tt_data_id);
                if (!$q->exec()) {
			return db_error();
		} else {
			return NULL;
		}
	}
}

?>
