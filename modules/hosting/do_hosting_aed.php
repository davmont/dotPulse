<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* Hosting module -> $Id: do_hosting_aed.php,v 1.1 2007/04/16 05:36:02 mbelos Exp $ */

$obj = new CHosting();
$msg = '';

if (!$obj->bind( $_POST )) {
	$AppUI->setMsg( $obj->getError(), UI_MSG_ERROR );
	$AppUI->redirect();
}

$del = (int)dPgetParam( $_POST, 'del', 0 );
$domain_id = (int)$obj->domain_id;
$obj->company_id = (int)$obj->company_id;

// Permission on this record, as its addedit page checks.
dPrequireWritePermission('hosting', $domain_id, $del);

if ($domain_id) {
	$existing = new CHosting();
	if (!$existing->load($domain_id)) {
		$AppUI->setMsg('Hosting/Domain', UI_MSG_ERROR);
		$AppUI->setMsg('not found', UI_MSG_ERROR, true);
		$AppUI->redirect('m=hosting');
	}
}
// The company must be one the user may see, as the form's company list offers.
if (!$del && $obj->company_id && !getPermission('companies', 'view', $obj->company_id)) {
	$AppUI->setMsg('Access denied.', UI_MSG_ERROR);
	$AppUI->redirect('m=public&a=access_denied');
}

require_once("./classes/CustomFields.class.php");
// convert dates to SQL format first
if ($obj->domain_expiry_date) {
	$date = new CDate( $obj->domain_expiry_date );
	$obj->domain_expiry_date = $date->format( FMT_DATETIME_MYSQL );
}

if ($obj->hosting_expiry_date) {
	$date = new CDate( $obj->hosting_expiry_date );
	$obj->hosting_expiry_date = $date->format( FMT_DATETIME_MYSQL );
}

// prepare (and translate) the module name ready for the suffix
if ($del) {
	$canDelete = $obj->canDelete($msg, $domain_id);
	if (!$canDelete) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect( "m=hosting" );
	}
	if (($msg = $obj->delete())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect( "m=hosting" );
	} else {
		$AppUI->setMsg( "Domain deleted", UI_MSG_ALERT);
		$AppUI->redirect( "m=hosting" );
	}
} else {
	// store() updates an existing domain in place and inserts a new one.
	if (($msg = $obj->store())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
	} else {
 		$custom_fields = New CustomFields( $m, 'addedit', $obj->domain_id, "edit" );
 		$custom_fields->bind( $_POST );
 		$sql = $custom_fields->store( $obj->domain_id ); // Store Custom Fields

		$AppUI->setMsg( $domain_id ? 'Domain updated' : 'Domain inserted', UI_MSG_OK);
	}
	$AppUI->redirect( "m=hosting" );
}
?>
