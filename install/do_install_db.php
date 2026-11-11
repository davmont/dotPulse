<?php
define('DP_INSTALLER', true);
ob_start();
// $Id: do_install_db.php 6185 2012-11-15 04:30:47Z ajdonnison $
//Max Execution Time in Installation No Limit 
set_time_limit(0);

include_once 'check_upgrade.php';
if ($_POST['mode'] == 'install' && dPcheckUpgrade() == 'upgrade') {
  die('Security Check: dotPulse seems to be already configured. Communication broken for Security Reasons!');
}
if (!dPinstallerAuthorised($_POST)) {
  die('Security Check: the database settings do not match includes/config.php. Enter the settings, including the password, from that file.');
}
######################################################################################################################

$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off') ? 'https://' : 'http://';
$baseUrl .= isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : getenv('HTTP_HOST');
$baseUrl .= isset($_SERVER['SCRIPT_NAME']) ? dirname(dirname($_SERVER['SCRIPT_NAME'])) : dirname(dirname(getenv('SCRIPT_NAME')));

require_once DP_BASE_DIR . '/install/install.inc.php';
require_once DP_BASE_DIR . '/install/versions.inc.php';

$AppUI = new InstallerUI(); // Fake AppUI class to appease the db_connect utilities.

$dbMsg = '';
$cFileMsg = 'Not Created';
$dbErr = false;
$cFileErr = false;

// MySQL/MariaDB through mysqli is the only database dotPulse supports (the old 'mysql'
// value of existing configurations means the same).
$dbtype = 'mysqli';
$dbprefix = trim(dPInstallGetParam($_POST, 'dbprefix', ''));
$dbhost = trim(dPInstallGetParam($_POST, 'dbhost', ''));
$dbname = trim(dPInstallGetParam($_POST, 'dbname', ''));
$dbuser = trim(dPInstallGetParam($_POST, 'dbuser', ''));
$dbpass = trim(dPInstallGetParam($_POST, 'dbpass', ''));
$dbdrop = dPInstallGetParam($_POST, 'dbdrop', false);
$mode = dPInstallGetParam($_POST, 'mode', 'upgrade');
$dbpersist = dPInstallGetParam($_POST, 'dbpersist', false);
$dobackup = isset($_POST['dobackup']);
$do_db = isset($_POST['do_db']);
$do_db_cfg = isset($_POST['do_db_cfg']);
$do_cfg = isset($_POST['do_cfg']);

// Create a dPconfig array for dependent code
global $dPconfig;
$dPconfig = array(
  'dbtype' => $dbtype,
  'dbhost' => $dbhost,
  'dbname' => $dbname,
  'dbprefix' => $dbprefix,
  'dbpass' => $dbpass,
  'dbuser' => $dbuser,
  'dbpersist' => $dbpersist,
  'root_dir' => $baseDir,
  'base_url' => $baseUrl
);

global $lastDBUpdate;
$lastDBUpdate = '';

require_once(DP_BASE_DIR . '/lib/adodb/adodb.inc.php');
@include_once DP_BASE_DIR . '/includes/version.php';

$db = NewADOConnection($dbtype);

if (!empty($db)) {
  $dbc = $db->Connect($dbhost, $dbuser, $dbpass);
  if ($dbc)
    $existing_db = $db->SelectDB($dbname);
} else {
  $dbc = false;
}

if ('mysql' == $dbtype) {
  // Quick hack to ensure MySQL behaves itself (#2323)
  $db->Execute("SET sql_mode := ''");
}

$current_version = $dp_version_major . '.' . $dp_version_minor;
$current_version .= isset($dp_version_patch) ? ('.' . $dp_version_patch) : '';
$current_version .= isset($dp_version_prepatch) ? ('-' . $dp_version_prepatch) : '';

if ($dobackup) {

  if ($dbc) {
    require_once(DP_BASE_DIR . '/lib/adodb/adodb-xmlschema.inc.php');

    $schema = new adoSchema($db);

    $sql = $schema->ExtractSchema(true);

  header('Content-Disposition: attachment; filename="dPdbBackup'.date('Ymd').date('His').'.xml"');
  header('Content-Type: text/xml');
  ob_end_clean();
  echo $sql;
	exit;
 } else {
  $backupMsg = 'ERROR: No Database Connection available! - Backup not performed!';
 }
}

$early_out = ob_get_contents();
ob_end_clean();
?>
<html>

<head>
 <title>dotPulse Installer</title>
 <meta name="Description" content="dotPulse Installer">
 <link rel="stylesheet" type="text/css" href="../style/material/main.css">
 <style>
		body { background-color: #f5f5f6; margin: 0; padding: 0; }
		.installer-header { background-color: #1976d2; color: #fff; padding: 16px 32px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 24px; display: flex; align-items: center; }
		.installer-header h1 { margin: 0; font-size: 24px; color: #fff; font-weight: 500; display: flex; align-items: center; }
		.installer-header img { filter: brightness(0) invert(1); margin-right: 16px; height: 32px; }
		.installer-container { max-width: 1000px; margin: 0 auto; padding: 0 20px 40px 20px; }
		.card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 24px; margin-bottom: 24px; }
		.card h2 { margin-top: 0; color: #1976d2; font-size: 18px; font-weight: 500; border-bottom: 1px solid #eee; padding-bottom: 12px; margin-bottom: 20px; }
		.console { background-color: #263238; color: #eceff1; padding: 15px; border-radius: 4px; font-family: 'Courier New', Courier, monospace; font-size: 13px; line-height: 1.5; height: 300px; overflow-y: auto; white-space: pre-wrap; margin-bottom: 0; }
		.feedback-row { display: flex; align-items: flex-start; gap: 20px; padding: 12px 0; border-bottom: 1px solid #eee; }
		.feedback-row:last-child { border-bottom: none; }
		.feedback-label { width: 30%; font-weight: 600; color: #555; }
		.feedback-content { flex: 1; }
	</style>
</head>

<body>
<div class="installer-header">
	<h1><img src="../images/brand/icon.svg" width="40" height="40" alt="dotPulse"/> dotPulse Installer</h1>
</div>

<div class="installer-container">
	<div class="card">
		<h2>Installation Progress</h2>
		<div class="console">
<?php
if ($early_out) {
	echo trim($early_out) . "\n";
}

if ($dobackup)
  dPmsg($backupMsg);

if ($dbc && ($do_db || $do_db_cfg)) {

  if ($mode == 'install') {

    if ($dbdrop) {
      dPmsg('Dropping previous database');
      $db->Execute('DROP DATABASE IF EXISTS `' . $dbname . '`');
      $existing_db = false;
    }

    if (!$existing_db) {
      dPmsg('Creating new Database');
      $db->Execute('CREATE DATABASE `' . $dbname . '`');
      $dbError = $db->ErrorNo();

      if ($dbError <> 0 && $dbError <> 1007) {
        $dbErr = true;
        $dbMsg .= 'A Database Error occurred. Database has not been created! The provided database details are probably not correct.<br>' . $db->ErrorMsg() . '<br>';

      }
    }
  }

  // For some reason a db->SelectDB call here doesn't work.
  $db->Execute('USE `' . $dbname . '`');
  $db_version = InstallGetVersion($mode, $db);

  $code_updated = '';
  if ($mode == 'upgrade') {
    dPmsg('Applying database updates');
    $last_version = $db_version['code_version'];
    // Convert the code version to a version string.
    if ($last_version != $current_version) {
      // Per-release scripts (db/upgrade_<from>_to_<to>.sql), for the versions on the path.
      $from_key = array_search($last_version, $versionPath);
      $to_key = array_search($current_version, $versionPath);
      for ($i = $from_key; $from_key !== false && $to_key !== false && $i < $to_key; $i++) {
        $from_version = str_replace(array('.', '-'), '', $versionPath[$i]);
        $to_version = str_replace(array('.', '-'), '', $versionPath[$i + 1]);
        $file = DP_BASE_DIR . "/db/upgrade_{$from_version}_to_{$to_version}.sql";
        if (file_exists($file)) {
          InstallLoadSql($file, $db_version['last_db_update']);
          $db_version['last_db_update'] = $lastDBUpdate; // Global set by InstallLoadSql.
        }
      }
    }
    // The date-stamped changes not applied yet, whatever the version jump. This only ran when
    // the code version was unchanged, so a new version (2.3.1 -> 3.0.0) skipped all of them.
    if (file_exists(DP_BASE_DIR . '/db/upgrade_latest.sql')) {
      InstallLoadSql(DP_BASE_DIR . '/db/upgrade_latest.sql', $db_version['last_db_update']);
    }
  } else {
    dPmsg('Installing database');
    InstallLoadSql(DP_BASE_DIR . '/db/dotproject.sql');
    // After all the updates, find the new version information.
    $new_version = InstallGetVersion($mode, $db);
    $lastDBUpdate = $new_version['last_db_update'];
    // The fresh schema already has every change in upgrade_latest.sql: record its newest
    // entry, or the next upgrade applies them again ("Duplicate column").
    if (preg_match_all('/^#\s*(\d{8})\b/m', (string)@file_get_contents(DP_BASE_DIR . '/db/upgrade_latest.sql'), $stamps)) {
      $lastDBUpdate = max($lastDBUpdate, max($stamps[1]));
    }
    $code_updated = $new_version['last_code_update'];
  }

  $dbError = $db->ErrorNo();
  if ($dbError <> 0 && $dbError <> 1007) {
    $dbErr = true;
    $dbMsg .= 'A Database Error occurred. Database has probably not been populated completely!<br>' . $db->ErrorMsg() . '<br>';
  }
  if ($dbErr) {
    $dbMsg = 'DB setup incomplete - the following errors occured:<br>' . $dbMsg;
  } else {
    $dbMsg = 'Database successfully setup<br>';
  }

  if ($mode == 'upgrade') {
    dPmsg('Applying data modifications');
    // Check for an upgrade script and run it if necessary.
    // Note we don't need to run individual version files any more
    if (file_exists(DP_BASE_DIR . '/db/upgrade_latest.php')) {
      include_once DP_BASE_DIR . '/db/upgrade_latest.php';
      $code_updated = dPupgrade($db_version['code_version'], $current_version, $db_version['last_code_update']);
    } else {
      dPmsg('No data updates required');
    }
  } else {
    include_once DP_BASE_DIR . '/db/upgrade_permissions.php'; // Always required on install.
  }

  dPmsg('Updating version information');
  // No matter what occurs we should update the database version in the dpversion table.
  if (empty($lastDBUpdate)) {
    $lastDBUpdate = $code_updated;
  }
  $sql = "UPDATE " . $dbprefix . "dpversion
 SET db_version = '$dp_version_major',
 last_db_update = '$lastDBUpdate',
 code_version = '$current_version',
 last_code_update = '$code_updated'
 WHERE 1";
  $db->Execute($sql);

} else {
  $dbMsg = 'Not Created';
  if (!$dbc) {
    $dbErr = 1;
    $dbMsg .= '<br/>No Database Connection available! ' . ($db ? $db->ErrorMsg() : '');
  }
}

// always create the config file content

dPmsg('Creating config');
$config = '<?php ' . "\n";
$config .= 'if (!defined(\'DP_BASE_DIR\')) {' . "\n";
$config .= '	die(\'You should not access this file directly.\');' . "\n";
$config .= '}' . "\n";
$config .= '### Copyright (c) 2004, The dotProject Development Team dotproject.net and sf.net/projects/dotproject ###' . "\n";
$config .= '### All rights reserved. Released under GPL License. For further Information see LICENSE ###' . "\n";
$config .= "\n";
$config .= '### CONFIGURATION FILE AUTOMATICALLY GENERATED BY THE DOTPROJECT INSTALLER ###' . "\n";
$config .= '### FOR INFORMATION ON MANUAL CONFIGURATION AND FOR DOCUMENTATION SEE ./includes/config-dist.php ###' . "\n";
$config .= "\n";
$config .= '$dPconfig[\'dbtype\'] = ' . var_export($dbtype, true) . ';' . "\n";
$config .= '$dPconfig[\'dbhost\'] = ' . var_export($dbhost, true) . ';' . "\n";
$config .= '$dPconfig[\'dbname\'] = ' . var_export($dbname, true) . ';' . "\n";
$config .= '$dPconfig[\'dbprefix\'] = ' . var_export($dbprefix, true) . ';' . "\n";
$config .= '$dPconfig[\'dbuser\'] = ' . var_export($dbuser, true) . ';' . "\n";
$config .= '$dPconfig[\'dbpass\'] = ' . var_export($dbpass, true) . ';' . "\n";
$config .= '$dPconfig[\'dbpersist\'] = ' . ($dbpersist ? 'true' : 'false') . ";\n";
$config .= '$dPconfig[\'root_dir\'] = DP_BASE_DIR;' . "\n";
$config .= '$dPconfig[\'base_url\'] = DP_BASE_URL;' . "\n";
$config .= '?>';
$config = trim($config);

if ($do_cfg || $do_db_cfg) {
  if ((is_writable('../includes/config.php') || !is_file('../includes/config.php')) && ($fp = fopen('../includes/config.php', 'w'))) {
    fputs($fp, $config, mb_strlen($config));
    fclose($fp);
    $cFileMsg = 'Config file written successfully' . "\n";
  } else {
    $cFileErr = true;
    $cFileMsg = 'Config file could not be written' . "\n";
  }
}

//echo $msg;
?>
		</div> <!-- End Console -->
	</div> <!-- End Installation Progress Card -->

	<div class="card">
		<h2>Configuration Summary</h2>
		<div class="feedback-row">
			<div class="feedback-label">Database Installation:</div>
			<div class="feedback-content"><b style="color:<?php echo $dbErr ? '#d32f2f' : '#4caf50'; ?>"><?php echo $dbMsg; ?></b></div>
		</div>
		<div class="feedback-row">
			<div class="feedback-label">Configuration File:</div>
			<div class="feedback-content"><b style="color:<?php echo $cFileErr ? '#d32f2f' : '#4caf50'; ?>"><?php echo $cFileMsg; ?></b></div>
		</div>
		
		<?php if (($do_cfg || $do_db_cfg) && $cFileErr) { ?>
			<div style="margin-top: 20px; border: 1px dashed #ccc; padding: 16px; background-color: #fafafa;">
				<p style="font-size: 13px; color: #666; margin-bottom: 12px;">Automatic writing failed. Please manually create <code>./includes/config.php</code> and paste the following content:</p>
				<textarea class="text" style="width: 100%; height: 200px; font-family: monospace; font-size: 12px;"><?php echo $msg.$config; ?></textarea>
			</div>
		<?php } ?>
	</div>

	<?php if (!$dbErr && (!$cFileErr || !($do_cfg || $do_db_cfg))) { ?>
	<div class="card" style="text-align: center; background-color: #e8f5e9; border: 1px solid #c8e6c9;">
		<h2 style="color: #2e7d32; border: none; margin-bottom: 8px;">Success!</h2>
		<p style="margin-bottom: 24px;">The installation/upgrade process is complete.</p>
		<?php if ($mode == 'install') { ?>
			<div style="margin-bottom: 24px; padding: 12px; background-color: #fff; border-radius: 4px; display: inline-block;">
				<p style="margin: 0; font-size: 14px;"><strong>Admin Username:</strong> admin</p>
				<p style="margin: 4px 0 0 0; font-size: 14px;"><strong>Initial Password:</strong> passwd</p>
			</div>
		<?php } ?>
		<div style="margin-top: 10px;">
			<a href="<?php echo $baseUrl.'/index.php'; ?>" class="button" style="text-decoration: none; padding: 12px 32px;">Proceed to dotPulse</a>
		</div>
	</div>
	<?php } ?>
</div> <!-- End Container -->
</body>

</html>