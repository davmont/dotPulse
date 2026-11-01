<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// Second step of a login (classes/twofactor.class.php): the password was right and the
// session holds a pending login; this page asks for the code of the user's app, or one of
// their recovery codes, and completes the login. Included by index.php while logged out.
global $AppUI, $dPconfig, $uistyle, $locale_char_set;
require_once DP_BASE_DIR . '/classes/twofactor.class.php';
require_once DP_BASE_DIR . '/classes/ratelimiter.class.php';

$AppUI->setUserLocale();
@include_once DP_BASE_DIR . '/locales/' . $AppUI->user_locale . '/locales.php';
@include_once DP_BASE_DIR . '/locales/core.php';

$pending = CTwoFactor::pending();
$message = '';
$back_to_login = function ($msg) use ($AppUI) {
	CTwoFactor::clearPending();
	$AppUI->setMsg($msg, UI_MSG_ERROR);
	header('Location: ./index.php');
	exit;
};
if (!$pending) {
	$back_to_login('The time to enter the code has run out. Log in again.');
}
$token_ok = hash_equals($pending['token'], (string)dPgetParam($_POST, 'twofactor_token', ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && dPgetParam($_POST, 'twofactor_cancel', '') !== '' && $token_ok) {
	CTwoFactor::clearPending();
	header('Location: ./index.php');
	exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && dPgetParam($_POST, 'twofactor_code') !== null) {
	// Per address too: a new password login would otherwise start a fresh set of attempts.
	$limiter = new RateLimiter('twofactor', 10, 900);
	if (!$token_ok) {
		$message = $AppUI->_('Your session has changed. Enter the code again.');
	} else if (!$limiter->isAllowed()) {
		$back_to_login('Too many wrong codes. Wait a few minutes and log in again.');
	} else {
		$used = CTwoFactor::verify($pending['user_id'], (string)dPgetParam($_POST, 'twofactor_code', ''));
		if ($used) {
			CTwoFactor::clearPending();
			if (!$AppUI->loadUser($pending['user_id'])) {
				$back_to_login('Login Failed');
			}
			session_regenerate_id(true);
			$AppUI->registerLogin();
			dPaudit('login.success', 'with two-step login');
			if ($used == 'recovery') {
				dPaudit('twofactor.recovery_used', CTwoFactor::recoveryCodesLeft($AppUI->user_id) . ' recovery codes left');
			}
			addHistory('login', $AppUI->user_id, 'login', $AppUI->user_first_name . ' ' . $AppUI->user_last_name);
			if ($used == 'recovery') {
				$AppUI->setMsg(sprintf($AppUI->_('You logged in with a recovery code. Recovery codes left: %d.', UI_OUTPUT_RAW),
					CTwoFactor::recoveryCodesLeft($AppUI->user_id)), UI_MSG_ALERT);
			}
			$AppUI->redirect($pending['redirect']);
		}
		$limiter->recordAttempt();
		dPaudit('twofactor.failure', '', '', 0, $pending['user_id'], null);
		if (!CTwoFactor::failedAttempt() || !$limiter->isAllowed()) {
			dPaudit('twofactor.blocked', 'the password is asked again', '', 0, $pending['user_id'], null);
			$back_to_login('Too many wrong codes. Log in again.');
		}
		$message = $AppUI->_('That code is not valid. Check the time on your phone and try the newest code.');
	}
}

$uistyle = $uistyle ?: dPgetConfig('host_style');
header('Content-Type: text/html; charset=' . (isset($locale_char_set) ? $locale_char_set : 'UTF-8'));
?>
<!DOCTYPE html>
<html lang="<?php echo dPhtml(substr($AppUI->user_locale, 0, 2)); ?>">
<head>
	<meta charset="<?php echo isset($locale_char_set) ? dPhtml($locale_char_set) : 'UTF-8'; ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title><?php echo dPhtml(dPgetConfig('page_title')); ?></title>
	<link rel="stylesheet" type="text/css" href="./style/<?php echo dPhtml($uistyle); ?>/main.css" media="all" />
	<link rel="icon" href="./images/brand/favicon.svg" type="image/svg+xml" />
	<style type="text/css">
		body { background: #f0f2f5 !important; margin: 0; font-family: Arial, sans-serif; color: #23262B;
			min-height: 100vh; display: flex; align-items: center; justify-content: center; }
		.tf-box { background: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0, 0, 0, .1);
			padding: 28px 32px; width: 100%; max-width: 380px; box-sizing: border-box; margin: 16px; }
		.tf-box img { height: 36px; display: block; margin: 0 auto 18px; }
		.tf-box h1 { font-size: 1.2em; margin: 0 0 8px; text-align: center; }
		.tf-box p { margin: 0 0 14px; line-height: 1.4; }
		.tf-box input.text { width: 100%; padding: 10px; font-size: 1.4em; letter-spacing: .2em; text-align: center;
			border: 1px solid #B8C3D4; border-radius: 4px; box-sizing: border-box; margin-bottom: 12px; }
		.tf-box .button { width: 100%; padding: 10px; border: 0; border-radius: 4px; background: #1B4F9C; color: #fff;
			font-weight: bold; font-size: 1em; cursor: pointer; }
		.tf-box .button:hover { background: #123A73; }
		.tf-error { color: #C93D27; font-weight: bold; }
		.tf-help { font-size: .9em; color: #5A6474; }
		.tf-cancel { background: none; border: 0; color: #1B4F9C; text-decoration: underline; cursor: pointer; padding: 0; font-size: .9em; }
	</style>
</head>
<body>
	<div class="tf-box">
		<img src="./images/brand/logo.svg" alt="dotPulse" />
		<h1><?php echo $AppUI->_('Two-step login'); ?></h1>
		<p><?php echo $AppUI->_('Enter the 6-digit code from the authenticator app on your phone.'); ?></p>
		<?php if ($message) { ?><p class="tf-error" role="alert"><?php echo $message; ?></p><?php } ?>
		<form method="post" action="./index.php" autocomplete="off">
			<input type="hidden" name="twofactor_token" value="<?php echo dPhtml($pending['token']); ?>" />
			<label for="twofactor_code" class="tf-help"><?php echo $AppUI->_('Code'); ?></label>
			<input type="text" class="text" id="twofactor_code" name="twofactor_code" inputmode="numeric"
				autocomplete="one-time-code" maxlength="12" required="required" autofocus="autofocus" />
			<input type="submit" class="button" value="<?php echo $AppUI->_('Verify'); ?>" />
		</form>
		<p class="tf-help" style="margin-top: 14px"><?php echo $AppUI->_('No phone? Enter one of your recovery codes (like abcd-2345) instead.'); ?></p>
		<form method="post" action="./index.php">
			<input type="hidden" name="twofactor_token" value="<?php echo dPhtml($pending['token']); ?>" />
			<input type="submit" class="tf-cancel" name="twofactor_cancel" value="<?php echo $AppUI->_('Cancel and log in again'); ?>" />
		</form>
	</div>
</body>
</html>
