<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

$AppUI->savePlace();

require_once($AppUI->getSystemClass('CustomFields'));

require_once DP_BASE_DIR . '/modules/system/admin_ui.php';
$edit_field_id = dpGetParam($_POST, 'field_id', NULL);
dPadminOpen('custom_field_editor');
dPadminHeading('Custom fields', 'Fields of your own on projects, tasks, companies and more.');

$sql = ('SELECT * FROM modules' 
        . ' ORDER BY mod_ui_order');
$q = new DBQuery;
$q->addTable('modules');
$q->addWhere('mod_name IN (\'Companies\', \'Projects\', \'Tasks\', \'Calendar\')');
$modules = $q->loadList();

echo '<table cellpadding="2" summary="module list">';

foreach ($modules as $module) {
	echo '<tr><td colspan="4">';
	echo '<h3>'.$AppUI->_($module['mod_name']).'</h3>';
	echo '</td></tr>';
	
	echo '<tr><td colspan="4">';
	echo ('<a href="?m=system&amp;a=custom_field_addedit&amp;module=' . $module['mod_name'] 
		  . '"><img src="./images/icons/stock_new.png" align="center" width="16" height="16" border="0" alt="" />' 
		  . $AppUI->_('Add a new Custom Field to this Module') . '</a><br /><br />');
	echo '</td></tr>';

	echo '<tr>';
	echo '<th>' . $AppUI->_('Edit') . '</th>';
	echo '<th>' . $AppUI->_('Delete') . '</th>';
	echo '<th>' . $AppUI->_('Order') . '</th>';
	echo '<th>' . $AppUI->_('Description') . '</th>';
	echo '</tr>';
	
	$q->clear();
	$q->addTable('custom_fields_struct');
	$q->addWhere('field_module = \''.mb_strtolower($module['mod_name'])."'");
	$q->addOrder('field_order ASC');
	$custom_fields = $q->loadList();
	
	foreach ($custom_fields as $f) {
		echo '<tr><td class="hilite">';
		echo ('<a href="?m=system&amp;a=custom_field_addedit&amp;module=' . $module['mod_name'] 
		      . '&amp;field_id=' . $f['field_id'] 
			  . '"><img src="./images/icons/stock_edit-16.png" align="center" width="16" height="16" border="0" alt="" />Edit</a>');
		echo '</td><td class="hilite">';
		echo ('<a href="#"' . dPaction('dpPost', array('?m=system&a=custom_field_addedit&field_id=' . (int)$f['field_id'], array('delete' => 1)))
			  . '><img src="./images/icons/stock_delete-16.png" align="center" width="16" height="16" border="0" alt="" />Delete</a>');
		echo '</td><td class="hilite">';
		echo $f['field_order'];
		echo '</td><td class="hilite">';
		echo htmlspecialchars($f['field_description']) . "\n";
		echo '</td></tr>';
	}
}
?>
<?php dPadminClose(); ?>
