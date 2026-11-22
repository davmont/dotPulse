<?php /* SYSTEM $Id: translate_save.php 6149 2012-01-09 11:58:40Z ajdonnison $ */
/**
* Processes the entries in the translation form.
* @version $Revision: 6149 $
* @author Andrew Eddie <users.sourceforge.net>
*/

if (! defined('DP_BASE_DIR')) {
	die('You should not call this file directly.');
}

// only user_type of Administrator (1) can save translations, as in translate.php
if (!$canEdit || $AppUI->user_type != 1) {
	$AppUI->redirect('m=public&a=access_denied');
}

$module = dPgetCleanParam($_POST, 'module', 0);
$lang = dPgetCleanParam($_POST, 'lang', $AppUI->user_locale);

// module and lang become part of the file name: accept only the modules and languages
// translate.php offers.
$modules = dPtranslationModules();
if (!is_string($module) || !isset($modules[$module]) || !in_array($lang, $AppUI->readDirs('locales'), true)) {
	$AppUI->setMsg('Invalid module or language.', UI_MSG_ERROR);
	$AppUI->redirect('m=system');
}

// Read raw: an entry submitted unchanged is written back exactly as the file has it, and
// only a new or edited text is cleaned (dPsanitiseHTML() would rewrite "<", ">=" and the like
// in texts left untouched). Browsers send line breaks as \r\n.
$trans = dPgetParam($_POST, 'trans', array());
$trans = is_array($trans) ? $trans : array();

// The file translate.php showed: saving another one (the core file of a module that has its
// own) replaced its entries with the page's.
$filename = dPtranslationFile($module, $lang);

// The file as it is, read before it is rewritten.
$current = dPlocaleEntries($filename);

// A submitted value, or '' for a missing or malformed one.
function dPtranslateValue($langs, $name)
{
	$value = is_array($langs) && isset($langs[$name]) && is_string($langs[$name]) ? $langs[$name] : '';
	return str_replace(array("\r\n", "\r"), "\n", $value);
}
// The text to write: the file's own when it is unchanged, the cleaned one otherwise.
function dPtranslateText($current, $key, $text)
{
	if (isset($current[$key]) && $current[$key]['text'] === $text) {
		return $text;
	}
	return ($text === '') ? $text : dPsanitiseHTML($text);
}

$fp = fopen ($filename, 'wt');

if (!$fp) {
	$AppUI->setMsg("Could not open locales file ($filename) to save.", UI_MSG_ERROR);
	$AppUI->redirect('m=system');
}

// Entries are written with var_export(): single-quoted literals that cannot run code
// when locales/core.php evaluates the file.
$txt = "##\n## DO NOT MODIFY THIS FILE BY HAND!\n##\n";

if ($lang == 'en') {
// editing the english file
	foreach ($trans as $langs) {
		$abbrev = dPtranslateValue($langs, 'abbrev');
		$english = dPtranslateValue($langs, 'english');
		if (($abbrev !== '' || $english !== '') && empty($langs['del'])) {
			$key = ($abbrev !== '') ? $abbrev : $english;
			if (!isset($current[$key])) {
				$key = dPsanitiseHTML($key);
			}
			$english = dPtranslateText($current, $key, $english);
			if ($abbrev !== '') {
				$txt .= var_export($key, true) . '=>';
			}
			$txt .= var_export(($abbrev !== '') ? $english : $key, true) . ',' . "\n";
		}
	}
} else {
// editing the translation: an entry still machine translated ("// MT") keeps its mark while
// its translation is left as it was, so a review can be done in parts.
	// The key is the English text or abbreviation the page sent back in a hidden field.
	foreach ($trans as $langs) {
		$key = dPtranslateValue($langs, 'english');
		if ($key !== '' && empty($langs['del'])) {
			$text = dPtranslateValue($langs, 'lang');
			$same = isset($current[$key]) && $current[$key]['text'] === $text;
			$mark = ($same && $current[$key]['mt']) ? ' // MT' : '';
			$txt .= var_export($key, true) . '=>' . var_export(dPtranslateText($current, $key, $text), true)
				. ',' . $mark . "\n";
		}
	}
}
//echo "<pre>$txt</pre>";
fwrite($fp, $txt);
fclose($fp);

$AppUI->setMsg('Locales file saved', UI_MSG_OK);
$AppUI->redirect($AppUI->getPlace());
?>
