<?php /* INCLUDES $Id: main_functions.php 6182 2012-11-02 09:17:02Z ajdonnison $ */
##
## Global General Purpose Functions
##
if (!(defined('DP_BASE_DIR'))) {
	die('You should not access this file directly.');
}

require_once DP_BASE_DIR . '/includes/htmLawed.php';

$CR = "\n";
define('SECONDS_PER_DAY', 60 * 60 * 24);

##
## Returns the best color based on a background color (x is cross-over)
##
function bestColor($bg, $lt = '#ffffff', $dk = '#000000')
{
	if (empty($bg)) {
		$bg = $lt;
	}
	$bg = str_replace('#', '', $bg);
	$dk = str_replace('#', '', $dk);
	$base = new CSS_Color($bg);
	$fg = '#' . $base->calcFG($bg, $dk);
	if (dPdarkTheme()) {
		require_once DP_BASE_DIR . '/includes/dark_colors.php';
		// The dark theme's output filter darkens a light background: light text then.
		if (dPdarkColor('#' . $bg, 'bg') !== null) {
			return DP_DARK_INK;
		}
		// A kept background (a project colour): the text sits on it, so dark text stays
		// dark. rgb() is the spelling the filter does not touch (it would lighten a hex
		// black as if it were on the page), so use it only in style attributes.
		$rgb = dPdarkParseColor($fg);
		if ($rgb !== null && dPdarkLuminance($rgb) < 0.2) {
			return 'rgb(' . implode(', ', $rgb) . ')';
		}
	}
	return $fg;
}

##
## returns a select box based on an key,value array where selected is based on key
##
function arraySelect(&$arr, $select_name, $select_attribs, $selected, $translate = false)
{
	global $AppUI;
	if (!is_array($arr)) {
		dprint(__FILE__, __LINE__, 0, 'arraySelect called with no array');
		return '';
	}
	reset($arr);
	$s = ("\n" . '<select name="' . $select_name . '" ' . $select_attribs . '>');
	$did_selected = 0;
	foreach ($arr as $k => $v) {
		if ($translate) {
			$v = @$AppUI->_($v);
			// This is supplied to allow some Hungarian characters to
			// be translated correctly. There are probably others.
			// As such a more general approach probably based upon an
			// array lookup for replacements would be a better approach. AJD.
			$v = str_replace('&#369;', '�', $v);
			$v = str_replace('&#337;', '�', $v);
		} else {
			$v = $AppUI->___($v);
		}
		$s .= ("\n\t" . '<option value="' . $AppUI->___($k) . '"'
			. (($k == $selected && !$did_selected) ? ' selected="selected"' : '') . ">"
			. $v . '</option>');
		if ($k == $selected) {
			$did_selected = 1;
		}
	}
	$s .= "\n</select>\n";
	return $s;
}

##
## returns a select box based on an key,value array where selected is based on key
##
function arraySelectTree(&$arr, $select_name, $select_attribs, $selected, $translate = false)
{
	global $AppUI;
	reset($arr);

	$children = array();
	// first pass - collect children
	foreach ($arr as $k => $v) {
		$id = $v[0];
		$pt = $v[2];
		$list = @$children[$pt] ? $children[$pt] : array();
		array_push($list, $v);
		$children[$pt] = $list;
	}
	$list = tree_recurse($arr[0][2], '', array(), $children);
	return arraySelect($list, $select_name, $select_attribs, $selected, $translate);
}

function tree_recurse($id, $indent, $list, $children)
{
	if (@$children[$id]) {
		foreach ($children[$id] as $v) {
			$id = $v[0];
			$txt = $v[1];
			$pt = $v[2];
			$list[$id] = ($indent . ' ' . $txt);
			$list = tree_recurse($id, "$indent--", $list, $children);
		}
	}
	return $list;
}

/**
**	Provide Projects Selectbox sorted by Companies
**	@author gregorerhardt with special thanks to original author aramis
**	@param	int			userID
**	@param	string	HTML select box name identifier
**	@param	string	HTML attributes
**	@param	int			Proejct ID for preselection
**	@param	int			Project ID which will be excluded from the list 
**									(e.g. in the tasks import list exclude the project to import into)
**	@return	string	HTML selectbox

*/

function projectSelectWithOptGroup(
	$user_id,
	$select_name,
	$select_attribs,
	$selected,
	$excludeProjWithId = null
) {
	global $AppUI;
	$q = new DBQuery();
	$q->addTable('projects');
	$q->addQuery('project_id, co.company_name, project_name');
	if (!empty($excludeProjWithId)) {
		$q->addWhere('project_id != ' . $excludeProjWithId);
	}
	$proj = new CProject();
	$proj->setAllowedSQL($user_id, $q);
	$q->addOrder('co.company_name, project_name');
	$projects = $q->loadList();
	$s = ("\n" . '<select name="' . $select_name . '"'
		. (($select_attribs) ? (' ' . $select_attribs) : '') . '>');
	$s .= ("\n\t" . '<option value="0"' . ($selected == 0 ? ' selected="selected"' : '') . ' >'
		. $AppUI->_('None') . '</option>');
	$current_company = '';
	foreach ($projects as $p) {
		if ($p['company_name'] != $current_company) {
			$current_company = $AppUI->___($p['company_name']);
			$s .= ("\n" . '<optgroup label="' . $current_company . '" >' . $current_company
				. '</optgroup>');
		}
		$s .= ("\n\t" . '<option value="' . $p['project_id'] . '"'
			. ($selected == $p['project_id'] ? ' selected="selected"' : '')
			. '>&nbsp;&nbsp;&nbsp;' . $AppUI->___($p['project_name']) . '</option>');
	}
	$s .= "\n</select>\n";
	return $s;
}

##
## Merges arrays maintaining/overwriting shared numeric indicees
##
function arrayMerge($a1, $a2)
{
	foreach ($a2 as $k => $v) {
		$a1[$k] = $v;
	}
	return $a1;
}

##
## breadCrumbs - show a colon separated list of bread crumbs
## array is in the form url => title
##
function breadCrumbs(&$arr)
{
	global $AppUI;
	$crumbs = array();
	foreach ($arr as $k => $v) {
		$crumbs[] = '<a href="' . $AppUI->___($k) . '">' . $AppUI->_($v) . '</a>';
	}
	return implode(' <strong>:</strong> ', $crumbs);
}

##
## generate link for context help
##
function dPcontextHelp($title, $link = '')
{
	global $AppUI;
	return ('<a href="#' . $AppUI->___($link) . '"'
		. dPaction('window.open', array('?m=help&dialog=1&hid=' . $link, 'contexthelp', 'width=400,height=400,left=50,top=50,scrollbars=yes,resizable=yes'))
		. '>' . $AppUI->_($title) . '</a>');
}

/**
 * Items that active modules add to the header's user bar, next to Help and My Info:
 * modules/<module>/header_widget.php returns the item's HTML ('' for none). Each
 * item is followed by $separator, the bar's own (' | ', ' &bull; ').
 */
function dPheaderWidgets($separator)
{
	global $AppUI;
	$html = '';
	$files = glob(DP_BASE_DIR . '/modules/*/header_widget.php');
	if (!$files) {
		return $html;
	}
	$active = $AppUI->getActiveModules();
	// Each file runs in its own scope, so its variables cannot overwrite these.
	$run = function ($file) {
		return include $file;
	};
	foreach ($files as $file) {
		if (isset($active[basename(dirname($file))])) {
			$widget = $run($file);
			if (is_string($widget) && $widget !== '') {
				$html .= $widget . $separator;
			}
		}
	}
	return $html;
}


/**
 * Retrieves a configuration setting.
 * @param $key string The name of a configuration setting
 * @param $default string The default value to return if the key not found.
 * @return The value of the setting, or the default value if not found.
 */
function dPgetConfig($key, $default = null)
{
	global $dPconfig;
	return ((array_key_exists($key, $dPconfig)) ? $dPconfig[$key] : $default);
}

function dPgetUsername($user)
{
	$q = new DBQuery;
	$q->addTable('users');
	$q->addQuery('contact_first_name, contact_last_name');
	$q->addJoin('contacts', 'con', 'contact_id = user_contact');
	$q->addWhere("user_username LIKE '" . $user . "'");
	$r = $q->loadList();
	return $r[0]['contact_first_name'] . ' ' . $r[0]['contact_last_name'];
}

function dPgetUsernameFromID($user)
{
	$q = new DBQuery;
	$q->addTable('users');
	$q->addQuery('contact_first_name, contact_last_name');
	$q->addJoin('contacts', 'con', 'contact_id = user_contact');
	$q->addWhere('user_id = ' . (int) $user);
	$r = $q->loadList();
	return $r[0]['contact_first_name'] . ' ' . $r[0]['contact_last_name'];
}

function dPgetUsers()
{
	global $AppUI;
	$q = new DBQuery;
	$q->addTable('users');
	$q->addQuery('user_id, concat_ws(" ", contact_first_name, contact_last_name) as name');
	$q->addJoin('contacts', 'con', 'contact_id = user_contact');
	$q->addOrder('contact_last_name,contact_first_name');
	return arrayMerge(array(0 => $AppUI->_('All Users')), $q->loadHashList());
}
##
## displays the configuration array of a module for informational purposes
##
function dPshowModuleConfig($config)
{
	global $AppUI;
	$s = '<table cellspacing="2" cellpadding="2" border="0" class="std" width="50%">';
	$s .= '<tr><th colspan="2">' . $AppUI->_('Module Configuration') . '</th></tr>';
	foreach ($config as $k => $v) {
		$s .= ('<tr><td width="50%">' . $AppUI->_($k) . '</td><td width="50%" class="hilite">'
			. $AppUI->_($v) . '</td></tr>');
	}
	$s .= '</table>';
	return ($s);
}

/**
 * Whether the theme shows the vector icons (tools/icons/build.py) instead of the bitmaps:
 * a theme opts in by defining DP_VECTOR_ICONS in its overrides.php (dotpulse does). The
 * classic themes keep the original bitmaps.
 */
function dPvectorIcons()
{
	return defined('DP_VECTOR_ICONS') && DP_VECTOR_ICONS;
}

/**
 * Whether the theme is dark (style/dotpulse-dark defines DP_DARK_THEME in its overrides.php):
 * it gets the dark versions of the small icons and the colour filter of includes/dark_colors.php.
 */
function dPdarkTheme()
{
	return defined('DP_DARK_THEME') && DP_DARK_THEME;
}

/**
 * The vector icon to serve for a file path without its extension (".../stock_edit-16"): its
 * dark version in a dark theme where there is one, else the SVG; the extension, or null.
 */
function dPvectorIconExtension($base_file)
{
	if (dPdarkTheme() && file_exists($base_file . '.dark.svg')) {
		return '.dark.svg';
	}
	return file_exists($base_file . '.svg') ? '.svg' : null;
}

/**
 * Output filter of the themes with vector icons: every src of a bitmap of the installation
 * (images/, modules/, style/) that has an SVG of the same name next to it names the SVG (in a
 * dark theme, its dark version where there is one). Markup names the bitmaps, so the classic
 * themes are left as they were.
 */
function dPvectorIconFilter($html)
{
	static $ext_of = array();
	$base = DP_BASE_URL;
	return preg_replace_callback('/(\ssrc\s*=\s*)(["\'])([^"\'<>?#]+)\.(gif|png|jpe?g)\2/i',
		function ($m) use (&$ext_of, $base) {
			$path = $m[3];
			$key = (dPdarkTheme() ? 'dark:' : '') . $path;
			if (!array_key_exists($key, $ext_of)) {
				$rel = $path;
				if ($base !== '' && strpos($rel, $base) === 0) {
					$rel = substr($rel, strlen($base));
				}
				$rel = preg_replace('#^\.?/#', '', $rel);
				$ext = null;
				$dir = realpath(DP_BASE_DIR . '/' . dirname($rel));
				if (strpos($rel, '://') === false && $dir !== false
					&& preg_match('#^' . preg_quote(realpath(DP_BASE_DIR), '#') . '/(images|modules|style)(/|$)#', $dir)) {
					$ext = dPvectorIconExtension($dir . '/' . basename($rel));
				}
				$ext_of[$key] = $ext;
			}
			return $ext_of[$key] !== null ? $m[1] . $m[2] . $path . $ext_of[$key] . $m[2] : $m[0];
		}, $html);
}

/**
 *	Function to recussively find an image in a number of places
 *	@param string The name of the image
 *	@param string Optional name of the current module
 *	@return location for image, "default" location returned if not found elsewhere (even if not present there).
 */
function dPfindImage($name, $module = null)
{
	global $uistyle; // uistyle must be declared globally

	//array of locations, rooted at DP_BASE
	$locations_to_search = array(
		('/style/' . $uistyle . '/images/'),
		'module-image' => ('/modules/' . $module . '/images/'),
		'module-icon' => ('/modules/' . $module . '/images/icons/'),
		('/images/icons/'),
		('/images/obj/')
	);

	// In a theme with vector icons, the SVG of a bitmap (tools/icons/build.py) wins where both are.
	$stem = dPvectorIcons() ? preg_replace('/\.(png|gif|jpe?g)$/i', '', $name) : $name;
	foreach ($locations_to_search as $exception => $folder) {
		if (($module) || ($exception != 'module-image' && $exception != 'module-icon')) {
			if ($stem !== $name && ($ext = dPvectorIconExtension(DP_BASE_DIR . $folder . $stem)) !== null) {
				return (DP_BASE_URL . $folder . $stem . $ext);
			}
			if (file_exists(DP_BASE_DIR . $folder . $name)) {
				return (DP_BASE_URL . $folder . $name);
			}
		}
	}
	// default to base image directory
	if ($stem !== $name && ($ext = dPvectorIconExtension(DP_BASE_DIR . '/images/' . $stem)) !== null) {
		return ('./images/' . $stem . $ext);
	}
	return ('./images/' . $name);
}

/**
 *	@param string The name of the image
 *	@param string The image width
 *	@param string The image height
 *	@param string The alt text for the image
 */
function dPshowImage($src, $wid = '', $hgt = '', $alt = '', $title = '')
{
	global $AppUI;

	if ($src == '') {
		return '';
	}

	return ('<img src="' . $AppUI->___($src) . '" '
		. (($wid) ? (' width="' . $AppUI->___($wid) . '"') : '')
		. (($hgt) ? (' height="' . $AppUI->___($hgt) . '"') : '')
		. ' alt="' . (($alt) ? $AppUI->_($alt) : $AppUI->___($src)) . '"'
		. (($title) ? (' title="' . $AppUI->_($title) . '"') : '') . ' border="0" />');
}

/**
 * function to return a default value if a variable is not set
 */
function defVal($var, $def)
{
	return isset($var) ? $var : $def;
}

/**
 * Utility function to return a value from a named array or a specified default
 */
function dPgetParam(&$arr, $name, $def = null)
{
	return isset($arr[$name]) ? $arr[$name] : $def;
}

/**
 * Returns a named array value as an integer, or $def when it is not set. Use it for ids
 * and other numbers that end up in SQL.
 */
function dPgetIntParam(&$arr, $name, $def = 0)
{
	return isset($arr[$name]) ? (int)$arr[$name] : (int)$def;
}

/**
 * The department filter of the project lists: a department id, or company_<id> for a
 * whole company. Anything else becomes a department id (int), so the value can go into SQL.
 */
function dPdepartmentParam(&$arr, $name = 'department')
{
	$value = (string)dPgetParam($arr, $name, '');
	return preg_match('/^company_(\d+)$/', $value, $m) ? 'company_' . (int)$m[1] : (int)$value;
}

/**
 * Escapes a value for HTML text or a quoted attribute (single or double quotes), in the
 * install's character set. Use it for every database or request value written into a page.
 */
function dPhtml($str)
{
	global $AppUI;
	return $AppUI->___((string)$str);
}

/**
 * Converts a string from the page charset ($locale_char_set) to UTF-8. Several
 * locales use ISO-8859 or Windows code pages, and mbstring does not know all of
 * them (big5 and WINDOWS-1257 made mb_convert_encoding() throw), so iconv comes
 * first. Unconvertible input is returned unchanged.
 */
function dPtoUtf8($value, $charset = null)
{
	global $locale_char_set;
	$charset = $charset !== null ? $charset : $locale_char_set;
	$value = (string)$value;
	if (!$charset || mb_strtolower($charset) == 'utf-8' || $value === '') {
		return $value;
	}
	$converted = function_exists('iconv') ? @iconv($charset, 'UTF-8', $value) : false;
	if ($converted === false) {
		try {
			$converted = mb_convert_encoding($value, 'UTF-8', $charset);
		} catch (ValueError $e) {
			$converted = $value;
		}
	}
	return $converted;
}

/**
 * Returns a value as a JavaScript string literal (with its quotes) that is safe anywhere in a
 * <script> block: quotes, <, > and & are \u-escaped. Use dPjsAttr() inside an onclick or
 * other event attribute.
 */
function dPjs($value)
{
	$value = dPtoUtf8($value);
	return json_encode($value, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP
		| JSON_INVALID_UTF8_SUBSTITUTE);
}

/**
 * dPjs() for event attributes such as onclick="f(<?php echo dPjsAttr($v); ?>)".
 */
function dPjsAttr($value)
{
	return htmlspecialchars(dPjs($value), ENT_QUOTES);
}

/**
 * Returns $url when it is safe to use as a link target: relative, or with an http, https,
 * mailto or ftp scheme. Anything else (javascript:, data:, vbscript:, ...) returns ''.
 * The result still needs HTML escaping when written into an attribute.
 */
function dPsafeUrl($url)
{
	$url = trim((string)$url);
	if ($url === '') {
		return '';
	}
	// Browsers ignore control characters and whitespace inside the scheme ("java\tscript:").
	$probe = preg_replace('/[\x00-\x20]+/', '', $url);
	if (preg_match('/^([a-z][a-z0-9+.-]*):/i', $probe, $m)) {
		return in_array(mb_strtolower($m[1]), array('http', 'https', 'mailto', 'ftp'), true) ? $url : '';
	}
	return $url;
}

/**
 * Returns the real path of $name inside $dir, the installation directory of an external
 * application a module reads or includes (Gallery2, Eventum), or false. The file must
 * exist on the local file system and must not be under a directory users upload into
 * (files/, and SGD/ where the document management module used to store uploads).
 */
function dPexternalAppFile($dir, $name)
{
	$file = realpath(rtrim((string)$dir, '/\\') . DIRECTORY_SEPARATOR . $name);
	if ($file === false || !is_file($file)) {
		return false;
	}
	foreach (array('/files', '/SGD') as $upload_dir) {
		$upload_dir = realpath(DP_BASE_DIR . $upload_dir);
		if ($upload_dir !== false && strpos($file, $upload_dir . DIRECTORY_SEPARATOR) === 0) {
			return false;
		}
	}
	return $file;
}

/**
 * Returns $value when it is one of $allowed, otherwise $default. ORDER BY and GROUP BY
 * clauses cannot take bound parameters, so sort columns from the request must pass this.
 */
function dPvalidateOrder($value, array $allowed, $default)
{
	return in_array($value, $allowed, true) ? $value : $default;
}

/**
 * Alternative to protect from XSS attacks.
 */
/**
 * Hash a password for storage in users.user_password.
 *
 * Uses password_hash() once the column can hold its output (VARCHAR(255),
 * added by the 20261009 entry in db/upgrade_latest.sql). On a database that
 * has not been upgraded yet it keeps the legacy MD5 format, because a bcrypt
 * hash cut to 32 characters would lock the user out.
 */
function dPhashPassword($password)
{
	return dPpasswordColumnFitsHash() ? password_hash($password, PASSWORD_DEFAULT) : md5($password);
}

/**
 * Cleans a comma-separated list of ids: every entry becomes an integer, '' stays ''.
 */
function dPintList($ids)
{
	if ((string)$ids === '') {
		return '';
	}
	return implode(',', array_map('intval', explode(',', (string)$ids)));
}

/**
 * True when $source (the contents of locale .inc files) holds only string literals,
 * "=>", commas and comments, so it can be evaluated as the body of array(...) without
 * running code. Double-quoted strings may contain a plain $name (it only reads a variable)
 * but not {$...}, ${...}, $name[...] or $name->..., which can call code.
 */
function dPisTranslationSource($source)
{
	$name = '\$[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*+(?!\[|->)';
	$string = '"(?:[^"\\\\${]++|\\\\.|\{(?!\$)|' . $name . '|\$(?![A-Za-z_\x80-\xff{]))*+"'
		. '|\'(?:[^\'\\\\]++|\\\\.)*+\'';
	$comment = '#[^\n]*+|//[^\n]*+|/\*.*?\*/';
	if (preg_match('~\A(?:\s++|' . $string . '|=>|,|' . $comment . ')*+\z~s', $source) === 1) {
		return true;
	}
	error_log('dotProject: translation file skipped, it contains more than string literals');
	return false;
}

/**
 * The strings of locales/$locale/<module>.inc for each of $modules, later files
 * winning, as locales/core.php loads them for a page. For text built outside a page
 * request, such as mail sent from the event queue in the recipient's language.
 */
function dPtranslationsFor($locale, $modules)
{
	$locale = preg_replace('/[^A-Za-z_]/', '', (string)$locale);
	$strings = array();
	if ($locale === '') {
		return $strings;
	}
	foreach ($modules as $module) {
		$file = DP_BASE_DIR . '/locales/' . $locale . '/' . preg_replace('/[^A-Za-z0-9_]/', '', $module) . '.inc';
		$source = is_readable($file) ? file_get_contents($file) : '';
		if ($source === '' || $source === false || !dPisTranslationSource($source)) {
			continue;
		}
		$file_strings = array();
		try {
			eval('$file_strings = array(' . $source . "\n);");
			$strings = array_merge($strings, $file_strings);
		} catch (ParseError $e) {
			error_log('dotProject: translation file skipped, it does not parse: ' . $file);
		}
	}
	return $strings;
}

/**
 * True when users.user_password is wide enough for password_hash() output.
 */
function dPpasswordColumnFitsHash()
{
	static $fits = null;
	if ($fits === null) {
		$len = db_loadResult('SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.COLUMNS'
			. ' WHERE TABLE_SCHEMA = DATABASE()'
			. " AND TABLE_NAME = '" . db_escape(dPgetConfig('dbprefix', '') . 'users') . "'"
			. " AND COLUMN_NAME = 'user_password'");
		$fits = ((int)$len >= 60);
		if (!$fits) {
			error_log('dotProject: users.user_password is too short for password_hash();'
				. ' run the database upgrade (db/upgrade_latest.sql). Falling back to MD5.');
		}
	}
	return $fits;
}

function dPgetCleanParam(&$arr, $name, $def = null)
{
	if (isset($arr[$name]) && is_array($arr[$name])) {
		$val = array();
		foreach (array_keys($arr[$name]) as $key) {
			$val[$key] = dPgetCleanParam($arr[$name], $key, $def);
		}
		return $val;
	}
	$val = $arr[$name] ?? $def;
	if (empty($val)) {
		return $val;
	}
	return htmLawed($val, array('safe' => 1));
}

function dPsanitiseHTML($text)
{
	return htmLawed($text, array('safe' => 1));
}

function dPlink($title, $href)
{
	return dPsanitiseHTML('<a href="' . $href . '">' . $title . '</a>');
}

#
# add history entries for tracking changes
#

/**
 * Adds an entry to the security audit log (classes/auditlog.class.php): an event name from
 * CAuditLog::events(), details for people (no passwords or tokens), and optionally the
 * object it concerns. A failure to write it is logged, never shown.
 */
function dPaudit($event, $details = '', $object = '', $object_id = 0, $user_id = null, $username = null)
{
	require_once DP_BASE_DIR . '/classes/auditlog.class.php';
	try {
		CAuditLog::add($event, $details, $object, $object_id, $user_id, $username);
	} catch (Throwable $e) {
		error_log('dotPulse audit log: ' . $e->getMessage());
	}
}

function addHistory($table, $id, $action = 'modify', $description = '', $project_id = 0)
{
	global $AppUI;
	/*
	 * TODO:
	 * 1) description should be something like:
	 *	    command(arg1, arg2...)
	 *	The command should be as module_action
	 *	for example:
	 *	    forums_new('Forum Name', 'URL')
	 *
	 * This way, the history module will be able to display descriptions
	 * using locale definitions:
	 *	   "forums_new" -> "New forum '%s' was created" -> "Se ha creado un nuevo foro llamado '%s'"
	 *
	 * 2) project_id and module_id should be provided in order to filter history entries
	 *
	 */
	if (!dPgetConfig('log_changes'))
		return;
	$description = str_replace("'", "\'", $description);

	$q = new DBQuery;
	$q->addTable('modules');
	$q->addWhere("mod_name = 'History' AND mod_active = 1");
	$qid = $q->exec();

	if (!$qid || db_num_rows($qid) == 0) {
		$AppUI->setMsg('History module is not loaded, but your config file has requested that'
			. ' changes be logged. You must either change the config file or install'
			. ' and activate the history module to log changes.', UI_MSG_ALERT);
		$q->clear();
		return;
	}

	$q->clear();
	$q->addTable('history');
	$q->addInsert('history_action', $action);
	$q->addInsert('history_item', $id);
	$q->addInsert('history_description', $description);
	$q->addInsert('history_user', $AppUI->user_id);
	$q->addInsert('history_date', 'now()', false, true);
	$q->addInsert('history_project', $project_id);
	$q->addInsert('history_table', $table);
	$q->exec();
	echo db_error();
	$q->clear();
}

##
## Looks up a value from the SYSVALS table
##
function dPgetSysVal($title)
{
	$q = new DBQuery;
	$q->addTable('sysvals');
	$q->leftJoin('syskeys', 'sk', 'syskey_id = sysval_key_id');
	$q->addQuery('syskey_type, syskey_sep1, syskey_sep2, sysval_value');
	$q->addWhere("sysval_title = '$title'");
	$q->exec();
	$row = $q->fetchRow();
	$q->clear();
	if (!$row)
		return array();

	// type 0 = list
	$sep1 = $row['syskey_sep1']; // item separator
	$sep2 = $row['syskey_sep2']; // alias separator

	// A bit of magic to handle newlines and returns as separators
	// Missing sep1 is treated as a newline.
	if (!isset($sep1) || empty($sep1) || $sep1 == "\\n") {
		$sep1 = "\n";
	} else if ($sep1 == "\\r") {
		$sep1 = "\r";
	}

	$temp = explode($sep1, $row['sysval_value']);
	$arr = array();
	// We use trim() to make sure a numeric that has spaces
	// is properly treated as a numeric
	foreach ($temp as $item) {
		if ($item) {
			$sep2 = empty($sep2) ? "\n" : $sep2;
			$temp2 = explode($sep2, $item);
			$arr[trim($temp2[0])] = trim(((isset($temp2[1])) ? $temp2[1] : $temp2[0]));
		}
	}
	return $arr;
}

function dPuserHasRole($name)
{
	global $AppUI;
	$uid = (int) $AppUI->user_id;

	// user_roles table was superseded by phpGACL.
	// A user "has" a role when their ARO appears in the named ARO group.
	$q = new DBQuery;
	$q->addTable('gacl_aro_groups', 'g');
	$q->innerJoin('gacl_groups_aro_map', 'm', 'm.group_id = g.id');
	$q->innerJoin('gacl_aro', 'a', 'a.id = m.aro_id');
	$q->addQuery('g.id');
	$q->addWhere("g.value = '" . addslashes($name) . "' AND a.value = '$uid'");
	$q->setLimit(1);
	return $q->loadResult();
}


/**
 *	@param int number of hours to format
 *	@return string 
 */
function dPformatDuration($x)
{
	global $AppUI;

	$dur_day = floor($x / dPgetConfig('daily_working_hours'));
	$dur_hour = ($x % dPgetConfig('daily_working_hours'));
	$str = '';

	if ($dur_day) {
		$str .= $dur_day . ' ' . $AppUI->_('day' . ((abs($dur_day) == 1) ? '' : 's')) . ' ';
	}
	if ($dur_hour) {
		$str .= $dur_hour . ' ' . $AppUI->_('hour' . ((abs($dur_hour) == 1) ? '' : 's')) . ' ';
	}
	if ($str == '') {
		$str = $AppUI->_('n/a');
	}

	return $str;

}

function dPsetMicroTime()
{
	global $microTimeSet;
	list($usec, $sec) = explode(' ', microtime());
	$microTimeSet = (float) $usec + (float) $sec;
}

function dPgetMicroDiff()
{
	global $microTimeSet;
	$mt = $microTimeSet;
	dPsetMicroTime();
	return sprintf('%.3f', $microTimeSet - $mt);
}

/**
 * Make text safe to output into double-quote enclosed attirbutes of an HTML tag
 */
define('DP_FORM_DESLASH', 1);
define('DP_FORM_URI', 2);
define('DP_FORM_JSVARS', 4);
function dPformSafe($txt, $flag_bits = 0)
{
	global $AppUI, $locale_char_set;

	if (!$locale_char_set) {
		$locale_char_set = 'utf-8';
	}

	$deslash = $flag_bits & DP_FORM_DESLASH;
	$isURI = $flag_bits & DP_FORM_URI;
	$isJSVars = $flag_bits & DP_FORM_JSVARS;

	if (is_object($txt) || is_array($txt)) {
		$txt_arr = is_object($txt) ? get_object_vars($txt) : $txt;
		foreach ($txt_arr as $k => $v) {
			$value = $deslash ? $AppUI->___($v, UI_OUTPUT_RAW) : $v;
			// URIs are written into href attributes, so they are HTML-escaped too.
			$value = $isURI ? htmlspecialchars($AppUI->___($value, UI_OUTPUT_URI), ENT_QUOTES) : $value;

			if (!$isURI) {
				$value = $isJSVars ? $AppUI->___($value, UI_OUTPUT_JS) : $value;
				$value = $deslash ? htmlspecialchars($value) : $AppUI->___($value);
			}

			if (is_object($txt)) {
				$txt->$k = $value;
			} else {
				$txt[$k] = $value;
			}
		}

	} else {
		$txt = $deslash ? $AppUI->___($txt, UI_OUTPUT_RAW) : $txt;
		$txt = $isURI ? htmlspecialchars($AppUI->___($txt, UI_OUTPUT_URI), ENT_QUOTES) : $txt;

		if (!$isURI) {
			$txt = $isJSVars ? $AppUI->___($txt, UI_OUTPUT_JS) : $txt;
			$txt = $deslash ? htmlspecialchars($txt) : $AppUI->___($txt);
		}

		/*
		$txt = (($deslash) ? stripslashes($txt) : $txt);
		$txt = (($isURI) ? str_replace(" ", "%20", $txt) : $txt);
		$txt = (($isJSVars) 
				? str_replace("'", "\\'", str_replace('"', '\"', $txt)) 
				: str_replace('&#039;', '&apos;', 
							  htmlspecialchars($txt, ENT_QUOTES, $locale_char_set)));
		*/
	}
	return $txt;
}

function convert2days($durn, $units)
{
	switch ($units) {
		case 0:
		case 1:
			return $durn / dPgetConfig('daily_working_hours');
			break;
		case 24:
			return $durn;
	}
}

function formatTime($uts)
{
	global $AppUI;
	$date = new CDate();
	$date->setDate($uts, DATE_FORMAT_UNIXTIME);
	return $date->format($AppUI->getPref('SHDATEFORMAT'));
}

/**
 * This function is necessary because Windows likes to
 * write their own standards.  Nothing that depends on locales
 * can be trusted in Windows.
 */
function formatCurrency($number, $format)
{
	global $AppUI, $locale_char_set;

	if (!$format) {
		$format = $AppUI->getPref('SHCURRFORMAT');
	}
	// If the requested locale doesn't work, don't fail,
	// revert to the system default.
	if (
		($locale_char_set != 'utf-8' || !setlocale(LC_MONETARY, (is_array($format) ? implode(',', $format) : $format) . '.UTF8'))
		&& !(setlocale(LC_MONETARY, $format))
	) {
		setlocale(LC_MONETARY, '');
	}
	// Technically this should be acheivable with the following, however
	// it seems that some versions of PHP will set this incorrectly
	// and you end up with everything using locale C.
	// setlocale(LC_MONETARY, $format . '.UTF8', $format, '');
	if (function_exists('money_format')) {
		return money_format('%i', $number);
	}
	// NOTE: This is called if money format doesn't exist.
	// Money_format only exists on non-windows 4.3.x sites.
	// This uses localeconv to get the information required
	// to format the money.	 It tries to set reasonable defaults.
	$mondat = localeconv();
	if (!isset($mondat['int_frac_digits']) || $mondat['int_frac_digits'] > 100) {
		$mondat['int_frac_digits'] = 2;
	}
	if (!isset($mondat['int_curr_symbol'])) {
		$mondat['int_curr_symbol'] = '';
	}
	if (!isset($mondat['mon_decimal_point'])) {
		$mondat['mon_decimal_point'] = '.';
	}
	if (!isset($mondat['mon_thousands_sep'])) {
		$mondat['mon_thousands_sep'] = ',';
	}
	$numeric_portion = number_format(
		abs($number),
		$mondat['int_frac_digits'],
		$mondat['mon_decimal_point'],
		$mondat['mon_thousands_sep']
	);
	// Not sure, but most countries don't put the sign in if it is positive.
	$letter = 'p';
	$currency_prefix = "";
	$currency_suffix = "";
	$prefix = "";
	$suffix = "";
	if ($number < 0) {
		$sign = $mondat['negative_sign'];
		$letter = 'n';
		switch ($mondat['n_sign_posn']) {
			case 0:
				$prefix = '(';
				$suffix = ')';
				break;
			case 1:
				$prefix = $sign;
				break;
			case 2:
				$suffix = $sign;
				break;
			case 3:
				$currency_prefix = $sign;
				break;
			case 4:
				$currency_suffix = $sign;
				break;
		}
	}
	$currency = $currency_prefix . $mondat['int_curr_symbol'] . $currency_suffix;
	$space = "";
	if (isset($mondat[$letter . '_sep_by_space']) && $mondat[$letter . '_sep_by_space']) {
		$space = " ";
	}
	if (isset($mondat[$letter . '_cs_precedes']) && $mondat[$letter . '_cs_precedes']) {
		$result = "$currency$space$numeric_portion";
	} else {
		$result = "$numeric_portion$space$currency";
	}
	return $result;
}

function format_backtrace($bt, $file, $line, $msg)
{
	echo "<pre>\n";
	echo "ERROR: $file($line): $msg\n";
	echo "Backtrace:\n";
	foreach ($bt as $level => $frame) {
		echo "$level $frame[file]:$frame[line] $frame[function](";
		$in = false;
		foreach ($frame['args'] as $arg) {
			echo ((($in) ? ',' : '') . var_export($arg, true));
			$in = true;
		}
		echo ")\n";
	}
	echo "<\pre>\n";
}

function dprint($file, $line, $level, $msg)
{
	$max_level = 0;
	$max_level = (int) dPgetConfig('debug');
	$display_debug = dPgetConfig('display_debug', false);
	if ($level <= $max_level) {
		error_log("$file($line): $msg");
		if ($display_debug) {
			echo "$file($line): $msg <br />";
		}
		if ($level == 0 && $max_level > 0 && version_compare(phpversion(), "4.3.0") >= 0) {
			format_backtrace(debug_backtrace(), $file, $line, $msg);
		}
	}
}

/**
 * Function to wrap the ADODB debug print so we can direct it via our normal debug processes.
 */
function db_dprint($msg, $newline)
{
	dprint('adodb', 0, 12, $msg);
}

/**
 * Return a list of modules that are associated with tabs for this
 * page.  This can be used to find post handlers, for instance.
 */
function findTabModules($module, $file = null)
{
	$modlist = array();
	if (!isset($_SESSION['all_tabs']) || !isset($_SESSION['all_tabs'][$module])) {
		return $modlist;
	}

	if (isset($file)) {
		if (
			isset($_SESSION['all_tabs'][$module][$file])
			&& is_array($_SESSION['all_tabs'][$module][$file])
		) {
			$tabs_array =& $_SESSION['all_tabs'][$module][$file];
		} else {
			return $modlist;
		}
	} else {
		$tabs_array =& $_SESSION['all_tabs'][$module];
	}
	foreach ($tabs_array as $tab) {
		if (isset($tab['module'])) {
			$modlist[] = $tab['module'];
		}
	}
	return array_unique($modlist);
}

/**
 * @return void
 * @param mixed $var
 * @param char $title
 * @desc Show an estructure (array/object) formatted
 */
function showFVar(&$var, $title = "")
{
	echo '<h1>' . $title . '</h1><pre>' . print_r($var, true) . '</pre>';
}

function getUsersArray()
{
	$q = new DBQuery;
	$q->addTable('users');
	$q->addQuery('user_id, user_username, contact_first_name, contact_last_name');
	$q->addJoin('contacts', 'con', 'contact_id = user_contact');
	$q->addOrder('contact_first_name, contact_last_name');
	return $q->loadHashList('user_id');
}

function getUsersCombo($default_user_id = 0, $first_option = 'All users')
{
	global $AppUI;

	$parsed = '<select name="user_id" class="text">';
	if ($first_option != "") {
		$parsed .= ('<option value="0" '
			. ((!($default_user_id)) ? 'selected="selected"' : '') . '>'
			. $AppUI->_($first_option) . '</option>');
	}
	foreach (getUsersArray() as $user_id => $user) {
		$selected = $user_id == $default_user_id ? ' selected="selected"' : '';
		$parsed .= ('<option value="' . $user_id . '"' . $selected . '>'
			. $user['contact_first_name'] . ' ' . $user['contact_last_name'] . '</option>');
	}
	$parsed .= '</select>';
	return $parsed;
}

/*
 * Moved modified version from files.class.php as pagation could be useful in any module
 */
function shownavbar($xpg_totalrecs, $xpg_pagesize, $xpg_total_pages, $page, $folder = false)
{

	global $AppUI, $tab, $m, $a;
	$NUM_PAGES_TO_DISPLAY = 15; //TODO?: Set by System Config Value ...
	$RANGE_LIMITS = floor($NUM_PAGES_TO_DISPLAY / 2);

	$xpg_prev_page = $xpg_next_page = 1;

	echo "\t" . '<table width="100%" cellspacing="0" cellpadding="0" border="0"><tr>' . "\n";
	// more than a page of results
	if ($xpg_totalrecs > $xpg_pagesize) {
		$xpg_prev_page = $page - 1;
		$xpg_next_page = $page + 1;

		// left buttons, if applicable
		echo '<td align="left" width="15%">' . "\n";
		if ($xpg_prev_page > 0) {
			echo ('<a href="./index.php?m=' . $m
				. (($a) ? ('&amp;a=' . $a) : '') . (($tab) ? ('&amp;tab=' . $tab) : '')
				. (($folder) ? ('&amp;folder=' . $folder) : '') . '&amp;page=1">'
				. '<img src="images/navfirst.svg" border="0" Alt="'
				. $AppUI->_('First Page') . '"></a>' . "\n");
			echo ('&nbsp;&nbsp;' . "\n");
			echo ('<a href="./index.php?m=' . $m
				. (($a) ? ('&amp;a=' . $a) : '') . (($tab) ? ('&amp;tab=' . $tab) : '')
				. (($folder) ? ('&amp;folder=' . $folder) : '') . '&amp;page=' . $xpg_prev_page . '">'
				. '<img src="images/navleft.svg" border="0" Alt="'
				. $AppUI->_('Previous Page') . ': ' . $xpg_prev_page . '"></a>' . "\n");
		} else {
			echo ("&nbsp;\n");
		}
		echo "</td>\n";

		// central text (files, total pages, and page selectors)
		echo '<td align="center" width="70%">' . "\n";
		echo ($xpg_totalrecs . ' ' . $AppUI->_('Result(s)')
			. ' (' . $xpg_total_pages . ' ' . $AppUI->_('Page(s)') . ')' . "<br />\n");

		$start_page_range = (($page > $RANGE_LIMITS) ? ($page - $RANGE_LIMITS) : 1);
		$end_page_range = (($start_page_range - 1) + $NUM_PAGES_TO_DISPLAY);
		if ($xpg_total_pages < $end_page_range) {
			$start_page_range = (($xpg_total_pages + 1) - $NUM_PAGES_TO_DISPLAY);
			$start_page_range = (($start_page_range > 0) ? $start_page_range : 1);
			$end_page_range = $xpg_total_pages;
		}

		echo (($start_page_range <= $end_page_range) ? ' [ ' : '');
		for ($n = $start_page_range; $n <= $end_page_range; $n++) {
			echo (($n == $page)
				? '<b>' : ('<a href="./index.php?m=' . $m
					. (($a) ? ('&amp;a=' . $a) : '')
					. (($tab) ? ('&amp;tab=' . $tab) : '')
					. (($folder) ? ('&amp;folder=' . $folder) : '') . '&amp;page=' . $n . '">'));
			echo ($n);
			echo (($n == $page) ? '</b>' : '</a>');
			echo (($n < $end_page_range) ? ' | ' : " ]\n");
		}

		echo "</td>\n";

		// right buttons, if applicable
		echo '<td align="left" width="15%">' . "\n";
		if ($xpg_next_page <= $xpg_total_pages) {
			echo ('<a href="./index.php?m=' . $m
				. (($a) ? ('&amp;a=' . $a) : '') . (($tab) ? ('&amp;tab=' . $tab) : '')
				. (($folder) ? ('&amp;folder=' . $folder) : '') . '&amp;page=' . $xpg_next_page . '">'
				. '<img src="images/navright.svg" border="0" Alt="'
				. $AppUI->_('Next Page') . ': ' . $xpg_next_page . '"></a>' . "\n");
			echo "&nbsp;&nbsp;\n";
			echo ('<a href="./index.php?m=' . $m
				. (($a) ? ('&amp;a=' . $a) : '') . (($tab) ? ('&amp;tab=' . $tab) : '')
				. (($folder) ? ('&amp;folder=' . $folder) : '') . '&amp;page=' . $xpg_total_pages . '">'
				. '<img src="images/navlast.svg" border="0" Alt="'
				. $AppUI->_('Last Page') . '"></a>' . "\n");
		} else {
			echo ("&nbsp;\n");
		}
		echo "</td>\n";
	} else { // we dont have enough results for more than a page
		echo ('<td align="center">'
			. (($xpg_totalrecs)
				? ($xpg_totalrecs . ' ' . $AppUI->_('Result(s)'))
				: ($AppUI->_('No Result(s)')))
			. "</td>\n");
	}
	echo "</tr></table>";
}

/**
 * PHP doesn't come with a signum function
 */
function dPsgn($x)
{
	return $x ? ($x > 0 ? 1 : -1) : 0;
}

/*
 ** Create the Required Fields (From Sysvals) JavaScript Code
 ** For instance implemented in projects and tasks addedit.php
 ** @param array required field array from SysVals
 */
function dPrequiredFields($requiredFields)
{
	global $AppUI, $m;
	$buffer = 'var foc=false;' . "\n";

	if (!empty($requiredFields)) {
		foreach ($requiredFields as $rf => $comparator) {
			$buffer .= 'if (' . $rf . html_entity_decode($comparator, ENT_QUOTES) . ') {' . "\n";
			$buffer .= ("\t" . 'msg += "\n' . $AppUI->_('required_field_' . $rf, UI_OUTPUT_JS)
				. '";' . "\n");

			/* MSIE cannot handle the focus command for some disabled or hidden 
			 * fields like the start/end date fields. Another workaround would be 
			 * to check whether the field is disabled, but then one would for instance 
			 * need to use end_date instead of project_end_date in the projects addedit site.
			 * As this cannot be guaranteed since these fields are grabbed from a user-specifiable 
			 * System Value it's IMHO more safe to disable the focus for MSIE.
			 */
			$r = mb_strstr($rf, '.');
			$buffer .= ("\t"
				. 'if ((foc==false) && (navigator.userAgent.indexOf(\'MSIE\')== -1)) {'
				. "\n");
			$buffer .= "\t\t" . 'f.' . mb_substr($r, 1, mb_strpos($r, '.', 1) - 1) . '.focus();' . "\n";
			$buffer .= "\t\t" . 'foc=true;' . "\n";
			$buffer .= "\t}\n";
			$buffer .= "}\n";
		}
	}
	return $buffer;
}

/*
 * Make function htmlspecialchar_decode for older PHP versions
 */
if (!function_exists('htmlspecialchars_decode')) {
	function htmlspecialchars_decode($str)
	{
		return strtr($str, array_flip(get_html_translation_table(HTML_SPECIALCHARS)));
	}
}

/**
 * Return the number of bytes represented by a PHP.INI value
 */
function dPgetBytes($str)
{
	$val = $str;
	if (preg_match('/^([0-9]+)([kmg])?$/i', $str, $match)) {
		if (!empty($match[2])) {
			switch (mb_strtolower($match[2])) {
				case 'k':
					$val = $match[1] * 1024;
					break;
				case 'm':
					$val = $match[1] * 1024 * 1024;
					break;
				case 'g':
					$val = $match[1] * 1024 * 1024 * 1024;
					break;
			}
		}
	}
	return $val;
}

/**
 * Check for a memory limit, if we can't generate it then we fail.
 * @param int $min minimum amount of memory needed
 * @param bool $revert revert back to original config after test.
 * @return bool true if we have the minimum amount of RAM and if we can modify RAM
 */
function dPcheckMem($min = 0, $revert = false)
{
	// First of all check if we have the minimum memory requirement.
	$want = dPgetBytes($GLOBALS['dPconfig']['reset_memory_limit']);
	$have = ini_get('memory_limit');
	// Try upping the memory limit based on our config
	ini_set('memory_limit', $GLOBALS['dPconfig']['reset_memory_limit']);
	$now = dPgetBytes(ini_get('memory_limit'));
	// Revert, if necessary, back to the original after testing.
	if ($revert) {
		ini_set('memory_limit', $have);
	}
	return (($now < $want || $now < $min) ? false : true);
}

/*
 * From the PHP Manual, slightly modified to improve performance.
 * This is still a bit of a pig and is worse if there is nothing to find.
 */
function seems_utf8($Str)
{
	for ($i = 0, $len = mb_strlen($Str); $i < $len; $i++) {
		if (($ord = ord($Str[$i])) < 0x80)
			continue; # 0bbbbbbb
		else if (($ord & 0xE0) == 0xC0)
			$n = 1; # 110bbbbb
		else if (($ord & 0xF0) == 0xE0)
			$n = 2; # 1110bbbb
		else if (($ord & 0xF8) == 0xF0)
			$n = 3; # 11110bbb
		else if (($ord & 0xFC) == 0xF8)
			$n = 4; # 111110bb
		else if (($ord & 0xFE) == 0xFC)
			$n = 5; # 1111110b
		else
			return false; # Does not match any model
		for ($j = 0; $j < $n; $j++) { # n bytes matching 10bbbbbb follow ?
			if ((++$i == $len) || ((ord($Str[$i]) & 0xC0) != 0x80))
				return false;
		}
	}
	return true;
}


/**
 * "safe" utf8 decoder, only decodes if it finds utf8.
 */
function safe_utf8_decode($string)
{
	if (seems_utf8($string)) {
		return utf8_decode($string);
	} else {
		return $string;
	}
}

/**
 * Checks system requirements for login (PHP version, MySQL support) and returns warning messages if any.
 * Also includes any AppUI messages.
 *
 * @return string HTML content of warnings and errors
 */
function dPcheckLoginSystem()
{
	global $AppUI;
	$msg = '<span class="error">' . $AppUI->getMsg() . '</span>';
	return $msg;
}
/**
 * Per-request nonce for the Content-Security-Policy. Inline <script> blocks
 * that must stay inline carry it: <script nonce="<?php echo dPcspNonce(); ?>">.
 */
function dPcspNonce()
{
	static $nonce = null;
	if ($nonce === null) {
		$nonce = base64_encode(random_bytes(16));
	}
	return $nonce;
}

/**
 * The application's Content-Security-Policy (docs/deployment-security.md).
 * Enforced (see dPsendCspHeader()); violations are reported to csp-report.php.
 * No 'unsafe-eval': nothing loaded by the active modules needs it.
 * report-uri only: when report-to is also present Chrome ignores report-uri, and
 * its Reporting API delivery could not be verified (none arrived in testing).
 */
function dPcspPolicy()
{
	$base = rtrim(DP_BASE_URL, '/');
	return implode('; ', array(
		"default-src 'self'",
		// 'report-sample' puts the first 40 characters of a blocked script in the report.
		"script-src 'self' 'nonce-" . dPcspNonce() . "' 'report-sample'",
		"style-src 'self' 'unsafe-inline'",
		"img-src 'self' data:",
		"font-src 'self' data:",
		"connect-src 'self'",
		"frame-src 'self'",
		"frame-ancestors 'self'",
		"form-action 'self'",
		"base-uri 'self'",
		"object-src 'none'",
		'report-uri ' . $base . '/csp-report.php',
	));
}

/**
 * HTML attributes for js/dp-actions.js, the replacement for inline handlers:
 *   <a href="#"<?php echo dPaction('popCalendar', array('start_date')); ?>>
 *   <select<?php echo dPaction('changeView', array('$value'), 'change', array('submit' => '')); ?>>
 * Values are JSON-encoded and HTML-escaped here, so callers never build the
 * attribute strings themselves. $extra adds data-dp-<key> attributes
 * (confirm, href, submit, on).
 */
function dPaction($call, $args = array(), $on = null, $extra = array())
{
	global $locale_char_set;
	// Pages are in the locale's charset (several locales are ISO-8859 or Windows
	// code pages). Arguments become UTF-8 JSON with \u escapes, which is plain ASCII
	// and so right in any charset; other values are escaped in the page's charset.
	$page_charset = ($locale_char_set && mb_strtolower($locale_char_set) != 'utf-8') ? $locale_char_set : 'UTF-8';
	$html_charset = in_array(mb_strtoupper($page_charset), array('UTF-8', 'ISO-8859-1', 'ISO-8859-5',
		'ISO-8859-15', 'CP866', 'CP1251', 'CP1252', 'WINDOWS-1251', 'WINDOWS-1252', 'KOI8-R', 'BIG5',
		'GB2312', 'BIG5-HKSCS', 'SHIFT_JIS', 'EUC-JP', 'MACROMAN'), true)
		? $page_charset : 'ISO-8859-1'; // byte-transparent for other single-byte charsets
	$to_utf8 = function ($value) use (&$to_utf8, $page_charset) {
		if (is_array($value)) {
			return array_map($to_utf8, $value);
		}
		return is_string($value) ? dPtoUtf8($value, $page_charset) : $value;
	};
	$attrs = array();
	if ($on !== null) {
		$attrs['data-dp-on'] = $on;
	}
	if ($call !== null && $call !== '') {
		$attrs['data-dp-call'] = $call;
		if ($args) {
			$attrs['data-dp-args'] = json_encode($to_utf8(array_values($args)),
				JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
		}
	}
	foreach ($extra as $key => $value) {
		$attrs['data-dp-' . preg_replace('/[^a-z-]/', '', mb_strtolower($key))] = $value;
	}
	$html = '';
	foreach ($attrs as $name => $value) {
		$html .= ' ' . $name . '="' . htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, $html_charset) . '"';
	}
	return $html;
}

/**
 * Sends the policy, enforced unless $dPconfig['csp_report_only'] is true
 * (a rollback switch for a page that breaks: violations are then only reported).
 */
function dPsendCspHeader()
{
	if (headers_sent()) {
		return;
	}
	$name = dPgetConfig('csp_report_only') ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';
	header($name . ': ' . dPcspPolicy());
}
?>