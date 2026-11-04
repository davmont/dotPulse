<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

/**
 * Public holidays by country, the automatic holidays of the holiday module (they
 * replace PEAR Date_Holidays in dotPulse 3.1.1). National holidays only: regional
 * and local ones change from year to year and go in the whitelist.
 */
class CHolidayCalendar {

	private static $cache = array();

	/** Country code => name, in the order the settings list them. */
	static function countries() {
		return array(
			'AT' => 'Austria',
			'BE' => 'Belgium',
			'FR' => 'France',
			'DE' => 'Germany',
			'IE' => 'Ireland',
			'IT' => 'Italy',
			'NL' => 'Netherlands',
			'NO' => 'Norway',
			'PT' => 'Portugal',
			'ES' => 'Spain',
			'SE' => 'Sweden',
			'GB' => 'United Kingdom (England and Wales)',
			'US' => 'United States (federal)',
		);
	}

	/** The holidays of a year, 'YYYY-MM-DD' => name, by date. */
	static function forYear($country, $year) {
		$year = (int)$year;
		$key = $country . $year;
		if (!isset(self::$cache[$key])) {
			// A holiday moved off a weekend can land in the year before or after.
			$all = self::holidays($country, $year - 1) + self::holidays($country, $year) + self::holidays($country, $year + 1);
			$days = array();
			foreach ($all as $date => $name) {
				if ((int)substr($date, 0, 4) === $year) {
					$days[$date] = $name;
				}
			}
			ksort($days);
			self::$cache[$key] = $days;
		}
		return self::$cache[$key];
	}

	/** The name of the holiday on a date ('YYYY-MM-DD'), or null. */
	static function holidayName($country, $date) {
		$days = self::forYear($country, substr($date, 0, 4));
		return isset($days[$date]) ? $days[$date] : null;
	}

	/** Easter Sunday as a Unix time (UTC midnight), Gregorian calendar. */
	static function easter($year) {
		$a = $year % 19;
		$b = intdiv($year, 100);
		$c = $year % 100;
		$d = intdiv($b, 4);
		$e = $b % 4;
		$f = intdiv($b + 8, 25);
		$g = intdiv($b - $f + 1, 3);
		$h = (19 * $a + $b - $d - $g + 15) % 30;
		$i = intdiv($c, 4);
		$k = $c % 4;
		$l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
		$m = intdiv($a + 11 * $h + 22 * $l, 451);
		$n = $h + $l - 7 * $m + 114;
		return gmmktime(0, 0, 0, intdiv($n, 31), $n % 31 + 1, $year);
	}

	private static function day($year, $month, $day) {
		return gmdate('Y-m-d', gmmktime(0, 0, 0, $month, $day, $year));
	}

	private static function fromEaster($year, $days) {
		return gmdate('Y-m-d', self::easter($year) + $days * 86400);
	}

	/** The nth weekday (0 Sunday .. 6 Saturday) of a month; n = -1 is the last one. */
	private static function nthWeekday($year, $month, $weekday, $n) {
		if ($n > 0) {
			$first = (int)gmdate('w', gmmktime(0, 0, 0, $month, 1, $year));
			return self::day($year, $month, 1 + ($weekday - $first + 7) % 7 + 7 * ($n - 1));
		}
		$last = (int)gmdate('t', gmmktime(0, 0, 0, $month, 1, $year));
		$lastWeekday = (int)gmdate('w', gmmktime(0, 0, 0, $month, $last, $year));
		return self::day($year, $month, $last - ($lastWeekday - $weekday + 7) % 7);
	}

	/** The first given weekday on or after a date. */
	private static function weekdayOnOrAfter($year, $month, $day, $weekday) {
		$w = (int)gmdate('w', gmmktime(0, 0, 0, $month, $day, $year));
		return self::day($year, $month, $day + ($weekday - $w + 7) % 7);
	}

	private static function weekday($date) {
		return (int)gmdate('w', strtotime($date . ' UTC'));
	}

	private static function shift($date, $days) {
		return gmdate('Y-m-d', strtotime($date . ' UTC') + $days * 86400);
	}

	/** The holidays of one year, before moving any off the weekend. */
	private static function holidays($country, $y) {
		$h = array();
		$moves = array(); // holidays that move to a weekday when they fall on a weekend
		switch ($country) {
		case 'AT':
			$h[self::day($y, 1, 1)] = "New Year's Day";
			$h[self::day($y, 1, 6)] = 'Epiphany';
			$h[self::fromEaster($y, 1)] = 'Easter Monday';
			$h[self::day($y, 5, 1)] = 'Labour Day';
			$h[self::fromEaster($y, 39)] = 'Ascension Day';
			$h[self::fromEaster($y, 50)] = 'Whit Monday';
			$h[self::fromEaster($y, 60)] = 'Corpus Christi';
			$h[self::day($y, 8, 15)] = 'Assumption Day';
			$h[self::day($y, 10, 26)] = 'National Day';
			$h[self::day($y, 11, 1)] = "All Saints' Day";
			$h[self::day($y, 12, 8)] = 'Immaculate Conception';
			$h[self::day($y, 12, 25)] = 'Christmas Day';
			$h[self::day($y, 12, 26)] = "St Stephen's Day";
			break;
		case 'BE':
			$h[self::day($y, 1, 1)] = "New Year's Day";
			$h[self::fromEaster($y, 1)] = 'Easter Monday';
			$h[self::day($y, 5, 1)] = 'Labour Day';
			$h[self::fromEaster($y, 39)] = 'Ascension Day';
			$h[self::fromEaster($y, 50)] = 'Whit Monday';
			$h[self::day($y, 7, 21)] = 'National Day';
			$h[self::day($y, 8, 15)] = 'Assumption Day';
			$h[self::day($y, 11, 1)] = "All Saints' Day";
			$h[self::day($y, 11, 11)] = 'Armistice Day';
			$h[self::day($y, 12, 25)] = 'Christmas Day';
			break;
		case 'DE':
			$h[self::day($y, 1, 1)] = "New Year's Day";
			$h[self::fromEaster($y, -2)] = 'Good Friday';
			$h[self::fromEaster($y, 1)] = 'Easter Monday';
			$h[self::day($y, 5, 1)] = 'Labour Day';
			$h[self::fromEaster($y, 39)] = 'Ascension Day';
			$h[self::fromEaster($y, 50)] = 'Whit Monday';
			$h[self::day($y, 10, 3)] = 'German Unity Day';
			if ($y == 2017) {
				$h[self::day($y, 10, 31)] = 'Reformation Day';
			}
			$h[self::day($y, 12, 25)] = 'Christmas Day';
			$h[self::day($y, 12, 26)] = 'Second Day of Christmas';
			break;
		case 'ES':
			$h[self::day($y, 1, 1)] = "New Year's Day";
			$h[self::day($y, 1, 6)] = 'Epiphany';
			$h[self::fromEaster($y, -2)] = 'Good Friday';
			$h[self::day($y, 5, 1)] = 'Labour Day';
			$h[self::day($y, 8, 15)] = 'Assumption Day';
			$h[self::day($y, 10, 12)] = 'National Day';
			$h[self::day($y, 11, 1)] = "All Saints' Day";
			$h[self::day($y, 12, 6)] = 'Constitution Day';
			$h[self::day($y, 12, 8)] = 'Immaculate Conception';
			$h[self::day($y, 12, 25)] = 'Christmas Day';
			break;
		case 'FR':
			$h[self::day($y, 1, 1)] = "New Year's Day";
			$h[self::fromEaster($y, 1)] = 'Easter Monday';
			$h[self::day($y, 5, 1)] = 'Labour Day';
			$h[self::day($y, 5, 8)] = 'Victory in Europe Day';
			$h[self::fromEaster($y, 39)] = 'Ascension Day';
			$h[self::fromEaster($y, 50)] = 'Whit Monday';
			$h[self::day($y, 7, 14)] = 'Bastille Day';
			$h[self::day($y, 8, 15)] = 'Assumption Day';
			$h[self::day($y, 11, 1)] = "All Saints' Day";
			$h[self::day($y, 11, 11)] = 'Armistice Day';
			$h[self::day($y, 12, 25)] = 'Christmas Day';
			break;
		case 'GB':
			$h[self::day($y, 1, 1)] = "New Year's Day";
			$h[self::fromEaster($y, -2)] = 'Good Friday';
			$h[self::fromEaster($y, 1)] = 'Easter Monday';
			$h[in_array($y, array(1995, 2020)) ? self::day($y, 5, 8) : self::nthWeekday($y, 5, 1, 1)] = 'Early May bank holiday';
			$spring = array(2002 => self::day($y, 6, 4), 2012 => self::day($y, 6, 4), 2022 => self::day($y, 6, 2));
			$h[isset($spring[$y]) ? $spring[$y] : self::nthWeekday($y, 5, 1, -1)] = 'Spring bank holiday';
			$h[self::nthWeekday($y, 8, 1, -1)] = 'Summer bank holiday';
			$h[self::day($y, 12, 25)] = 'Christmas Day';
			$h[self::day($y, 12, 26)] = 'Boxing Day';
			$once = array(
				'1999-12-31' => 'Millennium Celebrations',
				'2002-06-03' => 'Golden Jubilee',
				'2011-04-29' => 'Royal Wedding',
				'2012-06-05' => 'Diamond Jubilee',
				'2022-06-03' => 'Platinum Jubilee',
				'2022-09-19' => 'State Funeral of Queen Elizabeth II',
				'2023-05-08' => 'Coronation of King Charles III',
			);
			foreach ($once as $date => $name) {
				if ((int)substr($date, 0, 4) === $y) {
					$h[$date] = $name;
				}
			}
			$moves = array(self::day($y, 1, 1), self::day($y, 12, 25), self::day($y, 12, 26));
			break;
		case 'IE':
			$h[self::day($y, 1, 1)] = "New Year's Day";
			if ($y >= 2023) {
				$feb1 = self::day($y, 2, 1);
				$h[self::weekday($feb1) == 5 ? $feb1 : self::nthWeekday($y, 2, 1, 1)] = "St Brigid's Day";
			}
			$h[self::day($y, 3, 17)] = "St Patrick's Day";
			if ($y == 2022) {
				$h['2022-03-18'] = 'Day of Remembrance and Recognition';
			}
			$h[self::fromEaster($y, 1)] = 'Easter Monday';
			$h[self::nthWeekday($y, 5, 1, 1)] = 'May bank holiday';
			$h[self::nthWeekday($y, 6, 1, 1)] = 'June bank holiday';
			$h[self::nthWeekday($y, 8, 1, 1)] = 'August bank holiday';
			$h[self::nthWeekday($y, 10, 1, -1)] = 'October bank holiday';
			$h[self::day($y, 12, 25)] = 'Christmas Day';
			$h[self::day($y, 12, 26)] = "St Stephen's Day";
			$moves = array(self::day($y, 1, 1), self::day($y, 3, 17), self::day($y, 12, 25), self::day($y, 12, 26));
			break;
		case 'IT':
			$h[self::day($y, 1, 1)] = "New Year's Day";
			$h[self::day($y, 1, 6)] = 'Epiphany';
			$h[self::fromEaster($y, 1)] = 'Easter Monday';
			$h[self::day($y, 4, 25)] = 'Liberation Day';
			$h[self::day($y, 5, 1)] = 'Labour Day';
			$h[self::day($y, 6, 2)] = 'Republic Day';
			$h[self::day($y, 8, 15)] = 'Assumption Day';
			if ($y >= 2026) {
				$h[self::day($y, 10, 4)] = 'St Francis of Assisi';
			}
			$h[self::day($y, 11, 1)] = "All Saints' Day";
			$h[self::day($y, 12, 8)] = 'Immaculate Conception';
			$h[self::day($y, 12, 25)] = 'Christmas Day';
			$h[self::day($y, 12, 26)] = "St Stephen's Day";
			break;
		case 'NL':
			$h[self::day($y, 1, 1)] = "New Year's Day";
			$h[self::fromEaster($y, 1)] = 'Easter Monday';
			if ($y >= 2014) {
				$kings = self::day($y, 4, 27);
				$h[self::weekday($kings) == 0 ? self::day($y, 4, 26) : $kings] = "King's Day";
			} else {
				$queens = self::day($y, 4, 30);
				$h[self::weekday($queens) == 0 ? self::day($y, 4, 29) : $queens] = "Queen's Day";
			}
			$h[self::day($y, 5, 5)] = 'Liberation Day';
			$h[self::fromEaster($y, 39)] = 'Ascension Day';
			$h[self::fromEaster($y, 50)] = 'Whit Monday';
			$h[self::day($y, 12, 25)] = 'Christmas Day';
			$h[self::day($y, 12, 26)] = 'Second Day of Christmas';
			break;
		case 'NO':
			$h[self::day($y, 1, 1)] = "New Year's Day";
			$h[self::fromEaster($y, -3)] = 'Maundy Thursday';
			$h[self::fromEaster($y, -2)] = 'Good Friday';
			$h[self::fromEaster($y, 0)] = 'Easter Sunday';
			$h[self::fromEaster($y, 1)] = 'Easter Monday';
			$h[self::day($y, 5, 1)] = 'Labour Day';
			$h[self::day($y, 5, 17)] = 'Constitution Day';
			$h[self::fromEaster($y, 39)] = 'Ascension Day';
			$h[self::fromEaster($y, 49)] = 'Whit Sunday';
			$h[self::fromEaster($y, 50)] = 'Whit Monday';
			$h[self::day($y, 12, 25)] = 'Christmas Day';
			$h[self::day($y, 12, 26)] = "St Stephen's Day";
			break;
		case 'PT':
			// Corpus Christi, 5 October, All Saints' and 1 December were suspended in 2013-2015.
			$suspended = ($y >= 2013 && $y <= 2015);
			$h[self::day($y, 1, 1)] = "New Year's Day";
			$h[self::fromEaster($y, -2)] = 'Good Friday';
			$h[self::fromEaster($y, 0)] = 'Easter Sunday';
			$h[self::day($y, 4, 25)] = 'Freedom Day';
			$h[self::day($y, 5, 1)] = 'Labour Day';
			if (!$suspended) {
				$h[self::fromEaster($y, 60)] = 'Corpus Christi';
			}
			$h[self::day($y, 6, 10)] = 'Portugal Day';
			$h[self::day($y, 8, 15)] = 'Assumption Day';
			if (!$suspended) {
				$h[self::day($y, 10, 5)] = 'Republic Day';
				$h[self::day($y, 11, 1)] = "All Saints' Day";
				$h[self::day($y, 12, 1)] = 'Restoration of Independence';
			}
			$h[self::day($y, 12, 8)] = 'Immaculate Conception';
			$h[self::day($y, 12, 25)] = 'Christmas Day';
			break;
		case 'SE':
			// Midsummer Eve, Christmas Eve and New Year's Eve are not public holidays
			// in law but are days off.
			$h[self::day($y, 1, 1)] = "New Year's Day";
			$h[self::day($y, 1, 6)] = 'Epiphany';
			$h[self::fromEaster($y, -2)] = 'Good Friday';
			$h[self::fromEaster($y, 0)] = 'Easter Sunday';
			$h[self::fromEaster($y, 1)] = 'Easter Monday';
			$h[self::day($y, 5, 1)] = 'May Day';
			$h[self::fromEaster($y, 39)] = 'Ascension Day';
			$h[self::fromEaster($y, 49)] = 'Whitsunday';
			if ($y >= 2005) {
				$h[self::day($y, 6, 6)] = 'National Day';
			}
			$h[self::weekdayOnOrAfter($y, 6, 19, 5)] = 'Midsummer Eve';
			$h[self::weekdayOnOrAfter($y, 6, 20, 6)] = 'Midsummer Day';
			$h[self::weekdayOnOrAfter($y, 10, 31, 6)] = "All Saints' Day";
			$h[self::day($y, 12, 24)] = 'Christmas Eve';
			$h[self::day($y, 12, 25)] = 'Christmas Day';
			$h[self::day($y, 12, 26)] = 'Boxing Day';
			$h[self::day($y, 12, 31)] = "New Year's Eve";
			break;
		case 'US':
			$h[self::day($y, 1, 1)] = "New Year's Day";
			if ($y >= 1986) {
				$h[self::nthWeekday($y, 1, 1, 3)] = 'Martin Luther King Jr. Day';
			}
			$h[self::nthWeekday($y, 2, 1, 3)] = "Washington's Birthday";
			$h[self::nthWeekday($y, 5, 1, -1)] = 'Memorial Day';
			if ($y >= 2021) {
				$h[self::day($y, 6, 19)] = 'Juneteenth';
			}
			$h[self::day($y, 7, 4)] = 'Independence Day';
			$h[self::nthWeekday($y, 9, 1, 1)] = 'Labor Day';
			$h[self::nthWeekday($y, 10, 1, 2)] = 'Columbus Day';
			$h[self::day($y, 11, 11)] = 'Veterans Day';
			$h[self::nthWeekday($y, 11, 4, 4)] = 'Thanksgiving Day';
			$h[self::day($y, 12, 25)] = 'Christmas Day';
			// Observed on the Friday before or the Monday after.
			foreach (array_keys($h) as $date) {
				$w = self::weekday($date);
				if ($w == 6 || $w == 0) {
					$h[self::shift($date, $w == 6 ? -1 : 1)] = $h[$date] . ' (observed)';
				}
			}
			break;
		}
		// The UK and Ireland give the next weekday that is not already a holiday.
		foreach ($moves as $date) {
			$w = self::weekday($date);
			if ($w != 6 && $w != 0) {
				continue;
			}
			$sub = $date;
			do {
				$sub = self::shift($sub, 1);
				$sw = self::weekday($sub);
			} while ($sw == 6 || $sw == 0 || isset($h[$sub]));
			$h[$sub] = $h[$date] . ' (substitute day)';
		}
		return $h;
	}
}
