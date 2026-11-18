<?php /* FORUMS $Id: do_forum_aed.php 4779 2007-02-21 14:53:28Z cyberhorse $ */
if (!defined('DP_BASE_DIR')){
	die('You should not access this file directly');
}

$del = dPgetParam($_POST, 'del', 0);
// Permission on this record, as its addedit page checks.
dPrequireWritePermission('forums', dPgetParam($_POST, 'forum_id', 0), $del);

$obj = new CForum();

if (($msg = $obj->bind( $_POST ))) {
	$AppUI->setMsg( $msg, UI_MSG_ERROR );
	$AppUI->redirect();
}

// prepare (and translate) the module name ready for the suffix
$AppUI->setMsg( 'Forum' );
if ($del) {
	if (($msg = $obj->delete())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect();
	} else {
		$AppUI->setMsg( "deleted", UI_MSG_ALERT, true );
		$AppUI->redirect( "m=forums" );
	}
} else {
	if (($msg = $obj->store())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	} else {
		$isNotNew = dPgetParam($_POST, 'forum_id');
		$AppUI->setMsg( $isNotNew ? 'updated' : 'added', UI_MSG_OK, true );
	}
	$AppUI->redirect();
}
?>