<?php
/* $Id: base.php 6175 2012-06-20 02:10:57Z ajdonnison $ */

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

// Log PHP errors instead of printing them: a warning printed inside a <script>
// block breaks the page's JavaScript, and messages can leak paths and SQL.
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (defined('E_DEPRECATED')) {
  error_reporting(E_ALL & ~(E_DEPRECATED | E_NOTICE | E_STRICT));
} else {
  error_reporting(E_ALL & ~E_NOTICE);
}

if (function_exists('date_default_timezone_set')) {
  # this is a bit of a hack in that it will guess from the system what
  # the timezone is.
  date_default_timezone_set(date_default_timezone_get());
}

global $baseDir;
global $baseUrl;

$baseDir = dirname(__FILE__);
//Make sure directoy seperator is at the end so that paths are well formed
//$baseDir .= ((substr_compare($baseDir, DIRECTORY_SEPERATOR, -1 , 1) == 0) ? '' : DIRECTORY_SEPERATOR);
// Define to deprecate the global baseDir
define('DP_BASE_DIR', $baseDir);
// strftime() is deprecated since PHP 8.1 and removed in PHP 9 (includes/strftime.php).
require_once DP_BASE_DIR . '/includes/strftime.php';

// Security headers for every page (the installer sends the same ones, see
// install/check_upgrade.php). The Content-Security-Policy comes separately.
if (PHP_SAPI !== 'cli' && !headers_sent()) {
  header('X-Content-Type-Options: nosniff');
  header('X-Frame-Options: SAMEORIGIN');
  header('Referrer-Policy: strict-origin-when-cross-origin');
}

require_once($baseDir . '/includes/dP_compat.php');

// only rely on env variables if not using a apache handler
function safe_get_env($name)
{
  if (isset($_SERVER[$name])) {
    return $_SERVER[$name];
  } else if (mb_strpos(php_sapi_name(), 'apache') === false) {
    getenv($name);
  } else {
    return '';
  }
}

// automatically define the base url
$baseUrl = (((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off')
  || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'))
  ? 'https://' : 'http://');
$baseUrl .= safe_get_env('HTTP_HOST');
$pathInfo = safe_get_env('PATH_INFO');
if (@$pathInfo) {
  $baseUrl .= str_replace('\\', '/', dirname($pathInfo));
} else {
  $baseUrl .= str_replace('\\', '/', dirname(safe_get_env('SCRIPT_NAME')));
}

$baseUrl = preg_replace('#/$#D', '', $baseUrl);

// Define to deprecate the global baseUrl. Command-line scripts (queuescanner.php)
// define it first from their --base-url option, since there is no request to read.
if (!defined('DP_BASE_URL')) {
  define('DP_BASE_URL', $baseUrl);
}
// The bundled libraries in lib/ are found on the include path.
$dpLib = DP_BASE_DIR . DIRECTORY_SEPARATOR . 'lib';
set_include_path('.' . PATH_SEPARATOR . $dpLib . PATH_SEPARATOR . get_include_path());

// required includes for start-up
global $dPconfig;
$dPconfig = array();

?>