<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

global $AppUI, $dPconfig, $locale_char_se;

$human_resource_id = intval(dPgetParam($_GET, 'human_resource_id', 0));

$hr = new CHumanResource;
if ($human_resource_id && ! $hr->load($human_resource_id)) {
	$AppUI->setMsg('Human Resources');
	$AppUI->setMsg('invalidID', UI_MSG_ERROR, true);
	$AppUI->redirect();
}

$user_id = intval(dPgetParam($_GET, 'user_id', 0));
if ( ! $human_resource_id) 
	$hr->human_resource_user_id = $user_id;

$titleBlock = new CTitleBlock((($human_resource_id) ? 'Edit Human Resource' : 'Configure Human Resource'),
							   'applet3-48.png', $m, "$m.$a");

$company_id = intval(dPgetParam($_GET, 'company_id', 0));
$query = new DBQuery;
$query->addTable('companies', 'c');
$query->addQuery('company_name');
$query->addWhere('c.company_id = ' . (int)$company_id);
$res =& $query->exec();
$company_name = ($res && !$res->EOF) ? $res->fields['company_name'] : '';
$titleBlock->addCrumb(('?m=companies&amp;a=view&amp;company_id=' . $company_id), 'company ' . $company_name);
$query->clear();

$contact_id = intval(dPgetParam($_GET, 'contact_id', 0));
$query = new DBQuery;
$query->addTable('contacts', 'c');
$query->addQuery('contact_last_name, contact_first_name');
$query->addWhere('c.contact_id = ' . (int)$contact_id);
$res =& $query->exec();
$contact_name = ($res && !$res->EOF) ? $res->fields['contact_last_name'] . ', ' . $res->fields['contact_first_name'] : '';
$titleBlock->addCrumb('?m=contacts&amp;a=view&amp;contact_id=' . $contact_id, 'contact ' . $contact_name);
$query->clear();

$titleBlock->addCrumb('?m=human_resources&amp;a=view_company_users&amp;company_id=' . $company_id, 'users list');

$titleBlock->show();

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
	if ($AppUI->user_lang) {
		setlocale(LC_ALL, $AppUI->user_lang);
	}
	
	$day_name = $wk[($val - LOCALE_FIRST_DAY)%7];
	if ($locale_char_set == "utf-8" && function_exists("utf8_encode")) {
	    $day_name = utf8_encode($day_name);
	}
	return htmlentities($day_name, ENT_COMPAT, $locale_char_set);
}
?>
<script src="./modules/human_resources/addedit_hr.js"></script>

<form name="editfrm" action="?m=human_resources" method="post">
<input type="hidden" name="dosql" value="do_hr_aed" />
<input name="roles_ids" id="roles_ids" type="hidden" value="">
<input type="hidden" name="human_resource_id" value="<?php echo dPformSafe($human_resource_id);?>" />
<input type="hidden" name="human_resource_user_id" value="<?php echo dPformSafe($hr->human_resource_user_id);?>" />
<input type="hidden" name="daily_working_hours" value="<?php echo dPformSafe($dPconfig['daily_working_hours']);?>" />
<table cellspacing="1" cellpadding="1" border="0" width="100%" class="std">
<tr>
<td align='center'>
  <table>  
	<tr><td align='right'><?php echo $AppUI->_('Lattes URL'); ?></td>
  <td align='left'><input type='text' size="100" maxlength="500" style="width: 100%; max-width: 60em; box-sizing: border-box;" name="human_resource_lattes_url"
    value="<?php echo dPformSafe($hr->human_resource_lattes_url);?>" />
  </td></tr>
	<tr>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Weekday working hours');?>:</td>
	</tr>
	<tr><td align='right'><?php echo $cwd_conv[0]; ?></td>
	<td align='left'><input type='text' size="2" maxlength="5" name="human_resource_mon"
    value="<?php echo dPformSafe($hr->human_resource_mon);?>" />
	</td></tr>
	<tr><td align='right'><?php echo $cwd_conv[1]; ?></td>
	<td align='left'><input type='text' size="2" maxlength="5" name="human_resource_tue"
    value="<?php echo dPformSafe($hr->human_resource_tue);?>" />
	</td></tr>
	<tr><td align='right'><?php echo $cwd_conv[2]; ?></td>
	<td align='left'><input type='text' size="2" maxlength="5" name="human_resource_wed"
    value="<?php echo dPformSafe($hr->human_resource_wed);?>" />
	</td></tr>
	<tr><td align='right'><?php echo $cwd_conv[3]; ?></td>
	<td align='left'><input type='text' size="2" maxlength="5" name="human_resource_thu"
    value="<?php echo dPformSafe($hr->human_resource_thu);?>" />
	</td></tr>
	<tr><td align='right'><?php echo $cwd_conv[4]; ?></td>
	<td align='left'><input type='text' size="2" maxlength="5" name="human_resource_fri"
    value="<?php echo dPformSafe($hr->human_resource_fri);?>" />
	</td></tr>
	<tr><td align='right'><?php echo $cwd_conv[5]; ?></td>
	<td align='left'><input type='text' size="2" maxlength="5" name="human_resource_sat"
    value="<?php echo dPformSafe($hr->human_resource_sat);?>" />
	</td></tr>
	<tr><td align='right'><?php echo $cwd_conv[6]; ?></td>
	<td align='left'><input type='text' size="2" maxlength="5" name="human_resource_sun"
    value="<?php echo dPformSafe($hr->human_resource_sun);?>" />
	</td></tr>	
	<tr>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Daily working hours: ') . ' ' . $dPconfig['daily_working_hours'];?></td>
	</tr>
</td>
</tr>
</table>
</form>

<?php
require_once DP_BASE_DIR."/modules/human_resources/view_hr_roles.php";
?>

<tr>
  <td>
    <input type="button" value="<?php echo $AppUI->_('back');?>"
    class="button"<?php echo dPaction('history.back'); ?> />
  </td>
  <td align="right">
    <input type="button" value="<?php echo $AppUI->_('submit');?>"
    class="button"<?php echo dPaction('submitHumanResourceForm'); ?> />
  </td>
</tr>


