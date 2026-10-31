<?php
/**
 * dotPulse REST API, version 1. See docs/api.md.
 *
 *   GET /api.php/v1/projects            Authorization: Bearer dpp_...
 *
 * No session and no cookies: every request carries a personal access token, created by
 * the user in their account (User > API tokens) and acting with that user's permissions.
 * Off until an administrator ticks "api_enabled" in System Configuration. Servers that do
 * not pass PATH_INFO can use api.php?route=/v1/projects.
 */
require_once 'base.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");

if (!is_file(DP_BASE_DIR . '/includes/config.php')) {
	http_response_code(503);
	echo '{"error":{"code":"not_installed","message":"dotPulse is not installed."}}';
	exit;
}
require_once DP_BASE_DIR . '/includes/config.php';
require_once DP_BASE_DIR . '/classes/csscolor.class.php';
require_once DP_BASE_DIR . '/includes/main_functions.php';
require_once DP_BASE_DIR . '/includes/db_adodb.php';
require_once DP_BASE_DIR . '/includes/db_connect.php';
require_once DP_BASE_DIR . '/classes/ui.class.php';
require_once DP_BASE_DIR . '/classes/permissions.class.php';
require_once DP_BASE_DIR . '/classes/api.class.php';

$AppUI = new CAppUI;
$GLOBALS['AppUI'] =& $AppUI;
require_once $AppUI->getSystemClass('date');
require_once $AppUI->getSystemClass('dp');
require_once $AppUI->getSystemClass('query');
// Module classes use $AppUI at file scope, so they are loaded here and not in CApi.
require_once DP_BASE_DIR . '/includes/permissions.php';
require_once $AppUI->getModuleClass('companies');
require_once $AppUI->getModuleClass('projects');
require_once $AppUI->getModuleClass('tasks');

$route = isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] !== ''
	? $_SERVER['PATH_INFO'] : (string)dPgetParam($_GET, 'route', '');
$api = new CApi($AppUI);
$api->run($_SERVER['REQUEST_METHOD'] ?? 'GET', $route, CApi::bearerToken($_SERVER));
