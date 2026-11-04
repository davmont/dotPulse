<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

global $AppUI, $dPconfig, $locale_char_se;

$titleBlock = new CTitleBlock('View Human Resource Configurations', 'applet3-48.png', $m, "$m.$a");

$company_id = intval(dPgetParam($_GET, 'company_id', 0));
$query = new DBQuery;
$query->addTable('companies', 'c');
$query->addQuery('company_name');
$query->addWhere('c.company_id = ' . (int)$company_id);
$res =& $query->exec();
$titleBlock->addCrumb(('?m=companies&amp;a=view&amp;company_id=' . $company_id), $AppUI->_('company').' ' . $res->fields['company_name']);
$query->clear();

$contact_id = intval(dPgetParam($_GET, 'contact_id', 0));
$query = new DBQuery;
$query->addTable('contacts', 'c');
$query->addQuery('contact_last_name, contact_first_name');
$query->addWhere('c.contact_id = ' . (int)$contact_id);
$res =& $query->exec();
$contact_name = $res->fields['contact_last_name'] . ', ' . $res->fields['contact_first_name'];
$titleBlock->addCrumb('?m=contacts&amp;a=view&amp;contact_id=' . $contact_id, $AppUI->_('contact').' '. $contact_name);
$query->clear();

$user_id = intval(dPgetParam($_GET, 'user_id', 0));
$query = new DBQuery;
$query->addTable('human_resource', 'h');
$query->addQuery('human_resource_id');
$query->addWhere('h.human_resource_user_id = ' . (int)$user_id);
$res =& $query->exec();
$human_resource_id = ($res && !$res->EOF) ? $res->fields['human_resource_id'] : 0;
$query->clear();

$obj = new CHumanResource;

$existsHumanResource = $obj->load($human_resource_id);
if($existsHumanResource) {
	$AppUI->savePlace();  
}		
$titleBlock->addCrumb('?m=human_resources&amp;a=view_company_users&amp;company_id=' . $company_id, 'users list');

if($existsHumanResource) {
	$canDelete = $obj->canDelete();
	if($canDelete) {
		$titleBlock->addCrumb("?m=human_resources&amp;a=addedit_hr&amp;human_resource_id=$human_resource_id&amp;contact_id=$contact_id&amp;company_id=$company_id", 'edit this human resource');
		$titleBlock->addCrumbDelete('delete human resource', true, 'no delete permission');
	}
	else {
		$titleBlock->addCell('<p style="color: red;">This human resource cannot be deleted because it is already allocated.</p>');
	}	
}
else {
	$titleBlock->addCrumb("?m=human_resources&amp;a=addedit_hr&amp;human_resource_id=$human_resource_id&amp;contact_id=$contact_id&amp;company_id=$company_id&amp;user_id=$user_id", "configure human resource");
}

$titleBlock->show();

$concat_roles_names = "";
if($existsHumanResource) {
	$query = new DBQuery;
	$query->addTable('human_resource_roles', 'r');
	$query->addQuery('h.human_resources_role_name, h.human_resources_role_id');
	$query->innerJoin('human_resources_role', 'h', 'h.human_resources_role_id = r.human_resources_role_id');
	$query->addWhere('r.human_resource_id = ' . (int)$human_resource_id);
	$sql = $query->prepare();
	$roles = db_loadList($sql);
	$roles_array = array();
	foreach($roles as $role) {
		array_unshift($roles_array, $role['human_resources_role_name']);
	}
	$concat_roles_names = implode(', ', $roles_array);
}


$cwd = array();
$cwd[0] = '1';
$cwd[1] = '2';
$cwd[2] = '3';
$cwd[3] = '4';
$cwd[4] = '5';
$cwd[5] = '6';
$cwd[6] = '7';
$cwd_conv = array_map('cal_work_day_conv', $cwd);

function cal_work_day_conv($val) {
	global $AppUI, $locale_char_set;
	setlocale(LC_ALL, 'en_AU'.(($locale_char_set)? ('.' . $locale_char_set) : '.utf8'));
	$wk = CDate::weekdayNames();
	if (!empty($AppUI->user_lang)) {
		setlocale(LC_ALL, $AppUI->user_lang);
	}
	
	$day_name = $wk[($val - LOCALE_FIRST_DAY)%7];
	if ($locale_char_set == "utf-8" && function_exists("utf8_encode")) {
	    $day_name = utf8_encode($day_name);
	}
	return htmlentities($day_name, ENT_COMPAT, $locale_char_set);
}

$query->addTable('users', 'u');
$query->addQuery('user_username');
$query->addWhere('u.user_id = ' . (int)$user_id);
$res =& $query->exec();
?>
<script src="./modules/human_resources/view_hr.js"></script>
<script type="text/javascript" nonce="<?php echo dPcspNonce(); ?>">
	can_delete = true;
	delete_msg = "<?php echo $AppUI->_('doDelete', UI_OUTPUT_JS).' '.$AppUI->_('Human Resource', UI_OUTPUT_JS).'?';?>";
</script>
<form name="frmDelete" action="?m=human_resources" method="post">
  <input type="hidden" name="dosql" value="do_hr_aed" />
  <input type="hidden" name="del" value="1" />
  <input type="hidden" name="human_resource_id" value="<?php echo $human_resource_id;?>" />
</form>

<table border="0" cellpadding="4" cellspacing="0" width="100%" class="std" summary="human_resources">
<tr>
  <td valign="top" width="100%">
		<strong><?php echo $AppUI->_('Details');?></strong>
		<table cellspacing="1" cellpadding="2" width="100%">
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('User contact');?>:</td>
			<td class="hilite" width="100%"><?php echo dPhtml($contact_name);?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('User username');?>:</td>
			<td class="hilite" width="100%"><?php echo dPhtml($res->fields['user_username']);?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Roles'); ?>:</td>
			<td class="hilite" width="100%"><?php echo dPhtml($concat_roles_names);?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Lattes URL');?>:</td>
			<td width="2%" align="left" >
			<a href="<?php echo dPhtml(dPsafeUrl($obj->human_resource_lattes_url));?>"><?php echo dPhtml($obj->human_resource_lattes_url);?></a>
			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Weekday working hours');?>:</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo  $AppUI->_($cwd_conv[0]);?>:</td>
			<td class="hilite" width="100%"><?php echo dPhtml($obj->human_resource_mon);?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo  $AppUI->_($cwd_conv[1]);?>:</td>
			<td class="hilite" width="100%"><?php echo dPhtml($obj->human_resource_tue);?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo  $AppUI->_($cwd_conv[2]);?>:</td>
			<td class="hilite" width="100%"><?php echo dPhtml($obj->human_resource_wed);?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_($cwd_conv[3]);?>:</td>
			<td class="hilite" width="100%"><?php echo dPhtml($obj->human_resource_thu);?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo  $AppUI->_($cwd_conv[4]);?>:</td>
			<td class="hilite" width="100%"><?php echo dPhtml($obj->human_resource_fri);?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo  $AppUI->_($cwd_conv[5]);?>:</td>
			<td class="hilite" width="100%"><?php echo dPhtml($obj->human_resource_sat);?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo  $AppUI->_($cwd_conv[6]);?>:</td>
			<td class="hilite" width="100%"><?php echo dPhtml($obj->human_resource_sun);?></td>
		</tr>
	</td>
</tr>
<tr>
</tr>
</table>
<?php 
$query->clear();
?>
<tr>
  <td>
    <input type="button" value="<?php echo $AppUI->_('back');?>"
    class="button"<?php echo dPaction('history.back'); ?> />
  </td>
</tr>