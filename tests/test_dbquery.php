<?php

if (!defined('DP_BASE_DIR')) {
    define('DP_BASE_DIR', realpath(dirname(__FILE__) . '/../'));
}

// Mocking required dependencies for DBQuery standalone testing
function dPgetConfig($arg1, $arg2 = null) { return $arg2; }
function dprint($file, $line, $level, $msg) {}
function db_error() {}

require_once DP_BASE_DIR . '/lib/adodb/adodb.inc.php';
require_once DP_BASE_DIR . '/classes/query.class.php';

require_once DP_BASE_DIR . '/tests/phpunit.php';

class DBQueryTest extends TestCase {
    function testAddWhere() {
        $q = new DBQuery();

        // Initial state
        $this->assertEquals(null, $q->where);
        $this->assertEquals(array(), $q->w_params);

        // Single condition, no params
        $q->addWhere("id = 1");
        $this->assertEquals(array("id = 1"), $q->where);
        $this->assertEquals(array(), $q->w_params);

        // Multiple conditions, no params
        $q->addWhere("status = 'active'");
        $this->assertEquals(array("id = 1", "status = 'active'"), $q->where);
        $this->assertEquals(array(), $q->w_params);

        // With single param
        $q->addWhere("type = ?", 'user');
        $this->assertEquals(array("id = 1", "status = 'active'", "type = ?"), $q->where);
        $this->assertEquals(array('user'), $q->w_params);

        // With multiple params
        $q->addWhere("age > ? AND age < ?", array(18, 65));
        $this->assertEquals(array("id = 1", "status = 'active'", "type = ?", "age > ? AND age < ?"), $q->where);
        $this->assertEquals(array('user', 18, 65), $q->w_params);

        // null condition should not be added
        $q->addWhere(null);
        $this->assertEquals(array("id = 1", "status = 'active'", "type = ?", "age > ? AND age < ?"), $q->where);
        $this->assertEquals(array('user', 18, 65), $q->w_params);
    }
}

$suite = new TestSuite("DBQueryTest");
$result = new TextTestResult();
$suite->run($result);
$result->report();
