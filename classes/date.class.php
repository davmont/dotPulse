<?php /* CLASSES $Id$ */
/**
* @package dotproject
* @subpackage utilites
*/
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

define('FMT_DATEISO', '%Y%m%dT%H%M%S');
define('FMT_DATELDAP', '%Y%m%d%H%M%SZ');
define('FMT_DATETIME_MYSQL', '%Y-%m-%d %H:%M:%S');
define('FMT_DATERFC822', '%a, %d %b %Y %H:%M:%S');
define('FMT_TIMESTAMP', '%Y%m%d%H%M%S');
define('FMT_TIMESTAMP_DATE', '%Y%m%d');
define('FMT_TIMESTAMP_TIME', '%H%M%S');
define('FMT_UNIX', '3');
define('WDAY_SUNDAY',	  0);
define('WDAY_MONDAY',	  1);
define('WDAY_TUESDAY',	  2);
define('WDAY_WEDNESDAY', 3);
define('WDAY_THURSDAY',  4);
define('WDAY_FRIDAY',	  5);
define('WDAY_SATURDAY',  6);
define('SEC_MINUTE',	 60);
define('SEC_HOUR',	   3600);
define('SEC_DAY',	  86400);

// getDate()/setDate() formats, with the values of the PEAR Date package this class
// replaced in dotPulse 3.1.1.
define('DATE_FORMAT_ISO', 1);
define('DATE_FORMAT_ISO_BASIC', 2);
define('DATE_FORMAT_ISO_EXTENDED', 3);
define('DATE_FORMAT_TIMESTAMP', 4);
define('DATE_FORMAT_UNIXTIME', 5);

/**
* A date and time of day, in local (wall-clock) time.
*
* Until dotPulse 3.1.1 this extended the PEAR Date package (2,000 lines of which
* dotProject used a few methods). It keeps that API and its behaviour: invalid
* dates are refused and leave the object unchanged, adding months clamps the day
* to the end of the month, format() takes strftime codes with month and day names
* in the user's locale, and weeks start on Monday in the calendar helpers.
* Unlike PEAR Date, addSeconds() does not shift the clock across a daylight
* saving change: 09:00 plus one day of seconds is 09:00.
*/
class CDate {
	public $year;
	public $month;
	public $day;
	public $hour;
	public $minute;
	public $second;
	public $partsecond = 0.0;
	/** The holiday calendar of isWorkingDay() (holiday module); 0 is the default one. */
	public $holidayCalendar = 0;

	function __construct($date = null)
	{
		if (is_object($date)) {
			$this->copy($date);
		} else if (is_null($date)) {
			$this->setFromTime(time());
		} else {
			if (strlen((string)$date) == 12 && strpos($date, '-') === false && strpos($date, '/') === false) {
				$date = $date.'00';
			}
			$this->setDate($date);
		}
	}

	function copy($date)
	{
		foreach (array('year', 'month', 'day', 'hour', 'minute', 'second', 'partsecond') as $field) {
			$this->$field = $date->$field;
		}
		$this->holidayCalendar = isset($date->holidayCalendar) ? $date->holidayCalendar : 0;
	}

	/**
	* Counts the holidays of a calendar as non-working days (see isWorkingDay()):
	* the calendar of a workplace, from holidayCalendarForUser(). Returns $this.
	*/
	function setHolidayCalendar($calendar_id)
	{
		$this->holidayCalendar = (int)$calendar_id;
		return $this;
	}

	/**
	* The holiday calendar of a user's workplace, or 0 (the default holidays, also
	* when the holiday module is not active).
	*/
	static function holidayCalendarForUser($user_id)
	{
		global $AppUI;
		if (!(isset($AppUI) && is_object($AppUI) && $AppUI->isActiveModule('holiday'))) {
			return 0;
		}
		require_once DP_BASE_DIR . '/modules/holiday/holiday_func.php';
		return holidayCalendarForUser($user_id);
	}

	function isNull()
	{
		return is_null($this->year);
	}

	/**
	* Sets the date from an ISO 8601 string (YYYY-MM-DD HH:MM:SS, YYYYMMDDHHMMSS,
	* YYYY-MM-DD, ...) or, with DATE_FORMAT_UNIXTIME, from a Unix time stamp.
	* A string that is not a valid date leaves the object unchanged.
	*/
	function setDate($date, $format = DATE_FORMAT_ISO)
	{
		if ($format == DATE_FORMAT_UNIXTIME) {
			if (is_numeric($date)) {
				$this->setFromTime((int)$date);
			}
			return;
		}
		if (!preg_match('/^([0-9]{4,4})-?(' .
				'(0[1-9]|1[0-2])-?(0[1-9]|[12][0-9]|3[01])|' .     // [mm]-[dd]
				'W(0[1-9]|[1-4][0-9]|5[0-3])-?([1-7])|' .          // ISO week date
				'(0(0[1-9]|[1-9][0-9])|[12][0-9]{2,2}|3([0-5][0-9]|6[1-6]))' . // [ddd]
				')([T\s]?' .
				'([01][0-9]|2[0-3])(:?' .                           // [hh]
				'([0-5][0-9])(:?' .                                 // [mm]
				'([0-5][0-9]|60)([,.][0-9]+)?)?)?' .                // [ss]
				'(Z|[+\-][0-9]{2,2}(:?[0-5][0-9])?)?)?$/i',         // offset (ignored)
				(string)$date, $regs)) {
			return;
		}
		if (substr($regs[2], 0, 1) == 'W') {
			$d = new DateTime('@0');
			$d->setISODate((int)$regs[1], (int)$regs[5], (int)$regs[6]);
			list($year, $month, $day) = explode(' ', $d->format('Y n j'));
		} else if (strlen($regs[2]) == 3) {
			list($year, $month, $day) = self::daysToDate(self::dateToDays(1, 1, $regs[1]) + $regs[2] - 1);
		} else {
			list($year, $month, $day) = array($regs[1], $regs[3], $regs[4]);
			if (!checkdate((int)$month, (int)$day, (int)$year)) {
				return;
			}
		}
		$this->year = (int)$year;
		$this->month = (int)$month;
		$this->day = (int)$day;
		$this->hour = isset($regs[11]) && $regs[11] != '' ? (int)$regs[11] : 0;
		$this->minute = isset($regs[13]) && $regs[13] != '' ? (int)$regs[13] : 0;
		// PEAR Date kept a leap second (:60) as it came.
		$this->second = isset($regs[15]) && $regs[15] != '' ? (int)$regs[15] : 0;
		$this->partsecond = isset($regs[16]) && $regs[16] != '' ? (float)str_replace(',', '.', $regs[16]) : 0.0;
	}

	function setFromTime($timestamp)
	{
		list($this->year, $this->month, $this->day, $this->hour, $this->minute, $this->second)
			= array_map('intval', explode(' ', date('Y n j G i s', (int)$timestamp)));
		$this->partsecond = 0.0;
	}

	function setNow()
	{
		$this->setFromTime(time());
	}

	/** Unix time stamp of this local date and time. */
	function getTime()
	{
		return mktime((int)$this->hour, (int)$this->minute, (int)$this->second,
		              (int)$this->month, (int)$this->day, (int)$this->year);
	}

	function getDate($format = DATE_FORMAT_ISO)
	{
		switch ($format) {
		case DATE_FORMAT_ISO_BASIC:
			return $this->formatLikeStrftime('%Y%m%dT%H%M%S') . (date_default_timezone_get() == 'UTC' ? 'Z' : '');
		case DATE_FORMAT_ISO_EXTENDED:
			return $this->formatLikeStrftime('%Y-%m-%dT%H:%M:%S') . (date_default_timezone_get() == 'UTC' ? 'Z' : '');
		case DATE_FORMAT_TIMESTAMP:
			return $this->formatLikeStrftime('%Y%m%d%H%M%S');
		case DATE_FORMAT_UNIXTIME:
			return (string)$this->getTime();
		default:
			return $this->formatLikeStrftime('%Y-%m-%d %T');
		}
	}

/**
* strftime-style format, with month and day names in the base locale.
*/
	function format($format = NULL) {
		global $AppUI;
		$AppUI->setBaseLocale();
		$output = $this->formatLikeStrftime((string)$format);
		setlocale(LC_ALL, $AppUI->user_lang);
		return $output;
	}

	/** The strftime codes, computed from the fields so any date works (PEAR Date's set). */
	function formatLikeStrftime($format)
	{
		$output = '';
		$length = strlen($format);
		for ($i = 0; $i < $length; $i++) {
			$char = $format[$i];
			if ($char != '%' || $i + 1 >= $length) {
				$output .= $char;
				continue;
			}
			$next = $format[++$i];
			$hour12 = $this->hour % 12 == 0 ? 12 : $this->hour % 12;
			switch ($next) {
			case 'a': $output .= $this->getDayName(true); break;
			case 'A': $output .= $this->getDayName(); break;
			case 'b': $output .= $this->getMonthName(true); break;
			case 'B': $output .= $this->getMonthName(); break;
			case 'C': $output .= sprintf('%02d', intval($this->year / 100)); break;
			case 'd': $output .= sprintf('%02d', $this->day); break;
			case 'D': $output .= sprintf('%02d/%02d/%02d', $this->month, $this->day, $this->year); break;
			case 'e': $output .= $this->day; break;
			case 'E': $output .= self::dateToDays($this->day, $this->month, $this->year); break;
			case 'g': $output .= sprintf('%02d', $this->isoWeek()[0] % 100); break;
			case 'G': $output .= sprintf('%04d', $this->isoWeek()[0]); break;
			case 'h': $output .= sprintf('%d', $this->hour); break;
			case 'H': $output .= sprintf('%02d', $this->hour); break;
			case 'i': $output .= $hour12; break;
			case 'I': $output .= sprintf('%02d', $hour12); break;
			case 'j': $output .= sprintf('%03d', $this->getDayOfYear()); break;
			case 'm': $output .= sprintf('%02d', $this->month); break;
			case 'M': $output .= sprintf('%02d', $this->minute); break;
			case 'n': $output .= "\n"; break;
			case 'O':
			case 'o': $output .= date('P', $this->getTime()); break;
			case 'p': $output .= $this->hour >= 12 ? 'pm' : 'am'; break;
			case 'P': $output .= $this->hour >= 12 ? 'PM' : 'AM'; break;
			case 'r': $output .= sprintf('%02d:%02d:%02d %s', $hour12, $this->minute, $this->second, $this->hour >= 12 ? 'PM' : 'AM'); break;
			case 'R': $output .= sprintf('%02d:%02d', $this->hour, $this->minute); break;
			case 's': $output .= str_replace(',', '.', sprintf('%09f', (float)$this->second + $this->partsecond)); break;
			case 'S': $output .= sprintf('%02d', $this->second); break;
			case 't': $output .= "\t"; break;
			case 'T': $output .= sprintf('%02d:%02d:%02d', $this->hour, $this->minute, $this->second); break;
			case 'u': $output .= $this->getDayOfWeek() == 0 ? 7 : $this->getDayOfWeek(); break;
			case 'U': $output .= sprintf('%02d', self::weekOfYear7th($this->day, $this->month, $this->year, 0)[1]); break;
			case 'V': $output .= $this->isoWeek()[1]; break;
			case 'w': $output .= $this->getDayOfWeek(); break;
			case 'W': $output .= sprintf('%02d', self::weekOfYear7th($this->day, $this->month, $this->year, 1)[1]); break;
			case 'y': $output .= sprintf('%02d', $this->year % 100); break;
			case 'Y': $output .= sprintf('%04d', $this->year); break;
			case 'Z': $output .= date('T', $this->getTime()); break;
			case '%': $output .= '%'; break;
			default: $output .= $char . $next;
			}
		}
		return $output;
	}

	/** ISO 8601 year and week number. */
	private function isoWeek()
	{
		$d = new DateTime(sprintf('%04d-%02d-%02d', $this->year, $this->month, $this->day), new DateTimeZone('UTC'));
		return array((int)$d->format('o'), (int)$d->format('W'));
	}

	// Calendar arithmetic, on day numbers (the Julian day number, as PEAR Date_Calc).

	static function dateToDays($day, $month, $year)
	{
		// An empty date (null fields) counts as 0, as it did with PEAR Date_Calc.
		list($day, $month, $year) = array((int)$day, (int)$month, (int)$year);
		if ($month > 2) {
			$month -= 3;
		} else {
			$month += 9;
			--$year;
		}
		$century = intval($year / 100);
		$year = $year % 100;
		return intval(146097 * $century / 4) + intval(1461 * $year / 4)
			+ intval((153 * $month + 2) / 5) + $day + 1721119;
	}

	/** array(year, month, day) of a day number. */
	static function daysToDate($days)
	{
		$days = intval($days) - 1721119;
		$century = floor((4 * $days - 1) / 146097);
		$days = floor(4 * $days - 1 - 146097 * $century);
		$day = floor($days / 4);
		$year = floor((4 * $day + 3) / 1461);
		$day = floor(4 * $day + 3 - 1461 * $year);
		$day = floor(($day + 4) / 4);
		$month = floor((5 * $day - 3) / 153);
		$day = floor(5 * $day - 3 - 153 * $month);
		$day = floor(($day + 5) / 5);
		$year = $century * 100 + $year;
		if ($month < 10) {
			$month += 3;
		} else {
			$month -= 9;
			++$year;
		}
		return array((int)$year, (int)$month, (int)$day);
	}

	/** Week of the year: array(year, week); weeks start on $firstDay (0 Sunday, 1 Monday). */
	static function weekOfYear7th($day, $month, $year, $firstDay)
	{
		$wd1 = (self::dateToDays(1, 1, $year) + 1) % 7;
		$doy = self::dateToDays($day, $month, $year) - self::dateToDays(1, 1, $year) + 1;
		$week = floor(($doy + (6 + $wd1 - $firstDay) % 7) / 7);
		if ($week > 0) {
			return array((int)$year, (int)$week);
		}
		return self::weekOfYear7th(31, 12, $year - 1, $firstDay);
	}

	private function setDays($days)
	{
		list($this->year, $this->month, $this->day) = self::daysToDate($days);
	}

	function addDays($days)
	{
		$this->setDays(self::dateToDays($this->day, $this->month, $this->year) + (int)$days);
	}

	/** Adds months; a day past the end of the new month becomes its last day. */
	function addMonths($months)
	{
		$months = (int)$months;
		$month = $this->month + $months % 12;
		$year = $this->year + intval($months / 12);
		if ($month < 1) {
			$month += 12;
			--$year;
		} else if ($month > 12) {
			$month -= 12;
			++$year;
		}
		$this->year = $year;
		$this->month = $month;
		$this->day = min($this->day, cal_days_in_month_dp($month, $year));
	}

	function addYears($years)
	{
		$this->addMonths(12 * (int)$years);
	}

	/** Adds seconds to the wall-clock time (no daylight saving shift). */
	function addSeconds($sec)
	{
		$total = (self::dateToDays($this->day, $this->month, $this->year) * 86400)
			+ $this->hour * 3600 + $this->minute * 60 + $this->second + (int)$sec;
		$days = intdiv($total, 86400);
		$rest = $total - $days * 86400;
		if ($rest < 0) {
			--$days;
			$rest += 86400;
		}
		$this->setDays($days);
		$this->hour = intdiv($rest, 3600);
		$this->minute = intdiv($rest % 3600, 60);
		$this->second = $rest % 60;
	}

	function subtractSeconds($sec)
	{
		$this->addSeconds(-(int)$sec);
	}

	function addMinutes($minutes)
	{
		$this->addSeconds(60 * (int)$minutes);
	}

	function addHours($hours)
	{
		$this->addSeconds(3600 * (int)$hours);
	}

	/**
	* Compares two dates (CDate objects or strings): -1, 0 or 1. Callable on an
	* object or statically.
	*/
	static function compare($d1, $d2)
	{
		$d1 = is_object($d1) ? $d1 : new CDate($d1);
		$d2 = is_object($d2) ? $d2 : new CDate($d2);
		$days = self::dateToDays($d1->day, $d1->month, $d1->year) - self::dateToDays($d2->day, $d2->month, $d2->year);
		foreach (array($days, $d1->hour - $d2->hour, $d1->minute - $d2->minute, $d1->second - $d2->second,
		               $d1->partsecond - $d2->partsecond) as $difference) {
			if ($difference) {
				return $difference < 0 ? -1 : 1;
			}
		}
		return 0;
	}

	function before($when)
	{
		return self::compare($this, $when) == -1;
	}

	function after($when)
	{
		return self::compare($this, $when) == 1;
	}

	function equals($when)
	{
		return self::compare($this, $when) == 0;
	}

/**
* The difference in days to another date, whatever the order and the time of day.
* @param CDate $po_date The date to compare to
*/
	function dateDiff($po_date, $pb_ignoretime = false) {
		if (!checkdate((int)$this->month, (int)$this->day, (int)$this->year)
		    || !checkdate((int)$po_date->month, (int)$po_date->day, (int)$po_date->year)) {
			return -1;
		}
		return abs(self::dateToDays($this->day, $this->month, $this->year)
		           - self::dateToDays($po_date->day, $po_date->month, $po_date->year));
	}

	function getDayOfWeek()
	{
		return (self::dateToDays($this->day, $this->month, $this->year) + 1) % 7;
	}

	function getDayOfYear()
	{
		return self::dateToDays($this->day, $this->month, $this->year) - self::dateToDays(1, 1, $this->year) + 1;
	}

	function getDaysInMonth()
	{
		return cal_days_in_month_dp($this->month, $this->year);
	}

	function isLeapYear()
	{
		return checkdate(2, 29, (int)$this->year);
	}

	/** Day name in the current locale (as strftime %A or %a). */
	function getDayName($abbr = false, $length = 3)
	{
		return dp_strftime($abbr ? '%a' : '%A', mktime(0, 0, 0, 1, 7 + $this->getDayOfWeek(), 2001));
	}

	/** Month name in the current locale (as strftime %B or %b). */
	function getMonthName($abbr = false)
	{
		return dp_strftime($abbr ? '%b' : '%B', mktime(0, 0, 0, (int)$this->month, 1, 2001));
	}

	function getNextDay()
	{
		$next = new CDate($this);
		$next->addDays(1);
		return $next;
	}

	function getPrevDay()
	{
		$prev = new CDate($this);
		$prev->addDays(-1);
		return $prev;
	}

	function getYear() { return $this->year; }
	function getMonth() { return $this->month; }
	function getDay() { return $this->day; }
	function getHour() { return $this->hour; }
	function getMinute() { return $this->minute; }
	function getSecond() { return $this->second; }

	// As with PEAR Date, setYear(), setMonth() and setDay() do not validate, so a
	// date can be changed one field at a time (31 January: setMonth(2), setDay(1));
	// setDayMonthYear() and the time setters refuse an invalid value.

	function setYear($y)
	{
		$this->year = (int)$y;
	}

	function setMonth($m)
	{
		$this->month = (int)$m;
	}

	function setDay($d)
	{
		$this->day = (int)$d;
	}

	function setDayMonthYear($d, $m, $y)
	{
		if (checkdate((int)$m, (int)$d, (int)$y)) {
			list($this->day, $this->month, $this->year) = array((int)$d, (int)$m, (int)$y);
		}
	}

	function setHour($h)
	{
		if ($h >= 0 && $h <= 23) {
			$this->hour = (int)$h;
		}
	}

	function setMinute($m)
	{
		if ($m >= 0 && $m <= 59) {
			$this->minute = (int)$m;
		}
	}

	function setSecond($s)
	{
		if ($s >= 0 && $s <= 59) {
			$this->second = (int)$s;
			$this->partsecond = 0.0;
		}
	}

	function setHourMinuteSecond($h, $m, $s)
	{
		$this->setTime($h, $m, $s);
	}

/**
* New method that sets hour, minute and second in a single call
* @param int hour
* @param int minute
* @param int second
* @author Andrew Eddie <eddieajau@users.sourceforge.net>
*/
	function setTime($h=0, $m=0, $s=0) {
		$this->setHour($h);
		$this->setMinute($m);
		$this->setSecond($s);
	}

	/** Sets the time of day from hours with a fraction (9.5 is 09:30). */
	function setHours($hours) {
		$minutes = (int)round($hours * 60);
		$this->setTime(intdiv($minutes, 60), $minutes % 60, 0);
	}

	/** The time of day in hours with a fraction (09:30 is 9.5). */
	function getHours() {
		return $this->hour + $this->minute / 60;
	}

	// Calendar helpers (PEAR Date_Calc's, used by the calendar and the reports).
	// Weeks start on Monday, as they did with Date_Calc.

	/** The Monday of the week of a date, formatted (default YYYYMMDD). */
	static function beginOfWeek($day, $month, $year, $format = '%Y%m%d')
	{
		$days = self::dateToDays($day, $month, $year);
		return self::formatDays($days - (6 + ($days + 1) % 7) % 7, $format);
	}

	/** The Sunday of the week of a date, formatted (default YYYYMMDD). */
	static function endOfWeek($day, $month, $year, $format = '%Y%m%d')
	{
		$days = self::dateToDays($day, $month, $year);
		return self::formatDays($days + (7 - ($days + 1) % 7) % 7, $format);
	}

	/** Week number, weeks starting on Sunday (Date_Calc::weekOfYear). */
	static function weekNumber($day, $month, $year)
	{
		return self::weekOfYear7th($day, $month, $year, 0)[1];
	}

	/** Last day of a month, formatted (default YYYYMMDD). */
	static function endOfMonth($month, $year, $format = '%Y%m%d')
	{
		return self::formatDays(self::dateToDays(cal_days_in_month_dp($month, $year), $month, $year), $format);
	}

	/** First day of the month before or after a month, formatted (default YYYYMMDD). */
	static function beginOfMonthOffset($months, $month, $year, $format = '%Y%m%d')
	{
		$date = new CDate(sprintf('%04d-%02d-01', $year, $month));
		$date->addMonths($months);
		return $date->formatLikeStrftime($format);
	}

	/** The seven day names of a week, from Monday, in the current locale. */
	static function weekdayNames($abbr = true)
	{
		$names = array();
		for ($i = 1; $i <= 7; $i++) {
			$names[] = dp_strftime($abbr ? '%a' : '%A', mktime(0, 0, 0, 1, $i, 2001)); // 2001-01-01 was a Monday
		}
		return $names;
	}

	/** The weeks (Monday to Sunday) that show a month, each a list of seven formatted dates. */
	static function calendarMonth($month, $year, $format)
	{
		$first = self::dateToDays(1, $month, $year);
		$start = $first - (6 + ($first + 1) % 7) % 7;
		$end = self::dateToDays(cal_days_in_month_dp($month, $year), $month, $year);
		$weeks = array();
		for ($days = $start; $days <= $end; $days += 7) {
			$week = array();
			for ($i = 0; $i < 7; $i++) {
				$week[] = self::formatDays($days + $i, $format);
			}
			$weeks[] = $week;
		}
		return $weeks;
	}

	private static function formatDays($days, $format)
	{
		list($year, $month, $day) = self::daysToDate($days);
		$date = new CDate(sprintf('%04d-%02d-%02d', $year, $month, $day));
		return $date->formatLikeStrftime($format);
	}

	function getAMPM() {
		return $this->format('%p');
	}
	
	function isWorkingDay() {
		global $AppUI;
		global $baseDir;
		
		// Holidays only count when the holiday module is installed and active.
		// Its PEAR Holidays code is fatal on PHP 8 and was loaded unconditionally,
		// so every working-day check (task reminders, next_working_day()) crashed.
		if (isset($AppUI) && is_object($AppUI) && $AppUI->isActiveModule('holiday')) {
			require_once $baseDir."/modules/holiday/holiday_func.php";
			if (isHoliday($this, $this->holidayCalendar)) {
				return 0;
			}
		}
		$working_days = dPgetConfig("cal_working_days");
		$working_days = ((is_null($working_days)) ? array('1','2','3','4','5') : explode(",", $working_days));
		return in_array($this->getDayOfWeek(), $working_days);
	}
	
	
	/* Return date obj for the end of the next working day
	 ** @param	bool	Determine whether to set time to start of day or preserve the time of the given object
	 */ 
	function next_working_day($preserveHours = false) {
		global $AppUI;
		$do = $this;
		$end = intval(dPgetConfig('cal_day_end'));
		$start = intval(dPgetConfig('cal_day_start'));
		while (! $this->isWorkingDay() || $this->getHour() > $end ||
				($preserveHours == false && $this->getHour() == $end && $this->getMinute() == '0')) {
			$this->addDays(1);
			$this->setTime($start, '0', '0');
		}
		
		if ($preserveHours) {
			$this->setTime($do->getHour(), '0', '0');
		}
		
		return $this;
	}
	
	
	/* Return date obj for the end of the previous working day
	 ** @param	bool	Determine whether to set time to end of day or preserve the time of the given object
	 */ 
	function prev_working_day($preserveHours = false) {
		global $AppUI;
		$do = $this;
		$end = intval(dPgetConfig('cal_day_end'));
		$start = intval(dPgetConfig('cal_day_start'));
		while (! $this->isWorkingDay() || ($this->getHour() < $start) ||
				($this->getHour() == $start && $this->getMinute() == '0')) {
			$this->addDays(-1);
			$this->setTime($end, '0', '0');
		}
		if ($preserveHours) {
			$this->setTime($do->getHour(), '0', '0');
		}
		
		return $this;
	}
	
	
	/* Calculating _robustly_ a date from a given date and duration
	 ** Works in both directions: forwards/prospective and backwards/retrospective
	 ** Respects non-working days
	 ** @param	int	duration	(positive = forward, negative = backward)
	 ** @param	int	durationType; 1 = hour; 24 = day;
	 ** @return	obj	Shifted DateObj
	 */ 
	
	function addDuration($duration = '8', $durationType ='1') {
		// using a sgn function lets us easily cover 
		// prospective and retrospective calcs at the same time
		
		// get signum of the duration
		$sgn = dPsgn($duration);
		
		// make duration positive
		$duration = abs($duration);
		
		if ($durationType == '24') { // duration type is 24, full days, we're finished very quickly
			$full_working_days = $duration;
		} else if ($durationType == '1') { // durationType is 1 hour
		// get dP time constants
			$cal_day_start = intval(dPgetConfig('cal_day_start'));
			$cal_day_end = intval(dPgetConfig('cal_day_end'));
			$dwh = floatval(dPgetConfig('daily_working_hours'));
			
			// move to the next working day if the first day is a non-working day
			($sgn > 0) ? $this->next_working_day() : $this->prev_working_day();
			
			// calculate the hours spent on the first day (durations can have fractions of an hour)
			$now = $this->getHours();
			$firstDay = ($sgn > 0) ? min($cal_day_end - $now, $dwh) : min($now - $cal_day_start, $dwh);
			
			/*
			 ** Catch some possible inconsistencies:
			 ** If we're later than cal_end_day or sooner than cal_start_day then we don't need to
			 ** subtract any time from duration. The difference is greater than the # of daily working hours
			 */
			if ($firstDay < 0) {
				$firstDay = 0;
			}
			// Intraday additions are handled easily by just changing the time
			if ($duration <= $firstDay) {
				$this->setHours($now + $sgn * $duration);
				return $this;
			}
			
			// subtract the first day hours from the total duration
			$duration -= $firstDay;
			
			// we've already processed the first day; move by one day!
			$this->addDays(1 * $sgn);
			
			// make sure that we didn't move to a non-working day
			($sgn > 0) ? $this->next_working_day() : $this->prev_working_day();
			
			// end of proceeding the first day
			
			// calc the remaining time and the full working days part of this residual
			// (rounded to the minute, so 0.1 + 0.2 hours does not leave a sliver)
			$full_working_days = (int)floor(round($duration * 60) / round($dwh * 60));
			$hoursRemaining = round($duration * 60 - $full_working_days * round($dwh * 60)) / 60;
			
			// (proceed the full days later)
			
			// proceed the last day now
			
			// we prefer wed 16:00 over thu 08:00 as end date :)
			if ($hoursRemaining == 0) {
				$full_working_days--;
				$hoursRemaining = $dwh;
			}
			$this->setHours(($sgn > 0) ? $cal_day_start + $hoursRemaining : $cal_day_end - $hoursRemaining);
			//end of proceeding the last day
		}
		
		// proceeding the fulldays finally which is easy
		// Full days
		for ($i = 0 ; $i < $full_working_days ; $i++) {
			$this->addDays(1 * $sgn);
			if (!$this->isWorkingDay()) {
				// just 'ignore' this non-working day		
				$full_working_days++;
			}
		}
		//end of proceeding the fulldays
		
		return $this->next_working_day();
	}
	
	
	/* Calculating _robustly_ the working duration between two dates
	 **
	 ** Works in both directions: forwards/prospective and backwards/retrospective
	 ** Respects non-working days
	 **
	 **
	 ** @param	obj	DateObject	may be viewed as end date
	 ** @return	float						working duration in hours
	 */ 
	function calcDuration($e) {
		
		// since one will alter the date ($this) one better copies it to a new instance
		$s = new CDate();
		$s->copy($this);
		
		// get dP time constants
		$cal_day_start = intval(dPgetConfig('cal_day_start'));
		$cal_day_end = intval(dPgetConfig('cal_day_end'));
		$dwh = floatval(dPgetConfig('daily_working_hours'));
		
		// assume start is before end and set a default signum for the duration	
		$sgn = 1;
		
		// check whether start before end, interchange otherwise (count from the earlier
		// date; copying into $s used to overwrite the start date it was meant to keep)
		if ($e->before($s)) {
			// calculated duration must be negative, set signum appropriately
			$sgn = -1;
			
			$end = new CDate();
			$end->copy($s);
			$s->copy($e);
			$e = $end;
		}	 
		
		// determine the (working + non-working) day difference between the two dates
		$days = abs($e->dateDiff($s));
		
		// if it is an intraday difference one is finished very easily
		if ($days == 0) {
			return (float)(min($dwh, $e->getHours() - $s->getHours()) * $sgn);
		}
		
		// initialize the duration var
		$duration = 0;
		
		// process the first day
		// take into account the first day if it is a working day!
		$duration += $s->isWorkingDay() ? max(0, min($dwh, $cal_day_end - $s->getHours())) : 0;
		$s->addDays(1);
		
		// end of processing the first day
		
		// calc workingdays between start and end
		for ($i=1; $i < $days; $i++) {
			$duration += $s->isWorkingDay() ? $dwh : 0;
			$s->addDays(1);
		}
		
		// take into account the last day in span only if it is a working day!
		$duration += $s->isWorkingDay() ? max(0, min($dwh, $e->getHours() - $cal_day_start)) : 0;
		
		return (float)($duration * $sgn);
	}	
	
	function workingDaysInSpan($e) {
		global $AppUI;
		
		// assume start is before end and set a default signum for the duration	
		$sgn = 1;
		
		// check whether start before end, interchange otherwise
		if ($e->before($this)) {
			// duration is negative, set signum appropriately
			$sgn = -1;
		}	 
		
		$wd = 0;
		$days = $e->dateDiff($this);
		$start = $this;
		
		for ($i = 0 ; $i <= $days ; $i++) {
			if ($start->isWorkingDay()) {
				$wd++;
			}
			$start->addDays(1 * $sgn);
		}
		
		return $wd;
	}
}

/** Days in a month (cal_days_in_month() needs the calendar extension). */
function cal_days_in_month_dp($month, $year)
{
	return (int)date('t', mktime(0, 0, 0, (int)$month, 1, (int)$year));
}
?>
