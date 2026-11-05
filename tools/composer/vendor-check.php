<?php
/*
 * vendor/ is committed (sites deploy it with the code), so it must hold what composer.lock
 * says. Dependabot updates only composer.json and composer.lock: without this check its
 * pull requests pass CI against the old libraries. Run on the oldest supported PHP: the
 * autoloader's platform check stops a library that needs a newer one.
 *
 *   make vendor-check
 */
if (PHP_SAPI !== 'cli') {
	exit(1);
}
$root = dirname(__DIR__, 2);
$lock = json_decode((string)file_get_contents($root . '/composer.lock'), true);
$installed = json_decode((string)file_get_contents($root . '/vendor/composer/installed.json'), true);
if (!is_array($lock) || !is_array($installed)) {
	fwrite(STDERR, "composer.lock or vendor/composer/installed.json is missing or unreadable\n");
	exit(1);
}
$want = array();
foreach ($lock['packages'] as $p) {
	$want[$p['name']] = $p['version'];
}
$have = array();
foreach ($installed['packages'] ?? $installed as $p) {
	$have[$p['name']] = $p['version'];
}
$problems = array();
foreach ($want as $name => $version) {
	if (!isset($have[$name])) {
		$problems[] = "$name $version is in composer.lock but not in vendor/";
	} elseif (ltrim($have[$name], 'v') !== ltrim($version, 'v')) {
		$problems[] = "$name is $version in composer.lock but {$have[$name]} in vendor/";
	}
}
foreach (array_diff_key($have, $want) as $name => $version) {
	$problems[] = "$name $version is in vendor/ but not in composer.lock";
}
if ($problems) {
	fwrite(STDERR, implode("\n", $problems) . "\nRun composer install --no-dev and commit vendor/.\n");
	exit(1);
}
// The platform check (vendor/composer/platform_check.php) stops here if a library
// needs a newer PHP than this one.
require $root . '/vendor/autoload.php';
printf("OK: vendor/ matches composer.lock (%d packages), autoload works on PHP %s.\n", count($want), PHP_VERSION);
