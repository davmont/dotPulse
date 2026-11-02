<?php
define('DP_BASE_DIR', realpath(dirname(__FILE__) . '/..'));

// Mock dPgetConfig
if (!function_exists('dPgetConfig')) {
    function dPgetConfig($key, $default = null) {
        return $default;
    }
}

// Mock dprint
if (!function_exists('dprint')) {
    function dprint($file, $line, $level, $msg) {
        // no-op
    }
}

require_once DP_BASE_DIR . '/classes/query.class.php';
require_once DP_BASE_DIR . '/tests/phpunit.php';

// Include the test file(s)
require_once dirname(__FILE__) . '/DBQueryTest.php';

$suite = new TestSuite('DBQueryTest');
$result = new TextTestResult();
$suite->run($result);
$result->report();
?>
