<?php /* PUBLIC $Id: chpwd.php 6149 2012-01-09 11:58:40Z ajdonnison $ */
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

if (! ($user_id = (int)dPgetParam($_REQUEST, 'user_id', 0))) {
	$user_id = @$AppUI->user_id;
}

// Anyone may change their own password, with the current one. Another user's password only
// by those who may edit that user (as in admin/do_user_aed.php), who don't need it; the user
// type is a label and gives no right here.
$own_password = ($user_id == $AppUI->user_id);
if ($user_id && !$own_password && !(getPermission('admin', 'edit') && getPermission('users', 'edit', $user_id))) {
	$AppUI->redirect('m=public&a=access_denied');
}

// check for a non-zero user id
if ($user_id) {
	// Passwords as typed, as login reads them: they are hashed, never shown or put in SQL.
	$old_pwd = dPgetParam($_POST, 'old_pwd', '');
	$new_pwd1 = dPgetParam($_POST, 'new_pwd1', '');
	$new_pwd2 = dPgetParam($_POST, 'new_pwd2', '');
	$old_pwd = is_string($old_pwd) ? $old_pwd : '';
	$new_pwd1 = is_string($new_pwd1) ? $new_pwd1 : '';
	$new_pwd2 = is_string($new_pwd2) ? $new_pwd2 : '';
	$min_len = (int)dPgetConfig('password_min_len');

	// has the change form been posted
	if ($new_pwd1 !== '' && $new_pwd2 !== '' && $new_pwd1 === $new_pwd2 && mb_strlen($new_pwd1) < $min_len) {
		echo $AppUI->_('chgpwValidNew') . ' ' . $min_len;
	} else if ($new_pwd1 !== '' && $new_pwd2 !== '' && $new_pwd1 === $new_pwd2) {
		// check that the old password matches (supports both password_hash and legacy MD5)
		$q = new DBQuery;
		$q->addQuery('user_id, user_password');
		$q->addTable('users');
		$q->addWhere('user_id = ' . intval($user_id));
		$row = $q->loadHash(); // fetchRow() without exec() returned false: own changes always failed
		$db_pwd = $row['user_password'] ?? '';

		if ($row && (!$own_password || password_verify($old_pwd, $db_pwd) || md5($old_pwd) === $db_pwd)) {
			require_once($AppUI->getModuleClass('admin'));
			$user = new CUser();
			$user->user_id = $user_id;
			$user->user_password = $new_pwd1;
			
			if (($msg = $user->store())) {
				$AppUI->setMsg($msg, UI_MSG_ERROR);
			} else {
				require_once DP_BASE_DIR . '/classes/auditlog.class.php';
				dPaudit('password.changed', $user_id == $AppUI->user_id ? '' : 'for ' . CAuditLog::username($user_id), 'users', $user_id);
				echo $AppUI->_('chgpwUpdated');
			}
		} else {
			echo $AppUI->_('chgpwWrongPW');
		}
	} else {
?>
<script language="javascript" nonce="<?php echo dPcspNonce(); ?>">
function submitIt() {
	var f = document.frmEdit;
	var msg = '';

	if (f.new_pwd1.value.length < <?php echo (int)dPgetConfig('password_min_len'); ?>) {
        	msg += "<?php echo $AppUI->_('chgpwValidNew', UI_OUTPUT_JS);?>" + <?php echo (int)dPgetConfig('password_min_len'); ?>;
			f.new_pwd1.focus();
	}
	if (f.new_pwd1.value != f.new_pwd2.value) {
		msg += "\n<?php echo $AppUI->_('chgpwNoMatch', UI_OUTPUT_JS);?>";
		f.new_pwd2.focus();
	}
	if (msg.length < 1) {
		f.submit();
	} else {
		alert(msg);
	}
}
// The form is only submitted by submitIt(); pressing Enter does nothing.
function chpwdNoSubmit() {
	return false;
}
</script>
<h1><?php echo $AppUI->_('Change User Password');?></h1>
<table width="100%" cellspacing="0" cellpadding="4" border="0" class="std">
<form name="frmEdit" method="post"<?php echo dPaction('chpwdNoSubmit', array(), 'submit'); ?>>
<input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
<?php if ($own_password)
{
?>
<tr>
	<td align="right" nowrap="nowrap" class="hilite"><?php echo $AppUI->_('Current Password');?></td>
	<td><input type="password" name="old_pwd" class="text"></td>
</tr>
<?php } ?>
<tr>
	<td align="right" nowrap="nowrap" class="hilite"><?php echo $AppUI->_('New Password');?></td>
	<td><input type="password" name="new_pwd1" class="text"></td>
</tr>
<tr>
	<td align="right" nowrap="nowrap" class="hilite"><?php echo $AppUI->_('Repeat New Password');?></td>
	<td><input type="password" name="new_pwd2" class="text"></td>
</tr>
<tr>
	<td>&nbsp;</td>
	<td align="right" nowrap="nowrap"><input type="button" value="<?php echo $AppUI->_('submit');?>"<?php echo dPaction('submitIt'); ?> class="button"></td>
</tr>
</form>
</table>
<?php
	}
} else {
	echo $AppUI->_('chgpwLogin');
}
?>
