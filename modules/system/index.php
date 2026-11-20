<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
// System Admin start page: the state of the installation and a card for each admin page.
require_once DP_BASE_DIR . '/modules/system/admin_ui.php';

$AppUI->savePlace();

// Modules: active ones, and those whose code is newer than their installed version.
$q = new DBQuery();
$q->addTable('modules');
$q->addQuery('mod_directory, mod_version, mod_active');
$modules = $q->loadList();
$active_modules = 0;
$upgrades = 0;
foreach ($modules as $row) {
	$active_modules += $row['mod_active'] ? 1 : 0;
	$setup = DP_BASE_DIR . '/modules/' . basename($row['mod_directory']) . '/setup.php';
	if (is_file($setup) && preg_match('/\$config\s*\[\s*[\'"]mod_version[\'"]\s*\]\s*=\s*[\'"]([^\'"]+)/', (string)file_get_contents($setup), $v)
		&& $v[1] != $row['mod_version']) {
		$upgrades++;
	}
}

global $db;
$db_info = is_object($db) && method_exists($db, 'ServerInfo') ? $db->ServerInfo() : array();
$db_version = isset($db_info['version']) ? preg_replace('/^(\d+\.\d+).*$/', '$1', $db_info['version']) : '';
$db_name = (isset($db_info['description']) && stripos($db_info['description'], 'mariadb') !== false)
	|| stripos((string)(isset($db_info['version']) ? $db_info['version'] : ''), 'mariadb') !== false ? 'MariaDB' : 'MySQL';

// The installer creates admin with the password "passwd" and nothing forces a change.
$q = new DBQuery();
$q->addTable('users');
$q->addQuery('user_id, user_password');
$q->addWhere("user_username = 'admin'");
$default_admin = $q->loadHash();
$q->clear();
$default_admin_id = 0;
if ($default_admin) {
	$stored = (string)$default_admin['user_password'];
	if (password_verify('passwd', $stored) || (strlen($stored) === 32 && hash_equals($stored, md5('passwd')))) {
		$default_admin_id = (int)$default_admin['user_id'];
	}
}

$transport = dPgetConfig('mail_transport', 'php');
$mail_from = (string)dPgetConfig('mail_from', '');

dPadminOpen('home');
?>
<div class="dpa-head-row">
	<?php dPadminHeading('System Administration', 'Settings, people and modules of this installation.', false); ?>
	<?php echo dPadminSearch(); ?>
</div>

<section class="dpa-status" aria-label="<?php echo $AppUI->_('Status'); ?>">
	<?php if (is_dir(DP_BASE_DIR . '/install')) { ?>
	<div class="dpa-stat dpa-warn">
		<span class="dpa-stat-label"><?php echo $AppUI->_('Attention'); ?></span>
		<span><?php echo $AppUI->_('The install directory is still on the server: remove it or deny access to it.'); ?></span>
	</div>
	<?php } ?>
	<?php if ($default_admin_id) { ?>
	<div class="dpa-stat dpa-warn">
		<span class="dpa-stat-label"><?php echo $AppUI->_('Attention'); ?></span>
		<span><?php echo $AppUI->_('The admin account still has the password set by the installer: change it.'); ?></span>
		<a class="dpa-stat-more" href="?m=admin&amp;a=viewuser&amp;user_id=<?php echo $default_admin_id; ?>">admin</a>
	</div>
	<?php } ?>
	<div class="dpa-stat">
		<span class="dpa-stat-label"><?php echo $AppUI->_('Version'); ?></span>
		<span class="dpa-stat-value">dotPulse <?php echo dPhtml($AppUI->getVersion()); ?></span>
		<span class="dpa-stat-more">PHP <?php echo dPhtml(PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION); ?><?php echo $db_version !== '' ? ' · ' . $db_name . ' ' . dPhtml($db_version) : ''; ?></span>
	</div>
	<div class="dpa-stat">
		<span class="dpa-stat-label"><?php echo $AppUI->_('Active modules'); ?></span>
		<span class="dpa-stat-value"><?php echo (int)$active_modules; ?></span>
		<?php if ($upgrades) { ?>
		<a class="dpa-stat-more" href="?m=system&amp;a=viewmods"><?php echo (int)$upgrades . ' ' . $AppUI->_($upgrades == 1 ? 'upgrade to install' : 'upgrades to install'); ?></a>
		<?php } else { ?>
		<span class="dpa-stat-more"><?php echo $AppUI->_('All up to date'); ?></span>
		<?php } ?>
	</div>
	<div class="dpa-stat">
		<span class="dpa-stat-label"><?php echo $AppUI->_('Email'); ?></span>
		<span class="dpa-stat-value"><?php echo $transport == 'smtp' ? 'SMTP' : $AppUI->_('PHP mail'); ?></span>
		<span class="dpa-stat-more"><?php echo $mail_from !== '' ? dPhtml($mail_from) : '<a href="?m=system&amp;a=systemconfig&amp;section=mail">' . $AppUI->_('No sender address set') . '</a>'; ?></span>
	</div>
</section>

<?php
$groups = array();
foreach (dPadminPages() as $key => $page) {
	$groups[$page['group']][$key] = $page;
}
foreach ($groups as $group => $pages) {
	echo '<section class="dpa-cards-group"><h2>' . $AppUI->_($group) . '</h2><div class="dpa-cards">' . "\n";
	foreach ($pages as $page) {
		echo '<a class="dpa-card" href="' . dPhtml($page['href']) . '"><span class="dpa-card-icon">' . dPadminIcon($page['icon']) . '</span>'
			. '<span><span class="dpa-card-title">' . $AppUI->_($page['title']) . '</span>'
			. '<span class="dpa-card-text">' . $AppUI->_($page['text']) . "</span></span></a>\n";
	}
	echo "</div></section>\n";
}

dPadminClose();
?>
