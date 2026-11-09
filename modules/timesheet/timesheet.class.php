<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* $Id: timesheet.class.php,v 1.1.1.1 2003/12/04 17:53:45 iexposure Exp $ */
##
## Timesheet Class
##

class Timesheet {
	var $timesheet_id = NULL;
	var $user_id = NULL;
	var $timesheet_date = NULL;
	var $timesheet_time_in = NULL;
	var $timesheet_time_out = NULL;
	var $timesheet_time_break = NULL;
	var $timesheet_time_break_start = NULL;
	var $timesheet_note = NULL;
        
	function __construct() {
		// empty constructor
	}

	function load( $oid ) {
		$q = new DBQuery();
		$q -> addTable('timesheet');
		$q -> addQuery('*');
		$q -> addWhere('timesheet_id = ' . (int)$oid);
		return $q->loadObject($this);
	}

	function bind( $hash ) {
		if (!is_array( $hash )) {
			return get_class( $this )."::bind failed";
		} else {
			bindHashToObject( $hash, $this );
			// Ids are integers: the raw request value must not reach the SQL that uses them.
			if (isset($this->timesheet_id) && $this->timesheet_id !== '') {
				$this->timesheet_id = (int)$this->timesheet_id;
			}
			if (isset($this->user_id) && $this->user_id !== '') {
				$this->user_id = (int)$this->user_id;
			}
			return NULL;
		}
	}

	function check() {
		if ($this->timesheet_id === NULL) {
			return 'timesheet id is NULL';
		}
		// TODO MORE
		return NULL; // object is ok
	}

	function store() {
		$q = new DBQuery();
		$msg = $this->check();
		if( $msg ) {
			return get_class( $this )."::store-check failed";
		}
		if( $this->timesheet_id ) {
			$ret = db_updateObject( 'timesheet', $this, 'timesheet_id', false );
		} else {
			$ret = db_insertObject( 'timesheet', $this, 'timesheet_id' );
		}
		if( !$ret ) {
			return get_class( $this )."::store failed <br>" . db_error();
		} else {
			return NULL;
		}
	}
	function delete() {
		$q = new DBQuery();
		$q -> setDelete('timesheet');
		$q -> addWhere('timesheet_id = ' . (int)$this->timesheet_id);
		
		if (!$q->exec()) {
			return db_error();
		} else {
			return NULL;
		}
	}
}

?>