<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
// this doSQL script is called from the addedit.php script
// its purpose is to use the CTest class to interoperate with the database (store, edit, delete)

/* the following variables can be retreived via POST from testing/addedit.php:
** int unittest_id	is '0' if a new database object has to be stored or the id of an existing quote that should be overwritten or deleted in the db
** str test_description	the text of the quote that should be stored
** int del		bool flag, in case of presence the row with the given unittest_id has to be dropped from db
*/

// create a new instance of the test class
$obj = new CTesting();
$msg = '';	// reset the message string

// bind the informations (variables) retrieved via post to the test object
if (!$obj->bind( $_POST )) {
	$AppUI->setMsg( $obj->getError(), UI_MSG_ERROR );
	$AppUI->redirect();
}

// detect if a deleete operation has to be processed
$del = (int)dPgetParam( $_POST, 'del', 0 );
$unittest_id = (int)$obj->unittest_id;
// Permission on this record, as addedit.php and runtest.php check (getDenyEdit).
dPrequireWritePermission('testing', $unittest_id, $del);
// An existing test of a project the user cannot see stays out of reach.
$stored_project = 0;
if ($unittest_id) {
	$existing = new CTesting();
	if (!$existing->load($unittest_id)) {
		$AppUI->setMsg( 'Test' );
		$AppUI->setMsg( 'invalidID', UI_MSG_ERROR, true );
		$AppUI->redirect();
	}
	$stored_project = (int)$existing->unittest_project_id;
	if ($stored_project && !getPermission('projects', 'view', $stored_project)) {
		$AppUI->setMsg('Access denied.', UI_MSG_ERROR);
		$AppUI->redirect('m=public&a=access_denied');
	}
}


if ($del) {
	// see how easy it is to run database commands with the object oriented architecture !
	// simply delete a quote from db and have detailed error or success report
	if (($msg = $obj->delete())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );			// message with error flag
		$AppUI->redirect();
	} else {
		$AppUI->setMsg( "Test deleted", UI_MSG_ALERT);		// message with success flag
		$AppUI->redirect( "m=testing" );
	}
} else {
	// simply store the added/edited quote in database via the store method of the test child class of the CDpObject provided ba the dPFramework
	// no sql command is necessary here! :-)
	// The forms name the project and task fields test_project / test_task.
	if (($test_project = dPgetParam( $_POST, 'test_project', null )) !== null) {
		$obj->unittest_project_id = (int)$test_project;
	}
	if (($test_task = dPgetParam( $_POST, 'test_task', null )) !== null) {
		$obj->unittest_task_id = (int)$test_task;
	}
	// The project must be one the user can see, and the task one of its tasks.
	$test_project_id = ($obj->unittest_project_id !== null) ? (int)$obj->unittest_project_id : $stored_project;
	if (($test_project_id && !getPermission('projects', 'view', $test_project_id))
			|| !dPrecordsBelongTo($test_project_id, array(array('tasks', 'task_id', 'task_project', (int)$obj->unittest_task_id)))) {
		$AppUI->setMsg('Access denied.', UI_MSG_ERROR);
		$AppUI->redirect('m=public&a=access_denied');
	}
	if (($msg = $obj->store())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	} else {
		$AppUI->setMsg( $unittest_id ? 'Test updated' : 'Test inserted', UI_MSG_OK);
	}
	$AppUI->redirect();
}
?>