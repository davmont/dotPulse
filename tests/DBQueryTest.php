<?php

// Ensure the testing framework is available
if (!class_exists('TestCase')) {
    require_once dirname(__FILE__) . '/phpunit.php';
}

if (!defined('DP_BASE_DIR')) {
    define('DP_BASE_DIR', dirname(__FILE__) . '/../');
}

// Mock configurations required by query.class.php
if (!function_exists('dPgetConfig')) {
    function dPgetConfig($key, $default = null) {
        if ($key == 'dbprefix') return 'dp_';
        if ($key == 'dbtype') return 'mysql';
        return $default;
    }
}

if (!function_exists('dprint')) {
    function dprint() {}
}

// Ensure adodb constants are available to prevent errors if we don't load the real adodb.inc.php
if (!defined('ADODB_FETCH_ASSOC')) {
    define('ADODB_FETCH_ASSOC', 2);
    define('ADODB_FETCH_NUM', 1);
    define('ADODB_FETCH_BOTH', 3);
}

// To avoid conflicts with the globally mocked DBQuery in tests/bootstrap.php,
// and to avoid polluting the file system or executing isolated processes,
// we will load the contents of query.class.php, rename the class to RealDBQuery,
// remove the problematic ADOdb require_once, and evaluate it in memory.

if (!class_exists('RealDBQuery')) {
    $queryCode = file_get_contents(DP_BASE_DIR . '/classes/query.class.php');
    // Remove the adodb.inc.php require
    $queryCode = preg_replace('/require_once.*adodb\.inc\.php["\'];/', '', $queryCode);
    // Rename class
    $queryCode = preg_replace('/\bclass DBQuery\b/', 'class RealDBQuery', $queryCode);

    // Evaluate the code
    // We strip the opening <?php tags
    $queryCode = preg_replace('/^\s*<\?php/', '', $queryCode);
    $queryCode = preg_replace('/\?>\s*$/', '', $queryCode);

    eval($queryCode);
}


class DBQueryTest extends TestCase {
    var $query;

    function setUp() {
        $this->query = new RealDBQuery();
    }

    function testAddTable() {
        // Test adding a simple table without alias
        $this->query->addTable('users');
        $this->assertEquals(array('users'), $this->query->table_list);

        // Test adding a table with an alias
        $this->query->clear();
        $this->query->addTable('users', 'u');
        $this->assertEquals(array('u' => 'users'), $this->query->table_list);

        // Test adding multiple tables
        $this->query->clear();
        $this->query->addTable('users', 'u');
        $this->query->addTable('projects', 'p');
        $this->assertEquals(array('u' => 'users', 'p' => 'projects'), $this->query->table_list);

        // Test that table list builds the correct array and is used in prepareSelect
        $this->query->clear();
        $this->query->addTable('users');
        $q = $this->query->prepareSelect();
        // Since table_list becomes an array `array(0 => 'users')` due to addMap:
        // 'SELECT * FROM (dp_users)'
        $this->assertRegexp('/FROM \(dp_users\)/', $q);

        $this->query->clear();
        $this->query->addTable('users', 'u');
        $q = $this->query->prepareSelect();
        // Since table_list is an array when an alias is used:
        // 'SELECT * FROM (dp_users as u)'
        $this->assertRegexp('/FROM \(dp_users as u\)/', $q);
    }

    function testLimit() {
        $q = new RealDBQuery('test_');

        // Initial state
        $this->assertEquals(null, $q->limit);
        $this->assertEquals(-1, $q->offset);

        // setLimit with only limit
        $q->setLimit(10);
        $this->assertEquals(10, $q->limit);
        $this->assertEquals(-1, $q->offset);

        // setLimit with limit and offset
        $q->setLimit(20, 5);
        $this->assertEquals(20, $q->limit);
        $this->assertEquals(5, $q->offset);

        // addLimit with limit and offset
        $q->addLimit(30, 15);
        $this->assertEquals(30, $q->limit);
        $this->assertEquals(15, $q->offset);

        // addLimit with only limit (default start is 0)
        $q->addLimit(50);
        $this->assertEquals(50, $q->limit);
        $this->assertEquals(0, $q->offset);
    }

    function testSanitise() {
        // It removes quote, semicolon and double dash
        $this->assertEquals('admin DROP TABLE users ', $this->query->sanitise('admin"; DROP TABLE users; --'));

        // Additional types (null, int, float)
        $this->assertEquals('', $this->query->sanitise(null));
        $this->assertEquals('123', $this->query->sanitise(123));
        $this->assertEquals('0', $this->query->sanitise(0));
        $this->assertEquals('1.5', $this->query->sanitise(1.5));
    }
}

?>
