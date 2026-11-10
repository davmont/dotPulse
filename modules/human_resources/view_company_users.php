<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

global $tabbed, $currentTabName, $currentTabId, $AppUI;

$company_id = intval(dPgetParam($_GET, 'company_id', 0));

$query = new DBQuery;
$query->addTable('companies', 'c');
$query->addQuery('company_name');
$query->addWhere('c.company_id = ' . (int)$company_id);
$res =& $query->exec();

$titleBlock = new CTitleBlock('Human Resource Configurations', 'applet3-48.png', $m, "$m.$a");
$titleBlock->addCrumb(('?m=companies&amp;a=view&amp;company_id=' . $company_id), $AppUI->_('company').' '. $res->fields['company_name']);
$titleBlock->show();
$query->clear();

$query = new DBQuery;
$query->addTable('users', 'u');
$query->addQuery('user_id, user_username, contact_last_name, contact_first_name, contact_id');
$query->addJoin('contacts', 'c', 'u.user_contact = c.contact_id');
$query->addWhere('c.contact_company = ' . (int)$company_id);
$query->addOrder('contact_last_name');
$res =& $query->exec();

?>
<table width='100%' border='0' cellpadding='2' cellspacing='1' class='tbl'>
<tr>
	<th nowrap='nowrap' width='30%'>
    <?php echo $AppUI->_('User username'); ?>
	</th>
	<th nowrap='nowrap' width='30%'>
    <?php echo $AppUI->_('User contact'); ?>
	</th>
	<th nowrap='nowrap' width='40%'>
    <?php echo $AppUI->_('User roles'); ?>
	</th>
</tr>
<?php
require_once DP_BASE_DIR."/modules/human_resources/configuration_functions.php";
require_once DP_BASE_DIR."/modules/human_resources/allocation_functions.php";
for ($res; ! $res->EOF; $res->MoveNext()) {
	$user_id = $res->fields['user_id'];
	$user_has_human_resource = userHasHumanResource($user_id);
	$style = $user_has_human_resource ? '' : 'background-color:#ED9A9A; font-weight:bold';
	$contact_name = $res->fields['contact_last_name'] . ', ' . $res->fields['contact_first_name'];
	$contact_id = $res->fields['contact_id'];
	
	$roles = getUserRolesByUserId($user_id);
	$concat_roles_names = "";
	if($roles != null) {
		$roles_array = array();
		foreach($roles as $role) {
			array_unshift($roles_array, $role['human_resources_role_name']);
		}
		$concat_roles_names = implode(', ', $roles_array);
	}
?>
<tr>
  <td style="<?php echo $style;?>">
   <a href="index.php?m=human_resources&amp;a=view_hr&amp;user_id=<?php echo $user_id;?>&amp;contact_id=<?php echo $contact_id;?>&amp;company_id=<?php echo $company_id;?>">
	<?php echo dPhtml($contact_name); ?>
	</td>
  <td style="<?php echo $style;?>">
   <a href="index.php?m=human_resources&amp;a=view_hr&amp;user_id=<?php echo $user_id;?>&amp;contact_id=<?php echo $contact_id;?>&amp;company_id=<?php echo $company_id;?>">
    <?php echo dPhtml($res->fields['user_username']); ?>
  </td>
  <td style="<?php echo $style;?>">
   <a href="index.php?m=human_resources&amp;a=view_hr&amp;user_id=<?php echo $user_id;?>&amp;contact_id=<?php echo $contact_id;?>&amp;company_id=<?php echo $company_id;?>">
    <?php echo dPhtml($concat_roles_names); ?>
  </td>
</tr>
<?php
  }
$query->clear();
?>
</table>
<table>
<tr>
  <td><?php echo $AppUI->_('Key'); ?>:&nbsp;&nbsp;</td>
  <td style="background-color:#FFFFFF; color:#000000" width="10">&nbsp;</td>
  <td>=<?php echo $AppUI->_('User with human resources configured'); ?>&nbsp;&nbsp;</td>
  <td style="background-color:#ED9A9A; color:#000000" width="10">&nbsp;</td>
  <td>=<?php echo $AppUI->_('User with human resources not configured'); ?>&nbsp;&nbsp;</td>
</tr>
</table>
<tr>
  <td>
    <input type="button" value="<?php echo $AppUI->_('back');?>"
    class="button"<?php echo dPaction('history.back'); ?> />
  </td>
 