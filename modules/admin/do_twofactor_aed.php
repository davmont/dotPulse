<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// Two-step login settings of a user (admin/vw_usr_twofactor.php). index.php lets every user
// reach this handler for their own account; the checks are here. A user sets it up, turns
// it off or renews their recovery codes, each with a current code; an administrator who may
// edit users can only reset it for another user (lost phone).
require_once DP_BASE_DIR . '/classes/twofactor.class.php';
require_once DP_BASE_DIR . '/classes/auditlog.class.php';

$tf_user = (int)dPgetParam($_POST, 'tf_user', 0);
$own = ($tf_user == $AppUI->user_id);
$action = (string)dPgetParam($_POST, 'tf_action', '');
$code = (string)dPgetParam($_POST, 'tf_code', '');
$back = 'm=admin&a=viewuser&user_id=' . $tf_user;
$AppUI->setMsg('Two-step login');

if ($action == 'reset') {
	if ($own || !(getPermission('admin', 'edit') && getPermission('users', 'edit', $tf_user))) {
		$AppUI->redirect('m=public&a=access_denied');
	}
	CTwoFactor::disable($tf_user);
	addHistory('users', $tf_user, 'update', 'Two-step login reset by an administrator');
	dPaudit('twofactor.reset', 'for ' . CAuditLog::username($tf_user), 'users', $tf_user);
	$AppUI->setMsg('reset: the user logs in with their password only until they set it up again', UI_MSG_OK, true);
	$AppUI->redirect($back);
}
if (!$own) {
	$AppUI->redirect('m=public&a=access_denied');
}

switch ($action) {
	case 'start':
		$AppUI->setState('TwoFactorSetup', CTwoFactor::newSecret());
		break;
	case 'cancel':
		$AppUI->setState('TwoFactorSetup', '');
		break;
	case 'enable':
		$secret = (string)$AppUI->getState('TwoFactorSetup');
		$counter = $secret !== '' ? CTwoFactor::matchCounter($secret, $code, time()) : false;
		if ($counter === false) {
			$AppUI->setMsg('the code is not valid: scan the QR code again and enter the newest code', UI_MSG_ERROR, true);
			break;
		}
		$AppUI->setState('TwoFactorCodes', implode(' ', CTwoFactor::enable($AppUI->user_id, $secret, $counter)));
		$AppUI->setState('TwoFactorSetup', '');
		addHistory('users', $AppUI->user_id, 'update', 'Two-step login turned on');
		dPaudit('twofactor.enabled');
		$AppUI->setMsg('turned on', UI_MSG_OK, true);
		break;
	case 'disable':
	case 'recovery':
		if (!CTwoFactor::isEnabled($AppUI->user_id) || !CTwoFactor::verify($AppUI->user_id, $code)) {
			$AppUI->setMsg('the code is not valid', UI_MSG_ERROR, true);
			break;
		}
		if ($action == 'disable') {
			CTwoFactor::disable($AppUI->user_id);
			addHistory('users', $AppUI->user_id, 'update', 'Two-step login turned off');
			dPaudit('twofactor.disabled');
			$AppUI->setMsg('turned off', UI_MSG_OK, true);
		} else {
			$AppUI->setState('TwoFactorCodes', implode(' ', CTwoFactor::newRecoveryCodes($AppUI->user_id)));
			dPaudit('twofactor.recovery_renewed');
			$AppUI->setMsg('new recovery codes created', UI_MSG_OK, true);
		}
		break;
}
$AppUI->redirect($back);
