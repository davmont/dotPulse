<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
##
## holiday module - A dotProject module for keeping track of holidays
##
## Sensorlink AS (c) 2006
## Vegard Fiksdal (fiksdal@sensorlink.no)
##
require_once DP_BASE_DIR . '/modules/holiday/holiday_calendar.class.php';

/** The module settings (one row), read once per request. */
function holidaySettings() {
	static $settings = null;
	if ($settings === null) {
		$q = new DBQuery();
		$q -> addTable('holiday_settings');
		$q -> addQuery('*');
		$rows = $q -> loadList();
		$settings = $rows ? $rows[0] : array();
	}
	return $settings;
}

/** Whether the module is upgraded to 0.3, which has the calendars. */
function holidayHasCalendars() {
	static $has = null;
	if ($has === null) {
		$q = new DBQuery();
		$q -> addTable('modules');
		$q -> addQuery('mod_version');
		$q -> addWhere("mod_directory = 'holiday'");
		$has = version_compare((string)$q -> loadResult(), '0.3', '>=');
	}
	return $has;
}

/** All the calendars: calendar_id => row, by name. */
function holidayCalendars() {
	static $calendars = null;
	if ($calendars === null) {
		$calendars = array();
		if (holidayHasCalendars()) {
			$q = new DBQuery();
			$q -> addTable('holiday_calendars');
			$q -> addQuery('*');
			$q -> addOrder('calendar_name');
			$calendars = $q -> loadHashList('calendar_id');
		}
	}
	return $calendars;
}

/**
 * The calendar and the calendars it builds on (its parent, the parent's parent...),
 * nearest first: calendar_id => row. A loop of parents is cut where it closes.
 */
function holidayCalendarChain($calendar_id, $calendars = null) {
	if ($calendars === null) {
		$calendars = holidayCalendars();
	}
	$chain = array();
	$id = (int)$calendar_id;
	while ($id && isset($calendars[$id]) && !isset($chain[$id])) {
		$chain[$id] = $calendars[$id];
		$id = (int)$calendars[$id]['calendar_parent'];
	}
	return $chain;
}

/** The country of the national holidays: the nearest one the calendars set, or the default. */
function holidayCountry($calendar_id) {
	foreach (holidayCalendarChain($calendar_id) as $calendar) {
		if ($calendar['calendar_country'] !== '') {
			return $calendar['calendar_country'];
		}
	}
	$settings = holidaySettings();
	return isset($settings['holiday_country']) ? $settings['holiday_country'] : '';
}

/** The manual days (whitelist and blacklist) of a calendar: its own, its parents' and the shared ones. */
function holidayEntries($calendar_id) {
	static $cache = array();
	$ids = array_merge(array(0), array_keys(holidayCalendarChain($calendar_id)));
	$key = implode(',', $ids);
	if (!isset($cache[$key])) {
		$q = new DBQuery();
		$q -> addTable('holiday');
		$q -> addQuery('holiday_white, holiday_annual, holiday_start_date, holiday_end_date, holiday_description');
		if (holidayHasCalendars()) {
			$q -> addWhere('holiday_calendar IN (' . implode(',', array_map('intval', $ids)) . ')');
		}
		$cache[$key] = $q -> loadList();
	}
	return $cache[$key];
}

/** Whether a whitelist or blacklist row covers the day ('Y-m-d'); annual rows every year. */
function holidayEntryCovers($row, $day) {
	$start = substr($row['holiday_start_date'], 0, 10);
	$end = substr($row['holiday_end_date'], 0, 10);
	if ($row['holiday_annual']) {
		return substr($start, 5) <= substr($day, 5) && substr($day, 5) <= substr($end, 5);
	}
	return $start <= $day && $day <= $end;
}

/**
 * The holiday on a day ('Y-m-d'): its name ('' for a whitelist day without a
 * description), or null for a day that is not a holiday. The blacklist wins over
 * the whitelist, and both over the national holidays of the country.
 */
function holidayLookup($entries, $country, $day) {
	foreach ($entries as $row) {
		if (!$row['holiday_white'] && holidayEntryCovers($row, $day)) {
			return null;
		}
	}
	foreach ($entries as $row) {
		if ($row['holiday_white'] && holidayEntryCovers($row, $day)) {
			return (string)$row['holiday_description'];
		}
	}
	return $country !== '' ? CHolidayCalendar::holidayName($country, $day) : null;
}

/** The holiday on a date (CDate) in a calendar (0: the default), as holidayLookup(). */
function holidayName($date, $calendar_id = 0) {
	$settings = holidaySettings();
	$entries = empty($settings['holiday_manual']) ? array() : holidayEntries($calendar_id);
	$country = empty($settings['holiday_auto']) ? '' : holidayCountry($calendar_id);
	return holidayLookup($entries, $country, $date->format('%Y-%m-%d'));
}

function isHoliday( $date=0, $calendar_id=0 ){
	if(!$date)
	{
		$date=new CDate;
	}
	return holidayName($date, $calendar_id) === null ? 0 : 1;
}

/** The holidays of a calendar from one day to another ('Y-m-d'), working days or not: 'Y-m-d' => name. */
function holidayDays($calendar_id, $from, $to) {
	$days = array();
	$d = new CDate($from . ' 00:00:00');
	while ($d->format('%Y-%m-%d') <= $to) {
		$name = holidayName($d, $calendar_id);
		if ($name !== null) {
			$days[$d->format('%Y-%m-%d')] = $name;
		}
		$d->addDays(1);
	}
	return $days;
}

/** The people, departments and companies each calendar is for: type => id => calendar_id. */
function holidayCalendarLinks() {
	static $links = null;
	if ($links === null) {
		$links = array('user' => array(), 'dept' => array(), 'company' => array());
		if (holidayHasCalendars()) {
			$q = new DBQuery();
			$q -> addTable('holiday_calendar_links');
			$q -> addQuery('link_type, link_id, link_calendar');
			foreach ($q -> loadList() as $row) {
				$links[$row['link_type']][(int)$row['link_id']] = (int)$row['link_calendar'];
			}
		}
	}
	return $links;
}

/**
 * The calendar of a user's workplace: the one given to the user, else to their
 * department (or the nearest parent department), else to their company; 0 (the
 * default holidays) when none is.
 */
function holidayCalendarForUser($user_id) {
	static $cache = array();
	$user_id = (int)$user_id;
	if (!$user_id || !holidayHasCalendars()) {
		return 0;
	}
	if (!isset($cache[$user_id])) {
		$links = holidayCalendarLinks();
		$calendar = isset($links['user'][$user_id]) ? $links['user'][$user_id] : 0;
		if (!$calendar) {
			$q = new DBQuery();
			$q -> addTable('users', 'u');
			$q -> leftJoin('contacts', 'c', 'c.contact_id = u.user_contact');
			$q -> addQuery('u.user_company, u.user_department, c.contact_company, c.contact_department');
			$q -> addWhere('u.user_id = ' . $user_id);
			$user = $q -> loadHash();
			$q -> clear();
			// The contact's company and department are ids kept as text.
			$dept = $user ? ((int)$user['contact_department'] ?: (int)$user['user_department']) : 0;
			$company = $user ? ((int)$user['contact_company'] ?: (int)$user['user_company']) : 0;
			$seen = array();
			while ($dept && !$calendar && !isset($seen[$dept])) {
				$seen[$dept] = true;
				if (isset($links['dept'][$dept])) {
					$calendar = $links['dept'][$dept];
				} else {
					$q -> addTable('departments');
					$q -> addQuery('dept_parent');
					$q -> addWhere('dept_id = ' . $dept);
					$dept = (int)$q -> loadResult();
					$q -> clear();
				}
			}
			if (!$calendar && isset($links['company'][$company])) {
				$calendar = $links['company'][$company];
			}
		}
		$cache[$user_id] = $calendar;
	}
	return $cache[$user_id];
}
?>
