<?php // $Id: vw_idx_check.php 4304 2006-06-05 14:53:06Z gregorerhardt $

global $cfgDir, $cfgFile, $failedImg, $filesDir, $locEnDir, $okImg, $tblwidth, $tmpDir, $baseDir;
$cfgDir = isset($cfgDir) ? $cfgDir : "$baseDir/includes";
$cfgFile = isset($cfgFile) ? $cfgFile : "$baseDir/includes/config.php";
$filesDir = isset($filesDir) ? $filesDir : "$baseDir/files";
$tmpDir = isset($tmpDir) ? $tmpDir : "$baseDir/files/temp";
$locEnDir = isset($locEnDir) ? $locEnDir : "$baseDir/locales/en";
$tblwidth = isset($tblwidth) ? $tblwidth : '100%';
$chmod = 0777;

function dPgetIniSize($val)
{
	$val = trim($val);
	if (strlen($val <= 1))
		return $val;
	$last = $val[strlen($val) - 1];
	switch ($last) {
		case 'k':
		case 'K':
			return (int) $val * 1024;
			break;
		case 'm':
		case 'M':
			return (int) $val * 1048576;
			break;
		default:
			return $val;
	}
}

?>

<table cellspacing="0" cellpadding="3" border="0" class="tbl" width="<?php echo $tblwidth; ?>" align="center">
	<tr>
		<td class="title" colspan="2">Check for Requirements</td>
	</tr>
	<tr>
		<td class="item">
			PHP Version &gt;= 8.2
		</td>
		<td align="left">
			<?php echo version_compare(phpversion(), '8.2', '<') ? '<b class="error">' . $failedImg . ' (' . phpversion() . '): dotPulse may not work. Please upgrade!</b>' : '<b class="ok">' . $okImg . '</b><span class="item"> (' . phpversion() . ')</span>'; ?>
		</td>
	</tr>
	<tr>
		<td class="item">
			<li>Server API</li>
		</td>
		<td align="left">
			<?php echo (php_sapi_name() != "cgi") ? '<b class="ok">' . $okImg . '</b><span class="item"> (' . php_sapi_name() . ')</span>' : '<b class="error">' . $failedImg . ' CGI mode is likely to have problems</b>'; ?>
		</td>
	</tr>

	<tr>
		<td class="item">
			<li>GD Support (for images in PDF reports)</li>
		</td>
		<td align="left">
			<?php echo extension_loaded('gd') ? '<b class="ok">' . $okImg . '</b>' : '<b class="error">' . $failedImg . '</b> Images in PDF reports may not show.'; ?>
		</td>
	</tr>
	<tr>
		<td class="item">
			<li>Multibyte string Support (mbstring)</li>
		</td>
		<td align="left">
			<?php echo extension_loaded('mbstring') ? '<b class="ok">' . $okImg . '</b>' : '<b class="error">' . $failedImg . ' Not available: dotPulse needs the mbstring extension</b>'; ?>
		</td>
	</tr>
	<tr>
		<td class="item">
			<li>Zlib compression Support</li>
		</td>
		<td align="left">
			<?php echo extension_loaded('zlib') ? '<b class="ok">' . $okImg . '</b>' : '<b class="error">' . $failedImg . '</b> Some non-core modules such as Backup may have restricted operation.'; ?>
		</td>
	</tr>
	<tr>
		<td class="item">
			<li>UTF8 Decode Support</li>
		</td>
		<td align="left">
			<?php echo function_exists('utf8_decode') ? '<b class="ok">' . $okImg . '</b>' : '<b class="error">' . $failedImg . '</b> utf8_decode function not found: some reports may show wrong characters'; ?>
		</td>
	</tr>
	<?php
	$maxfileuploadsize = min(dPgetIniSize(ini_get('upload_max_filesize')), dPgetIniSize(ini_get('post_max_size')));
	$memory_limit = dPgetIniSize(ini_get('memory_limit'));
	if ($memory_limit > 0 && $memory_limit < $maxfileuploadsize)
		$maxfileuploadsize = $memory_limit;
	// Convert back to human readable numbers
	if ($maxfileuploadsize > 1048576) {
		$maxfileuploadsize = (int) ($maxfileuploadsize / 1048576) . 'M';
	} elseif ($maxfileuploadsize > 1024) {
		$maxfileuploadsize = (int) ($maxfileuploadsize / 1024) . 'K';
	}
	?>

	<tr>
		<td class="item">
			<li>File Uploads</li>
		</td>
		<td align="left">
			<?php echo ini_get('file_uploads') ? '<b class="ok">' . $okImg . '</b><span class="item"> (Max File Upload Size: ' . $maxfileuploadsize . ')</span>' : '<b class="error">' . $failedImg . '</b><span class="warning"> Upload functionality will not be available</span>'; ?>
		</td>
	</tr>
	<tr>
		<td class="item">
			<li>Session Save Path writable?</li>
		</td>
		<td align="left">
			<?php
			$sspath = ini_get('session.save_path');
			if (!$sspath) {
				echo "<b class='error'>$failedImg Warning:</b> <span class='item'>session.save_path</span> <b class='error'> is not set. This can be ignored if you use \"App-Level\" session handling.</b>";
			} elseif (@is_dir($sspath) && @is_writable($sspath)) {
				echo "<b class='ok'>$okImg</b> <span class='item'>($sspath)</span>";
			} else {
				echo "<b class='error'>$failedImg Warning:</b> <span class='item'>$sspath</span><b class='error'> not existing or not writable. This can be ignored if you use \"App-Level\" session handling (using the database).</b>";
			}
			?>
		</td>
	</tr>
	<tr>
		<td class="title" colspan="2"><br />Database and directory</td>
	</tr>
	<tr>
		<td class="item">
			<li>MySQL/MariaDB Support (mysqli)</li>
		</td>
		<td align="left">
			<?php echo function_exists('mysqli_connect') ? '<b class="ok">' . $okImg . '</b>' : '<b class="error">' . $failedImg . ' Not available: dotPulse needs the mysqli extension</b>'; ?>
		</td>
	</tr>
	<tr>
		<td class="item">
			<li>LDAP Support (optional, for LDAP login)</li>
		</td>
		<td align="left">
			<?php echo function_exists('ldap_connect') ? '<b class="ok">' . $okImg . '</b>' : '<span class="warning">' . $failedImg . ' Not available: only needed for LDAP login</span>'; ?>
		</td>
	</tr>
	<tr>
		<td class="title" colspan="2"><br />Check for Directory and File Permissions</td>
	</tr>
	<tr>
		<td class="item" colspan="2">If the message 'World Writable' appears after a file/directory, then Permissions
			for this File have been set to allow all users to write to this file/directory.
			Consider changing this to a more restrictive setting to improve security. You will need to do this manually.
		</td>
	</tr>
	<?php
	$okMessage = "";
	if (!is_writable($tmpDir)) {
		@chmod($tmpDir, $chmod);
	}
	$filemode = @fileperms($tmpDir);
	if ($filemode & 2) {
		$okMessage = "<span class='ok'> World Writable</span>";
	}
	?>
	<tr>
		<td class="item">./files/temp writable?</td>
		<td align="left">
			<?php echo is_writable($tmpDir) ? '<b class="ok">' . $okImg . '</b>' . $okMessage : '<b class="error">' . $failedImg . '</b><span class="warning"> PDF report generation will be disabled</span>'; ?>
		</td>
	</tr>
	<?php
	$okMessage = "";
	if (!is_writable($locEnDir)) {
		@chmod($locEnDir, $chmod);
	}
	$filemode = @fileperms($locEnDir);
	if ($filemode & 2) {
		$okMessage = "<span class='ok'> World Writable</span>";
	}
	?>
	<tr>
		<td class="item">./locales/en writable?</td>
		<td align="left">
			<?php echo is_writable($locEnDir) ? '<b class="ok">' . $okImg . '</b>' . $okMessage : '<b class="error">' . $failedImg . '</b><span class="warning"> Translation files cannot be saved. Check /locales and subdirectories for permissions.</span>'; ?>
		</td>
	</tr>
	<tr>
		<td class="title" colspan="2"><br />Recommended PHP Settings</td>
	</tr>
	<tr>
		<td class="item">Safe Mode = OFF?</td>
		<td align="left">
			<?php echo !ini_get('safe_mode') ? '<b class="ok">' . $okImg . '</b>' : '<b class="error">' . $failedImg . '</b><span class="warning"></span>'; ?>
		</td>
	</tr>
	<tr>
		<td class="item">Register Globals = OFF?</td>
		<td align="left">
			<?php echo !ini_get('register_globals') ? '<b class="ok">' . $okImg . '</b>' : '<b class="error">' . $failedImg . '</b><span class="warning"> There are security risks with this turned ON</span>'; ?>
		</td>
	</tr>
	<tr>
		<td class="item">Session AutoStart = OFF?</td>
		<td align="left">
			<?php echo !ini_get('session.auto_start') ? '<b class="ok">' . $okImg . '</b>' : '<b class="error">' . $failedImg . '</b><span class="warning"> Set session.auto_start to Off: dotPulse starts its own session</span>'; ?>
		</td>
	</tr>
	<tr>
		<td class="item">Session Use Cookies = ON?</td>
		<td align="left">
			<?php echo ini_get('session.use_cookies') ? '<b class="ok">' . $okImg . '</b>' : '<b class="error">' . $failedImg . '</b><span class="warning"> Try setting to ON if you are experiencing problems logging in</span>'; ?>
		</td>
	</tr>
	<tr>
		<td class="item">Session Use Trans Sid = OFF?</td>
		<td align="left">
			<?php echo (!ini_get('session.use_only_cookies') && !ini_get('session.use_trans_sid')) ? '<b class="ok">' . $okImg . '</b>' : '<b class="error">' . $failedImg . '</b><span class="warning"> There are security risks with this turned ON</span>'; ?>
		</td>
	</tr>
	<tr>
		<td class="title" colspan="2"><br />Other Recommendations</td>
	</tr>
	<tr>
		<td class="item" colspan="2">
			<p>The dotPulse project openly recommends Free Open Source software (FOSS). This is not just
				because dotPulse is a FOSS application, but because we believe that the FOSS development
				method results in better software, with a lower Total Cost of Ownership (TCO).</p>
			<p>These recommendations reflect that belief, and the fact that as FOSS developers, we
				develop on FOSS systems, so they will have better support sooner than other non-FOSS
				systems.</p>
		</td>
	</tr>
	<tr>
		<td class="item">Free Operating System?</td>
		<td align="left"><?php echo (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN') ? '<b class="ok">' . $okImg . '</b><span class="item"> (' . php_uname() . ')</span>' : '<b class="error">' . $failedImg . '</b><span class="warning">
	It seems you are using a proprietary operating system.  You might want to consider a Free Open Source operating system such as Linux.  dotPulse is usually tested on Linux first and will always have better support for Linux than other operating systems.
	</span>'; ?></td>
	</tr>
	<tr>
		<td class="item">Supported Web Server?</td>
		<td align="left"><?php echo (stristr($_SERVER['SERVER_SOFTWARE'], 'apache') != false) ? '<b class="ok">' . $okImg . '</b><span class="item"> (' . $_SERVER['SERVER_SOFTWARE'] . ')</span>' : '<b class="error">' . $failedImg . '</b><span class="warning">
	It seems you are using an unsupported web server.  Only Apache Web server is fully supported by dotPulse, and using other web servers may result in unexpected problems.
	</span>'; ?></td>
	</tr>
	<tr>
		<td class="item">Standards Compliant Browser?</td>
		<td align="left"><?php echo (stristr($_SERVER['HTTP_USER_AGENT'], 'msie') == false) ? '<b class="ok">' . $okImg . '</b><span class="item"> (' . $_SERVER['HTTP_USER_AGENT'] . ')</span>' : '<b class="error">' . $failedImg . '</b><span class="warning">
	It seems you are using Internet Explorer.  This browser has many known security risks and is not standards compliant.  Consider using a browser such as Firefox - dotPulse is developed and tested on Firefox first.
	</span>'; ?></td>
	</tr>
</table>