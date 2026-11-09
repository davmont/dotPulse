<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/*
 * Shared by the System Admin pages: the settings sections (System Configuration),
 * the icons and the side menu. The pages load modules/system/admin.css, which
 * styles them the same in every theme.
 */

/**
 * The settings sections: slug => title, description and the settings (config_name) in
 * them, in order, by subsection. A setting no section lists (a module's own, for
 * example) goes to Advanced.
 */
function dPadminSettingsSections()
{
	return array(
		'general' => array('title' => 'General', 'icon' => 'gear',
			'text' => 'Name, address, language, currency and the page shown after login.',
			'groups' => array(
				'Site' => array('company_name', 'page_title', 'site_domain'),
				'Language and appearance' => array('host_locale', 'host_style', 'currency_symbol'),
				'Start page' => array('default_view_m', 'default_view_a', 'default_view_tab'),
			)),
		'work' => array('title' => 'Work and calendar', 'icon' => 'clock',
			'text' => 'The working hours that task dates and durations are calculated with, and the task rules.',
			'groups' => array(
				'Working hours' => array('cal_working_days', 'cal_day_start', 'cal_day_end', 'daily_working_hours', 'cal_day_increment', 'cal_day_view_show_minical'),
				'Tasks' => array('check_task_dates', 'check_overallocation', 'check_task_empty_dynamic', 'restrict_task_time_editing', 'show_all_task_assignees', 'direct_edit_assignment', 'enable_gantt_charts', 'restrict_color_selection'),
			)),
		'mail' => array('title' => 'Notifications and email', 'icon' => 'mail',
			'text' => 'How dotPulse sends task notifications, reminders and password resets.',
			'groups' => array(
				'Mail server' => array('mail_transport', 'mail_host', 'mail_port', 'mail_smtp_tls', 'mail_auth', 'mail_user', 'mail_pass', 'mail_from', 'email_prefix', 'mail_timeout', 'mail_defer'),
				'Task reminders' => array('task_reminder_control', 'task_reminder_days_before', 'task_reminder_repeat'),
			)),
		'security' => array('title' => 'Security and sessions', 'icon' => 'shield',
			'text' => 'Passwords, sessions, the REST API and the audit log.',
			'groups' => array(
				'Accounts' => array('admin_username', 'username_min_len', 'password_min_len', 'user_contact_inactivate', 'user_contact_activate'),
				'Sessions' => array('session_handling', 'session_idle_time', 'session_max_lifetime', 'session_gc_scan_queue'),
				'API and audit' => array('api_enabled', 'audit_log_days'),
			)),
		'login' => array('title' => 'Login (LDAP)', 'icon' => 'key',
			'text' => 'Log in with the dotPulse password or against an LDAP directory.',
			'groups' => array(
				'Method' => array('auth_method'),
				'LDAP directory' => array('ldap_host', 'ldap_port', 'ldap_version', 'ldap_base_dn', 'ldap_user_filter', 'ldap_search_user', 'ldap_search_pass', 'ldap_allow_login'),
			)),
		'files' => array('title' => 'Files', 'icon' => 'file',
			'text' => 'Versions, indexing of file contents and the programs that extract their text.',
			'groups' => array(
				'Files' => array('files_ci_preserve_attr', 'files_show_versions_edit'),
				'Indexing' => array('index_max_file_size', 'parser_default', 'parser_application/msword', 'parser_text/html', 'parser_application/pdf'),
			)),
		'integrations' => array('title' => 'Integrations', 'icon' => 'plug',
			'text' => 'Mantis and the ticket links.',
			'groups' => array(
				'Mantis' => array('mantis_url', 'mantis_allow_http'),
				'Tickets' => array('link_tickets_kludge'),
			)),
		'advanced' => array('title' => 'Advanced', 'icon' => 'sliders',
			'text' => 'Permission cache, change log, debugging and translation checks. Change them only if you know their effect.',
			'groups' => array(
				'Permissions cache' => array('gacl_cache', 'gacl_expire', 'gacl_cache_dir', 'gacl_timeout'),
				'Logs and debugging' => array('log_changes', 'debug', 'display_debug', 'reset_memory_limit'),
				'Translations' => array('locale_warn', 'locale_alert'),
				'Other' => array(),
			)),
	);
}

/** The section of each setting: config_name => slug (unlisted settings: Advanced). */
function dPadminSettingSection($name)
{
	static $where = null;
	if ($where === null) {
		$where = array();
		foreach (dPadminSettingsSections() as $slug => $section) {
			foreach ($section['groups'] as $names) {
				foreach ($names as $n) {
					$where[$n] = $slug;
				}
			}
		}
	}
	return isset($where[$name]) ? $where[$name] : 'advanced';
}

/** An icon (inline SVG, drawn in currentColor). */
function dPadminIcon($name)
{
	$paths = array(
		'users' => 'M16 19v-1a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v1M9 10a3 3 0 1 0 0-6 3 3 0 0 0 0 6M22 19v-1a4 4 0 0 0-3-3.9M16 4.1a3 3 0 0 1 0 5.8',
		'shield' => 'M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z',
		'key' => 'M15 7a4 4 0 1 1-3.9 5H3v3h3v3h3v-3h2.1A4 4 0 0 1 15 7z',
		'list' => 'M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01',
		'clock' => 'M12 7v5l3 2M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18',
		'cal' => 'M3 9h18M8 3v4M16 3v4M5 5h14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2',
		'tag' => 'M3 12V3h9l9 9-9 9zM7.5 7.5h.01',
		'form' => 'M4 4h16v16H4zM8 9h8M8 13h8M8 17h5',
		'gear' => 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z',
		'mail' => 'M3 6h18v12H3zM3 7l9 6 9-6',
		'plug' => 'M9 2v6M15 2v6M6 8h12v4a6 6 0 0 1-12 0zM12 18v4',
		'globe' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18',
		'sliders' => 'M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12M14 4v4M8 10v4M16 16v4',
		'import' => 'M12 3v12M7 10l5 5 5-5M4 21h16',
		'file' => 'M14 3H6v18h12V7zM14 3v4h4',
		'modules' => 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z',
		'prefs' => 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18M12 8v4l3 3',
		'check' => 'M20 6L9 17l-5-5',
		'alert' => 'M12 9v4M12 17h.01M10.3 3.9L1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z',
	);
	$d = isset($paths[$name]) ? $paths[$name] : $paths['gear'];
	return '<svg class="dpa-icon" aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="' . $d . '"/></svg>';
}

/**
 * The System Admin pages, for the side menu and the start page: key => title, link,
 * icon, description, menu group. Pages of modules that are not active are left out.
 */
function dPadminPages()
{
	global $AppUI;
	$pages = array();
	$add = function ($key, $group, $title, $href, $icon, $text) use (&$pages) {
		$pages[$key] = array('group' => $group, 'title' => $title, 'href' => $href, 'icon' => $icon, 'text' => $text);
	};
	$add('users', 'People and access', 'Users', '?m=admin', 'users', 'Accounts, passwords and the roles of each user.');
	$add('roles', 'People and access', 'Roles and permissions', '?m=system&u=roles', 'shield', 'What each role can see and change.');
	$add('s:login', 'People and access', 'Login (LDAP)', '?m=system&a=systemconfig&section=login', 'key', 'Log in with the dotPulse password or against an LDAP directory.');
	$add('s:security', 'People and access', 'Security and sessions', '?m=system&a=systemconfig&section=security', 'shield', 'Passwords, sessions, the REST API and the audit log.');
	$add('auditlog', 'People and access', 'Audit log', '?m=system&a=auditlog', 'list', 'Logins and changes made by administrators.');
	$add('contacts_ldap', 'People and access', 'Import contacts', '?m=system&a=contacts_ldap', 'import', 'Contacts from an LDAP directory.');
	$add('s:work', 'Work', 'Work and calendar', '?m=system&a=systemconfig&section=work', 'clock', 'Working days and hours, and the task rules.');
	if ($AppUI->isActiveModule('holiday')) {
		$add('holiday', 'Work', 'Holidays and calendars', '?m=holiday', 'cal', 'The holidays of each workplace.');
	}
	$add('billingcode', 'Work', 'Billing codes', '?m=system&a=billingcode', 'tag', 'The codes of the time log.');
	$add('custom_field_editor', 'Work', 'Custom fields', '?m=system&a=custom_field_editor', 'form', 'Fields of your own on projects, tasks, companies and more.');
	$add('s:general', 'System', 'General', '?m=system&a=systemconfig&section=general', 'gear', 'Name, address, language, currency and start page.');
	$add('s:mail', 'System', 'Notifications and email', '?m=system&a=systemconfig&section=mail', 'mail', 'Mail server, sender and task reminders.');
	$add('s:files', 'System', 'Files', '?m=system&a=systemconfig&section=files', 'file', 'Versions and indexing of file contents.');
	$add('s:integrations', 'System', 'Integrations', '?m=system&a=systemconfig&section=integrations', 'plug', 'Mantis and the ticket links.');
	$add('s:advanced', 'System', 'Advanced', '?m=system&a=systemconfig&section=advanced', 'sliders', 'Permission cache, logs and debugging.');
	$add('viewmods', 'System', 'Modules', '?m=system&a=viewmods', 'modules', 'Install, turn on and upgrade modules.');
	$add('addeditpref', 'System', 'Default user preferences', '?m=system&a=addeditpref', 'prefs', 'Language, date format and theme of new users.');
	$add('syskeys', 'System', 'Lookup values', '?m=system&u=syskeys', 'list', 'The statuses, priorities and types that the forms offer.');
	$add('translate', 'System', 'Translations', '?m=system&a=translate', 'globe', 'The interface texts in each language.');
	return $pages;
}

/**
 * Opens a System Admin page: the stylesheet and the side menu, with $current
 * highlighted ($current 'home': the start page, whose cards are the menu).
 */
function dPadminOpen($current)
{
	global $AppUI;
	$groups = array();
	foreach (dPadminPages() as $key => $page) {
		$groups[$page['group']][$key] = $page;
	}
	echo '<link rel="stylesheet" type="text/css" href="./modules/system/admin.css" />' . "\n";
	echo '<div class="dpa"><div class="dpa-layout">' . "\n";
	if ($current === 'home') {
		echo "<main class=\"dpa-main\">\n";
		return;
	}
	echo '<nav class="dpa-side" aria-label="' . $AppUI->_('System Admin') . '">' . "\n";
	echo '<a class="dpa-side-home" href="?m=system">' . $AppUI->_('System Administration') . "</a>\n";
	echo dPadminSearch();
	foreach ($groups as $group => $pages) {
		echo '<div class="dpa-side-group"><div class="dpa-side-title">' . $AppUI->_($group) . "</div>\n";
		foreach ($pages as $key => $page) {
			$on = ($key === $current) ? ' class="dpa-on" aria-current="page"' : '';
			echo '<a href="' . dPhtml($page['href']) . '"' . $on . '>' . $AppUI->_($page['title']) . "</a>\n";
		}
		echo "</div>\n";
	}
	echo "</nav>\n<main class=\"dpa-main\">\n";
}

/** The form that finds a setting by name or description. */
function dPadminSearch()
{
	global $AppUI;
	return '<form class="dpa-search" method="get" action="index.php" role="search"><input type="hidden" name="m" value="system" />'
		. '<input type="hidden" name="a" value="systemconfig" /><label><span>' . $AppUI->_('Find a setting') . '</span>'
		. '<input type="search" name="q" value="' . dPhtml(dPgetCleanParam($_GET, 'q', '')) . '" placeholder="'
		. $AppUI->_('email, password…') . '" /></label></form>' . "\n";
}

/** Closes what dPadminOpen() opened. */
function dPadminClose()
{
	echo "</main></div></div>\n";
}

/** The page heading: breadcrumb, title and description. */
function dPadminHeading($title, $text = '', $crumb = true)
{
	global $AppUI;
	echo '<div class="dpa-head">';
	if ($crumb) {
		echo '<div class="dpa-crumb"><a href="?m=system">' . $AppUI->_('System Administration') . '</a></div>';
	}
	echo '<h1>' . $AppUI->_($title) . '</h1>';
	if ($text !== '') {
		echo '<p>' . $AppUI->_($text) . '</p>';
	}
	echo "</div>\n";
}
?>
