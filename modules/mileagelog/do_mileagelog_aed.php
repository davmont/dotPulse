<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/*  */

$MILEAGELOG_CONFIG = array();
require_once( "./modules/mileagelog/config.php" );
require_once( "./modules/mileagelog/mileagelog.class.php" );

// The module's add/edit permission, as addedit.php checks.
if (getDenyEdit('mileagelog')) {
	$AppUI->redirect( "m=public&a=access_denied" );
}

$obj = new CMileageLog();
$AppUI->setMsg( 'Mileage Log' );

$msg = '';

if (($msg = $obj->bind( $_POST ))) {
	$AppUI->setMsg( $msg, UI_MSG_ERROR );
	$AppUI->redirect();
}

$del = (int)dPgetParam( $_POST, 'del', 0 );
$mileage_log_id = (int)$obj->mileage_log_id;
if ($del && !$mileage_log_id) {
	$AppUI->redirect();
}

// Other users' logs only for those at the configured level, as addedit.php and the list allow.
$can_edit_other_mileagelogs = $MILEAGELOG_CONFIG['minimum_edit_level'] >= $AppUI->user_type;
if ($mileage_log_id) {
	$existing = new CMileageLog();
	if (!$existing->load($mileage_log_id)) {
		$AppUI->setMsg('invalidID', UI_MSG_ERROR, true);
		$AppUI->redirect();
	}
	if (!$can_edit_other_mileagelogs && (int)$existing->mileage_log_user_id != (int)$AppUI->user_id) {
		$AppUI->redirect( "m=public&a=access_denied" );
	}
}
if (!$del) {
	if (!$obj->mileage_log_user_id) {
		$obj->mileage_log_user_id = (int)$AppUI->user_id;
	}
	if (!$can_edit_other_mileagelogs && (int)$obj->mileage_log_user_id != (int)$AppUI->user_id) {
		$AppUI->redirect( "m=public&a=access_denied" );
	}
}

// A purpose posted with an id must be one of this log's purposes.
$purpose_ids = array(
	dPgetIntParam($_POST, 'task_mileage_log_purpose_id', 0),
	dPgetIntParam($_POST, 'helpdesk_mileage_log_purpose_id', 0),
	dPgetIntParam($_POST, 'note_mileage_log_purpose_id', 0),
);
foreach ($purpose_ids as $purpose_id) {
	if ($purpose_id > 0 && (!$mileage_log_id || !dPrecordsBelongTo($mileage_log_id,
			array(array('mileage_log_purpose', 'mileage_log_purpose_id', 'mileage_log_id', $purpose_id))))) {
		$AppUI->redirect( "m=public&a=access_denied" );
	}
}

// prepare (and translate) the module name ready for the suffix
if ($del) {
	if (($msg = $obj->delete())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect();
	} else {
		$AppUI->setMsg( "deleted", UI_MSG_ALERT, true );
		$AppUI->redirect( "m=mileagelog&user_id=" . (int)$existing->mileage_log_user_id );
	}
} else {
	if (($msg = $obj->store())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	} else {
		$AppUI->setMsg( $mileage_log_id ? 'updated ': 'added', UI_MSG_OK, true );
		
		// update purposes
		if ($MILEAGELOG_CONFIG['show_purpose_task']) {
			$relation_id = dPgetIntParam($_POST, 'task_mileage_log_purpose_relation_id', 0);
			if ($relation_id) {
				$values = array();
				$values['mileage_log_purpose_id'] = $purpose_ids[0];
				$values['mileage_log_id'] = (int)$obj->mileage_log_id;
				$values['mileage_log_purpose_relation_id'] = $relation_id;
				$values['mileage_log_purpose_relation_type'] = 1;
				$purpose_obj = new CMileageLogPurpose();
				if (!$purpose_obj->bind( $values )) {
					$purpose_obj->store();
				}
			}
		}
		if ($MILEAGELOG_CONFIG['show_purpose_helpdesk']) {
			$relation_id = dPgetIntParam($_POST, 'helpdesk_mileage_log_purpose_relation_id', 0);
			if ($relation_id) {
				$values = array();
				$values['mileage_log_purpose_id'] = $purpose_ids[1];
				$values['mileage_log_id'] = (int)$obj->mileage_log_id;
				$values['mileage_log_purpose_relation_id'] = $relation_id;
				$values['mileage_log_purpose_relation_type'] = 2;
				$purpose_obj = new CMileageLogPurpose();
				if (!$purpose_obj->bind( $values )) {
					$purpose_obj->store();
				}
			}
		}
		if ($MILEAGELOG_CONFIG['show_purpose_note']) {
			$values = array();
			$values['mileage_log_purpose_id'] = $purpose_ids[2];
			$values['mileage_log_id'] = (int)$obj->mileage_log_id;
			$values['mileage_log_purpose_note'] = dPgetCleanParam($_POST, 'note_mileage_log_purpose_note', '');
			$values['mileage_log_purpose_relation_type'] = 0;
			$purpose_obj = new CMileageLogPurpose();
			if (!$purpose_obj->bind( $values )) {
				$purpose_obj->store();
			}
		}
	}
	
}
$AppUI->redirect();
?>
