<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// The API tokens tab of a user: the user creates and revokes their own tokens, an
// administrator who may edit users can see and revoke anyone's (docs/api.md).
global $AppUI, $user_id;

require_once DP_BASE_DIR . '/classes/apitoken.class.php';

$own = ($user_id == $AppUI->user_id);
$df = $AppUI->getPref('SHDATEFORMAT') . ' ' . $AppUI->getPref('TIMEFORMAT');
$fmt = function ($value) use ($df) {
	if (!$value) {
		return '-';
	}
	$d = new CDate($value);
	return dPhtml($d->format($df));
};

$new_token = $own ? $AppUI->getState('ApiNewToken') : null;
// setState() ignores null: an empty string clears it, so the token shows only once.
$AppUI->setState('ApiNewToken', '');
$base = DP_BASE_URL . '/api.php/v1/';
?>
<table width="100%" border="0" cellpadding="4" cellspacing="0" class="tbl">
<?php if ($new_token) { ?>
<tr>
	<td colspan="7">
		<p><strong><?php echo dPhtml($AppUI->_('Copy your new token now. It will not be shown again.')); ?></strong></p>
		<p><code class="api-token"><?php echo dPhtml($new_token); ?></code></p>
		<p><?php echo dPhtml($AppUI->_('Use it in the Authorization header:')); ?>
		<code>curl -H "Authorization: Bearer <?php echo dPhtml($new_token); ?>" <?php echo dPhtml($base); ?>me</code></p>
	</td>
</tr>
<?php } ?>
<tr>
	<th><?php echo $AppUI->_('Name'); ?></th>
	<th><?php echo $AppUI->_('Token'); ?></th>
	<th><?php echo $AppUI->_('Access'); ?></th>
	<th><?php echo $AppUI->_('Created'); ?></th>
	<th><?php echo $AppUI->_('Expires'); ?></th>
	<th><?php echo $AppUI->_('Last used'); ?></th>
	<th>&nbsp;</th>
</tr>
<?php
$tokens = CApiToken::listFor($user_id);
if (!$tokens) {
	echo '<tr><td colspan="7">' . dPhtml($AppUI->_('No API tokens.')) . '</td></tr>';
}
foreach ($tokens as $t) {
	$expired = $t['token_expires'] && strtotime($t['token_expires']) < time();
	?>
<tr>
	<td><?php echo dPhtml($t['token_name']); ?></td>
	<td><code><?php echo dPhtml($t['token_prefix']); ?>&hellip;</code></td>
	<td><?php echo dPhtml($AppUI->_($t['token_scope'] == 'write' ? 'Read and write' : 'Read only')); ?></td>
	<td><?php echo $fmt($t['token_created']); ?></td>
	<td><?php echo $fmt($t['token_expires']) . ($expired ? ' (' . dPhtml($AppUI->_('expired')) . ')' : ''); ?></td>
	<td><?php echo $fmt($t['token_last_used']); ?></td>
	<td>
		<form method="post" action="?m=admin&amp;a=viewuser&amp;user_id=<?php echo (int)$user_id; ?>">
			<input type="hidden" name="dosql" value="do_api_token_aed" />
			<input type="hidden" name="token_user" value="<?php echo (int)$user_id; ?>" />
			<input type="hidden" name="token_id" value="<?php echo (int)$t['token_id']; ?>" />
			<input type="hidden" name="del" value="1" />
			<input type="submit" class="button" value="<?php echo dPhtml($AppUI->_('Revoke')); ?>"
				data-dp-confirm="<?php echo dPhtml($AppUI->_('Revoke this token? Programs using it stop working.')); ?>" />
		</form>
	</td>
</tr>
<?php
}
if ($own) {
	?>
<tr>
	<td colspan="7">
		<form method="post" action="?m=admin&amp;a=viewuser&amp;user_id=<?php echo (int)$user_id; ?>">
			<input type="hidden" name="dosql" value="do_api_token_aed" />
			<input type="hidden" name="token_user" value="<?php echo (int)$user_id; ?>" />
			<strong><?php echo $AppUI->_('New token'); ?></strong>
			<label><?php echo $AppUI->_('Name'); ?>:
				<input type="text" class="text" name="token_name" maxlength="100" size="25" required="required" /></label>
			<label><?php echo $AppUI->_('Access'); ?>:
				<select name="token_scope" class="text">
					<option value="read"><?php echo dPhtml($AppUI->_('Read only')); ?></option>
					<option value="write"><?php echo dPhtml($AppUI->_('Read and write')); ?></option>
				</select></label>
			<label><?php echo $AppUI->_('Expires'); ?>:
				<select name="token_expires" class="text">
					<option value="30">30 <?php echo dPhtml($AppUI->_('days')); ?></option>
					<option value="90" selected="selected">90 <?php echo dPhtml($AppUI->_('days')); ?></option>
					<option value="365">365 <?php echo dPhtml($AppUI->_('days')); ?></option>
					<option value="0"><?php echo dPhtml($AppUI->_('Never')); ?></option>
				</select></label>
			<input type="submit" class="button" value="<?php echo dPhtml($AppUI->_('Create token')); ?>" />
		</form>
		<p><?php echo dPhtml($AppUI->_('A token acts as you, with your permissions. API documentation: docs/api.md.')); ?>
		<?php echo dPhtml($AppUI->_('Address')); ?>: <code><?php echo dPhtml($base); ?></code></p>
	</td>
</tr>
<?php } ?>
</table>
