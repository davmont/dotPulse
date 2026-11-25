<?php
/**
 * Password reset form, reached from the link emailed by includes/sendpass.php.
 * Included from index.php (resetpass=1) before the login check, so it works
 * while logged out.
 */
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

$reset_user_id = (int)dPgetParam($_REQUEST, 'user_id', 0);
$reset_token = (string)dPgetParam($_REQUEST, 'token', '');
$reset_error = '';
$reset_done = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	require DP_BASE_DIR . '/modules/public/do_reset_password.php';
}

$reset_link_ok = ($reset_user_id > 0 && preg_match('/^[0-9a-f]{64}$/', $reset_token));
$charset = isset($locale_char_set) ? $locale_char_set : 'utf-8';
header('Content-type: text/html;charset=' . $charset);
$h = function ($s) {
	return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
?>
<!DOCTYPE html>
<html<?php echo dPappearanceAttributes($uistyle); ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html;charset=<?php echo $h($charset); ?>" />
	<title><?php echo $h(dPgetConfig('page_title')); ?></title>
	<?php echo dPthemeStylesheets($uistyle); ?>
</head>
<body>
<br /><br />
<table align="center" border="0" width="300" cellpadding="6" cellspacing="0" class="std">
<tr>
	<th colspan="2"><em><?php echo $h(dPgetConfig('company_name')); ?></em></th>
</tr>
<?php if ($reset_error) { ?>
<tr><td colspan="2" class="error"><?php echo $h($AppUI->_($reset_error)); ?></td></tr>
<?php } ?>
<?php if ($reset_done) { ?>
<tr><td colspan="2"><?php echo $h($AppUI->_('Your password has been changed. You can now log in.')); ?></td></tr>
<tr><td colspan="2" align="right"><a href="./index.php"><?php echo $h($AppUI->_('login')); ?></a></td></tr>
<?php } elseif (!$reset_link_ok) { ?>
<tr><td colspan="2"><?php echo $h($AppUI->_('Invalid or expired password reset link.')); ?></td></tr>
<tr><td colspan="2" align="right"><a href="./index.php"><?php echo $h($AppUI->_('login')); ?></a></td></tr>
<?php } else { ?>
<tr><td colspan="2">
<form method="post" action="./index.php" name="resetpassform">
	<input type="hidden" name="resetpass" value="1" />
	<input type="hidden" name="user_id" value="<?php echo $h($reset_user_id); ?>" />
	<input type="hidden" name="token" value="<?php echo $h($reset_token); ?>" />
	<table width="100%" border="0" cellpadding="2" cellspacing="0">
	<tr>
		<td align="right" nowrap="nowrap"><?php echo $h($AppUI->_('New Password')); ?>:</td>
		<td><input type="password" name="new_password" class="text" size="25" autocomplete="new-password" required /></td>
	</tr>
	<tr>
		<td align="right" nowrap="nowrap"><?php echo $h($AppUI->_('Repeat New Password')); ?>:</td>
		<td><input type="password" name="password_confirm" class="text" size="25" autocomplete="new-password" required /></td>
	</tr>
	<tr>
		<td colspan="2" align="right"><input type="submit" class="button" value="<?php echo $h($AppUI->_('submit')); ?>" /></td>
	</tr>
	</table>
</form>
</td></tr>
<?php } ?>
</table>
</body>
</html>
