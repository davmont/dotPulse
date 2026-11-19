<?php /* $Id: addedit.php 6149 2012-01-09 11:58:40Z ajdonnison $ */
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

$history_id = dPgetIntParam($_GET, 'history_id', 0);

/*
// check permissions
if (!$canEdit) {
	$AppUI->redirect("m=public&a=access_denied");
}
*/
// Adding, updating and deleting change data: POST with the CSRF token and edit
// permission only.
$action = ($_SERVER['REQUEST_METHOD'] === 'POST') ? dPgetCleanParam($_POST, 'action', '') : '';
$q = new DBQuery;
if ($action) {
	$AppUI->verifyCsrfToken();
	if (!getPermission('history', 'edit')) {
		$AppUI->redirect('m=public&a=access_denied');
	}
	$history_description = dPgetCleanParam($_POST, 'history_description', '');
	$history_project = dPgetCleanParam($_POST, 'history_project', '');
	$userid = $AppUI->user_id;
	
	if ($action == 'add') {
		$q->addTable('history');
		$q->addInsert('history_table', "history");
		$q->addInsert('history_action', "add");
		$q->addInsert('history_date', str_replace("'", '', $db->DBTimeStamp(time())));
		$q->addInsert('history_description', $history_description);
		$q->addInsert('history_user', $userid);
		$q->addInsert('history_project', $history_project);
		$okMsg = 'History added';
	} else if ($action == 'update') {
		$q->addTable('history');
		$q->addUpdate('history_description', $history_description);
		$q->addUpdate('history_project', $history_project);
		$q->addWhere('history_id =' . (int)$history_id);
		$okMsg = 'History updated';
	} else if ($action == 'del') {
		$q->setDelete('history');
		$q->addWhere('history_id =' . (int)$history_id);
		$okMsg = 'History deleted';				
	}
	if (!$q->exec()) {
		$AppUI->setMsg(db_error());
	} else {	
		$AppUI->setMsg($okMsg);
	}
	$q->clear();
	$AppUI->redirect();
}

// pull the history
$q->addTable('history');
$q->addQuery('*');
$q->addWhere('history_id =' . (int)$history_id);
$sql = $q->prepare();
$q->clear();
$history = array();
if (!db_loadHash($sql, $history)) {
	if ($history_id) {
		$AppUI->setMsg('History', UI_MSG_ERROR);
		$AppUI->setMsg('not found', UI_MSG_ERROR, true);
		$AppUI->redirect('m=history');
	}
	$history = array('history_project' => 0, 'history_description' => '');
}

$title = (($history_id) ? 'Edit History' : 'Add History');
$titleBlock = new CTitleBlock($title, 'stock_book_blue_48.png', $m, "$m.$a");
$titleBlock->show();
?>

<form name="AddEdit" method="post">				
<div>
<input name="action" type="hidden" value="<?php echo $history_id ? "update" : "add"  ?>">
</div>
<table border="0" cellpadding="4" cellspacing="0" width="98%">
<tr>
	<td width="50%" align="right">
		<a href="#"<?php echo dPaction('delIt'); ?>><img align="middle" src="./images/icons/trash.gif" width="16" height="16" alt="" border="0" alt="" /><?php echo $AppUI->_('delete history');?></a>
	</td>
</tr>
</table>

<table border="1" cellpadding="4" cellspacing="0" width="98%" class="std" summary="project history">
	
<script type="text/javascript" nonce="<?php echo dPcspNonce(); ?>">
	function delIt() {
		document.AddEdit.action.value = "del";
		document.AddEdit.submit();
	}	
</script>
	
<tr>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Project');?>:</td>
	<td width="60%">
<?php
// pull the projects list
$q->addTable('projects');
$q->addQuery('project_id, project_name');
$q->addOrder('project_name');
$projects = arrayMerge(array(0 => '('.$AppUI->_('any', UI_OUTPUT_RAW).')'), $q->loadHashList());
echo arraySelect($projects, 'history_project', 'class="text"', $history["history_project"]);
?>
	</td>
</tr>
	
<tr>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Description');?>:</td>
	<td width="60%">
		<textarea name="history_description" class="textarea" cols="60" rows="5" wrap="virtual"><?php echo dPformSafe($history["history_description"]);?></textarea>
	</td>
</tr>	
		
<table border="0" cellspacing="0" cellpadding="3" width="98%">
<tr>
	<td height="40" width="30%">&nbsp;</td>
	<td  height="40" width="35%" align="right">
		<table>
		<tr>
			<td>
				<input class="button" type="button" name="cancel" value="<?php echo $AppUI->_('cancel'); ?>" <?php echo dPaction(null, array(), null, array('confirm' => $AppUI->_('Are you sure you want to cancel?', UI_OUTPUT_RAW), 'href' => '?' . $AppUI->getPlace())); ?>>
			</td>
			<td>
				<input class="button" type="button" name="btnFuseAction" value="<?php echo $AppUI->_('save'); ?>"<?php echo dPaction(null, array(), null, array('submit' => '')); ?>>
			</td>
		</tr>
		</table>
	</td>
</tr>
</table>	
	
</table>
</form>		
