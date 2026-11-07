<?php
/**
 * Translation coverage of the maintained languages (English and Spanish).
 *
 *   php tools/locales/check.php            fails when a string is missing (make locales)
 *   php tools/locales/check.php missing es lists what is missing in a language, as JSON:
 *                                          target locale file => array(string, ...)
 *
 * The strings are the literals the code translates: $AppUI->_('...'), new CTitleBlock('...'),
 * addCrumb(url, '...'), the labels of tab boxes ($tabBox->add(file, '...')) and setMsg('...').
 * A string used in modules/<m>/ must be in common.inc or in the files locales/core.php loads
 * for <m> (locales/<lang>/<m>.inc, modules/<m>/locales/<lang>.inc); one used outside the
 * modules (classes/, includes/, style/...) in common.inc. Every string of an English file
 * must also be in the Spanish one, and an English key that is an identifier (noDeletePermission)
 * needs an English text. An empty value counts as missing. Other languages are not maintained here.
 * Strings without a letter, and those in tools/locales/ignore.txt, are left out.
 */
$root = dirname(__DIR__, 2);
$langs = array('en', 'es');

/** The strings of a locale file: key => value (bare English keys translate to themselves). */
function localeStrings($file)
{
	if (!is_readable($file)) {
		return array();
	}
	$source = file_get_contents($file);
	try {
		$strings = eval('return array(' . $source . "\n);");
	} catch (ParseError $e) {
		fwrite(STDERR, "does not parse: $file\n");
		exit(2);
	}
	// An empty value is no translation: $AppUI->_() shows the key.
	$out = array();
	foreach ($strings as $k => $v) {
		if (is_int($k)) {
			$out[$v] = $v;
		} else if ($v !== '') {
			$out[$k] = $v;
		}
	}
	return $out;
}

/** The literal strings a PHP file translates. */
function usedStrings($code)
{
	$str = '(?|\'((?:[^\'\\\\]|\\\\.)*)\'|"((?:[^"\\\\$]|\\\\[^$])*)")';
	$patterns = array(
		'/AppUI->_\(\s*' . $str . '\s*[,)]/',
		'/new\s+CTitleBlock\(\s*' . $str . '/',
		'/->addCrumb\(\s*[^,]+?,\s*' . $str . '\s*[,)]/',
		'/\$\w*tab\w*->add\(\s*[^,]+?,\s*' . $str . '\s*[,)]/i',
		'/->setMsg\(\s*' . $str . '\s*[,)]/',
	);
	$found = array();
	foreach ($patterns as $p) {
		preg_match_all($p, $code, $m);
		foreach ($m[1] as $s) {
			// $AppUI->_() trims the string before looking it up.
			$s = trim(stripcslashes($s));
			if (preg_match('/\pL/u', $s) || preg_match('/[A-Za-z]/', $s)) {
				$found[$s] = true;
			}
		}
	}
	return array_keys($found);
}

$ignore = array();
if (is_readable(__DIR__ . '/ignore.txt')) {
	foreach (file(__DIR__ . '/ignore.txt', FILE_IGNORE_NEW_LINES) as $line) {
		if ($line !== '' && $line[0] !== '#') {
			$ignore[$line] = true;
		}
	}
}

// Strings used per context: '' (common) or a module directory.
$used = array();
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $f) {
	$path = substr($f->getPathname(), strlen($root) + 1);
	if (substr($path, -4) !== '.php' || preg_match('#^(vendor|lib|tools|tests|docker|install|db|locales|\.git|\.claude)/#', $path)) {
		continue;
	}
	$context = preg_match('#^modules/([^/]+)/#', $path, $mm) ? $mm[1] : '';
	if ($context === 'install') {
		continue;
	}
	foreach (usedStrings(file_get_contents($f->getPathname())) as $s) {
		if (!isset($ignore[$s])) {
			$used[$context][$s] = $path;
		}
	}
}
ksort($used);

/** The files locales/core.php loads for a context, and the one new strings go to. */
function contextFiles($root, $lang, $context)
{
	$files = array("$root/locales/$lang/common.inc");
	if ($context === '') {
		return array($files, "locales/$lang/common.inc");
	}
	$files[] = "$root/locales/$lang/$context.inc";
	if ($context === 'departments') {
		$files[] = "$root/locales/$lang/companies.inc";
	}
	if ($context === 'system') {
		$files[] = "$root/locales/$lang/styles.inc";
	}
	$own = "modules/$context/locales/$lang.inc";
	$files[] = "$root/$own";
	if (is_dir("$root/modules/$context/locales")) {
		return array($files, $own);
	}
	return array($files, "locales/$lang/$context.inc");
}

$missing = array(); // lang => target file => strings
foreach ($langs as $lang) {
	foreach ($used as $context => $strings) {
		list($files, $target) = contextFiles($root, $lang, $context);
		$have = array();
		foreach ($files as $file) {
			$have += localeStrings($file);
		}
		foreach ($strings as $s => $path) {
			if (!isset($have[$s])) {
				$missing[$lang][$target][$s] = true;
			}
		}
	}
}
// An English key that is an identifier (noDeletePermission) needs its text.
foreach (array_merge(glob("$root/locales/en/*.inc"), glob("$root/modules/*/locales/en.inc")) as $en) {
	foreach (localeStrings($en) as $k => $v) {
		if ($k === $v && preg_match('/^[a-z]+[A-Z][A-Za-z0-9]*$/', $k)) {
			$missing['en'][substr($en, strlen($root) + 1)][$k] = true;
		}
	}
}
// Every English entry has a Spanish one in the matching file.
foreach (array_merge(glob("$root/locales/en/*.inc"), glob("$root/modules/*/locales/en.inc")) as $en) {
	$rel = substr($en, strlen($root) + 1);
	$es = preg_match('#^modules/#', $rel) ? preg_replace('#/en\.inc$#', '/es.inc', $rel) : preg_replace('#^locales/en/#', 'locales/es/', $rel);
	$have = localeStrings("$root/$es");
	foreach (localeStrings($en) as $k => $v) {
		$k = trim($k);
		if (!isset($have[$k]) && !isset($ignore[$k])) {
			$missing['es'][$es][$k] = true;
		}
	}
}

if (isset($argv[1]) && $argv[1] === 'missing') {
	$lang = isset($argv[2]) ? $argv[2] : 'es';
	$out = array();
	foreach (isset($missing[$lang]) ? $missing[$lang] : array() as $file => $strings) {
		$out[$file] = array_keys($strings);
	}
	echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE), "\n";
	exit(0);
}

$total = 0;
foreach ($missing as $lang => $files) {
	foreach ($files as $file => $strings) {
		$total += count($strings);
		echo "$file: " . count($strings) . " missing\n";
		foreach (array_slice(array_keys($strings), 0, 5) as $s) {
			echo "    $s\n";
		}
	}
}
if ($total) {
	echo "FAIL: $total strings without an English or Spanish entry; see tools/locales/check.php.\n";
	exit(1);
}
echo "OK: every translated string has an English and a Spanish entry.\n";
