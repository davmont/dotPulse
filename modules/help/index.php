<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
// The help is the documentation website: send the old help links (?m=help&hid=module.action)
// to its page for that part of the application (dPhelpUrl(), includes/main_functions.php).
$hid = (string)dPgetCleanParam($_GET, 'hid', '');
if (!preg_match('/^[A-Za-z0-9_-]+(\.[A-Za-z0-9_-]+)*$/', $hid)) {
	$hid = '';
}
header('Location: ' . dPhelpUrl($hid));
exit;
