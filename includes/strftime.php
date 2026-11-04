<?php
/**
 * strftime() replacement.
 *
 * strftime() and gmstrftime() are deprecated since PHP 8.1 and removed in PHP 9,
 * but dotProject and its bundled libraries (PEAR Date, ADOdb, ...) format
 * dates with them. Code calls dp_strftime()/dp_gmstrftime() instead:
 *
 * - while PHP still has strftime(), it is used (without the deprecation
 *   notice), so the output, including the month and day names of the locale set
 *   with setlocale(LC_TIME, ...), is exactly what it was;
 * - without it, the format is rebuilt from date(); month and day names come
 *   from IntlDateFormatter in the current LC_TIME locale when the intl
 *   extension is loaded, in English otherwise.
 *
 * When strftime()/gmstrftime() do not exist, they are defined here on top of
 * dp_strftime(), so library code that still calls them keeps working.
 */

if (!function_exists('dp_strftime')) {

	function dp_strftime($format, $timestamp = null)
	{
		return dp_strftime_impl((string)$format, $timestamp, false);
	}

	function dp_gmstrftime($format, $timestamp = null)
	{
		return dp_strftime_impl((string)$format, $timestamp, true);
	}

	function dp_strftime_impl($format, $timestamp, $gmt)
	{
		$timestamp = ($timestamp === null || $timestamp === '') ? time() : (int)$timestamp;
		$native = $gmt ? 'gmstrftime' : 'strftime';
		if (function_exists($native) && !defined('DP_STRFTIME_POLYFILL')) {
			// Deprecated, not broken: keep its exact, locale-aware output.
			return @$native($format, $timestamp);
		}
		return dp_strftime_format($format, $timestamp, $gmt);
	}

	/**
	 * The format rebuilt from date(); used when PHP has no strftime().
	 */
	function dp_strftime_format($format, $timestamp, $gmt)
	{
		$date = $gmt ? 'gmdate' : 'date';
		$out = '';
		$len = strlen($format);
		for ($i = 0; $i < $len; $i++) {
			$c = $format[$i];
			if ($c !== '%' || $i + 1 >= $len) {
				$out .= $c;
				continue;
			}
			$code = $format[++$i];
			switch ($code) {
				case 'a': $out .= dp_strftime_name('EEE', 'D', $timestamp, $gmt); break;
				case 'A': $out .= dp_strftime_name('EEEE', 'l', $timestamp, $gmt); break;
				case 'b':
				case 'h': $out .= dp_strftime_name('MMM', 'M', $timestamp, $gmt); break;
				case 'B': $out .= dp_strftime_name('MMMM', 'F', $timestamp, $gmt); break;
				case 'd': $out .= $date('d', $timestamp); break;
				case 'e': $out .= sprintf('%2d', $date('j', $timestamp)); break;
				case 'j': $out .= sprintf('%03d', (int)$date('z', $timestamp) + 1); break;
				case 'u': $out .= $date('N', $timestamp); break;
				case 'w': $out .= $date('w', $timestamp); break;
				case 'U': // week of the year, weeks starting on Sunday
					$out .= sprintf('%02d', (int)(((int)$date('z', $timestamp) + 7 - (int)$date('w', $timestamp)) / 7));
					break;
				case 'W': // week of the year, weeks starting on Monday
					$out .= sprintf('%02d', (int)(((int)$date('z', $timestamp) + 7 - (((int)$date('w', $timestamp) + 6) % 7)) / 7));
					break;
				case 'V': $out .= $date('W', $timestamp); break;
				case 'G': $out .= $date('o', $timestamp); break;
				case 'g': $out .= substr($date('o', $timestamp), -2); break;
				case 'm': $out .= $date('m', $timestamp); break;
				case 'y': $out .= $date('y', $timestamp); break;
				case 'Y': $out .= $date('Y', $timestamp); break;
				case 'C': $out .= sprintf('%02d', (int)((int)$date('Y', $timestamp) / 100)); break;
				case 'H': $out .= $date('H', $timestamp); break;
				case 'k': $out .= sprintf('%2d', $date('G', $timestamp)); break;
				case 'I': $out .= $date('h', $timestamp); break;
				case 'l': $out .= sprintf('%2d', $date('g', $timestamp)); break;
				case 'M': $out .= $date('i', $timestamp); break;
				case 'S': $out .= $date('s', $timestamp); break;
				case 'p': $out .= $date('A', $timestamp); break;
				case 'P': $out .= $date('a', $timestamp); break;
				case 'r': $out .= $date('h:i:s A', $timestamp); break;
				case 'R': $out .= $date('H:i', $timestamp); break;
				case 'T':
				case 'X': $out .= $date('H:i:s', $timestamp); break;
				case 'D':
				case 'x': $out .= $date('m/d/y', $timestamp); break;
				case 'F': $out .= $date('Y-m-d', $timestamp); break;
				case 'c':
					$out .= dp_strftime_name('EEE', 'D', $timestamp, $gmt) . ' '
						. dp_strftime_name('MMM', 'M', $timestamp, $gmt) . ' '
						. sprintf('%2d', $date('j', $timestamp)) . ' ' . $date('H:i:s Y', $timestamp);
					break;
				case 's': $out .= $timestamp; break;
				case 'z': $out .= $gmt ? '+0000' : $date('O', $timestamp); break;
				case 'Z': $out .= $gmt ? 'GMT' : $date('T', $timestamp); break;
				case 'n': $out .= "\n"; break;
				case 't': $out .= "\t"; break;
				case '%': $out .= '%'; break;
				default: $out .= '%' . $code; // unknown: left as it was, like strftime()
			}
		}
		return $out;
	}

	/**
	 * A month or day name in the LC_TIME locale (intl), or in English (date()).
	 */
	function dp_strftime_name($icuPattern, $dateFormat, $timestamp, $gmt)
	{
		if (class_exists('IntlDateFormatter')) {
			$locale = setlocale(LC_TIME, 0);
			$locale = ($locale === false || $locale === 'C' || $locale === 'POSIX')
				? 'en_US' : preg_replace('/[.@].*$/', '', $locale);
			$fmt = new IntlDateFormatter($locale, IntlDateFormatter::NONE, IntlDateFormatter::NONE,
				$gmt ? 'UTC' : date_default_timezone_get(), IntlDateFormatter::GREGORIAN, $icuPattern);
			$name = $fmt->format($timestamp);
			if ($name !== false) {
				return $name;
			}
		}
		return $gmt ? gmdate($dateFormat, $timestamp) : date($dateFormat, $timestamp);
	}
}

if (!function_exists('strftime')) {
	function strftime($format, $timestamp = null)
	{
		return dp_strftime($format, $timestamp);
	}
}

if (!function_exists('gmstrftime')) {
	function gmstrftime($format, $timestamp = null)
	{
		return dp_gmstrftime($format, $timestamp);
	}
}
