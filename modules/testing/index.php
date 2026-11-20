<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// this is the index site for our testing module
// it is automatically appended on the applications main ./index.php
// by the dPframework

// we check for permissions on this module
$canRead = !getDenyRead( $m );		// retrieve module-based readPermission bool flag
$canEdit = !getDenyEdit( $m );		// retrieve module-based writePermission bool flag
if (isset( $_REQUEST['project_id'] )) {
	$AppUI->setState( 'TestIdxProject', (int)$_REQUEST['project_id'] );
}

$project_id = $AppUI->getState( 'TestIdxProject', 0 );

if (!$canRead) {			// lock out users that do not have at least readPermission on this module
	$AppUI->redirect( "m=public&a=access_denied" );
}

$AppUI->savePlace();	//save the workplace state (have a footprint on this site)

// retrieve any state parameters (temporary session variables that are not stored in db)

if (isset( $_GET['tab'] )) {
	$AppUI->setState( 'TestingIdxTab', $_GET['tab'] );		// saves the current tab box state
}
$tab = $AppUI->getState( 'TestingIdxTab' ) !== NULL ? $AppUI->getState( 'TestingIdxTab' ) : 0;	// use first tab if no info is available
$active = intval( !$AppUI->getState( 'TestingIdxTab' ) );						// retrieve active tab info for the tab box that
													// will be created down below
// we prepare the User Interface Design with the dPFramework

// setup the title block with Name, Icon and Help
$titleBlock = new CTitleBlock( 'Unit Testing', 'testing.png', $m, "$m.$a" );	// load the icon automatically from ./modules/testing/images/
$titleBlock->addCell();

require_once( $AppUI->getModuleClass( 'projects' ) );
$project = new CProject();
$projects = $project->getAllowedRecords( $AppUI->user_id, 'project_id,project_name', 'project_name' );
$allowedProjects = array_keys($projects);
$projects = arrayMerge( array( '0'=>$AppUI->_('All', UI_OUTPUT_RAW) ), $projects );

$titleBlock->addCell( $AppUI->_('Filter') . ':' );
$titleBlock->addCell(
	arraySelect( $projects, 'project_id', 'size="1" class="text"' . dPaction(null, array(), null, array('submit' => '')), $project_id ), '',
	'<form name="pickProject" action="?m=testing" method="post">', '</form>'
);


// adding the 'add'-Button if user has writePermissions
if ($canEdit) {
	$titleBlock->addCell(
		'<input type="submit" class="button" value="'.$AppUI->_('new test case').'">', '',
		'<form action="?m=testing&a=addedit" method="post">', '</form>'			//call addedit.php in case of mouseclick
	);
}

$titleBlock->show();	//finally show the titleBlock

// now prepare and show the tabbed information boxes with the dPFramework

// build new tab box object
$tabBox = new CTabBox( "?m=testing", "{$dPconfig['root_dir']}/modules/testing/", $tab );
$tabBox->add( 'index_table'  , 'Non-executed Tests ' );	// add another subsite to the tab box object
$tabBox->add( 'index_table'  , 'Failed Tests' );	// add another subsite to the tab box object
$tabBox->add( 'index_table'  , 'Passed Tests' );	// add another subsite to the tab box object
$tabBox->add( 'index_table', 'All Tests' );			// add a subsite vw_idx_about.php to the tab box object with title 'About Albert'
$tabBox->show();						// finally show the tab box

// this is the whole main site!
// all further development now has to be done in the files addedit.php, vw_idx_about.php, vw_idx_quotes.php
// and in the subroutine do_quote_aed.php
?>