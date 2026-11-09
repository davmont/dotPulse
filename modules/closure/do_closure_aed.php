<?php /* PROJECTS $Id: do_project_aed.php 4779 2007-02-21 14:53:28Z cyberhorse $ */
if (!defined('DP_BASE_DIR')){
	die('You should not access this file directly');
}

$obj = new CClosure();
$msg = '';

if (!$obj->bind( $_POST )) {
	$AppUI->setMsg( $obj->getError(), UI_MSG_ERROR );
	$AppUI->redirect();
}

require_once(DP_BASE_DIR . '/classes/CustomFields.class.php');

$del = (int)dPgetParam( $_POST, 'del', 0 );
$pma_id = (int)dPgetParam($_POST, 'pma_id', 0);
// Permission on this record, as its addedit page checks.
dPrequireWritePermission('closure', $pma_id, $del);

if (!$del) {
	// A post mortem belongs to a project by name: the user must be allowed to edit the
	// project it is about, and the one it was about before (as CClosure::canDelete()).
	$closure_names = array((string)$obj->project_name);
	if ($pma_id) {
		$stored = new CClosure();
		if (!$stored->load($pma_id)) {
			$AppUI->setMsg('Post Mortem');
			$AppUI->setMsg('invalidID', UI_MSG_ERROR, true);
			$AppUI->redirect();
		}
		$closure_names[] = (string)$stored->project_name;
	}
	foreach (array_unique($closure_names) as $closure_name) {
		$q = new DBQuery();
		$q->addTable('projects');
		$q->addQuery('project_id');
		$q->addWhere('project_name = ' . $q->quote($closure_name));
		$closure_project = (int)$q->loadResult();
		$q->clear();
		// A stored name whose project is gone (renamed or deleted) does not block an edit.
		if ($closure_project || $closure_name === (string)$obj->project_name) {
			dPrequireProjectEdit($closure_project);
		}
	}
}

// prepare (and translate) the module name ready for the suffix
if ($del) {
	$canDelete = $obj->canDelete($msg, $pma_id);
	if (!$canDelete) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect();
	}
	if (($msg = $obj->delete($pma_id))) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect();
	} else {
		$AppUI->setMsg( 'Post Mortem deleted', UI_MSG_ALERT);
		$AppUI->redirect( 'm=closure' );
	}
} else {
	if (($msg = $obj->store())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	} else {
		$isNotNew = @$_POST['pma_id'];
 		$custom_fields = New CustomFields( $m, 'addedit', $obj->pma_id, "edit" );
 		$custom_fields->bind( $_POST );
 		$sql = $obj->store( $obj->pma_id ); // Store Custom Fields

		$AppUI->setMsg( $isNotNew ? 'Post Mortem updated' : 'Post Mortem inserted', UI_MSG_OK);
	}
	$AppUI->redirect();
}
?>
