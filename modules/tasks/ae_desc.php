<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// $Id$
global $AppUI, $task_id, $obj, $users, $task_access, $department_selection_list;
global $task_parent_options, $dPconfig, $projects, $task_project, $can_edit_time_information, $tab;

?>

<form action="?m=tasks&a=addedit&task_project=<?php echo $task_project; ?>"
  method="post"  name="detailFrm">
<input type="hidden" name="dosql" value="do_task_aed" />
<input type="hidden" name="sub_form" value="1" />
<input type="hidden" name="task_id" value="<?php echo $task_id; ?>" />
<div style="display: flex; flex-wrap: wrap; width: 100%; gap: 16px; background: #fff; border: 1px solid #e0e0e0; border-radius: 4px; padding: 16px; box-sizing: border-box;">
	<div style="flex: 1 1 350px; min-width: 0;">
		<table border="0">
			<tr>
				<td>
					<?php
if ($can_edit_time_information) {
	echo $AppUI->_('Task Owner');
?>
					<br />
					<?php 
	echo arraySelect($users, 'task_owner', 'class="text"', 
	                 ((!(isset($obj->task_owner))) ? $AppUI->user_id : $obj->task_owner)); 
?>
					<br />
					<?php
} // $can_edit_time_information
echo $AppUI->_('Access'); 
?>
					<br />
					<?php 
echo arraySelect($task_access, 'task_access', 'class="text"', intval($obj->task_access), true); 
?>
					<br /><?php echo $AppUI->_('Web Address'); ?>
					<br /><input type="text" class="text" name="task_related_url" value="<?php 
echo dPhtml(@$obj->task_related_url);?>" size="40" maxlength="255" />
				</td>
				<td valign='top'>
					<?php echo $AppUI->_("Task Type"); ?><br />
					<?php 
$task_types = dPgetSysVal('TaskType'); 
echo arraySelect($task_types, 'task_type',  'class="text"', $obj->task_type, false); 
?>
					<br /><br />
					<?php
if ($AppUI->isActiveModule('contacts') && getPermission('contacts', 'access')) {
	$project = new CProject();
	$project->load($task_project);
	echo '<input type="button" class="button" value="' . $AppUI->_('Select contacts...') 
	      . '"' . dPaction('popContacts') . ' />&nbsp;<input type="button" class="button" value="'.$AppUI->_('Import contacts from project').'"';
	echo dPaction('importProjectContacts', array('$this', 
	              (string)(($project->project_contacts != '') ? $obj->task_contacts.$project->project_contacts : $project->project_contacts), 
	              $AppUI->_('Contacts imported', UI_OUTPUT_RAW)));
	echo ' />';
}
// Let's check if the actual company has departments registered
if ($department_selection_list != '') {
?>
					<br />
					<?php echo $AppUI->_("Departments"); ?><br />
					<?php echo $department_selection_list; ?>
					<?php
}
?>
				</td>
			</tr>
			<tr>
				<td><?php echo $AppUI->_('Task Parent');?>:</td>
				<td><?php echo $AppUI->_('Target Budget');?>:</td>
			</tr>
			<tr>
				<td>
					<select name='task_parent' class='text'>
						<option value='<?php echo $obj->task_id; ?>'><?php 
echo $AppUI->_('None'); ?></option>
						<?php echo $task_parent_options; ?>
					</select>
				</td>
				<td>
					<?php echo $dPconfig['currency_symbol']; ?>
					<input type="text" class="text" name="task_target_budget" value="<?php 
echo dPhtml(@$obj->task_target_budget);?>" size="10" maxlength="10" />
				</td>
			</tr><?php 
if ($task_id > 0) { 
?>
			<tr>
				<td>
					<?php echo $AppUI->_('Move this task (and its children), to project'); ?>:
				</td>
			</tr>
			<tr>
				<td>
					<?php 
echo arraySelect($projects, 'new_task_project', 
                 'size="1" class="text" id="medium"' . dPaction('submitEditFrm'), 
                 $task_project); 
?>
				</td>
			</tr><?php 
} 
?>
		</table>
	</div>
	<div style="flex: 1 1 350px; min-width: 0; text-align: center;">
		<div style="text-align: left; width: 100%; height: 100%; display: flex; flex-direction: column;">
			<?php echo $AppUI->_('Description');?>:
			<br />
			<textarea name="task_description" class="textarea" cols="60" rows="10" wrap="virtual" style="width: 100%; flex-grow: 1; box-sizing: border-box; min-height: 200px;"><?php 
echo dPhtml(@$obj->task_description);?></textarea>
		</div><br />
		<?php
require_once($AppUI->getSystemClass('CustomFields'));
GLOBAL $m;
$custom_fields = New CustomFields($m, 'addedit', $obj->task_id, "edit");
$custom_fields->printHTML();
		?>
	</div>
</div>
</form>
<script language="javascript" nonce="<?php echo dPcspNonce(); ?>">
 subForm.push(new FormDefinition(<?php echo (int)$currentTabId;?>, document.detailFrm, checkDetail, saveDetail));
</script>
