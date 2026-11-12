<?php /* SYSTEM $Id: addeditpref.php 6149 2012-01-09 11:58:40Z ajdonnison $ */
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

##
## add or edit a user preferences
##
$user_id = (int)dPgetParam($_GET, 'user_id', '0');
// Check permissions
if (!$canEdit && $user_id != $AppUI->user_id) {
  $AppUI->redirect("m=public&a=access_denied");
}

// load the preferences
$q = new DBQuery;
$q->addQuery('pref_name, pref_value');
$q->addTable('user_preferences');
$q->addWhere('pref_user in (0,' . (int)$user_id . ')');
$q->addOrder('pref_user');
$prefs = $q->loadHashList();

// get the user name
$user = (($user_id) ? dPgetUsernameFromID($user_id) : 'Default');

// The defaults (no user) are a System Admin page; a user's own preferences are not.
$admin_page = (!$user_id && getPermission('system', 'edit'));
if ($admin_page) {
	require_once DP_BASE_DIR . '/modules/system/admin_ui.php';
	dPadminOpen('addeditpref');
	dPadminHeading('Default user preferences', 'Language, date format, theme and other preferences of the users who have not chosen their own.');
} else {
	$titleBlock = new CTitleBlock('Edit User Preferences', 'myevo-weather.png', $m, "$m.$a");
	$titleBlock->show();
}
?>
<script type="text/javascript" language="javascript" nonce="<?php echo dPcspNonce(); ?>">
function submitIt() {
	var form = document.changeuser;
	// Collate the checked states of the task log stuff
	var defs = document.getElementById('task_log_email_defaults');
	var mask = 0;
	if (form.tl_assign.checked)
		mask += 1;
	if (form.tl_task.checked)
		mask += 2;
	if (form.tl_proj.checked)
		mask += 4;
	defs.value = mask;
	form.submit();
}
</script>

<form name="changeuser" action="./index.php?m=system" method="post">
	<input type="hidden" name="dosql" value="do_preference_aed" />
	<input type="hidden" name="pref_user" value="<?php echo $user_id;?>" />
	<input type="hidden" name="del" value="0" />

<table width="100%" border="0" cellpadding="1" cellspacing="1" class="std">
<tr>
	<th colspan="2"><?php echo $AppUI->_('User Preferences');?>:
	<?php
		echo $user_id ? dPhtml($user) : $AppUI->_("Default");
	?></th>
</tr>

<tr>
	<td align="right"><?php echo $AppUI->_('Locale');?>:</td>
	<td>
<?php
	// read the installed languages
	$LANGUAGES = $AppUI->loadLanguages();
	$temp = $AppUI->setWarning(false);
	$langlist = array();
	foreach ($LANGUAGES as $lang => $langinfo)
		$langlist[$lang] = $langinfo[1];
	// A language without its country ('en', the installer's default) selects one of its
	// locales, not the first of the list (which saving would then make the default).
	$locale_pref = isset($prefs['LOCALE']) ? $prefs['LOCALE'] : '';
	if (!isset($langlist[$locale_pref])) {
		$matches = preg_grep('/^' . preg_quote($locale_pref, '/') . '_/', array_keys($langlist));
		if (isset($langlist[$locale_pref . '_US'])) {
			$locale_pref = $locale_pref . '_US';
		} else if ($matches) {
			$locale_pref = reset($matches);
		}
	}
	echo arraySelect($langlist, 'pref_name[LOCALE]', 'class="text" size="1"', $locale_pref, true);
	$AppUI->setWarning($temp);
?>
	</td>
</tr>

<tr>
	<td align="right"><?php echo $AppUI->_('Tabbed Box View');?>:</td>
	<td>
<?php
	$tabview = array('either', 'tabbed', 'flat');
	echo arraySelect($tabview, 'pref_name[TABVIEW]', 'class="text" size="1"', @$prefs['TABVIEW'], true);
?>
	</td>
</tr>

<tr>
	<td align="right"><?php echo $AppUI->_('Short Date Format');?>:</td>
	<td>
<?php
	// exmample date
	$ex = new CDate();

	$dates = array();
	$f = "%d/%m/%Y"; $dates[$f]	= $ex->format($f);
	$f = "%d/%b/%Y"; $dates[$f]	= $ex->format($f);
	$f = "%m/%d/%Y"; $dates[$f]	= $ex->format($f);
	$f = "%b/%d/%Y"; $dates[$f]	= $ex->format($f);
	$f = "%d.%m.%Y"; $dates[$f]	= $ex->format($f);
        $f = "%Y/%b/%d"; $dates[$f]     = $ex->format($f); 
	echo arraySelect($dates, 'pref_name[SHDATEFORMAT]', 'class="text" size="1"', @$prefs['SHDATEFORMAT'], false);
?>
	</td>
</tr>

<tr>
	<td align="right"><?php echo $AppUI->_('Time Format');?>:</td>
	<td>
<?php
	// exmample date
	$times = array();
	$f = "%I:%M %p"; $times[$f]	= $ex->format($f);
	$f = "%H:%M"; $times[$f]	= $ex->format($f).' (24)';
	$f = "%H:%M:%S"; $times[$f]	= $ex->format($f).' (24)';
	echo arraySelect($times, 'pref_name[TIMEFORMAT]', 'class="text" size="1"', @$prefs['TIMEFORMAT'], false);
?>
	</td>
</tr>

<tr>
	<td align="right"><?php echo $AppUI->_('Currency Format');?>:</td>
	<td>
<?php
	$currencies = array();
	$currEx = 1234567.89;

	if (mb_strtoupper(mb_substr(PHP_OS, 0, 3)) == 'WIN')
		$is_win = true;
	else
		$is_win = false;
	foreach (array_keys($LANGUAGES) as $lang) {
		$currencies[$lang] = formatCurrency($currEx, $AppUI->setUserLocale($lang, false));
	}
	echo arraySelect($currencies, 'pref_name[CURRENCYFORM]', 'class="text" size="1"', @$prefs['CURRENCYFORM'], false);
?>
	</td>
</tr>

<tr>
	<td align="right"><?php echo $AppUI->_('User Interface Style');?>:</td>
	<td>
<?php
        $uis = !empty($prefs['UISTYLE']) ? $prefs['UISTYLE'] : dPgetConfig('host_style', 'dotpulse');
	$styles = dPstyleOptions();
	echo arraySelect($styles, 'pref_name[UISTYLE]', 'class="text" size="1"', $uis);
?>
	</td>
</tr>
<tr>
	<td align="right"><?php echo $AppUI->_('User Task Assignment Maximum');?>:</td>
	<td>
<?php
        $tam = (@$prefs['TASKASSIGNMAX'] > 0) ? $prefs['TASKASSIGNMAX'] : 100;
        $taskAssMax = array();
        for ($i = 5; $i <= 200; $i+=5) {
                $taskAssMax[$i] = $i.'%';
        }
	echo arraySelect($taskAssMax, 'pref_name[TASKASSIGNMAX]', 'class="text" size="1"', $tam, false);

?>
	</td>
</tr>
<tr>
	<td align="right"><?php echo $AppUI->_('Default Event Filter');?>:</td>
	<td>
<?php
	require_once $AppUI->getModuleClass('calendar');
	echo arraySelect($event_filter_list, 'pref_name[EVENTFILTER]', 'class="text" size="1"', @$prefs['EVENTFILTER'], true);
?>
	</td>
</tr>
<tr>
	<td align="right"><?php echo $AppUI->_('Task Notification Method');?>:</td>
	<td>
<?php
	$notify_filter = array(
		0 => 'Do not include task/event owner',
		1 => 'Include task/event owner'
	);
 
	echo arraySelect($notify_filter, 'pref_name[MAILALL]', 'class="text" size="1"', @$prefs['MAILALL'], true);

?>
	</td>
</tr>
<tr>
	<td align="right"><?php echo $AppUI->_('Email me when I am assigned to a task');?>:</td>
	<td>
<?php
	$assign_mail = array('1' => 'Yes', '0' => 'No');
	echo arraySelect($assign_mail, 'pref_name[TASKASSIGNMAIL]', 'class="text" size="1"',
		isset($prefs['TASKASSIGNMAIL']) ? $prefs['TASKASSIGNMAIL'] : '1', true);
?>
	</td>
</tr>
<tr>
	<td align="right"><?php echo $AppUI->_('Task Log Email Defaults');?>:</td>
	<td>
		<input type='hidden' name='pref_name[TASKLOGEMAIL]' id='task_log_email_defaults' value='<?php echo @$prefs['TASKLOGEMAIL']; ?>' />
<?php
	if (! isset($prefs['TASKLOGEMAIL'])) {
		$prefs['TASKLOGEMAIL'] = 0;
	}
	$tl_assign = $prefs['TASKLOGEMAIL'] & 1;
	$tl_task = $prefs['TASKLOGEMAIL'] & 2;
	$tl_proj = $prefs['TASKLOGEMAIL'] & 4;
	echo '<label for="tl_assign">' . $AppUI->_('Email Assignees') . '</label>&nbsp;<input type="checkbox" name="tl_assign" id="tl_assign"';
	if ($tl_assign) {
		echo ' checked="checked"';
	}
	echo ' /><br />';
	echo '<label for="tl_task">' . $AppUI->_('Email Task Contacts') . '</label>&nbsp;<input type="checkbox" name="tl_task" id="tl_task"';
	if ($tl_task) {
		echo 'checked="checked"';
	}
	echo ' /><br />';
	echo '<label for="tl_proj">' . $AppUI->_('Email Project Contacts') . '</label>&nbsp;<input type="checkbox" name="tl_proj" id="tl_proj"';
	if ($tl_proj) {
		echo ' checked="checked"';
	}
	echo ' />';
?>
	</td>
</tr>
<tr>
	<td align="right"><?php echo $AppUI->_('Task Log Email Subject');?>:</td>
	<td>
		<input type='text' name='pref_name[TASKLOGSUBJ]' value='<?php echo @$prefs['TASKLOGSUBJ']; ?>' />
	</td>
</tr>
<tr>
	<td align="right"><?php echo $AppUI->_('Task Log Email Recording Method');?>:</td>
	<td>
	<?php
		$record_method['0'] = $AppUI->_('None');
		$record_method['1'] = $AppUI->_('Append to Log');
		echo arraySelect($record_method, 'pref_name[TASKLOGNOTE]', 'class="text" size="1"', @$prefs['TASKLOGNOTE'], false);
	?>
	</td>
</tr>

<tr>
	<td align="left"><input class="button"  type="button" value="<?php echo $AppUI->_('back');?>"<?php echo dPaction('history.back'); ?> /></td>
	<td align="right"><input class="button" type="button" value="<?php echo $AppUI->_('submit');?>"<?php echo dPaction('submitIt'); ?> /></td>
</tr>
</table>
</form>
<?php if ($admin_page) { dPadminClose(); } ?>
