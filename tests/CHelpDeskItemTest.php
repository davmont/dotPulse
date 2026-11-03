<?php
if (!defined('LOAD_REAL_DBQUERY')) {
    define('LOAD_REAL_DBQUERY', true);
}
if (!defined('DP_BASE_DIR')) {
    define('DP_BASE_DIR', realpath(dirname(__FILE__) . '/../'));
}
require_once DP_BASE_DIR . '/tests/bootstrap.php';
require_once DP_BASE_DIR . '/tests/phpunit.php';

// Mock required functions
if (!function_exists('db_loadObject')) {
    function db_loadObject($sql, &$object) { return true; }
}
if (!function_exists('db_unix2dateTime')) {
    function db_unix2dateTime($t) { return date('Y-m-d H:i:s', $t); }
}
if (!function_exists('addHistory')) {
    function addHistory($a, $b, $c, $d, $e = null) {}
}
if (!function_exists('db_loadHash')) {
    function db_loadHash($sql, &$res) { $res = array(); return true; }
}
if (!function_exists('db_insert_id')) {
    function db_insert_id() { return 1; }
}
if (!function_exists('mysql_insert_id')) {
    function mysql_insert_id() { return 1; }
}
if (!function_exists('db_exec')) {
    function db_exec($sql) { return true; }
}
if (!function_exists('db_error')) {
    function db_error() { return ''; }
}
if (!function_exists('bindHashToObject')) {
    function bindHashToObject($hash, &$object) { return true; }
}
if (!function_exists('dprint')) {
    function dprint($file, $line, $level, $msg) {}
}
if (!function_exists('dPgetConfig')) {
    function dPgetConfig($key, $default = '') { return $default; }
}

if (!class_exists('CHelpDeskItem')) {
    require_once DP_BASE_DIR . '/modules/bugspray/helpdesk.class.php';
}

class CHelpDeskItemTest extends TestCase {
    function __construct($name) {
        parent::__construct($name);
    }

    function testCheckNewItemPasses() {
        $item = new CHelpDeskItem();
        $item->item_id = NULL; // New item
        $item->item_title = 'Test Title';
        $item->item_summary = 'Test Summary';
        $item->item_project_id = 1;
        $item->item_company_id = 1;

        $result = $item->check();
        $this->assertEquals(NULL, $result, "New item with all required fields should pass");
        $this->assert($item->item_created !== NULL, "item_created should be set if missing");
    }

    function testCheckMissingTitleFails() {
        $item = new CHelpDeskItem();
        $item->item_title = '';
        $result = $item->check();
        $this->assertEquals('Help Desk item title is required', $result);
    }

    function testCheckMissingSummaryFails() {
        $item = new CHelpDeskItem();
        $item->item_title = 'Title';
        $item->item_summary = '';
        $result = $item->check();
        $this->assertEquals('Help Desk item summary is required', $result);
    }

    function testCheckMissingProjectFails() {
        $item = new CHelpDeskItem();
        $item->item_title = 'Title';
        $item->item_summary = 'Summary';
        $item->item_project_id = 0;
        $result = $item->check();
        $this->assertEquals('Help Desk item project is required', $result);
    }

    function testCheckMissingCompanyFails() {
        $item = new CHelpDeskItem();
        $item->item_title = 'Title';
        $item->item_summary = 'Summary';
        $item->item_project_id = 1;
        $item->item_company_id = 0;
        $result = $item->check();
        $this->assertEquals('Help Desk item company is required', $result);
    }

    function testNumericCasting() {
        $item = new CHelpDeskItem();
        $item->item_title = 'Title';
        $item->item_summary = 'Summary';
        $item->item_project_id = '123';
        $item->item_company_id = '456';
        $item->item_priority = '5';

        $result = $item->check();
        $this->assertEquals(NULL, $result);
        $this->assert(is_int($item->item_project_id), "project_id should be cast to int");
        $this->assertEquals(123, $item->item_project_id);
        $this->assert(is_int($item->item_company_id), "company_id should be cast to int");
        $this->assertEquals(456, $item->item_company_id);
        $this->assert(is_int($item->item_priority), "priority should be cast to int");
        $this->assertEquals(5, $item->item_priority);
    }
}

if (php_sapi_name() == 'cli' && basename(__FILE__) == basename($_SERVER['PHP_SELF'])) {
    $suite = new TestSuite("CHelpDeskItemTest");
    $result = new TextTestResult();
    $suite->run($result);
    $result->report();
}
