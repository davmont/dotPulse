<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* Hosting module -> $Id: index.php,v 1.1 2007/04/16 05:36:02 mbelos Exp $ */

$AppUI->savePlace();

// load the companies class to retrieved denied companies
require_once( $AppUI->getModuleClass( 'companies' ) );

// retrieve any state parameters
if (isset( $_GET['tab'] )) {
	$AppUI->setState( 'HostIdxTab', $_GET['tab'] );
}
$tab = $AppUI->getState( 'HostIdxTab' ) !== NULL ? $AppUI->getState( 'HostIdxTab' ) : 0;
$active = intval( !$AppUI->getState( 'HostIdxTab' ) );

if (isset( $_POST['company_id'] )) {
	$company_id = $_POST['company_id'];
	$AppUI->setState( 'HostIdxCompany', intval( $_POST['company_id'] ) );
}

//$company_id = $AppUI->getState( 'HostIdxCompany' ) !== NULL ? $AppUI->getState( 'HostIdxCompany' ) : $AppUI->user_company;

$company_prefix = 'company_';

if (isset( $_POST['department'] )) {
	$AppUI->setState('HostIdxDepartment', dPdepartmentParam($_POST));

	//if department is set, ignore the company_id field
	unset($company_id);
}

//if $department contains the $company_prefix string that it's requesting a company and not a department.  So, clear the
// $department variable, and populate the $company_id variable.
/*
if(!(strpos($department, $company_prefix)===false)){
	$company_id = substr($department,strlen($company_prefix));
	$AppUI->setState( 'HostIdxCompany', $company_id );
	unset($department);
}*/

if (isset($company_id) && !(strpos($company_id, $company_prefix)===false)){
	$company_id = (int)substr($company_id,strlen($company_prefix));
	$AppUI->setState( 'HostIdxCompany', $company_id );
}

if (isset( $_GET['orderby'] )) {
    $orderdir = $AppUI->getState( 'HostIdxOrderDir' ) ? ($AppUI->getState( 'HostIdxOrderDir' )== 'asc' ? 'desc' : 'asc' ) : 'desc';
    $AppUI->setState( 'HostIdxOrderBy', $_GET['orderby'] );
    $AppUI->setState( 'HostIdxOrderDir', $orderdir);
}
$orderby  = $AppUI->getState( 'HostIdxOrderBy' ) ? $AppUI->getState( 'HostIdxOrderBy' ) : 'project_end_date';
$orderdir = $AppUI->getState( 'HostIdxOrderDir' ) ? $AppUI->getState( 'HostIdxOrderDir' ) : 'asc';

getCompanies();

// setup the title block
$titleBlock = new CTitleBlock( 'Hosting', 'applet3-48.png', $m, "$m.$a" );
$titleBlock->addCell( $AppUI->_('Company') . '/' . $AppUI->_('Division') . ':');
$titleBlock->addCell( $buffer, '', '<form action="index.php?m=hosting" method="post" name="pickCompany">', '</form>');
$titleBlock->addCell();

$titleBlock->addCell(
	'<input type="submit" class="button" value="'.$AppUI->_('new hosting/domain').'">', '',
	'<form action="?m=hosting&a=addedit" method="post">', '</form>'
);

$titleBlock->show();

$tabBox = new CTabBox( "?m=hosting", "{$dPconfig['root_dir']}/modules/hosting/", $tab );
if ( $tabBox->isTabbed() ) {
	$tabBox->add("vw_all", "All");
}

$min_view = true;
$tabBox->add("vw_domains", "Domains");
$tabBox->add("vw_hosting", "Hosting");
$tabBox->add("vw_expiring", "Expiring Soon");
$tabBox->show();

?>
