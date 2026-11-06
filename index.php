<?php /* $Id: index.php 6206 2013-02-26 04:10:17Z ajdonnison $ */

/* {{{ Copyright (c) 2003-2005 The dotProject Development Team <core-developers@dotproject.net>

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
}}} */

// If you experience a 'white screen of death' or other problems,
// uncomment the following line of code:
//error_reporting(E_ALL);

$loginFromPage = 'index.php';
require_once 'base.php';

clearstatcache();
if (is_file(DP_BASE_DIR . '/includes/config.php')) {
	require_once DP_BASE_DIR . '/includes/config.php';

} else {
	echo ('<html><head><meta http-equiv="refresh" content="5; URL=' . DP_BASE_URL
		. '/install/index.php"></head><body>'
		. 'Fatal Error. You haven\'t created a config file yet.<br/>'
		. '<a href="./install/index.php">Click Here To Start Installation and Create One!</a>'
		. ' (forwarded in 5 sec.)</body></html>');
	exit();
}

if (!(isset($GLOBALS['OS_WIN']))) {
	$GLOBALS['OS_WIN'] = (mb_stristr(PHP_OS, 'WIN') !== false);
}

// tweak for pathname consistence on windows machines
require_once(DP_BASE_DIR . '/classes/csscolor.class.php'); // Required before main_functions
require_once(DP_BASE_DIR . '/includes/main_functions.php');
require_once(DP_BASE_DIR . '/includes/db_adodb.php');
require_once(DP_BASE_DIR . '/includes/db_connect.php');

require_once(DP_BASE_DIR . '/classes/ui.class.php');
require_once(DP_BASE_DIR . '/classes/permissions.class.php');
require_once(DP_BASE_DIR . '/includes/session.php');

// don't output anything. Usefull for fileviewer.php, gantt.php, etc.
$suppressHeaders = (bool) dPgetParam($_GET, 'suppressHeaders', false);

// manage the session variable(s)
dPsessionStart(array('AppUI'));

// write the HTML headers

header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); //Date in the past
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); //always modified
header('Cache-Control: no-cache, must-revalidate, no-store, post-check=0, pre-check=0'); //HTTP/1.1
header('Pragma: no-cache');	// HTTP/1.0
dPsendCspHeader();

// check if session has previously been initialised
if (!(isset($_SESSION['AppUI'])) || isset($_GET['logout'])) {
	if (isset($_GET['logout']) && isset($_SESSION['AppUI']->user_id)) {
		$AppUI =& $_SESSION['AppUI'];
		$AppUI->registerLogout($AppUI->user_id);
		if ($AppUI->user_id > 0) {
			dPaudit('logout');
		}
		addHistory(
			'login',
			$AppUI->user_id,
			'logout',
			($AppUI->user_first_name . ' ' . $AppUI->user_last_name)
		);
	}
	$_SESSION['AppUI'] = new CAppUI;
}
$AppUI =& $_SESSION['AppUI'];
$last_insert_id = $AppUI->last_insert_id;

$AppUI->checkStyle();

// load the commonly used classes
require_once($AppUI->getSystemClass('date'));
require_once($AppUI->getSystemClass('dp'));
require_once($AppUI->getSystemClass('query'));

require_once DP_BASE_DIR . '/misc/debug.php';

//Function for update lost action in user_access_log
$AppUI->updateLastAction($last_insert_id);
// load default preferences if not logged in
if ($AppUI->doLogin()) {
	$AppUI->loadPrefs(0);
}

// check is the user needs a new password
if (dPgetParam($_POST, 'lostpass', 0)) {
	$uistyle = (($AppUI->getPref('UISTYLE')) ? $AppUI->getPref('UISTYLE') : dPgetConfig('host_style'));
	$AppUI->setUserLocale();
	@include_once(DP_BASE_DIR . '/locales/' . $AppUI->user_locale . '/locales.php');
	@include_once(DP_BASE_DIR . '/locales/core.php');
	setlocale(LC_TIME, $AppUI->user_lang);
	if (dPgetParam($_REQUEST, 'sendpass', 0)) {
		// Security Mitigation: Rate limit password reset requests.
		require_once(DP_BASE_DIR . '/classes/ratelimiter.class.php');
		$rateLimiter = new RateLimiter('sendpass', 5, 900); // 5 attempts per 15 minutes

		if (!$rateLimiter->isAllowed()) {
			$AppUI->setMsg('Too many password reset requests. Please wait before trying again.', UI_MSG_ERROR);
			$AppUI->redirect();
			exit;
		}
		// Record the attempt before processing.
		$rateLimiter->recordAttempt();

		require(DP_BASE_DIR . '/includes/sendpass.php');
		sendNewPass();
	} else {
		require(DP_BASE_DIR . '/style/' . $uistyle . '/lostpass.php');
	}
	exit();
}

// Password reset from the emailed link. Reachable while logged out.
if (dPgetParam($_REQUEST, 'resetpass', 0)) {
	$uistyle = (($AppUI->getPref('UISTYLE')) ? $AppUI->getPref('UISTYLE') : dPgetConfig('host_style'));
	$AppUI->setUserLocale();
	@include_once(DP_BASE_DIR . '/locales/' . $AppUI->user_locale . '/locales.php');
	@include_once(DP_BASE_DIR . '/locales/core.php');
	setlocale(LC_TIME, $AppUI->user_lang);
	require(DP_BASE_DIR . '/modules/public/reset_password.php');
	exit();
}

// check if the user is trying to log in
// Note the change to REQUEST instead of POST.  This is so that we can
// support alternative authentication methods such as HTTP auth.
if (isset($_REQUEST['login'])) {
	// Security Mitigation: Rate limit login attempts to prevent brute-force attacks.
	require_once(DP_BASE_DIR . '/classes/ratelimiter.class.php');
	$rateLimiter = new RateLimiter('login');

	if (!$rateLimiter->isAllowed()) {
		$AppUI->setMsg('Too many login attempts. Please wait before trying again.', UI_MSG_ERROR);
		$AppUI->redirect();
		exit;
	}

	$username = dPgetCleanParam($_POST, 'username', '');
	$password = dPgetParam($_POST, 'password', '');
	$redirect = dPgetParam($_REQUEST, 'redirect', '');
	$AppUI->setUserLocale();
	@include_once(DP_BASE_DIR . '/locales/' . $AppUI->user_locale . '/locales.php');
	@include_once DP_BASE_DIR . '/locales/core.php';
	$ok = $AppUI->login($username, $password);
	if (!$ok) {
		// If login fails, record the attempt.
		$rateLimiter->recordAttempt();
		dPaudit('login.failure', '', '', 0, 0, $username);
		if (!$rateLimiter->isAllowed()) {
			dPaudit('login.blocked', 'this address must wait before logging in again', '', 0, 0, $username);
		}
		$AppUI->setMsg('Login Failed');
	} else {
		session_regenerate_id(true);
		// Two-step login: the password was right, but the session stays logged out until the
		// code is given (includes/twofactor_login.php).
		require_once DP_BASE_DIR . '/classes/twofactor.class.php';
		if (CTwoFactor::isEnabled($AppUI->user_id)) {
			CTwoFactor::startPending($AppUI->user_id, $redirect);
			$_SESSION['AppUI'] = new CAppUI;
			header('Location: ./index.php');
			exit;
		}
		//Register login in user_acces_log
		$AppUI->registerLogin();
		dPaudit('login.success');
	}
	addHistory(
		'login',
		$AppUI->user_id,
		'login',
		($AppUI->user_first_name . ' ' . $AppUI->user_last_name)
	);
	$AppUI->redirect($redirect);
}

// supported since PHP 4.2
// writeDebug(var_export($AppUI, true), 'AppUI', __FILE__, __LINE__);

// set the default ui style
$uistyle = (($AppUI->getPref('UISTYLE')) ? $AppUI->getPref('UISTYLE') : dPgetConfig('host_style'));

// clear out main url parameters
$m = '';
$a = '';
$u = '';

// A login waiting for its two-step code: the code form, nothing else.
if ($AppUI->doLogin() && !empty($_SESSION['dp_twofactor'])) {
	require DP_BASE_DIR . '/includes/twofactor_login.php';
	exit;
}

// check if we are logged in
if ($AppUI->doLogin()) {
	// load basic locale settings
	$AppUI->setUserLocale();
	@include_once('./locales/' . $AppUI->user_locale . '/locales.php');
	@include_once('./locales/core.php');
	setlocale(LC_TIME, $AppUI->user_lang);
	$redirect = (($_SERVER['QUERY_STRING']) ? strip_tags($_SERVER['QUERY_STRING']) : '');
	if (mb_strpos($redirect, 'logout') !== false) {
		$redirect = '';
	}

	if (isset($locale_char_set)) {
		header('Content-type: text/html;charset=' . $locale_char_set);
	}

	require(DP_BASE_DIR . '/style/' . $uistyle . '/login.php');
	// destroy the current session and output login page
	session_unset();
	session_destroy();
	exit;
}
$AppUI->setUserLocale();


// bring in the rest of the support and localisation files
require_once(DP_BASE_DIR . '/includes/permissions.php');


$def_a = 'index';
if (!(isset($_GET['m']) || empty($dPconfig['default_view_m']))) {
	$m = $dPconfig['default_view_m'];
	$def_a = ((!empty($dPconfig['default_view_a'])) ? $dPconfig['default_view_a'] : $def_a);
	$tab = $dPconfig['default_view_tab'];
} else {
	// set the module from the url
	$m = $AppUI->checkFileName(dPgetCleanParam($_GET, 'm', getReadableModule()));
}
// set the action from the url
$a = $AppUI->checkFileName(dPgetCleanParam($_GET, 'a', $def_a));

/* This check for $u implies that a file located in a subdirectory of higher depth than 1
 * in relation to the module base can't be executed. So it would'nt be possible to
 * run for example the file module/directory1/directory2/file.php
 * Also it won't be possible to run modules/module/abc.zyz.class.php for that dots are
 * not allowed in the request parameters.
 */

$u = $AppUI->checkFileName(dPgetCleanParam($_GET, 'u', ''));

// load module based locale settings
@include_once(DP_BASE_DIR . '/locales/' . $AppUI->user_locale . '/locales.php');
@include_once(DP_BASE_DIR . '/locales/core.php');

setlocale(LC_TIME, $AppUI->user_lang);
$m_config = dPgetConfig($m);
@include_once(DP_BASE_DIR . '/functions/' . $m . '_func.php');

// TODO: canRead/Edit assignements should be moved into each file

// check overall module permissions
// these can be further modified by the included action files
$canAccess = getPermission($m, 'access');
$canRead = getPermission($m, 'view');
$canEdit = getPermission($m, 'edit');
$canAuthor = getPermission($m, 'add');
$canDelete = getPermission($m, 'delete');

// Module access is required before any of the module's write handlers or views run.
// The public module (login, password change, selectors, access denied) stays open, and
// every user can view their own account and edit their own preferences.
$dosql = isset($_REQUEST['dosql']) ? $_REQUEST['dosql'] : '';
$ownAccount = ($m == 'admin' && $a == 'viewuser' && !$dosql
		&& (int)dPgetParam($_GET, 'user_id', 0) == $AppUI->user_id)
	|| ($m == 'system' && $a == 'addeditpref' && !$dosql
		&& (int)dPgetParam($_GET, 'user_id', 0) == $AppUI->user_id)
	|| ($m == 'system' && $dosql == 'do_preference_aed'
		&& (int)dPgetParam($_POST, 'pref_user', 0) == $AppUI->user_id)
	// do_api_token_aed checks that the token belongs to the user (or that they administer users).
	|| ($m == 'admin' && $a == 'viewuser' && $dosql == 'do_api_token_aed'
		&& (int)dPgetParam($_POST, 'token_user', 0) == $AppUI->user_id)
	// do_twofactor_aed: users manage their own two-step login (the handler checks it again).
	|| ($m == 'admin' && $a == 'viewuser' && $dosql == 'do_twofactor_aed'
		&& (int)dPgetParam($_POST, 'tf_user', 0) == $AppUI->user_id);
if (!$canAccess && $m != 'public' && !$ownAccount) {
	$AppUI->redirect('m=public&a=access_denied');
}

if (!$suppressHeaders) {
	// output the character set header
	if (isset($locale_char_set)) {
		header('Content-type: text/html;charset=' . $locale_char_set);
	}
}

// include the module class file - we use file_exists instead of @ so
// that any parse errors in the file are reported, rather than errors
// further down the track.
$modclass = $AppUI->getModuleClass($m);
if (file_exists($modclass)) {
	include_once($modclass);
}
if ($u && file_exists(DP_BASE_DIR . '/modules/' . $m . '/' . $u . '/' . $u . '.class.php')) {
	include_once(DP_BASE_DIR . '/modules/' . $m . '/' . $u . '/' . $u . '.class.php');
}

// Every POST from a logged-in session must carry the CSRF token: server-rendered POST
// forms get it injected (CAppUI::injectCsrfToken), script-built ones from js/dp-ui.js,
// and AJAX posts send it. This also covers pages that write on POST without being
// named do_* (they were missed by the checks below).
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$AppUI->verifyCsrfToken();
}

// do some db work if dosql is set
// TODO - MUST MOVE THESE INTO THE MODULE DIRECTORY
if (isset($_REQUEST['dosql'])) {
	// Write handlers run only for POST requests that carry the session's CSRF token.
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		$AppUI->setMsg('Invalid request method.', UI_MSG_ERROR);
		$AppUI->redirect('m=public&a=access_denied');
	}
	$AppUI->verifyCsrfToken();
	// Write handlers need write permission on the module: delete for del=1, otherwise add
	// or edit. Handlers check the individual record themselves.
	if (!$ownAccount && !(dPgetParam($_POST, 'del', 0) ? $canDelete : ($canEdit || $canAuthor))) {
		$AppUI->setMsg('Access denied.', UI_MSG_ERROR);
		$AppUI->redirect('m=public&a=access_denied');
	}
	//require('./dosql/' . $_REQUEST['dosql'] . '.php');
	require(DP_BASE_DIR . '/modules/' . $m . '/' . ($u ? ($u . '/') : '')
		. $AppUI->checkFileName($_REQUEST['dosql']) . '.php');
}

// Views named do_*, dosql* or domodsql are write handlers too: same POST, token and
// write permission rules.
if (preg_match('/^do(_|sql|modsql)/', $a)) {
	if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
		$AppUI->setMsg('Invalid request method.', UI_MSG_ERROR);
		$AppUI->redirect('m=public&a=access_denied');
	}
	$AppUI->verifyCsrfToken();
	if (!(dPgetParam($_POST, 'del', 0) ? $canDelete : ($canEdit || $canAuthor))) {
		$AppUI->setMsg('Access denied.', UI_MSG_ERROR);
		$AppUI->redirect('m=public&a=access_denied');
	}
}

// Module configuration pages save settings when posted to (some into PHP files the
// module includes): system administrators only, and posts need the CSRF token.
if ($a == 'configure') {
	if (!getPermission('system', 'edit')) {
		$AppUI->redirect('m=public&a=access_denied');
	}
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$AppUI->verifyCsrfToken();
	}
}

// start output proper
include(DP_BASE_DIR . '/style/' . $uistyle . '/overrides.php');
// Every POST form in the page gets the CSRF token checked on dosql requests.
ob_start(array($AppUI, 'injectCsrfToken'));
if (!$suppressHeaders) {
	require(DP_BASE_DIR . '/style/' . $uistyle . '/header.php');
}

if (!(isset($_SESSION['all_tabs'][$m]))) {
	// For some reason on some systems if you don't set this up
	// first you get recursive pointers to the all_tabs array, creating
	// phantom tabs.
	if (!isset($_SESSION['all_tabs'])) {
		$_SESSION['all_tabs'] = array();
	}
	$_SESSION['all_tabs'][$m] = array();
	$all_tabs =& $_SESSION['all_tabs'][$m];
	foreach ($AppUI->getActiveModules() as $dir => $module) {
		if (!(getPermission($dir, 'access'))) {
			continue;
		}
		$modules_tabs = $AppUI->readFiles(
			(DP_BASE_DIR . '/modules/' . $dir . '/'),
			('^' . $m . '_tab.*\.php')
		);
		foreach ($modules_tabs as $mod_tab) {
			// Get the name as the subextension
			// cut the module_tab. and the .php parts of the filename 
			// (begining and end)
			$nameparts = explode('.', $mod_tab);
			$filename = mb_substr($mod_tab, 0, -4);
			if (count($nameparts) > 3) {
				$file = $nameparts[1];
				if (!(isset($all_tabs[$file]))) {
					$all_tabs[$file] = array();
				}
				$arr =& $all_tabs[$file];
				$name = $nameparts[2];
			} else {
				$arr =& $all_tabs;
				$name = $nameparts[1];
			}
			$arr[] = array(
				'name' => ucfirst(str_replace('_', ' ', $name)),
				'file' => (DP_BASE_DIR . '/modules/' . $dir . '/' . $filename),
				'module' => $dir
			);

			/* 
			 * Don't forget to unset $arr again! $arr is likely to be used in the sequel declaring
			 * any temporary array. This may lead to strange bugs with disappearing tabs(cf. #1767).
			 * @author: gregorerhardt @date: 20070203
			 */
			unset($arr);
		}
	}
} else {
	$all_tabs =& $_SESSION['all_tabs'][$m];
}

$module_file = (DP_BASE_DIR . '/modules/' . $m . '/' . (($u) ? ($u . '/') : '') . $a . '.php');
if (file_exists($module_file)) {
	require $module_file;
} else {
	//TODO: make this part of the public module? 
	//TODO: internationalise the string.
	$titleBlock = new CTitleBlock('Warning', 'log-error.gif');
	$titleBlock->show();

	echo $AppUI->_('Missing file. Possible Module "' . $m . '" missing!');
}
// wtf??  why?
if (!$suppressHeaders) {
	echo ('<iframe name="thread" src="' . DP_BASE_URL
		. '/modules/index.html" width="0" height="0" frameborder="0"></iframe>');
	require(DP_BASE_DIR . '/style/' . $uistyle . '/footer.php');
}
ob_end_flush();
?>