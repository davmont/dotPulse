<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

require_once($AppUI->getSystemClass('CustomFields'));
	
require_once DP_BASE_DIR . '/modules/system/admin_ui.php';
dPadminOpen('custom_field_editor');
dPadminHeading('Add or edit a custom field', '');

$field_id = ((dpGetParam($_POST, 'field_id', NULL) != NULL) 
             ? dPgetIntParam($_POST, 'field_id', 0) : dPgetIntParam($_GET, 'field_id', 0));
// Deleting a field changes data: POST with the CSRF token and system edit permission only.
$delete_field = ($_SERVER['REQUEST_METHOD'] === 'POST') ? dpGetParam($_POST, 'delete', 0) : 0;
if ($delete_field) {
	$AppUI->verifyCsrfToken();
	if (!getPermission('system', 'edit')) {
		$AppUI->redirect('m=public&a=access_denied');
	}
}
$module = ((dpGetParam($_GET, 'module', NULL) == NULL) 
           ? dpGetParam($_POST, 'module', NULL) : dpGetParam($_GET, 'module', NULL));
// Custom fields belong to an installed module: accept only a module name (case as listed).
$q = new DBQuery();
$q->addTable('modules');
$q->addQuery('mod_name');
$known_module = null;
foreach ($q->loadColumn() as $mod_name) {
	if (is_string($module) && mb_strtolower($mod_name) === mb_strtolower($module)) {
		$known_module = $module;
		break;
	}
}
$q->clear();
if ($known_module === null) {
	$AppUI->setMsg('Module', UI_MSG_ERROR);
	$AppUI->setMsg('invalidID', UI_MSG_ERROR, true);
	$AppUI->redirect('m=system&a=custom_field_editor');
}
$module = $known_module;

$select_newitem = dpGetParam($_POST, 'select_newitem', NULL);
$select_items = dpGetParam($_POST, 'select_items', Array());

$select_delitem = dpGetParam($_POST, 'delete_item', NULL);

if ($select_newitem != NULL) {
	$select_items[] = $select_newitem;
}

if ($select_delitem != NULL) {
	$new_selectitems = Array();
	
	foreach ($select_items as $itm) {
		if ($itm != $select_delitem) { 
			$new_selectitems[] = $itm;
		}
	}
	
	unset($select_items);
	$select_items = &$new_selectitems;
}

// Loading the page for the first time
if (dpGetParam($_GET, 'field_id', NULL) != NULL) {	
	$custom_fields = New CustomFields($module, 'addedit', NULL, 'edit');
	
	if ($delete_field) {
		$custom_fields->deleteField($field_id);
		$AppUI->redirect();
	}
	
	$cf =& $custom_fields->fieldWithId($field_id);
	
	if (is_object($cf)) {
		$field_name = $cf->fieldName();
		$field_description = $cf->fieldDescription();
		$field_htmltype = $cf->fieldHtmlType();
		$field_extratags = $cf->fieldExtraTags();
		$field_order = $cf->field_order;
		$field_datatype = $cf->field_datatype;
		
		if ($field_htmltype == 'select') {
			$select_options = New CustomOptionList($field_id);
			$select_options->load();
			$select_items = $select_options->getOptions();
		}
	} else {
		//No such field exists with this ID
		$AppUI->setMsg('Couldnt load the Custom Field, It might have been deleted somehow.'); 
		$AppUI->redirect();
	}
	
	$edit_title = $AppUI->_('Edit Custom Field In'); 
} else {
	$edit_title = $AppUI->_('New Custom Field In');
	$field_name = dpGetParam($_POST, 'field_name', NULL);
	$field_description = dpGetParam($_POST, 'field_description', NULL);
	$field_htmltype = dpGetParam($_POST, 'field_htmltype', 'textinput');
	$field_extratags = dpGetParam($_POST, 'field_extratags', NULL);
	$field_order = dpGetParam($_POST, 'field_order', 1);
	$field_datatype = dpGetParam($_POST, 'field_datatype', 'alpha');
}

$html_types = Array('file' => $AppUI->_('File'),
                    'textinput'=>$AppUI->_('Text Input'),
                    'textarea'=>$AppUI->_('Text Area'),
                    'checkbox'=>$AppUI->_('Checkbox'),
                    'select'=>$AppUI->_('Select List'),
                    'label' => $AppUI->_('Label'),
                    'separator' => $AppUI->_('Separator'),
                    'href'=>$AppUI->_('Weblink'),);

$visible_state = Array();

foreach ($html_types as $k => $ht) {
	$visible_state['div_'.$k] = 'display: ' . (($k == $field_htmltype) ? 'block' : 'none');
}
?>
<script type="text/javascript" nonce="<?php echo dPcspNonce(); ?>">
function hideAll() {
	var selobj = document.getElementById('field_htmltype');
	for (i = 0; i < selobj.options.length; i++) {
		var atbl = document.getElementById('atbl_'+(selobj.options[i].value));
		var adiv = document.getElementById('div_'+(selobj.options[i].value));
		
		atbl.style.visibility = 'hidden';
		adiv.style.display = 'none';
	} 
}

function showAttribs() {
	hideAll();
	var selobj = document.getElementById('field_htmltype');
	
	var atbl = document.getElementById('atbl_'+(selobj.options[selobj.selectedIndex].value));
	var adiv = document.getElementById('div_'+(selobj.options[selobj.selectedIndex].value));
	atbl.style.visibility = 'visible';
	adiv.style.display = 'block';
}

function addSelectItem() {
	frm = document.getElementById('custform');
	frm.action = '?m=system&a=custom_field_addedit';
	frm.submit();
}

function deleteItem(itmname) {
	del = document.getElementById('delete_item');
	del.value = itmname;
	addSelectItem();
}

function postCustomField() {
	frm = document.getElementById('custform');
	frm.action = '?m=system&a=custom_field_editor';
	sql = document.getElementById('dosql');
	sql.name = 'dosql';	
	frm.submit();
}

// Field names: letters and digits only.
function cleanFieldName(el) {
	el.value = el.value.replace(/[^a-z|^A-Z|^0-9]*/gi,'');
}
</script>
<form method="post" action="?m=system&amp;a=custom_field_editor" id="custform" />
<table class="std">
	<th colspan="2">
		<?php echo $edit_title?> <?php echo $AppUI->_($module)?> <?php echo $AppUI->_('Module') ?>
		<input type="hidden" name="field_id" value="<?php echo htmlspecialchars((string)$field_id); ?>" />
		<input type="hidden" name="module" value="<?php echo htmlspecialchars((string)$module)?>" /> 
		<input type="hidden" name="dontdosql" id="dosql" value="do_custom_field_aed" />
	</td></tr>
	<tr><td>
		<?php echo $AppUI->_('Field Name/Identifier')?>:
		<br />
		<?php echo $AppUI->_('(No Spaces)')?>
		</td><td>
		<input type="text" name="field_name" maxlength="100" value="<?php 
echo htmlspecialchars($field_name)?>"<?php echo dPaction('cleanFieldName', array('$this'), 'blur'); ?> />
	</td></tr>
	<tr><td>
		<?php echo $AppUI->_('Field Description')?>:
		</td><td>
		<input type="text" name="field_description" size="40" maxlength="250" value="<?php 
echo htmlspecialchars($field_description)?>" />
	</td></tr>
	<tr><td>
		<?php echo $AppUI->_('Field Order')?>:
		</td><td>
		<input type="text" name="field_order" size="4" maxlength="4" value="<?php
echo intval($field_order)?>" />
	</td></tr>
	<tr><td>
		<?php echo $AppUI->_('Field Data Type')?>:
		</td><td>
		<?php
// arraySelect() takes the list by reference, so it must be a variable (PHP 8).
$datatypes = array('alpha' => $AppUI->_('Text'), 'numeric' => $AppUI->_('Numeric'));
echo arraySelect($datatypes, 'field_datatype', 'id="field_datatype"', $field_datatype); ?>
	</td></tr>
	<tr><td>
		<?php echo $AppUI->_('Field Display Type')?>:
		</td><td>
		<?php 
echo arraySelect($html_types, 'field_htmltype', 
                 'id="field_htmltype"' . dPaction('showAttribs'), $field_htmltype); ?>
	</td></tr>
	<tr><td colspan="2">
		<hr />
		<tr><td>
			<?php echo $AppUI->_('HTML Tag Options')?>:
		</td>
		<td>
			<input type="text" name="field_extratags" value="<?php echo htmlspecialchars((string)$field_extratags)?>" />
		</td></tr>
	</td></tr>
	<tr><td colspan="2">
	<div id="div_select" style="<?php echo $visible_state['div_select']?>">
		<table id="atbl_select">
		<tr><td colspan="2">
			<b><?php echo $AppUI->_('List of Options')?>:</b> 
		</td></tr>
		<tr><td colspan="2">
			<input type="hidden" name="delete_item" value="0" id="delete_item" />
			<table>
			<?php
foreach ($select_items as $itm) {
	$itmhtml = htmlspecialchars($itm);
	echo '<tr><td>';
	echo '<li>'.$itmhtml."</li>\n";
	echo '<input type="hidden" name="select_items[]" value="'.$itmhtml.'" />';
	echo '</td><td>';
	echo '<a href="#"' . dPaction('deleteItem', array((string)$itm)) . '>[Delete]</a>';
	echo '</td></tr>';
}
?>
		<tr>
			<td><input type="text" name="select_newitem" /></td>
			<td><input type="button" value="<?php echo $AppUI->_('Add')?>"<?php echo dPaction('addSelectItem'); ?> /></td>
		</tr>
		</table>
	</td></tr>
	</table>
	<hr />
	</div>
	</div>
	<div id="div_file" style="<?php echo $visible_state['div_file']?>">
		<table id="atbl_file">
		</table>
	</div>
	<div id="div_textinput" style="<?php echo $visible_state['div_textinput']?>">
		<table id="atbl_textinput">
		</table>
	</div>
	<div id="div_textarea" style="<?php echo $visible_state['div_textarea']?>">
		<table id="atbl_textarea">
		</table>
	</div>
	<div id="div_checkbox" style="<?php echo $visible_state['div_checkbox']?>">
		<table id="atbl_checkbox">
		</table>
	</div>
	<div id="div_label" style="<?php echo $visible_state['div_label']?>">
		<table id="atbl_label">
		</table>
	</div>
	<div id="div_separator" style="<?php echo $visible_state['div_separator']?>">
		<table id="atbl_separator">
		</table>
	</div>
	<div id="div_href" style="<?php echo $visible_state['div_href']?>">
		<table id="atbl_href">
		</table>
	</div>
	</td></tr>
	<tr><td colspan="2" align="right">
		<input type="button" value="Cancel"<?php echo dPaction(null, array(), null, array('href' => '?m=system&a=custom_field_editor')); ?> />
		<input type="button" value="Save"<?php echo dPaction('postCustomField'); ?> />
	</td></tr>
	</form>
</table>
<?php dPadminClose(); ?>
