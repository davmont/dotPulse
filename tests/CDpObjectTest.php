<?php
// Mock dependencies if not already defined
if (!defined('DP_BASE_DIR')) {
    define('DP_BASE_DIR', realpath(__DIR__ . '/../'));
}

require_once DP_BASE_DIR . '/tests/phpunit.php';

// We need to mock AppUI and its method getSystemClass BEFORE including dp.class.php
// But if dp.class.php is already included, we are fine.
// Since this is a new test file, we assume it runs in isolation or we manage includes carefully.

if (!class_exists('MockAppUI')) {
    class MockAppUI {
        public function getSystemClass($class) {
            if ($class == 'query') {
                return DP_BASE_DIR . '/classes/query.class.php';
            }
            return '';
        }
        public function setMsg($msg, $type) {} // Mock setMsg
        public function getMsg() { return ''; }
        public function acl() { return null; } // Mock acl
    }
}

global $AppUI;
if (!isset($AppUI)) {
    $AppUI = new MockAppUI();
}

// Mock global functions if not defined
if (!function_exists('dPgetConfig')) {
    function dPgetConfig($name, $default = '') {
        return $default;
    }
}
if (!function_exists('dprint')) {
    function dprint($file, $line, $level, $msg) {}
}

// Mock bindHashToObject if not defined
if (!function_exists('bindHashToObject')) {
    function bindHashToObject($hash, &$obj, $prefix=NULL, $checkSlashes=true, $bindAll=false) {
        // Mock implementation
    }
}

require_once DP_BASE_DIR . '/classes/dp.class.php';

// Subclass for testing
class TestCDpObject extends CDpObject {
    public function __construct() {
        // Use parent constructor which initializes DBQuery
        // DBQuery needs ADOdb.
        // We rely on real ADOdb being available via query.class.php inclusion.
        parent::__construct('test_table', 'test_id');
    }
}

class CDpObjectTest extends TestCase {
    function testBindNotArray() {
        $obj = new TestCDpObject();
        $result = $obj->bind('not an array');
        $this->assertEquals(false, $result, 'bind() should return false for non-array input');

        $this->assertEquals('TestCDpObject::bind failed.', $obj->getError(), 'Error message mismatch');
    }
}
?>
