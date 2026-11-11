<?php /* ADMIN $Id: do_user_aed.php 6149 2012-01-09 11:58:40Z ajdonnison $ */
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

require_once $AppUI->getSystemClass('libmail');
include $AppUI->getModuleClass('contacts');
$del = (bool)dPgetParam($_REQUEST, 'del', false);
$user_id_aed = intval(dPgetParam($_REQUEST, 'user_id', 0));

$obj = new CUser();
$contact = new CContact();

if (!$obj->bind($_POST)) {
	$AppUI->setMsg($obj->getError(), UI_MSG_ERROR);
	$AppUI->redirect();
}
if (!$contact->bind($_POST)) {
	$AppUI->setMsg($contact->getError(), UI_MSG_ERROR);
	$AppUI->redirect();
}
$obj->user_username = mb_strtolower($obj->user_username);
$isNewUser = !($user_id_aed);

// prepare (and translate) the module name ready for the suffix
$AppUI->setMsg('User');

// !User's contact information not deleted - left for history.
if ($del) {
	if (! getPermission('admin', 'delete') 
	    || !(getPermission('users', 'delete', $user_id_aed))) {
		$AppUI->redirect('m=public&a=access_denied');
	}
	require_once DP_BASE_DIR . '/classes/auditlog.class.php';
	$audit_name = CAuditLog::username($user_id_aed);
	if (($msg = $obj->delete())) {
		$AppUI->setMsg($msg, UI_MSG_ERROR);
	} else {
		dPaudit('user.deleted', $audit_name, 'users', $user_id_aed);
		$AppUI->setMsg('deleted', UI_MSG_ALERT, true);
	}
	$AppUI->redirect();
	return;
} else if ($isNewUser) {
	if (!(getPermission('admin', 'add') && getPermission('users', 'add'))) {
		$AppUI->redirect('m=admin&a=access_denied');
	}
	
	// Security Mitigation: Use parameterized query to prevent SQL injection.
	$q = new DBQuery;
	$q->addTable('users','u');
	$q->addQuery('user_username');
	$q->addWhere('user_username = ?', $obj->user_username);
	$userEx = $q->loadResult();
	
	// If userName already exists quit with error and do nothing
	if ($userEx) {
		$AppUI->setMsg('already exists. Try another username.', UI_MSG_ERROR, true);
		$AppUI->redirect();
	}
	
	$contact->contact_owner = $AppUI->user_id;
} else if (! getPermission('admin', 'edit') || ! getPermission('users', 'edit', $user_id_aed)) {
	$AppUI->redirect('m=public&a=access_denied');
}

if (!$isNewUser && $AppUI->user_id == $user_id_aed) {
	$q = new DBQuery;
	$q->addTable('users');
	$q->addQuery('user_password');
	$q->addWhere("user_id = $user_id_aed");
	$db_pwd = $q->loadResult();
	
	if (!empty($_POST['user_password']) && $db_pwd != $_POST['user_password']) {
		if (!isset($_POST['old_password']) || (!password_verify($_POST['old_password'], $db_pwd) && md5($_POST['old_password']) !== $db_pwd)) {
			$AppUI->setMsg('Invalid old password', UI_MSG_ERROR, true);
			$AppUI->redirect();
		}
	}
}

// For the audit log: did this save change the password? (The form sends the stored hash back
// when the password field is left alone.)
$audit_password = '';
if (!$isNewUser && (string)dPgetParam($_POST, 'user_password', '') !== '') {
	$q = new DBQuery;
	$q->addTable('users');
	$q->addQuery('user_password');
	$q->addWhere('user_id = ' . (int)$user_id_aed);
	if ($q->loadResult() != dPgetParam($_POST, 'user_password', '')) {
		$audit_password = ', password changed';
	}
}

if (($msg = $contact->store())) {
	$AppUI->setMsg($msg, UI_MSG_ERROR);
} else {        
	$obj->user_contact = $contact->contact_id;
	if (($msg = $obj->store())) {
		$AppUI->setMsg($msg, UI_MSG_ERROR);
	} else {
		if ($isNewUser && isset($_POST['send_user_mail']) && $_POST['send_user_mail']) {
			// Security Mitigation: Do not send password in email.
			notifyNewUser($contact->contact_email, $contact->contact_first_name, 
			              $obj->user_username);
		}
		if (isset($_POST['user_role']) && $_POST['user_role']) {
			$perms =& $AppUI->acl();
			if ($perms->insertUserRole(dPgetIntParam($_POST, 'user_role', 0), $obj->user_id)) {
				$AppUI->setMsg('', UI_MSG_ALERT, true);
			} else {
				$AppUI->setMsg('failed to add role', UI_MSG_ERROR);
			}
		}
		dPaudit($isNewUser ? 'user.created' : 'user.updated', $obj->user_username . $audit_password
			. ((int)dPgetParam($_POST, 'user_role', 0) ? ', role ' . (int)dPgetParam($_POST, 'user_role', 0) : ''),
			'users', $obj->user_id);
		$AppUI->setMsg($isNewUser ? 'added' : 'updated', UI_MSG_OK, true);
	}
	$AppUI->redirect(($isNewUser ? ('m=admin&a=viewuser&user_id=' . $obj->user_id . '&tab=3') : ''));
}

function notifyNewUser($address, $username, $logname) {
	global $AppUI, $dPconfig;
	$mail = new Mail;
	if ($mail->ValidEmail($address)) {
		// The administrator's address; without one the mail class uses no-reply@<site domain>.
		// With a Sender address (mail_from) set, the message leaves from that one.
		$email = $mail->ValidEmail($AppUI->user_email) ? $AppUI->user_email : '';
		$product = dPgetConfig('page_title', 'dotPulse') ?: 'dotPulse';
		
		$name = $AppUI->user_first_name .' ' . $AppUI->user_last_name;
		// Security Mitigation: Removed password from email body.
		$body = $username.',

An access account has been created for you in our ' . $product . ' project management system.

You can access it here at ' . $dPconfig['base_url'] . '

Your username is: ' . $logname . '

To set your password, use the forgotten-password link on the login page.

This account will allow you to see and interact with projects. If you have any questions please contact us.';
		
		if ($email !== '') {
			$mail->From('"' . $name . '" <' . $email . '>');
		}
		$mail->To($address);
		$mail->Subject('New Account Created - ' . $product . ' Project Management System');
		$mail->Body($body);
		$mail->Send();
	}
}
