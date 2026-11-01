<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// Creates or revokes an API token (the API tokens tab of a user). index.php lets every
// user reach this handler for their own account; the checks are here: users create
// tokens only for themselves, and revoke their own or, with admin edit rights, anyone's.
require_once DP_BASE_DIR . '/classes/apitoken.class.php';

$token_user = (int)dPgetParam($_POST, 'token_user', 0);
$own = ($token_user == $AppUI->user_id);
$back = 'm=admin&a=viewuser&user_id=' . $token_user;

if (!dPgetConfig('api_enabled', false)) {
	$AppUI->setMsg('The API is turned off.', UI_MSG_ERROR);
	$AppUI->redirect($back);
}

if (dPgetParam($_POST, 'del', 0)) {
	if (!$own && !(getPermission('admin', 'edit') && getPermission('users', 'edit', $token_user))) {
		$AppUI->redirect('m=public&a=access_denied');
	}
	$AppUI->setMsg('API token');
	if (CApiToken::revoke((int)dPgetParam($_POST, 'token_id', 0), $token_user)) {
		addHistory('api_tokens', $token_user, 'revoke', 'API token revoked');
		$AppUI->setMsg('revoked', UI_MSG_OK, true);
	} else {
		$AppUI->setMsg('not found', UI_MSG_ERROR, true);
	}
	$AppUI->redirect($back);
}

if (!$own) {
	$AppUI->redirect('m=public&a=access_denied');
}
$name = trim((string)dPgetCleanParam($_POST, 'token_name', ''));
if ($name === '') {
	$AppUI->setMsg('Give the token a name.', UI_MSG_ERROR);
	$AppUI->redirect($back);
}
$expires = (int)dPgetParam($_POST, 'token_expires', 90);
$token = CApiToken::create($AppUI->user_id, $name,
	dPgetParam($_POST, 'token_scope', 'read') === 'write' ? 'write' : 'read',
	in_array($expires, array(0, 30, 90, 365)) ? $expires : 90);
if ($token === false) {
	$AppUI->setMsg('You have too many API tokens. Revoke one first.', UI_MSG_ERROR);
} else {
	addHistory('api_tokens', $AppUI->user_id, 'add', 'API token created: ' . $name);
	$AppUI->setState('ApiNewToken', $token);
	$AppUI->setMsg('API token created', UI_MSG_OK);
}
$AppUI->redirect($back);
