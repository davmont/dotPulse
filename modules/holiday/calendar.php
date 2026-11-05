<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
##
## holiday module - add or edit a holiday calendar (a workplace's holidays)
##
require_once DP_BASE_DIR . '/modules/holiday/holiday_func.php';

if (!holidayHasCalendars()) {
	$AppUI->setMsg('Upgrade the Holiday module in System Admin, Modules, to use calendars.', UI_MSG_ERROR);
	$AppUI->redirect('m=holiday');
}

$calendar_id = (int)dPgetCleanParam($_GET, 'calendar_id', 0);
$calendars = holidayCalendars();
if ($calendar_id && !isset($calendars[$calendar_id])) {
	$AppUI->setMsg('Calendar not found', UI_MSG_ERROR);
	$AppUI->redirect('m=holiday');
}
$link_types = array('company', 'dept', 'user');

// Saving and deleting change data: POST with the CSRF token and edit permission only.
$action = ($_SERVER['REQUEST_METHOD'] === 'POST') ? dPgetCleanParam($_POST, 'action', '') : '';
if ($action) {
	$AppUI->verifyCsrfToken();
	if (!getPermission('holiday', 'edit')) {
		$AppUI->redirect('m=public&a=access_denied');
	}
	$q = new DBQuery();
	if ($action == 'del' && $calendar_id) {
		// Its days, its links and the calendars built on it go back to the default.
		$q -> setDelete('holiday');
		$q -> addWhere('holiday_calendar = ' . $calendar_id);
		$q -> exec();
		$q -> clear();
		$q -> setDelete('holiday_calendar_links');
		$q -> addWhere('link_calendar = ' . $calendar_id);
		$q -> exec();
		$q -> clear();
		$q -> addTable('holiday_calendars');
		$q -> addUpdate('calendar_parent', 0);
		$q -> addWhere('calendar_parent = ' . $calendar_id);
		$q -> exec();
		$q -> clear();
		$q -> setDelete('holiday_calendars');
		$q -> addWhere('calendar_id = ' . $calendar_id);
		$q -> exec();
		$AppUI->setMsg('Calendar deleted', UI_MSG_OK);
		$AppUI->redirect('m=holiday');
	}

	// Plain text, escaped where it is shown.
	$name = trim((string)dPgetParam($_POST, 'calendar_name', ''));
	$description = trim((string)dPgetParam($_POST, 'calendar_description', ''));
	$country = dPgetCleanParam($_POST, 'calendar_country', '');
	if (!array_key_exists($country, CHolidayCalendar::countries())) {
		$country = '';
	}
	// A parent that would make a loop (the calendar itself or one built on it) is dropped.
	$parent = (int)dPgetCleanParam($_POST, 'calendar_parent', 0);
	if (!isset($calendars[$parent]) || ($calendar_id && isset(holidayCalendarChain($parent)[$calendar_id]))) {
		$parent = 0;
	}
	if ($name === '') {
		$AppUI->setMsg('Give the calendar a name', UI_MSG_ERROR);
		$AppUI->redirect('m=holiday&a=calendar' . ($calendar_id ? '&calendar_id=' . $calendar_id : ''));
	}
	$q -> addTable('holiday_calendars');
	if ($calendar_id) {
		$q -> addUpdate('calendar_name', $name);
		$q -> addUpdate('calendar_parent', $parent);
		$q -> addUpdate('calendar_country', $country);
		$q -> addUpdate('calendar_description', $description);
		$q -> addWhere('calendar_id = ' . $calendar_id);
		$q -> exec();
	} else {
		$q -> addInsert('calendar_name', $name);
		$q -> addInsert('calendar_parent', $parent);
		$q -> addInsert('calendar_country', $country);
		$q -> addInsert('calendar_description', $description);
		$q -> exec();
		$calendar_id = (int)db_insert_id();
	}
	$q -> clear();

	// Whom the calendar is for. A company, department or user has one calendar:
	// choosing it here takes it from another calendar.
	$q -> setDelete('holiday_calendar_links');
	$q -> addWhere('link_calendar = ' . $calendar_id);
	$q -> exec();
	$q -> clear();
	foreach ($link_types as $type) {
		$ids = dPgetParam($_POST, 'link_' . $type, array());
		if (!is_array($ids)) {
			$ids = array();
		}
		foreach (array_unique(array_map('intval', $ids)) as $id) {
			if ($id <= 0) {
				continue;
			}
			$q -> addTable('holiday_calendar_links');
			$q -> addReplace('link_type', $type);
			$q -> addReplace('link_id', $id);
			$q -> addReplace('link_calendar', $calendar_id);
			$q -> exec();
			$q -> clear();
		}
	}
	$AppUI->setMsg('Calendar saved', UI_MSG_OK);
	$AppUI->redirect('m=holiday&a=calendar&calendar_id=' . $calendar_id);
}

// The days added from here come back to this page.
$AppUI->savePlace();

$calendar = $calendar_id ? $calendars[$calendar_id] : array('calendar_name' => '', 'calendar_parent' => 0,
	'calendar_country' => '', 'calendar_description' => '');
$links = holidayCalendarLinks();
$linked = array();
foreach ($link_types as $type) {
	$linked[$type] = array_keys(array_filter($links[$type], function ($id) use ($calendar_id) {
		return $calendar_id && $id == $calendar_id;
	}));
}

// The calendars it can build on: not itself, nor one that builds on it.
$parents = array(0 => $AppUI->_('None', UI_OUTPUT_RAW));
foreach ($calendars as $id => $row) {
	if (!$calendar_id || !isset(holidayCalendarChain($id)[$calendar_id])) {
		$parents[$id] = $row['calendar_name'];
	}
}
$settings = holidaySettings();
$default_country = isset($settings['holiday_country']) ? $settings['holiday_country'] : '';
$countries = array('' => $AppUI->_('As the calendar it builds on, or the default', UI_OUTPUT_RAW));
foreach (CHolidayCalendar::countries() as $code => $name) {
	$countries[$code] = $AppUI->_($name, UI_OUTPUT_RAW);
}

$q = new DBQuery();
$q -> addTable('companies');
$q -> addQuery('company_id, company_name, company_city');
$q -> addOrder('company_name');
$companies = array();
foreach ($q -> loadList() as $row) {
	$companies[$row['company_id']] = $row['company_name'] . ($row['company_city'] ? ' (' . $row['company_city'] . ')' : '');
}
$q -> addTable('departments', 'd');
$q -> leftJoin('companies', 'co', 'co.company_id = d.dept_company');
$q -> addQuery('d.dept_id, d.dept_name, d.dept_city, co.company_name');
$q -> addOrder('co.company_name, d.dept_name');
$departments = array();
foreach ($q -> loadList() as $row) {
	$departments[$row['dept_id']] = $row['company_name'] . ': ' . $row['dept_name'] . ($row['dept_city'] ? ' (' . $row['dept_city'] . ')' : '');
}
$q -> addTable('users', 'u');
$q -> leftJoin('contacts', 'c', 'c.contact_id = u.user_contact');
$q -> addQuery('u.user_id, u.user_username, c.contact_first_name, c.contact_last_name, c.contact_city');
$q -> addOrder('c.contact_last_name, c.contact_first_name, u.user_username');
$users = array();
foreach ($q -> loadList() as $row) {
	$name = trim($row['contact_first_name'] . ' ' . $row['contact_last_name']);
	$users[$row['user_id']] = ($name !== '' ? $name . ' (' . $row['user_username'] . ')' : $row['user_username'])
		. ($row['contact_city'] ? ', ' . $row['contact_city'] : '');
}

/** A multiple select of the options (id => label), the selected ids chosen. */
function holidayMultiSelect($name, $options, $selected) {
	$html = '<select name="' . $name . '[]" multiple="multiple" size="' . max(3, min(10, count($options))) . '" class="text">';
	foreach ($options as $id => $label) {
		$html .= '<option value="' . (int)$id . '"' . (in_array($id, $selected) ? ' selected="selected"' : '') . '>'
			. dPhtml($label) . '</option>';
	}
	return $html . '</select>';
}

$titleBlock = new CTitleBlock($calendar_id ? 'Edit calendar' : 'New calendar', 'calendar.gif', $m, "$m.$a");
$titleBlock->addCrumb('?m=holiday', 'Holidays');
$titleBlock->show();
?>
<script nonce="<?php echo dPcspNonce(); ?>">
function delCalendar() {
	if (confirm(<?php echo json_encode($AppUI->_('Delete this calendar and its days?', UI_OUTPUT_RAW), JSON_HEX_TAG); ?>)) {
		document.editCalendar.action.value = 'del';
		document.editCalendar.submit();
	}
}
</script>
<form name="editCalendar" method="post" action="?m=holiday&amp;a=calendar<?php echo $calendar_id ? '&amp;calendar_id=' . $calendar_id : ''; ?>">
<input name="action" type="hidden" value="save" />
<table border="0" cellpadding="4" cellspacing="0" width="100%" class="std">
<tr>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Name');?>:</td>
	<td width="100%"><input type="text" class="text" name="calendar_name" size="40" maxlength="100" value="<?php echo dPhtml($calendar['calendar_name']);?>" /></td>
</tr>
<tr>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Builds on');?>:</td>
	<td><?php echo arraySelect($parents, 'calendar_parent', 'size="1" class="text"', (int)$calendar['calendar_parent']);?>
		<?php echo $AppUI->_('it has the holidays of that calendar as well (a region for a city, for example)');?></td>
</tr>
<tr>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('National holidays');?>:</td>
	<td><?php echo arraySelect($countries, 'calendar_country', 'size="1" class="text"', $calendar['calendar_country']);?></td>
</tr>
<tr>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Description');?>:</td>
	<td><textarea name="calendar_description" class="textarea" cols="60" rows="2"><?php echo dPhtml((string)$calendar['calendar_description']);?></textarea></td>
</tr>
<tr>
	<td align="right" valign="top" nowrap="nowrap"><?php echo $AppUI->_('For');?>:</td>
	<td>
		<table cellpadding="2" cellspacing="0" border="0">
		<tr>
			<th align="left"><?php echo $AppUI->_('Companies');?></th>
			<th align="left"><?php echo $AppUI->_('Departments');?></th>
			<th align="left"><?php echo $AppUI->_('Users');?></th>
		</tr>
		<tr valign="top">
			<td><?php echo holidayMultiSelect('link_company', $companies, $linked['company']);?></td>
			<td><?php echo holidayMultiSelect('link_dept', $departments, $linked['dept']);?></td>
			<td><?php echo holidayMultiSelect('link_user', $users, $linked['user']);?></td>
		</tr>
		</table>
		<?php echo $AppUI->_('A user has the calendar given to them, else their department\'s (or a parent department\'s), else their company\'s, from their contact. The others have the default holidays.');?>
	</td>
</tr>
<tr>
	<td colspan="2" align="right">
		<?php if ($calendar_id) { ?>
		<input class="button" type="button" value="<?php echo $AppUI->_('delete');?>"<?php echo dPaction('delCalendar'); ?> />
		<?php } ?>
		<input class="button" type="button" value="<?php echo $AppUI->_('cancel');?>"<?php echo dPaction(null, array(), null, array('href' => '?m=holiday')); ?> />
		<input class="button" type="submit" value="<?php echo $AppUI->_('save');?>" />
	</td>
</tr>
</table>
</form>
<?php
if (!$calendar_id) {
	return;
}

// The calendar's own days, to add to those of the calendars it builds on.
$q -> addTable('holiday');
$q -> addQuery('*');
$q -> addWhere('holiday_calendar = ' . $calendar_id);
$q -> addOrder('holiday_start_date');
$days = $q -> loadList();
$df = $AppUI->getPref('SHDATEFORMAT');
?>
<h3><?php echo $AppUI->_('Days of this calendar');?></h3>
<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tbl">
<tr>
	<th width="10">&nbsp;</th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Description');?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Start date');?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_('End date');?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Annual');?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Type');?></th>
</tr>
<?php
foreach ($days as $row) {
	$start = new CDate($row['holiday_start_date']);
	$end = new CDate($row['holiday_end_date']);
	echo '<tr><td><a href="?m=holiday&amp;a=addedit&amp;holiday_id=' . (int)$row['holiday_id'] . '"><img src="./images/icons/pencil.gif" title="'
		. $AppUI->_($row['holiday_white'] ? 'Edit holiday' : 'Edit workday') . '" border="0" width="12" height="12" alt="" /></a></td>';
	echo '<td>' . dPhtml($row['holiday_description']) . '</td>';
	echo '<td nowrap="nowrap">' . $start->format($df) . '</td><td nowrap="nowrap">' . $end->format($df) . '</td>';
	echo '<td>' . $AppUI->_($row['holiday_annual'] ? 'Yes' : 'No') . '</td>';
	echo '<td>' . $AppUI->_($row['holiday_white'] ? 'Holiday' : 'Working day') . '</td></tr>';
}
if (!$days) {
	echo '<tr><td colspan="6">' . $AppUI->_('None yet: add the regional and local holidays of this workplace.') . '</td></tr>';
}
?>
</table>
<table width="100%" border="0" cellpadding="3" cellspacing="1">
<tr>
	<td align="right">
		<input class="button" type="button" value="<?php echo $AppUI->_('Add holiday');?>" <?php echo dPaction(null, array(), null, array('href' => '?m=holiday&a=addedit&white=1&calendar_id=' . $calendar_id)); ?> />
		<input class="button" type="button" value="<?php echo $AppUI->_('Add workday');?>" <?php echo dPaction(null, array(), null, array('href' => '?m=holiday&a=addedit&white=0&calendar_id=' . $calendar_id)); ?> />
	</td>
</tr>
</table>
<?php
// Every holiday of the calendar, this year and next: what the task dates skip.
$this_year = (int)date('Y');
foreach (array($this_year, $this_year + 1) as $year) {
	echo '<h3>' . $AppUI->_('Holidays') . ' ' . $year . '</h3>';
	echo '<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tbl">';
	foreach (holidayDays($calendar_id, "$year-01-01", "$year-12-31") as $day => $name) {
		$d = new CDate($day);
		if (preg_match('/^(.*) \((observed|substitute day)\)$/', $name, $parts)) {
			$name = $AppUI->_($parts[1], UI_OUTPUT_RAW) . ' (' . $AppUI->_($parts[2], UI_OUTPUT_RAW) . ')';
		} else {
			$name = $AppUI->_($name, UI_OUTPUT_RAW);
		}
		echo '<tr><td width="150" nowrap="nowrap">' . dPhtml($d->format($df)) . '</td><td>' . dPhtml($name) . '</td></tr>';
	}
	echo '</table>';
}
?>
