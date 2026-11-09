<?php // $Id: db.php 4955 2007-05-26 01:35:42Z caseydk $
/** {{{
 * @license		http://www.gnu.org/licenses/gpl.txt GNU Public License (GPL)
 * @copyright	2003-2005 The dotProject Development Team <core-developers@dotproject.net>
 * 
 * @package		dotProject/install
 * @version		CVS: $Id: db.php 4955 2007-05-26 01:35:42Z caseydk $
 * }}}
 */
$baseDir = dirname(dirname(__FILE__));
define('DP_BASE_DIR', $baseDir);
// includes/config.php refers to DP_BASE_URL; base.php is not loaded here, so define it
// the same way do_install_db.php computes the site URL.
if (!defined('DP_BASE_URL')) {
	$installerBaseUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https://' : 'http://')
		. (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : getenv('HTTP_HOST'))
		. dirname(dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : getenv('SCRIPT_NAME')));
	define('DP_BASE_URL', $installerBaseUrl);
}
?>
<html>
<head>
	<title>dotPulse Installer</title>
	<meta name="Description" content="dotPulse Installer" />
 	<link rel="stylesheet" type="text/css" href="../style/material/main.css">
	<style>
		body { background-color: #f5f5f6; margin: 0; padding: 0; }
		.installer-header { background-color: #1976d2; color: #fff; padding: 16px 32px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 24px; display: flex; align-items: center; }
		.installer-header h1 { margin: 0; font-size: 24px; color: #fff; font-weight: 500; display: flex; align-items: center; }
		.installer-header img { filter: brightness(0) invert(1); margin-right: 16px; height: 32px; }
		.installer-container { max-width: 1000px; margin: 0 auto; padding: 0 20px 40px 20px; }
		.card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 24px; margin-bottom: 24px; }
		.card h2 { margin-top: 0; color: #1976d2; font-size: 18px; font-weight: 500; border-bottom: 1px solid #eee; padding-bottom: 12px; margin-bottom: 20px; }
		table.tbl { margin-top: 0; border: none; box-shadow: none; width: 100%; }
		table.tbl td { padding: 10px 0; border: none; vertical-align: middle; }
		table.tbl td.item { width: 30%; color: #555; font-weight: 500; }
		input.text, select.text { width: 100%; max-width: 400px; }
		.actions { display: flex; justify-content: space-between; margin-top: 32px; padding-top: 24px; border-top: 1px solid #eee; gap: 10px; flex-wrap: wrap; }
		.actions-secondary { display: flex; gap: 10px; }
		.recommendation { font-size: 12px; color: #4caf50; font-weight: 600; margin-bottom: 4px; text-transform: uppercase; }
	</style>
</head>
<body>
<div class="installer-header">
	<h1><img src="../images/brand/icon.svg" width="40" height="40" alt="dotPulse"/> dotPulse Installer</h1>
</div>

<div class="installer-container">
	<div class="card">
		<h2>Database Configuration</h2>
<?php
if ($_POST['mode'] == 'upgrade') {
	@include_once '../includes/config.php';
} elseif (is_file("../includes/config.php")) {
	require_once 'check_upgrade.php';
	@include_once "../includes/config.php";
	if (dPcheckExistingDB($dPconfig)) {
		die('<div class="error-box">dotPulse (or dotProject) appears to already be installed, aborting install.</div>');
	}
} else {
	@include_once "../includes/config-dist.php";
}
?>
<form name="instFrm" action="do_install_db.php" method="post">
<input type="hidden" name="mode" value="<?php echo htmlspecialchars($_POST['mode'], ENT_QUOTES); ?>" />
<table cellspacing="0" cellpadding="0" border="0" class="tbl">
	<tr>
		<td class="item">Database Server Type</td>
		<td align="left">
		<select name="dbtype" size="1" class="text">
			<option value="mysqli" <?php if("mysqli"== $dPconfig['dbtype'] || "mysql" == $dPconfig['dbtype']){ ?>selected="selected"<?php } ?>>MySQL/MariaDB - Recommended</option>
			<option value="mysqlt" <?php if("mysqlt"== $dPconfig['dbtype']){ ?>selected="selected"<?php } ?>>MySQL With Transactions</option>
			<option value="postgres" <?php if("postgres"== $dPconfig['dbtype']){ ?>selected="selected"<?php } ?>>PostgreSQL</option>
		</select>
		<div style="font-size: 11px; color: #d32f2f; margin-top: 4px;">Note: MariaDB is fully supported via the mysqli driver.</div>
		</td>
	</tr>
	<tr>
		<td class="item">Database Host Name</td>
		<?php // An installed site's database settings are not shown to whoever opens the
		// installer: enter them, with the password, from includes/config.php. ?>
		<td align="left"><input class="text" type="text" name="dbhost" value="<?php echo is_file('../includes/config.php') ? '' : htmlspecialchars($dPconfig['dbhost'], ENT_QUOTES); ?>" /></td>
	</tr>
	<tr>
		<td class="item">Database Name</td>
		<td align="left"><input class="text" type="text" name="dbname" value="<?php echo is_file('../includes/config.php') ? '' : htmlspecialchars($dPconfig['dbname'], ENT_QUOTES); ?>" /></td>
	</tr>
	<tr>
		<td class="item">Database Prefix</td>
		<td align="left"><input class="text" type="text" name="dbprefix" value="<?php echo is_file('../includes/config.php') ? '' : htmlspecialchars($dPconfig['dbprefix'], ENT_QUOTES); ?>" /></td>
	</tr>
	<tr>
		<td class="item">Database User Name</td>
		<td align="left"><input class="text" type="text" name="dbuser" value="<?php echo is_file('../includes/config.php') ? '' : htmlspecialchars($dPconfig['dbuser'], ENT_QUOTES); ?>" /></td>
	</tr>
	<tr>
		<td class="item">Database User Password</td>
		<td align="left"><input class="text" type="password" name="dbpass" value="<?php echo is_file('../includes/config.php') ? '' : htmlspecialchars($dPconfig['dbpass'], ENT_QUOTES); ?>" /><?php if (is_file('../includes/config.php')) { ?> <span class="item" style="font-size: 12px; color: #666;">(as set in includes/config.php)</span><?php } ?></td>
	</tr>
	<tr>
		<td class="item">Use Persistent Connection?</td>
		<td align="left"><input type="checkbox" name="dbpersist" value="1" <?php echo ($dPconfig['dbpersist']==true) ? 'checked="checked"' : ''; ?> /> <span class="item" style="font-size: 12px; color: #666;">(Recommended for high-performance setups)</span></td>
	</tr>
<?php if ($_POST['mode'] == 'install'): ?>
	<tr>
		<td class="item">Drop Existing Database?</td>
		<td align="left"><input type="checkbox" name="dbdrop" value="1" /> <span class="warning" style="font-size: 12px;">Caution: If checked, all existing data in this database will be lost!</span></td>
	</tr>
<?php endif; ?>
</table>
</div> <!-- End Database Config Card -->

<div class="card">
	<h2>Data Backup</h2>
	<p style="font-size: 13px; color: #666; margin-bottom: 20px;">We recommend downloading an XML Schema of your existing database before performing an upgrade or a clean install over an existing one.</p>
	<div style="display: flex; align-items: center; gap: 16px;">
		<input class="button" style="background-color: #607d8b;" type="submit" name="dobackup" value="Download XML Backup" />
		<span style="font-size: 12px; color: #999;">Only restorable with the Backup module.</span>
	</div>
</div>

<div class="actions">
	<div class="actions-secondary">
		<input class="button" style="background-color: #666;" type="submit" name="do_db" value="<?php echo htmlspecialchars($_POST['mode'], ENT_QUOTES); ?> Database Only" />
		<input class="button" style="background-color: #666;" type="submit" name="do_cfg" value="Write Config Only" />
	</div>
	<div style="text-align: right;">
		<div class="recommendation">Recommended</div>
		<input class="button" type="submit" name="do_db_cfg" value="<?php echo htmlspecialchars($_POST['mode'], ENT_QUOTES); ?> & Write Config" />
	</div>
</div>
</div> <!-- End Container -->
</form>
</body>
</html>
