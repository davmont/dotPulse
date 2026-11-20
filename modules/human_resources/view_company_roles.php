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

$titleBlock = new CTitleBlock('Roles', 'applet3-48.png', 'human_resources', "$m.$a");
$titleBlock->addCrumb(('?m=companies&amp;a=view&amp;company_id=' . $company_id), $AppUI->_('company') .' '. $res->fields['company_name']);

$titleBlock->addCell(('<input type="submit" class="button" value="' . $AppUI->_('new role') 
	                      . '">'), '', '<form action="?m=human_resources&amp;a=view_role&amp;company_id=' . $company_id . '" method="post">', '</form>');

$titleBlock->show();
$query->clear();

$query = new DBQuery;
$query->addTable('human_resources_role', 'r');
$query->addQuery('r.*');
$query->addWhere('r.human_resources_role_company_id = ' . (int)$company_id);
$res_companies =& $query->exec();

?>
<table width='100%' border='0' cellpadding='2' cellspacing='1' class='tbl'>
<tr>
	<th nowrap='nowrap' width='10%'>
    <?php echo $AppUI->_('Role name'); ?>
	</th>
	<th nowrap='nowrap' width='30%'>
    <?php echo $AppUI->_('Role responsability'); ?>
	</th>
	<th nowrap='nowrap' width='30%'>
    <?php echo $AppUI->_('Role authority'); ?>
	</th>
	<th nowrap='nowrap' width='30%'>
    <?php echo $AppUI->_('Role competence'); ?>
	</th>
</tr>

<?php
require_once DP_BASE_DIR."/modules/human_resources/configuration_functions.php";
for ($res_companies; ! $res_companies->EOF; $res_companies->MoveNext()) {
	$human_resources_role_id = $res_companies->fields['human_resources_role_id'];
	$configured = isConfiguredRole($human_resources_role_id);
	$style = $configured ? '' : 'background-color:#ED9A9A; font-weight:bold';
?>
<tr>
  <td style="<?php echo $style;?>">
   <a href="index.php?m=human_resources&amp;a=view_role&amp;human_resources_role_id=<?php echo $human_resources_role_id;?>&amp;company_id=<?php echo $company_id;?>">
	<?php echo dPhtml($res_companies->fields['human_resources_role_name']); ?>
	</td>
  <td style="<?php echo $style;?>">
   <a href="index.php?m=human_resources&amp;a=view_role&amp;human_resources_role_id=<?php echo $human_resources_role_id;?>&amp;company_id=<?php echo $company_id;?>">
    <?php echo dPhtml($res_companies->fields['human_resources_role_responsability']); ?>
  </td>
  <td style="<?php echo $style;?>">
   <a href="index.php?m=human_resources&amp;a=view_role&amp;human_resources_role_id=<?php echo $human_resources_role_id;?>&amp;company_id=<?php echo $company_id;?>">
    <?php echo dPhtml($res_companies->fields['human_resources_role_authority']); ?>
  </td>
  <td style="<?php echo $style;?>">
   <a href="index.php?m=human_resources&amp;a=view_role&amp;human_resource_role_id=<?php echo $human_resource_role_id;?>&amp;company_id=<?php echo $company_id;?>">
    <?php echo dPhtml($res_companies->fields['human_resources_role_competence']); ?>
  </td>
</tr>
<?php
  }
$query->clear();
?>
</table>
<tr>
  <td>
    <input type="button" value="<?php echo $AppUI->_('back');?>"
    class="button"<?php echo dPaction('history.back'); ?> />
  </td>
</tr>
 