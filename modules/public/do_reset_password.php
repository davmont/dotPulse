<?php
/**
 * Processes the password reset form. Included by reset_password.php, which
 * defines $reset_user_id and $reset_token and reads $reset_error and
 * $reset_done afterwards.
 */
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

require_once DP_BASE_DIR . '/classes/ratelimiter.class.php';
$reset_limiter = new RateLimiter('resetpass', 10, 900);
if (!$reset_limiter->isAllowed()) {
	$reset_error = 'Too many attempts. Please wait before trying again.';
	return;
}

$new_password = (string)dPgetParam($_POST, 'new_password', '');
$password_confirm = (string)dPgetParam($_POST, 'password_confirm', '');

if ($reset_user_id <= 0 || !preg_match('/^[0-9a-f]{64}$/', $reset_token)) {
	$reset_limiter->recordAttempt();
	$reset_error = 'Invalid or expired password reset link.';
	return;
}

$q = new DBQuery();
$q->addTable('users');
$q->addQuery('user_reset_token, user_reset_expiry');
$q->addWhere('user_id = ?', $reset_user_id);
$reset_row = $q->loadHash();
$q->clear();

if (empty($reset_row['user_reset_token']) || empty($reset_row['user_reset_expiry'])
	|| strtotime($reset_row['user_reset_expiry']) < time()
	|| !password_verify($reset_token, $reset_row['user_reset_token'])) {
	$reset_limiter->recordAttempt();
	$reset_error = 'Invalid or expired password reset link.';
	return;
}

$min_len = (int)dPgetConfig('password_min_len', 4);
if ($new_password === '' || $new_password !== $password_confirm) {
	$reset_error = 'Passwords do not match.';
	return;
}
if (mb_strlen($new_password) < $min_len) {
	$reset_error = 'The password is too short.';
	return;
}

// Set the new password and invalidate the token so the link works once.
$q->addTable('users');
$q->addUpdate('user_password', dPhashPassword($new_password));
$q->addUpdate('user_reset_token', null);
$q->addUpdate('user_reset_expiry', null);
$q->addWhere('user_id = ?', $reset_user_id);
if ($q->exec()) {
	$reset_done = true;
	addHistory('users', $reset_user_id, 'password reset',
		'Password reset by email link from IP ' . $_SERVER['REMOTE_ADDR']);
	dPaudit('password.reset', '', 'users', $reset_user_id, $reset_user_id, null);
} else {
	$reset_error = 'An error occurred while updating your password.';
}
$q->clear();
