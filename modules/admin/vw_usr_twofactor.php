<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// The Two-step login tab of a user (classes/twofactor.class.php, do_twofactor_aed.php).
global $AppUI, $user_id;
require_once DP_BASE_DIR . '/classes/twofactor.class.php';

$own = ($user_id == $AppUI->user_id);
$row = CTwoFactor::row($user_id);
$setup = $own ? (string)$AppUI->getState('TwoFactorSetup') : '';
$codes = $own ? trim((string)$AppUI->getState('TwoFactorCodes')) : '';
// setState() ignores null: an empty string clears it, so the codes show only once.
$AppUI->setState('TwoFactorCodes', '');
$action = '?m=admin&amp;a=viewuser&amp;user_id=' . (int)$user_id;
$form = function ($tf_action, $label, $with_code = false, $confirm = '') use ($action, $user_id, $AppUI) {
	echo '<form method="post" action="' . $action . '" class="tf-form">'
		. '<input type="hidden" name="dosql" value="do_twofactor_aed" />'
		. '<input type="hidden" name="tf_user" value="' . (int)$user_id . '" />'
		. '<input type="hidden" name="tf_action" value="' . $tf_action . '" />';
	if ($with_code) {
		echo '<label>' . $AppUI->_('Code') . ': <input type="text" class="text" name="tf_code" size="12" maxlength="12"'
			. ' inputmode="numeric" autocomplete="one-time-code" required="required" /></label> ';
	}
	echo '<input type="submit" class="button" value="' . $AppUI->_($label) . '"'
		. ($confirm ? ' data-dp-confirm="' . $AppUI->_($confirm) . '"' : '') . ' /></form>';
};
?>
<style type="text/css">
	.tf-tab { padding: 8px 10px; max-width: 760px; }
	.tf-tab p { margin: 6px 0 10px; line-height: 1.4; }
	.tf-form { margin: 8px 0 14px; }
	.tf-qr { display: flex; flex-wrap: wrap; gap: 16px; align-items: center; margin: 10px 0; }
	.tf-qr img { width: 200px; height: 200px; background: #fff; border: 1px solid #B8C3D4; }
	.tf-secret { font-family: monospace; font-size: 1.1em; letter-spacing: .08em; word-break: break-all; }
	.tf-codes { columns: 2; font-family: monospace; font-size: 1.1em; max-width: 320px; padding-left: 20px; }
	.tf-on { color: #0A8080; font-weight: bold; }
	.tf-note { color: #5A6474; }
</style>
<div class="tf-tab">
<?php if ($codes) { ?>
	<p><strong><?php echo $AppUI->_('Save these recovery codes now. They will not be shown again.'); ?></strong>
	<?php echo $AppUI->_('Each one logs you in once if you lose your phone. Keep them somewhere safe, away from your phone.'); ?></p>
	<ol class="tf-codes"><?php foreach (explode(' ', $codes) as $c) { echo '<li>' . dPhtml($c) . '</li>'; } ?></ol>
<?php } ?>

<?php if ($row) { ?>
	<p class="tf-on"><?php echo $AppUI->_('Two-step login is on.'); ?></p>
	<p><?php
		$on = new CDate($row['tf_enabled_on']);
		echo $AppUI->_('Turned on') . ': ' . dPhtml($on->format($AppUI->getPref('SHDATEFORMAT'))) . '. '
			. sprintf($AppUI->_('Recovery codes left: %d.'), CTwoFactor::recoveryCodesLeft($user_id));
	?></p>
	<?php if ($own) { ?>
		<p class="tf-note"><?php echo $AppUI->_('To renew your recovery codes or turn two-step login off, enter a current code from your app (or a recovery code).'); ?></p>
		<?php $form('recovery', 'New recovery codes', true); ?>
		<?php $form('disable', 'Turn off', true, 'Turn off two-step login? Your password alone will log you in.'); ?>
	<?php } else if (getPermission('users', 'edit', $user_id)) { ?>
		<p class="tf-note"><?php echo $AppUI->_('If the user lost their phone and their recovery codes, reset two-step login: they log in with their password and can set it up again.'); ?></p>
		<?php $form('reset', 'Reset two-step login', false, 'Reset two-step login for this user?'); ?>
	<?php } ?>
<?php } else if (!$own) { ?>
	<p><?php echo $AppUI->_('Two-step login is off for this user.'); ?></p>
<?php } else if ($setup === '') { ?>
	<p><?php echo $AppUI->_('Two-step login asks for a code from an app on your phone after your password, so a stolen password is not enough to log in as you.'); ?>
	<?php echo $AppUI->_('Any authenticator app works: Google Authenticator, Microsoft Authenticator, Aegis, FreeOTP, 1Password, ...'); ?></p>
	<?php $form('start', 'Set up two-step login'); ?>
<?php } else {
	$account = $AppUI->user_email ? $AppUI->user_email : ('user ' . (int)$AppUI->user_id);
	$uri = CTwoFactor::otpauthUri($setup, $account, dPgetConfig('page_title') ? strip_tags((string)dPgetConfig('page_title')) : 'dotPulse');
	?>
	<p><strong>1.</strong> <?php echo $AppUI->_('Scan this QR code with your authenticator app, or type the key into it.'); ?></p>
	<div class="tf-qr">
		<img src="<?php echo dPhtml(CTwoFactor::qrDataUri($uri)); ?>" alt="<?php echo $AppUI->_('QR code for your authenticator app'); ?>" />
		<div><?php echo $AppUI->_('Key'); ?>:<br /><span class="tf-secret"><?php echo dPhtml(trim(chunk_split($setup, 4, ' '))); ?></span></div>
	</div>
	<p><strong>2.</strong> <?php echo $AppUI->_('Enter the 6-digit code the app shows to turn two-step login on.'); ?></p>
	<?php $form('enable', 'Turn on', true); ?>
	<?php $form('cancel', 'Cancel'); ?>
<?php } ?>
</div>
