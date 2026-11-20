<?php /* SYSTEM $Id: translate.php 6149 2012-01-09 11:58:40Z ajdonnison $ */
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}


// only user_type of Administrator (1) can access this page
if (!$canEdit || $AppUI->user_type != 1) {
	$AppUI->redirect('m=public&a=access_denied');
}

$module = dPgetCleanParam($_REQUEST, 'module', 'admin');
$lang = dPgetCleanParam($_REQUEST, 'lang', $AppUI->user_locale);
// lang names a locale directory and is echoed into the form below.
if (!in_array($lang, $AppUI->readDirs('locales'), true)) {
	$lang = $AppUI->user_locale;
}

$AppUI->savePlace('m=system&a=translate&module='.$module.'&lang=' . $lang);

// read the installed modules
$modules = arrayMerge($AppUI->readDirs('modules'), array('common' => 'common', 'styles' => 'styles'));
asort($modules);

// read the installed languages
$locales = $AppUI->readDirs('locales');

ob_start();
// The same file translate_save.php writes (dPtranslationFile()).
@readfile(dPtranslationFile($modules[$module], 'en'));
$english = array();
if (dPisTranslationSource(ob_get_contents())) {
	eval("\$english=array(".ob_get_contents()."\n'0');");
}
ob_end_clean();

$trans = array();
foreach ($english as $k => $v) {
	if ($v != '0') {
		$trans[ (is_int($k) ? $v : $k) ] = array(
			'english' => $v
		);
	}
}

//echo '<pre>';print_r($trans);echo '</pre>';die;

if ($lang != 'en') {
	ob_start();
	@readfile(dPtranslationFile($modules[$module], $lang));
	$locale = array();
	if (dPisTranslationSource(ob_get_contents())) {
		eval("\$locale=array(".ob_get_contents()."\n'0');");
	}
	ob_end_clean();

	foreach ($locale as $k => $v) {
		if ($v != '0') {
			$trans[$k]['lang'] = $v;
		}
	}
}
ksort($trans);

require_once DP_BASE_DIR . '/modules/system/admin_ui.php';
dPadminOpen('translate');
dPadminHeading('Translations', 'The interface texts of each module in each language.');
$temp = $AppUI->setWarning(false);
echo '<form action="?m=system&amp;a=translate" method="post" name="modlang" class="dpa-actions">'
	. '<label class="dpa-search"><span>' . $AppUI->_('Module') . '</span>'
	. arraySelect($modules, 'module', 'size="1"' . dPaction(null, array(), null, array('submit' => 'modlang')), $module) . '</label>'
	. '<label class="dpa-search"><span>' . $AppUI->_('Language') . '</span>'
	. arraySelect($locales, 'lang', 'size="1"' . dPaction(null, array(), null, array('submit' => 'modlang')), $lang, true) . '</label>'
	. '</form>';
$AppUI->setWarning($temp);
?>

<form action="?m=system&amp;a=translate_save" method="post" name="editlang">
<input type="hidden" name="module" value="<?php echo $modules[$module];?>" />
<input type="hidden" name="lang" value="<?php echo $lang;?>" />
<table width="100%" border="0" cellpadding="1" cellspacing="1" class="tbl">
<tr>
	<th width="15%" nowrap><?php echo $AppUI->_('Abbreviation');?></th>
	<th width="40%" nowrap><?php echo $AppUI->_('English String');?></th>
	<th width="40%" nowrap><?php echo $AppUI->_('String').': '.$AppUI->_($locales[$lang]);?></th>
	<th width="5%" nowrap><?php echo $AppUI->_('delete');?></th>
</tr>
<?php
$index = 0;
if ($lang == 'en') {
	echo "<tr>\n";
	echo ('<td><input type="text" name="trans[' . $index 
	      . '][abbrev]" value="" size="20" class="text" /></td>' . "\n");
	echo ('<td><input type="text" name="trans[' . $index 
	      . '][english]" value="" size="40" class="text" /></td>' . "\n");
	echo '<td colspan="2">'.$AppUI->_('New Entry')."</td>\n";
	echo "</tr>\n";
}

$index++;
// Values are escaped with htmlspecialchars() only (dPformSafe() also strips backslashes), so
// an entry submitted untouched comes back as the file has it and translate_save.php keeps it.
// A text box drops line breaks: a value with one gets a textarea whatever its length.
function dPtranslateField($name, $value, $size)
{
	$value = (string)$value;
	$html = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
	if (mb_strlen($value) < 40 && strpos($value, "\n") === false) {
		return '<input type="text" name="' . $name . '" value="' . $html . '" size="' . $size . '" class="text" />';
	}
	$rows = round(mb_strlen($value) / 35) + 1 + substr_count($value, "\n");
	return '<textarea name="' . $name . '" cols="40" class="small" rows="' . $rows . '">' . $html . '</textarea>';
}
foreach ($trans as $k => $langs) {
	$k = (string)$k;
	$english = (string)@$langs['english'];
?>
<tr>
	<td><?php
if ($k !== $english) {
	echo ($lang == 'en') ? dPtranslateField('trans[' . $index . '][abbrev]', $k, 20)
		: htmlspecialchars($k, ENT_QUOTES, 'UTF-8');
} else {
	echo '&nbsp;';
}
?></td>
	<td><?php
if ($lang == 'en') {
	echo dPtranslateField('trans[' . $index . '][english]', $english, 40);
} else {
	// The key the translation is saved under: the abbreviation, or the English text.
	echo nl2br(htmlspecialchars($english, ENT_QUOTES, 'UTF-8'));
	echo '<input type="hidden" name="trans[' . $index . '][english]" value="'
		. htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '" />';
}
?></td>
	<td><?php
if ($lang != 'en') {
	echo dPtranslateField('trans[' . $index . '][lang]', @$langs['lang'], 40);
}
?></td>
	<td align="center"><input type="checkbox" name="trans[<?php echo $index; ?>][del]" /></td>
</tr>
<?php
	$index++;
}
?>
<tr>
	<td colspan="4" align="right">
		<input type="submit" value="<?php echo $AppUI->_('submit');?>" class="button" />
	</td>
</tr>
</form>
</table>
<?php dPadminClose(); ?>
