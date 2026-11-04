<?php
/*
 * Runs the PHP unit tests (tests/*Test.php, the JUnit-style framework in tests/phpunit.php)
 * from the command line, each file in its own PHP process because the files mock different
 * global functions. Exits 1 if any test fails or errors, or a file cannot run.
 *
 *   php tests/run.php              every tests/*Test.php
 *   php tests/run.php TwoFactor    only tests/TwoFactorTest.php
 */
if (PHP_SAPI !== 'cli') {
	exit(1);
}
error_reporting(E_ALL);
ini_set('display_errors', '1');

if (isset($argv[1]) && $argv[1] === '--one') {
	define('DP_BASE_DIR', realpath(__DIR__ . '/..'));
	require_once __DIR__ . '/phpunit.php';
	$class = basename($argv[2], '.php');
	require_once __DIR__ . '/' . $class . '.php';

	class CliTestResult extends TestResult {
		function _endTest($test) {
			printf("  %s %s::%s\n", $test->failed() ? 'FAIL' : 'ok  ', get_class($test), $test->name());
			foreach ($test->getExceptions() as $e) {
				printf("       %s: %s\n", $e->type, html_entity_decode(strip_tags(str_replace(array("<br>", "&nbsp;"), " ", $e->getMessage()))));
			}
		}
	}
	$result = new CliTestResult();
	$suite = new TestSuite($class);
	$suite->run($result);
	exit($result->countTests() > 0 && !$result->failureCount() && !$result->errorCount() ? 0 : 1);
}

$names = array_slice($argv, 1);
$files = $names ? array_map(function ($n) { return __DIR__ . '/' . preg_replace('/(Test)?(\.php)?$/', '', $n) . 'Test.php'; }, $names)
	: glob(__DIR__ . '/*Test.php');
$failed = array();
foreach ($files as $file) {
	$name = basename($file, '.php');
	echo $name, "\n";
	passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' --one ' . escapeshellarg($name), $status);
	if ($status !== 0) {
		$failed[] = $name;
	}
}
echo $failed ? 'FAILED: ' . implode(', ', $failed) . "\n" : 'OK: ' . count($files) . " test files\n";
exit($failed ? 1 : 0);
