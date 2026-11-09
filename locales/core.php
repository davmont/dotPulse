<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly');
}

// The common strings, those of the modules that add tabs to this one, the module's
// (locales/[the-locale]/[the-module].inc) and, for modules that ship their own,
// modules/[the-module]/locales/[the-locale].inc.
// Each file is checked and evaluated on its own and the module's own file is read
// last, so its strings win (reading only that file dropped the module's other
// strings, e.g. the projects ones).
$dp_locale_files = array(
	DP_BASE_DIR . '/locales/' . $AppUI->user_locale . '/common.inc',
	DP_BASE_DIR . '/locales/' . $AppUI->user_locale . '/' . $m . '.inc',
);
switch ($m) {
	case 'departments':
		$dp_locale_files[] = DP_BASE_DIR . '/locales/' . $AppUI->user_locale . '/companies.inc';
		break;
	case 'system':
		$dp_locale_files[] = DP_BASE_DIR . '/locales/' . $dPconfig['host_locale'] . '/styles.inc';
		break;
}
$dp_locale_files[] = DP_BASE_DIR . '/modules/' . $m . '/locales/' . $AppUI->user_locale . '.inc';
// Modules that add tabs to this module's pages (projects_tab.*.php...) bring their
// strings: before this module's own, which win.
$dp_tab_modules = array();
if (isset($_SESSION['all_tabs'][$m]) && is_array($_SESSION['all_tabs'][$m])) {
	array_walk_recursive($_SESSION['all_tabs'][$m], function ($value, $key) use (&$dp_tab_modules, $m) {
		if ($key === 'module' && is_string($value) && $value !== $m && preg_match('/^[A-Za-z0-9_]+$/', $value)) {
			$dp_tab_modules[$value] = true;
		}
	});
}
foreach (array_keys($dp_tab_modules) as $dp_tab_module) {
	array_splice($dp_locale_files, 1, 0, array(
		DP_BASE_DIR . '/locales/' . $AppUI->user_locale . '/' . $dp_tab_module . '.inc',
		DP_BASE_DIR . '/modules/' . $dp_tab_module . '/locales/' . $AppUI->user_locale . '.inc',
	));
}
unset($dp_tab_modules, $dp_tab_module);
$GLOBALS['translate'] = array();
foreach ($dp_locale_files as $dp_locale_file) {
	$dp_locale_source = is_readable($dp_locale_file) ? file_get_contents($dp_locale_file) : '';
	if ($dp_locale_source !== '' && $dp_locale_source !== false && dPisTranslationSource($dp_locale_source)) {
		try {
			eval('$dp_locale_strings = array(' . $dp_locale_source . "\n);");
			$GLOBALS['translate'] = array_merge($GLOBALS['translate'], $dp_locale_strings);
		} catch (ParseError $e) {
			error_log('dotProject: translation file skipped, it does not parse: ' . $dp_locale_file);
		}
	}
}
unset($dp_locale_files, $dp_locale_file, $dp_locale_source, $dp_locale_strings);
?>