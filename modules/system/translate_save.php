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
$modules = arrayMerge($AppUI->readDirs('modules'), array('common' => 'common', 'styles' => 'styles'));
if (!isset($modules[$module]) || !in_array($lang, $AppUI->readDirs('locales'), true)) {
	$AppUI->setMsg('Invalid module or language.', UI_MSG_ERROR);
	$AppUI->redirect('m=system');
}

$trans = dPgetCleanParam($_POST, 'trans', 0);
//echo '<pre>';print_r($trans);echo '</pre>';die;

// save to core locales if a translation exists there, otherwise save
// into the module's local locale area if it exists.  If not then
// the core table is updated.
$core_filename = DP_BASE_DIR.'/locales/'.$lang.'/'.$module.'.inc';
if (file_exists($core_filename)) {
	$filename = $core_filename;
} else {
	$mod_locale = DP_BASE_DIR.'/modules/'.$module.'/locales';
	if (is_dir($mod_locale))
		$filename = DP_BASE_DIR.'/modules/'.$module.'/locales/'.$lang.'.inc';
	else
		$filename = $core_filename;
}

// The marks of the file as it is, read before it is rewritten.
$review = ($lang == 'en') ? array() : dPlocaleReviewMarks($filename);

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
		if ((@$langs['abbrev'] || $langs['english']) && empty($langs['del'])) {
			$langs['abbrev'] = stripslashes(@$langs['abbrev']);
			$langs['english'] = stripslashes($langs['english']);
			if (!empty($langs['abbrev'])) {
				$txt .= var_export($langs['abbrev'], true) . '=>';
			}
			$txt .= var_export($langs['english'], true) . ',' . "\n";
		}
	}
} else {
// editing the translation: an entry still machine translated ("// MT") keeps its mark while
// its translation is left as it was, so a review can be done in parts.
	foreach ($trans as $langs) {
		if (empty($langs['del'])) {
			$langs['english'] = stripslashes($langs['english']);
			$langs['lang'] = stripslashes($langs['lang']);
			$mark = (isset($review[$langs['english']]) && $review[$langs['english']] === $langs['lang']) ? ' // MT' : '';
			$txt .= var_export($langs['english'], true) . '=>' . var_export($langs['lang'], true) . ',' . $mark . "\n";
		}
	}
}
//echo "<pre>$txt</pre>";
fwrite($fp, $txt);
fclose($fp);

$AppUI->setMsg('Locales file saved', UI_MSG_OK);
$AppUI->redirect($AppUI->getPlace());
?>
