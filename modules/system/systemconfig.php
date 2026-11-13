<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
// System Configuration: the settings of one section (or those a search finds), with
// their help, and the right control for each.
require_once DP_BASE_DIR . '/modules/system/admin_ui.php';

// check permissions
if (!$canEdit) {
	$AppUI->redirect('m=public&a=access_denied');
}
// Saving (and the test email) come back here.
$AppUI->savePlace();

$sections = dPadminSettingsSections();
$query = trim((string)dPgetCleanParam($_GET, 'q', ''));
$section = (string)dPgetCleanParam($_GET, 'section', 'general');
if (!isset($sections[$section])) {
	$section = 'general';
}

$dPcfg = new CConfig();
$settings = array();
foreach ($dPcfg->loadAll('config_group') as $c) {
	$settings[$c['config_name']] = $c;
}

/** A setting's label and help, translated; the help is '' when there is none. */
function dPconfigTexts($name)
{
	global $AppUI;
	$title = $AppUI->_($name . '_title', UI_OUTPUT_RAW);
	if ($title === '' || $title === $name . '_title') {
		$title = ucfirst(str_replace('_', ' ', $name));
	}
	$help = $AppUI->_($name . '_tooltip', UI_OUTPUT_RAW);
	if ($help === $name . '_tooltip') {
		$help = '';
	}
	return array($title, $help);
}

/** The control of a setting. */
function dPconfigControl($c, $dPcfg)
{
	global $AppUI;
	$name = $c['config_name'];
	$field = 'dPcfg[' . dPhtml($name) . ']';
	$id = 'cfg_' . preg_replace('/[^a-z0-9_]/i', '_', $name);
	$value = (string)$c['config_value'];
	$numbers = array('username_min_len' => 1, 'password_min_len' => 1, 'daily_working_hours' => 0.5,
		'mail_port' => 1, 'mail_timeout' => 1, 'task_reminder_days_before' => 1, 'task_reminder_repeat' => 1,
		'audit_log_days' => 1, 'gacl_timeout' => 1, 'ldap_port' => 1, 'ldap_version' => 1, 'debug' => 1);

	if ($name == 'cal_working_days') {
		$on = array_map('trim', explode(',', $value));
		$html = '<fieldset class="dpa-days"><legend class="hidden">' . $AppUI->_('Working days') . '</legend>';
		// Monday first; 0 is Sunday.
		$names = CDate::weekdayNames(false);
		foreach (array(1, 2, 3, 4, 5, 6, 0) as $i => $day) {
			$label = mb_substr($names[$i], 0, 2);
			$html .= '<label title="' . dPhtml($names[$i]) . '"><input type="checkbox" name="' . $field . '[]" value="' . $day . '"'
				. (in_array((string)$day, $on, true) ? ' checked="checked"' : '') . ' /><span>' . dPhtml($label) . '</span></label>';
		}
		return $html . '<input type="hidden" name="' . $field . '[]" value="" /></fieldset>';
	}
	if ($name == 'cal_day_start' || $name == 'cal_day_end') {
		$hours = array();
		for ($h = 0; $h < 24; $h++) {
			$hours[$h] = sprintf('%02d:00', $h);
		}
		return arraySelect($hours, $field, 'id="' . $id . '"', (int)$value);
	}
	if ($name == 'cal_day_increment') {
		$steps = array();
		foreach (array(5, 10, 15, 20, 30, 60) as $minutes) {
			$steps[$minutes] = $minutes . ' ' . $AppUI->_('minutes', UI_OUTPUT_RAW);
		}
		return arraySelect($steps, $field, 'id="' . $id . '"', (int)$value);
	}
	if ($name == 'host_style' || $name == 'host_locale') {
		$options = (($name == 'host_style') ? dPstyleOptions() : $AppUI->readDirs('locales'));
		unset($options['CVS'], $options['.svn']);
		if (!isset($options[$value])) {
			$options[$value] = $value;
		}
		return arraySelect($options, $field, 'id="' . $id . '"', $value);
	}
	if ($name == 'default_view_m') {
		$options = array();
		foreach ($AppUI->getActiveModules() as $dir => $ui_name) {
			$options[$dir] = $AppUI->_($ui_name, UI_OUTPUT_RAW);
		}
		if (!isset($options[$value])) {
			$options[$value] = $value;
		}
		asort($options);
		return arraySelect($options, $field, 'id="' . $id . '"', $value);
	}
	switch ($c['config_type']) {
		case 'select':
			$options = array();
			foreach ($dPcfg->getChildren($c['config_id']) as $child) {
				$options[$child['config_list_name']] = $AppUI->_($child['config_list_name'] . '_item_title', UI_OUTPUT_RAW);
			}
			return arraySelect($options, $field, 'id="' . $id . '"', $value);
		case 'checkbox':
			return '<span class="dpa-switch"><input type="checkbox" role="switch" id="' . $id . '" name="' . $field . '" value="true"'
				. ($value == 'true' ? ' checked="checked"' : '') . ' /><span></span></span>';
		case 'password':
			// Never sent back to the browser: left empty, the saved one is kept.
			return '<input type="password" class="dpa-wide" id="' . $id . '" name="' . $field . '" value="" autocomplete="new-password" placeholder="'
				. $AppUI->_($value !== '' ? 'Saved: type another to change it' : 'Not set') . '" />';
		default:
			if (isset($numbers[$name]) && ($value === '' || is_numeric($value))) {
				return '<input type="number" id="' . $id . '" name="' . $field . '" value="' . dPhtml($value) . '" step="' . $numbers[$name] . '" />';
			}
			return '<input type="text" class="dpa-wide" id="' . $id . '" name="' . $field . '" value="' . dPhtml($value) . '" />';
	}
}

/** One setting as a row: label, help and control. */
function dPconfigRow($c, $dPcfg, $with_section = false)
{
	global $AppUI;
	list($title, $help) = dPconfigTexts($c['config_name']);
	$id = 'cfg_' . preg_replace('/[^a-z0-9_]/i', '_', $c['config_name']);
	$html = '<div class="dpa-row"><div class="dpa-row-text"><label class="dpa-row-label" for="' . $id . '">' . dPhtml($title) . '</label>';
	if ($help !== '') {
		$html .= '<div class="dpa-row-help">' . dPhtml($help) . '</div>';
	}
	if ($with_section) {
		$sections = dPadminSettingsSections();
		$slug = dPadminSettingSection($c['config_name']);
		$html .= '<div class="dpa-section-tag">' . $AppUI->_($sections[$slug]['title']) . '</div>';
	}
	$html .= '<div class="dpa-row-name">' . dPhtml($c['config_name']) . '</div></div>';
	$html .= '<div class="dpa-row-control">' . dPconfigControl($c, $dPcfg)
		. '<input type="hidden" name="dPcfgShown[]" value="' . dPhtml($c['config_name']) . '" /></div></div>' . "\n";
	return $html;
}

// The panels to show: the section's subsections, or the search results.
$panels = array();
if ($query !== '') {
	$found = array();
	foreach ($settings as $name => $c) {
		list($title, $help) = dPconfigTexts($name);
		if (mb_stripos($name . ' ' . $title . ' ' . $help, $query) !== false) {
			$found[] = $c;
		}
	}
	$panels[''] = $found;
	$current = 'search';
} else {
	foreach ($sections[$section]['groups'] as $group => $names) {
		$panels[$group] = array();
		foreach ($names as $name) {
			if (isset($settings[$name])) {
				$panels[$group][] = $settings[$name];
			}
		}
	}
	if ($section == 'advanced') {
		// Settings no section lists (added by a module, for example).
		$listed = array();
		foreach ($sections as $s) {
			foreach ($s['groups'] as $names) {
				$listed = array_merge($listed, $names);
			}
		}
		foreach ($settings as $name => $c) {
			if (!in_array($name, $listed, true)) {
				$panels['Other'][] = $c;
			}
		}
	}
	$current = 's:' . $section;
}

dPadminOpen($current);
if ($query !== '') {
	echo '<div class="dpa-head"><div class="dpa-crumb"><a href="?m=system">' . $AppUI->_('System Administration') . '</a></div>'
		. '<h1>' . $AppUI->_('Settings matching') . ' “' . dPhtml($query) . '”</h1></div>';
} else {
	dPadminHeading($sections[$section]['title'], $sections[$section]['text']);
}

if (is_dir(DP_BASE_DIR . '/install')) {
	echo '<div class="dpa-note dpa-warn">' . dPadminIcon('alert') . '<span>'
		. $AppUI->_('The install directory is still on the server: remove it or deny access to it.') . '</span></div>';
}
?>
<form name="cfgFrm" action="?m=system&amp;a=systemconfig" method="post" class="dpa-main">
<input type="hidden" name="dosql" value="do_systemconfig_aed" />
<?php
$shown = 0;
foreach ($panels as $group => $rows) {
	if (!$rows) {
		continue;
	}
	echo '<section class="dpa-panel">' . ($group !== '' ? '<h2>' . $AppUI->_($group) . '</h2>' : '') . "\n";
	foreach ($rows as $c) {
		echo dPconfigRow($c, $dPcfg, $query !== '');
		$shown++;
	}
	echo "</section>\n";
	if ($section == 'work' && $group == 'Working hours' && $query === '' && $AppUI->isActiveModule('holiday')) {
		echo '<a class="dpa-card" href="?m=holiday"><span class="dpa-card-icon">' . dPadminIcon('cal') . '</span><span>'
			. '<span class="dpa-card-title">' . $AppUI->_('Holidays and calendars') . '</span><span class="dpa-card-text">'
			. $AppUI->_('Holidays are not working days. Each workplace can have its own calendar.') . '</span></span></a>' . "\n";
	}
}
if (!$shown) {
	echo '<div class="dpa-panel">' . $AppUI->_('No setting matches the search.') . '</div>';
} else {
?>
<div class="dpa-savebar">
	<span class="dpa-savebar-note"><?php echo $AppUI->_('Changes apply to every user as soon as they are saved.'); ?></span>
	<a class="dpa-btn" href="?m=system&amp;a=systemconfig&amp;<?php echo $query !== '' ? 'q=' . rawurlencode($query) : 'section=' . rawurlencode($section); ?>"><?php echo $AppUI->_('Discard changes'); ?></a>
	<button type="submit" class="dpa-btn dpa-primary"><?php echo $AppUI->_('Save changes'); ?></button>
</div>
<?php } ?>
</form>
<?php
if ($section == 'mail' && $query === '') {
?>
<form name="testMailFrm" action="?m=system&amp;a=systemconfig" method="post" class="dpa-panel">
	<input type="hidden" name="dosql" value="do_testmail" />
	<h2><label for="test_mail_to"><?php echo $AppUI->_('Send a test email to'); ?></label></h2>
	<div class="dpa-row-control">
		<input type="email" class="dpa-wide" name="test_mail_to" id="test_mail_to" value="<?php echo dPhtml($AppUI->user_email); ?>" />
		<button type="submit" class="dpa-btn"><?php echo $AppUI->_('Send'); ?></button>
	</div>
	<p class="dpa-row-help"><?php echo $AppUI->_('Uses the saved settings: save your changes first.'); ?></p>
</form>
<?php
}
dPadminClose();
?>
