<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
##
## holiday module - the holiday calendars of the workplaces
##
require_once DP_BASE_DIR . '/modules/holiday/holiday_func.php';

$AppUI->savePlace();

if (!holidayHasCalendars()) {
	echo '<p class="error">' . $AppUI->_('Upgrade the Holiday module in System Admin, Modules, to use calendars.') . '</p>';
	return;
}

$calendars = holidayCalendars();
$countries = CHolidayCalendar::countries();
$links = holidayCalendarLinks();
$settings = holidaySettings();

// Whom each calendar is given to, by name.
$q = new DBQuery();
$q -> addTable('companies');
$q -> addQuery('company_id, company_name');
$company_names = $q -> loadHashList();
$q -> addTable('departments');
$q -> addQuery('dept_id, dept_name');
$dept_names = $q -> loadHashList();
$q -> addTable('users', 'u');
$q -> leftJoin('contacts', 'c', 'c.contact_id = u.user_contact');
$q -> addQuery('u.user_id, u.user_username, c.contact_first_name, c.contact_last_name, c.contact_city, c.contact_country');
$q -> addOrder('c.contact_last_name, c.contact_first_name, u.user_username');
$users = $q -> loadHashList('user_id');
$given = array();
foreach (array('company' => $company_names, 'dept' => $dept_names) as $type => $names) {
	foreach ($links[$type] as $id => $calendar_id) {
		$given[$calendar_id][] = isset($names[$id]) ? $names[$id] : '#' . $id;
	}
}
foreach ($links['user'] as $id => $calendar_id) {
	$given[$calendar_id][] = isset($users[$id]) ? $users[$id]['user_username'] : '#' . $id;
}

if (empty($settings['holiday_manual']) || empty($settings['holiday_auto'])) {
	echo '<p>' . $AppUI->_('The manual or the automatic holidays are turned off in the Settings tab, for every calendar.') . '</p>';
}
?>
<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tbl">
<tr>
	<th width="10">&nbsp;</th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Calendar');?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Builds on');?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_('National holidays');?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_('For');?></th>
</tr>
<?php
foreach ($calendars as $id => $calendar) {
	$parent = (int)$calendar['calendar_parent'];
	$country = holidayCountry($id);
	echo '<tr><td><a href="?m=holiday&amp;a=calendar&amp;calendar_id=' . (int)$id . '"><img src="./images/icons/pencil.gif" title="'
		. $AppUI->_('Edit calendar') . '" border="0" width="12" height="12" alt="" /></a></td>';
	echo '<td><a href="?m=holiday&amp;a=calendar&amp;calendar_id=' . (int)$id . '">' . dPhtml($calendar['calendar_name']) . '</a>';
	if ($calendar['calendar_description'] !== null && $calendar['calendar_description'] !== '') {
		echo '<br />' . dPhtml($calendar['calendar_description']);
	}
	echo '</td><td>' . ($parent && isset($calendars[$parent]) ? dPhtml($calendars[$parent]['calendar_name']) : '&nbsp;') . '</td>';
	echo '<td>' . ($country !== '' && isset($countries[$country]) ? $AppUI->_($countries[$country]) : '&nbsp;') . '</td>';
	echo '<td>' . (isset($given[$id]) ? dPhtml(implode(', ', $given[$id])) : '&nbsp;') . '</td></tr>';
}
if (!$calendars) {
	echo '<tr><td colspan="5">' . $AppUI->_('No calendars: everybody has the default holidays.') . '</td></tr>';
}
?>
</table>
<table width="100%" border="0" cellpadding="3" cellspacing="1">
	<tr valign="top">
		<td align="left"><?php echo $AppUI->_('A calendar holds the holidays of a workplace: the national holidays of its country and its own regional and local days. Give it to companies, departments or people; tasks follow the calendar of their owner.');?></td>
		<td align="right"><input class="button" type="button" value="<?php echo $AppUI->_('Add calendar');?>" <?php echo dPaction(null, array(), null, array('href' => '?m=holiday&a=calendar')); ?>></td>
	</tr>
</table>

<h3><?php echo $AppUI->_('Calendar of each user');?></h3>
<table width="100%" border="0" cellpadding="3" cellspacing="1" class="tbl">
<tr>
	<th nowrap="nowrap"><?php echo $AppUI->_('User');?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_('City');?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Calendar');?></th>
</tr>
<?php
foreach ($users as $user_id => $user) {
	$calendar_id = holidayCalendarForUser($user_id);
	$name = trim($user['contact_first_name'] . ' ' . $user['contact_last_name']);
	$place = implode(', ', array_filter(array((string)$user['contact_city'], (string)$user['contact_country']), 'strlen'));
	echo '<tr><td>' . dPhtml($name !== '' ? $name . ' (' . $user['user_username'] . ')' : $user['user_username']) . '</td>';
	echo '<td>' . ($place !== '' ? dPhtml($place) : '&nbsp;') . '</td>';
	echo '<td>' . ($calendar_id && isset($calendars[$calendar_id]) ? dPhtml($calendars[$calendar_id]['calendar_name'])
		: $AppUI->_('Default holidays')) . '</td></tr>';
}
?>
</table>
