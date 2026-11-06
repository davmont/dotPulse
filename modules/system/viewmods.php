<?php /* SYSTEM $Id: viewmods.php 6046 2010-10-04 09:04:46Z ajdonnison $*/
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

$AppUI->savePlace();

$canEdit = getPermission($m, 'edit');
$canRead = getPermission($m, 'view');
if (!$canRead) {
	$AppUI->redirect('m=public&a=access_denied');
}

$hidden_modules = array(
	'public',
	'install',
);
$q = new DBQuery;
$q->addQuery('*');
$q->addTable('modules');
foreach ($hidden_modules as $no_show) {
	$q->addWhere('mod_directory != \'' . $no_show . '\'');
}
$q->addOrder('mod_ui_order');
$modules = db_loadList($q->prepare());
// get the modules actually installed on the file system
$modFiles = $AppUI->readDirs('modules');

// Module commands change the installation, so the links post frmModCmd (with the CSRF token)
// instead of sending a GET request to domodsql.
function modCmdLink($cmd, $mod_id, $mod_directory = '', $confirm = '')
{
	return 'href="#"' . dPaction('modCmd', array($cmd, (int)$mod_id, (string)$mod_directory), null,
		$confirm ? array('confirm' => $confirm) : array());
}

require_once DP_BASE_DIR . '/modules/system/admin_ui.php';

// What each installed module offers (from its setup.php): upgrade and configure.
$installed = array();
$available = array();
foreach ($modules as $row) {
	if (isset($modFiles[$row['mod_directory']])) {
		$modFiles[$row['mod_directory']] = '';
	}
	$config = array();
	$setup = DP_BASE_DIR . '/modules/' . $row['mod_directory'] . '/setup.php';
	if (file_exists($setup)) {
		include_once $setup;
	}
	$row['_upgrade'] = isset($config['mod_version']) && $config['mod_version'] != $row['mod_version'];
	$row['_configure'] = !empty($config['mod_config']);
	$installed[] = $row;
}
foreach ($modFiles as $v) {
	if ($v && !in_array($v, $hidden_modules)) {
		$available[] = $v;
	}
}
$upgrades = count(array_filter($installed, function ($r) { return $r['_upgrade']; }));

dPadminOpen('viewmods');
dPadminHeading('Modules', 'Install, turn on and upgrade modules, and choose which ones appear in the menu.');
if ($upgrades) {
	echo '<div class="dpa-note dpa-warn">' . dPadminIcon('alert') . '<span>' . (int)$upgrades . ' '
		. $AppUI->_($upgrades == 1 ? 'module has an upgrade to install: back up the database first.' : 'modules have an upgrade to install: back up the database first.')
		. '</span></div>';
}
?>
<div class="dpa-scroll">
<table class="dpa-table">
<thead><tr>
	<th><?php echo $AppUI->_('Order'); ?></th>
	<th><?php echo $AppUI->_('Module'); ?></th>
	<th><?php echo $AppUI->_('Status'); ?></th>
	<th><?php echo $AppUI->_('Menu'); ?></th>
	<th><?php echo $AppUI->_('Actions'); ?></th>
</tr></thead>
<tbody>
<?php
foreach ($installed as $row) {
	$id = (int)$row['mod_id'];
	echo '<tr>';
	// order in the menu
	echo '<td class="dpa-actions">';
	if ($canEdit) {
		if ($row['mod_ui_order'] > 0) {
			echo '<a ' . modCmdLink('moveup', $id) . ' title="' . $AppUI->_('Move up') . '" aria-label="' . $AppUI->_('Move up') . '">&#9650;</a>';
		}
		echo '<a ' . modCmdLink('movedn', $id) . ' title="' . $AppUI->_('Move down') . '" aria-label="' . $AppUI->_('Move down') . '">&#9660;</a>';
	}
	echo ' <span class="dpa-row-name">' . (int)$row['mod_ui_order'] . '</span></td>';
	// name, type and version
	echo '<td><div class="dpa-row-label">' . $AppUI->_($row['mod_name']) . '</div><div class="dpa-row-help">'
		. dPhtml($row['mod_directory']) . ' · ' . $AppUI->_($row['mod_type']) . ' · ' . dPhtml($row['mod_version']) . '</div></td>';
	// status
	echo '<td>';
	if ($row['_upgrade']) {
		echo '<span class="dpa-badge dpa-up">' . $AppUI->_('Upgrade available') . '</span> ';
	}
	echo '<span class="dpa-badge' . ($row['mod_active'] ? '' : ' dpa-off') . '">' . $AppUI->_($row['mod_active'] ? 'active' : 'disabled') . '</span></td>';
	// menu
	echo '<td><span class="dpa-badge' . ($row['mod_ui_active'] ? '' : ' dpa-off') . '">' . $AppUI->_($row['mod_ui_active'] ? 'visible' : 'hidden') . '</span>'
		. ($row['mod_ui_name'] ? ' <span class="dpa-row-help">' . $AppUI->_($row['mod_ui_name']) . '</span>' : '') . '</td>';
	// actions
	echo '<td><div class="dpa-actions">';
	if ($canEdit) {
		if ($row['_upgrade']) {
			echo '<a class="dpa-btn dpa-primary" ' . modCmdLink('upgrade', $id, '', $AppUI->_('Are you sure?', UI_OUTPUT_RAW)) . '>' . $AppUI->_('upgrade') . '</a>';
		}
		echo '<a class="dpa-btn" ' . modCmdLink('toggle', $id) . '>' . $AppUI->_($row['mod_active'] ? 'Turn off' : 'Turn on') . '</a>';
		echo '<a class="dpa-btn" ' . modCmdLink('toggleMenu', $id) . '>' . $AppUI->_($row['mod_ui_active'] ? 'Hide from menu' : 'Show in menu') . '</a>';
		if ($row['_configure']) {
			echo '<a class="dpa-btn" ' . modCmdLink('configure', $id) . '>' . $AppUI->_('configure') . '</a>';
		}
		if ($row['mod_type'] != 'core') {
			echo '<a class="dpa-btn dpa-danger" ' . modCmdLink('remove', $id, '',
				$AppUI->_('This will delete all data associated with the module!', UI_OUTPUT_RAW) . "\n\n" . $AppUI->_('Are you sure?', UI_OUTPUT_RAW) . "\n")
				. '>' . $AppUI->_('remove') . '</a>';
		}
	}
	echo "</div></td></tr>\n";
}
?>
</tbody>
</table>
</div>
<?php if ($available) { ?>
<section class="dpa-panel">
	<h2><?php echo $AppUI->_('Not installed'); ?></h2>
	<?php foreach ($available as $v) { ?>
	<div class="dpa-row">
		<div class="dpa-row-text"><div class="dpa-row-label"><?php echo dPhtml($v); ?></div></div>
		<?php if ($canEdit) { ?><div class="dpa-row-control"><a class="dpa-btn" <?php echo modCmdLink('install', 0, $v); ?>><?php echo $AppUI->_('install'); ?></a></div><?php } ?>
	</div>
	<?php } ?>
</section>
<?php } ?>
<form name="frmModCmd" method="post" action="?m=system&amp;a=domodsql">
	<input type="hidden" name="cmd" value="" />
	<input type="hidden" name="mod_id" value="" />
	<input type="hidden" name="mod_directory" value="" />
</form>
<script type="text/javascript" nonce="<?php echo dPcspNonce(); ?>">
function modCmd(cmd, mod_id, mod_directory) {
	var f = document.frmModCmd;
	f.cmd.value = cmd;
	f.mod_id.value = mod_id;
	f.mod_directory.value = mod_directory;
	f.submit();
}
</script>
<?php dPadminClose(); ?>
