<?php // $Id: index.php 4791 2007-02-26 21:04:48Z merlinyoda $
/*
All files in this work, except the modules/ticketsmith directory, are now
covered by the following copyright notice.  The ticketsmith module is
under the Voxel Public License.  See modules/ticketsmith/LICENSE
for details.  Please note that included libraries in the lib directory
may have their own license.

Copyright (c) 2003-2005 The dotProject Development Team <core-developers@dotproject.net>

    This file is part of dotProject.

    dotProject is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    dotProject is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with dotProject; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

The full text of the GPL is in the COPYING file.
*/

require_once 'check_upgrade.php';
$mode = dPcheckUpgrade();
?>
<html>
<head>
	<title>dotPulse Installer</title>
	<meta name="Description" content="dotPulse Installer">
 	<link rel="stylesheet" type="text/css" href="install.css">
	<style>
		body { background-color: #f5f5f6; margin: 0; padding: 0; }
		.installer-header { background-color: #1976d2; color: #fff; padding: 16px 32px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 24px; display: flex; align-items: center; }
		.installer-header h1 { margin: 0; font-size: 24px; color: #fff; font-weight: 500; display: flex; align-items: center; }
		.installer-header img { filter: brightness(0) invert(1); margin-right: 16px; height: 32px; }
		.installer-container { max-width: 1000px; margin: 0 auto; padding: 0 20px 40px 20px; }
		.welcome-card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 24px; margin-bottom: 24px; }
		.welcome-card h2 { margin-top: 0; color: #1976d2; font-size: 18px; font-weight: 500; }
		.welcome-card p { color: #555; line-height: 1.6; }
		table.tbl { margin-top: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; border: none; }
		table.tbl td.title { background-color: #f5f5f5; color: #1976d2; font-weight: 600; padding: 16px; border-bottom: 1px solid #e0e0e0; text-transform: uppercase; font-size: 14px; letter-spacing: 0.5px; }
		table.tbl td { padding: 12px 16px; border-bottom: 1px solid #e0e0e0; }
		table.tbl tr:last-child td { border-bottom: none; }
		.btn-container { text-align: center; margin: 24px 0; }
		.error-box { background-color: #ffebee; border-left: 4px solid #d32f2f; padding: 16px; color: #c62828; margin-bottom: 20px; border-radius: 4px; }
		li { list-style: none; position: relative; padding-left: 20px; }
		li::before { content: '•'; position: absolute; left: 0; color: #1976d2; font-weight: bold; }
	</style>
</head>
<body>
<div class="installer-header">
	<h1><img src="../images/brand/icon.svg" width="40" height="40" alt="dotPulse"/> dotPulse Installer</h1>
</div>

<div class="installer-container">
	<div class="welcome-card">
		<h2>Welcome</h2>
		<p>Welcome to the dotPulse Installer! This wizard will guide you through setting up the database and creating the appropriate configuration files. In some cases, a manual installation step may be necessary.</p>
		
		<div style="background-color: #e3f2fd; border-left: 4px solid #1976d2; padding: 16px; margin: 20px 0; border-radius: 4px;">
			<p style="margin: 0; font-weight: 500; color: #0d47a1;">Requirements Check</p>
			<p style="margin: 8px 0 0 0; font-size: 13px; color: #1565c0;">Please review the compatibility checks below. A database connection and a writable <code>./includes/config.php</code> are essential for a successful setup.</p>
		</div>

		<?php if ($mode == 'upgrade') { ?>
			<div class="error-box">
				<strong>Note:</strong> It appears an existing dotProject installation was detected. The installer will attempt to upgrade your system. <em>We strongly recommend taking a full database backup before proceeding!</em>
			</div>
		<?php } ?>

		<div class="btn-container">
			<form action="db.php" method="post" name="form" id="form">
				<input class="button" type="submit" name="next" value="Start <?php echo $mode == 'install' ? "Installation" : "Upgrade" ?>" />
				<input type="hidden" name="mode" value="<?php echo $mode; ?>" />
			</form>
		</div>
	</div>

	<div style="margin-top: 32px;">
<?php
// define some necessary variables for check inclusion
$failedImg = '<span class="error" style="font-size: 1.3em; margin-right: 4px;">✖</span>';
$okImg = '<span class="ok" style="font-size: 1.3em; margin-right: 4px;">✔</span>';
$tblwidth = '100%';
$cfgDir = '../includes';
$cfgFile = '../includes/config.php';
$filesDir = '../files';
$locEnDir = '../locales/en';
$tmpDir = '../files/temp';
include_once('vw_idx_check.php');
?>
	</div>
</div>
</body>
</html>