<?php /* CONTACTS $Id: do_contact_aed.php 6149 2012-01-09 11:58:40Z ajdonnison $ */
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

$obj = new CContact();
$msg = '';

if (!$obj->bind($_POST)) {
	$AppUI->setMsg($obj->getError(), UI_MSG_ERROR);
	$AppUI->redirect();
}

$del = (int)dPgetParam($_POST, 'del', 0);
// Permission on this record, as its addedit page checks.
dPrequireWritePermission('contacts', dPgetParam($_POST, 'contact_id', 0), $del);

$contact_id = (int)$obj->contact_id;
if ($contact_id) {
	$existing = new CContact();
	if (!$existing->load($contact_id)) {
		$AppUI->setMsg('Contact');
		$AppUI->setMsg('invalidID', UI_MSG_ERROR, true);
		$AppUI->redirect();
	}
	// Someone else's private contact: only its owner, as contacts/addedit.php allows.
	$denied = ($existing->contact_private && $existing->contact_owner
		&& $existing->contact_owner != $AppUI->user_id);
	// The contact of another user's account holds the e-mail its password reset is sent
	// to (includes/sendpass.php): only those who manage users may change it.
	$q = new DBQuery();
	$q->addTable('users');
	$q->addQuery('user_id');
	$q->addWhere('user_contact = ' . $contact_id);
	$account_user = (int)$q->loadResult();
	if ($account_user && $account_user != $AppUI->user_id && !getPermission('admin', 'edit')) {
		$denied = true;
	}
	if ($denied) {
		$AppUI->setMsg('Access denied.', UI_MSG_ERROR);
		$AppUI->redirect('m=public&a=access_denied');
	}
	// The owner stays: it decides who may see and change a private contact.
	$obj->contact_owner = $existing->contact_owner;
}

// prepare (and translate) the module name ready for the suffix
$AppUI->setMsg('Contact');
if ($del) {
	if (($msg = $obj->delete())) {
		$AppUI->setMsg($msg, UI_MSG_ERROR);
		$AppUI->redirect();
	} else {
		$AppUI->setMsg("deleted", UI_MSG_ALERT, true);
		$AppUI->redirect("m=contacts");
	}
} else {
	$isNotNew = @$_POST['contact_id'];

	if (($msg = $obj->store())) {
		$AppUI->setMsg($msg, UI_MSG_ERROR);
	} else {
		$AppUI->setMsg($isNotNew ? 'updated' : 'added', UI_MSG_OK, true);
	}
	$AppUI->redirect();
}
?>
