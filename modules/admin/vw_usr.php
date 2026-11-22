<?php /* ADMIN  $Id: vw_usr.php 6149 2012-01-09 11:58:40Z ajdonnison $ */ 
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

?>
<table cellpadding="2" cellspacing="1" border="0" width="100%" class="tbl">
<tr>
	<td width="60" align="right">
		&nbsp; <?php echo $AppUI->_('sort by');?>:&nbsp;
	</td>
	<?php if ((int)dPgetParam($_GET, 'tab', 0) == 0) { ?>
	<th width="125">
	           <?php echo $AppUI->_('Login History');?>
	</th>
	<?php } ?>
	<th width="150">
		<a href="?m=admin&amp;a=index&amp;orderby=user_username" class="hdr"><?php echo $AppUI->_('Login Name');?></a>
	</th>
	<th>
		<a href="?m=admin&amp;a=index&amp;orderby=contact_last_name" class="hdr"><?php echo $AppUI->_('Real Name');?></a>
	</th>
	<th>
		<a href="?m=admin&amp;a=index&amp;orderby=contact_company" class="hdr"><?php echo $AppUI->_('Company');?></a>
	</th>
</tr>
<?php 

$perms =& $AppUI->acl();
$show_tab_0 = ((int)dPgetParam($_REQUEST, 'tab', 0) == 0);

$all_user_logs = array();
if ($show_tab_0) {
	$user_ids = array();
	foreach ($users as $row) {
		if ($perms->checkLogin($row['user_id']) == $canLogin) {
			$user_ids[] = (int)$row['user_id'];
		}
	}

	if (!empty($user_ids)) {
		$q = new DBQuery;
		$q->addTable('user_access_log', 'ual');
		$q->addQuery('user_access_log_id, user_id,'
		             . ' (unix_timestamp(now()) - unix_timestamp(date_time_in))/3600 as hours,'
		             . ' (unix_timestamp(now()) - unix_timestamp(date_time_last_action))/3600'
		             . ' as idle, if (isnull(date_time_out)'
		             . " or date_time_out ='0000-00-00 00:00:00','1','0') as online");

		// To only fetch the most recent log per user efficiently:
		// We use a subquery in the WHERE clause to only select the maximum user_access_log_id per user.
		$subq = new DBQuery;
		$subq->addTable('user_access_log');
		$subq->addQuery('MAX(user_access_log_id)');
		$subq->addWhere("user_id IN (" . implode(',', $user_ids) . ")");
		$subq->addGroup('user_id');
		$max_ids_sql = $subq->prepare();

		$q->addWhere("ual.user_access_log_id IN (" . $max_ids_sql . ")");

		$logs = $q->loadList();
		if ($logs) {
			foreach ($logs as $log) {
				$all_user_logs[$log['user_id']] = array($log);
			}
		}
	}
}

foreach ($users as $row) {
	if ($perms->checkLogin($row['user_id']) != $canLogin) {
		continue;
	}
?>
<tr>
	<td align="right" nowrap="nowrap">
<?php 
   if ($canEdit) { ?>
		<table cellspacing="0" cellpadding="0" border="0">
		<tr>
			<td>
				<a href="./index.php?m=admin&amp;a=addedituser&amp;user_id=<?php echo $row['user_id'];?>" title="<?php echo $AppUI->_('edit');?>">
					<?php echo dPshowImage('./images/icons/stock_edit-16.png', 16, 16, ''); ?>
				</a>
			</td>
			<td>
				<a href="?m=admin&amp;a=viewuser&amp;user_id=<?php echo $row['user_id'];?>&amp;tabname=perms" title="">
					<img src="images/obj/lock.gif" width="16" height="16" border="0" alt="<?php echo $AppUI->_('edit permissions');?>" />
				</a>
			</td>
			<td>
<?php 
		$user_display = $row['contact_first_name'] . ' ' . $row['contact_last_name'];
		$user_display = trim($user_display);
		if (empty($user_display)) {
        $user_display = $row['user_username'];
		}
?>
				<a href="#"<?php echo dPaction('delMe', array((int)$row['user_id'], $user_display)); ?> title="<?php echo $AppUI->_('delete');?>">
					<?php echo dPshowImage('./images/icons/stock_delete-16.png', 16, 16, ''); ?>
				</a>
			</td>
		</tr>
		</table>
<?php } ?>
	</td>
	<?php 
		if ($show_tab_0) { ?>
	<td>
	       <?php 
			$user_logs = isset($all_user_logs[$row['user_id']]) ? $all_user_logs[$row['user_id']] : null;
	           
			if ($user_logs) {
				foreach ($user_logs as $row_log) {
					if ($row_log["online"] == '1') {
						// One decimal: the query gives hours with four ("0.3353 hrs.").
						echo ('<span style="color: green">' . number_format((float)$row_log['hours'], 1) . ' '
						      . $AppUI->_('hrs.'). ' (' . number_format((float)$row_log['idle'], 1) . ' '
						      .  $AppUI->_('hrs.') . ' ' . $AppUI->_('idle') . ') - ' 
						      . $AppUI->_('Online'));  
					} else {
						echo '<span style="color: red">'.$AppUI->_('Offline');
					}
				}
			} else {
				echo '<span style="color: grey">'.$AppUI->_('Never Visited');
			}
			echo '</span>';
		}
?>
	</td>
	<td>
		<a href="?m=admin&amp;a=viewuser&amp;user_id=<?php echo $row['user_id'];?>"><?php 
echo dPhtml($row['user_username']);?></a>
	</td>
	<td>
		<a href="mailto:<?php echo dPhtml($row['contact_email']);?>"><img src="images/obj/email.gif" width="16" height="16" border="0" alt="email"></a>
<?php
	if ($row['contact_last_name'] && $row['contact_first_name']) {
		echo dPhtml($row['contact_last_name'].', '.$row['contact_first_name']);
	} else {
        echo '<span style="font-style: italic">unknown</span>';
	}
?>
	</td>
	<td>
		<a href="?m=companies&amp;a=view&amp;company_id=<?php echo (int)$row['contact_company'];?>"><?php echo dPhtml($row['company_name']);?></a>
	</td>
</tr>
<?php 
}
?>

</table>
