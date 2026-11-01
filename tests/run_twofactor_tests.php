<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('DP_BASE_DIR', realpath(dirname(__FILE__) . '/../'));

require_once DP_BASE_DIR . '/tests/phpunit.php';
require_once DP_BASE_DIR . '/tests/TwoFactorTest.php';

echo "Running two-step login tests...\n";

$suite = new TestSuite('TwoFactorTest');
$runner = new TestRunner();
$runner->run($suite);
?>
