<?php /* PUBLIC $Id: chpwd.php 6149 2012-01-09 11:58:40Z ajdonnison $ */
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

if (! ($user_id = (int)dPgetParam($_REQUEST, 'user_id', 0))) {
	$user_id = @$AppUI->user_id;
}

// check for a non-zero user id
if ($user_id) {
	$old_pwd = db_escape(trim(dPgetCleanParam($_POST, 'old_pwd', null)));
	$new_pwd1 = db_escape(trim(dPgetCleanParam($_POST, 'new_pwd1', null)));
	$new_pwd2 = db_escape(trim(dPgetCleanParam($_POST, 'new_pwd2', null)));

	// has the change form been posted
	if ($new_pwd1 && $new_pwd2 && $new_pwd1 == $new_pwd2) {
		// check that the old password matches (supports both password_hash and legacy MD5)
		$q = new DBQuery;
		$q->addQuery('user_id, user_password');
		$q->addTable('users');
		$q->addWhere('user_id = ' . intval($user_id));
		$row = $q->fetchRow();
		$db_pwd = $row['user_password'] ?? '';

		if ($AppUI->user_type == 1 || password_verify($old_pwd, $db_pwd) || md5($old_pwd) === $db_pwd) {
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
<?php if ($AppUI->user_type != 1)
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
