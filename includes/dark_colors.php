<?php
/**
 * Colours for a dark theme (style/dotpulse-dark): the page output filter that adapts the
 * colours templates and data write into the markup (bgcolor and color attributes, style
 * attributes, <style> blocks), which the theme's stylesheet cannot reach.
 *
 * - A light background (white cells, pastel status colours) becomes a dark one of the same
 *   hue; the lighter it was, the darker it gets, so white is the theme's surface and the
 *   shades of a table stay apart.
 * - Text too dark to read on the theme's surface becomes light, same hue, until it reaches
 *   a contrast of 4.5:1 (WCAG AA).
 * - Mid and saturated backgrounds (project colours, bars) stay: so does the text colour
 *   chosen for them, when the same tag sets both.
 * - Light borders become dark lines and black ones a visible grey.
 */
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

/** The dark theme's surface (style/dotpulse-dark/main.css --dp-surface): what white becomes. */
define('DP_DARK_SURFACE', '#1A202B');

/** #rgb, #rrggbb or a common colour name as array(r, g, b) of 0..255, or null. */
function dPdarkParseColor($value)
{
	static $names = array(
		'white' => 'ffffff', 'black' => '000000', 'silver' => 'c0c0c0', 'gray' => '808080',
		'grey' => '808080', 'lightgrey' => 'd3d3d3', 'lightgray' => 'd3d3d3', 'gainsboro' => 'dcdcdc',
		'whitesmoke' => 'f5f5f5', 'navy' => '000080', 'maroon' => '800000', 'darkblue' => '00008b',
		'darkred' => '8b0000', 'darkgreen' => '006400', 'red' => 'ff0000', 'blue' => '0000ff',
		'green' => '008000', 'yellow' => 'ffff00', 'ivory' => 'fffff0', 'beige' => 'f5f5dc',
		'lightyellow' => 'ffffe0', 'lightblue' => 'add8e6', 'lightgreen' => '90ee90',
	);
	$v = strtolower(trim($value));
	if (isset($names[$v])) {
		$v = $names[$v];
	} else if (preg_match('/^#?([0-9a-f]{3}|[0-9a-f]{6})$/', $v, $m)) {
		$v = $m[1];
		if (strlen($v) === 3) {
			$v = $v[0] . $v[0] . $v[1] . $v[1] . $v[2] . $v[2];
		}
	} else {
		return null;
	}
	return array(hexdec(substr($v, 0, 2)), hexdec(substr($v, 2, 2)), hexdec(substr($v, 4, 2)));
}

function dPdarkHex($rgb)
{
	return sprintf('#%02X%02X%02X', $rgb[0], $rgb[1], $rgb[2]);
}

/** Relative luminance (WCAG). */
function dPdarkLuminance($rgb)
{
	$c = array();
	foreach ($rgb as $i => $v) {
		$v /= 255;
		$c[$i] = $v <= 0.03928 ? $v / 12.92 : pow(($v + 0.055) / 1.055, 2.4);
	}
	return 0.2126 * $c[0] + 0.7152 * $c[1] + 0.0722 * $c[2];
}

function dPdarkContrast($a, $b)
{
	$la = dPdarkLuminance($a);
	$lb = dPdarkLuminance($b);
	return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
}

function dPdarkToHsl($rgb)
{
	list($r, $g, $b) = array($rgb[0] / 255, $rgb[1] / 255, $rgb[2] / 255);
	$max = max($r, $g, $b);
	$min = min($r, $g, $b);
	$l = ($max + $min) / 2;
	if ($max == $min) {
		return array(0, 0, $l);
	}
	$d = $max - $min;
	$s = $l > 0.5 ? $d / (2 - $max - $min) : $d / ($max + $min);
	if ($max == $r) {
		$h = ($g - $b) / $d + ($g < $b ? 6 : 0);
	} else if ($max == $g) {
		$h = ($b - $r) / $d + 2;
	} else {
		$h = ($r - $g) / $d + 4;
	}
	return array($h * 60, $s, $l);
}

function dPdarkFromHsl($h, $s, $l)
{
	$c = (1 - abs(2 * $l - 1)) * $s;
	$x = $c * (1 - abs(fmod($h / 60, 2) - 1));
	$m = $l - $c / 2;
	$sector = (int) floor($h / 60) % 6;
	$parts = array(array($c, $x, 0), array($x, $c, 0), array(0, $c, $x), array(0, $x, $c), array($x, 0, $c), array($c, 0, $x));
	list($r, $g, $b) = $parts[$sector];
	return array((int) round(($r + $m) * 255), (int) round(($g + $m) * 255), (int) round(($b + $m) * 255));
}

/**
 * The dark-theme version of a colour in a role: 'bg', 'fg' or 'border'.
 * Returns the colour as it was (null) when it reads well as it is.
 */
function dPdarkColor($value, $role)
{
	static $cache = array();
	$key = $role . '|' . strtolower($value);
	if (array_key_exists($key, $cache)) {
		return $cache[$key];
	}
	$rgb = dPdarkParseColor($value);
	$out = null;
	if ($rgb !== null) {
		list($h, $s, $l) = dPdarkToHsl($rgb);
		$neutral = $s < 0.08;
		if ($neutral) {
			// The blue-grey of the theme's surfaces.
			$h = 220;
		}
		if ($role === 'bg' && $l >= 0.6) {
			$out = dPdarkFromHsl($h, $neutral ? 0.24 : min($s, 0.5), 0.135 + (1 - $l) * 0.4);
		} else if ($role === 'border') {
			if ($l >= 0.6) {
				$out = dPdarkFromHsl($h, $neutral ? 0.2 : min($s, 0.4), 0.22 + (1 - $l) * 0.3);
			} else if ($l < 0.3) {
				$out = dPdarkFromHsl($h, $neutral ? 0.15 : min($s, 0.4), 0.4);
			}
		} else if ($role === 'fg') {
			$surface = dPdarkParseColor(DP_DARK_SURFACE);
			if (dPdarkContrast($rgb, $surface) < 4.5) {
				$s2 = $neutral ? 0.18 : min($s, 0.85);
				$l2 = $neutral ? 0.92 - $l * 0.5 : max(0.62, 1 - $l);
				$out = dPdarkFromHsl($h, $s2, $l2);
				while (dPdarkContrast($out, $surface) < 4.5 && $l2 < 0.97) {
					$l2 += 0.02;
					$out = dPdarkFromHsl($h, $s2, $l2);
				}
			}
		}
	}
	return $cache[$key] = ($out === null ? null : dPdarkHex($out));
}

/**
 * Adapts the colours of a list of CSS declarations (a style attribute or a rule). With
 * $bg_kept, the element keeps its background, so its text colour stays as well.
 */
function dPdarkDeclarations($css, &$bg_kept)
{
	return preg_replace_callback(
		'/(^|[;{\s])(background-color|background|color|border(?:-(?:top|right|bottom|left))?(?:-color)?)(\s*:\s*)([^;}]*)/i',
		function ($m) use (&$bg_kept) {
			$prop = strtolower($m[2]);
			$role = ($prop === 'color') ? 'fg' : (strpos($prop, 'border') === 0 ? 'border' : 'bg');
			if ($role === 'fg' && $bg_kept) {
				return $m[0];
			}
			$value = preg_replace_callback('/#[0-9a-f]{6}\b|#[0-9a-f]{3}\b|\b(?:white|black|silver|gr[ae]y|lightgr[ae]y|gainsboro|whitesmoke|navy|maroon|darkblue|darkred|darkgreen|ivory|beige|lightyellow)\b/i',
				function ($c) use ($role) {
					$new = dPdarkColor($c[0], $role);
					return $new === null ? $c[0] : $new;
				}, $m[4]);
			return $m[1] . $m[2] . $m[3] . $value;
		}, $css);
}

/** Whether a background stays as it is (a mid or saturated colour, such as a project's). */
function dPdarkBackgroundKept($css_or_value)
{
	if (preg_match('/background(?:-color)?\s*:\s*[^;]*?(#[0-9a-f]{3,6}\b|\b[a-z]+\b)/i', $css_or_value, $m)) {
		$value = $m[1];
	} else {
		$value = $css_or_value;
	}
	return dPdarkParseColor($value) !== null && dPdarkColor($value, 'bg') === null;
}

/** The output filter (index.php): every tag and <style> block of the page. */
function dPdarkColorFilter($html)
{
	// Only pages: an export or a download keeps its bytes.
	foreach (headers_list() as $header) {
		if (preg_match('/^content-type:\s*(?!text\/html)/i', $header)) {
			return $html;
		}
	}
	$html = preg_replace_callback('#(<style\b[^>]*>)(.*?)(</style>)#is', function ($m) {
		$css = preg_replace_callback('/\{([^{}]*)\}/', function ($r) {
			$kept = dPdarkBackgroundKept($r[1]);
			return '{' . dPdarkDeclarations($r[1], $kept) . '}';
		}, $m[2]);
		return $m[1] . $css . $m[3];
	}, $html);

	return preg_replace_callback('/<[a-zA-Z][a-zA-Z0-9]*\s[^<>]*>/', function ($m) {
		$tag = $m[0];
		if (stripos($tag, 'color') === false && stripos($tag, 'background') === false && stripos($tag, 'border') === false) {
			return $tag;
		}
		// The tag's own background: does it stay (so its text colour stays too)?
		$bg_kept = false;
		if (preg_match('/\sbgcolor\s*=\s*["\']?([#a-z0-9]+)/i', $tag, $b)) {
			$bg_kept = dPdarkBackgroundKept($b[1]);
		}
		if (preg_match('/\sstyle\s*=\s*(["\'])(.*?)\1/is', $tag, $st) && preg_match('/background/i', $st[2])) {
			$bg_kept = dPdarkBackgroundKept($st[2]);
		}
		$tag = preg_replace_callback('/(\s(bgcolor|color|bordercolor)\s*=\s*)(["\']?)([#a-z0-9]+)\3/i', function ($a) use ($bg_kept) {
			$attr = strtolower($a[2]);
			$role = $attr === 'bgcolor' ? 'bg' : ($attr === 'color' ? 'fg' : 'border');
			if ($role === 'fg' && $bg_kept) {
				return $a[0];
			}
			$new = dPdarkColor($a[4], $role);
			return $new === null ? $a[0] : $a[1] . $a[3] . $new . $a[3];
		}, $tag);
		return preg_replace_callback('/(\sstyle\s*=\s*)(["\'])(.*?)\2/is', function ($s) use ($bg_kept) {
			return $s[1] . $s[2] . dPdarkDeclarations($s[3], $bg_kept) . $s[2];
		}, $tag);
	}, $html);
}
