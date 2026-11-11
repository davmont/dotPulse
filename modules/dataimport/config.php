<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
// Configure the import file format for data imports.

if (! $canEdit)
  $AppUI->redirect('m=public&a=access_denied');

// Allowed tables is a list of tables that we allow updates into.
// At the moment we limit this to projects and tasks.
$allowedTables = diAllowedTables();

$firstTime = dPgetParam($_REQUEST, 'first', 0);
$diconfig_id = dPgetIntParam($_REQUEST, 'diconfig_id', 0);
$obj = new CDataImport;
if ($diconfig_id) {
  $obj->load($diconfig_id, false);
  $field_list = $obj->getDataMap();
} else {
  $field_list = array();
}

$field_count = count($field_list);

$colList = array();
foreach ($allowedTables as $table) {
  $cols = $db->MetaColumns(dPgetConfig('dbprefix', '') . $table) ?: array();
  $colList[$table] = array();
  foreach ($cols as $col) {
    $colList[$table][] = $col->name;
  }
}

$titleBlock = new CTitleBlock('Data Import', 'companies.gif', $m, $m.'.'.$a);
$perms =& $AppUI->acl();
$canDelete = $perms->checkModuleItem($m, 'delete', $diconfig_id);
$msg = '';
if ($diconfig_id && $canDelete)
  $titleBlock->addCrumbDelete('delete', $canDelete, $msg);
if ($firstTime) {
  $titleBlock->addCell('<b>' . $AppUI->_('First time configuration') . '</b>');
} else {
  $titleBlock->addCrumb('index.php?m=dataimport', 'import');
}

$titleBlock->show();

?>
<script language="javascript" nonce="<?php echo dPcspNonce(); ?>">
  var diconfig_target_tables = new Comparable;
  <?php
    foreach ($allowedTables as $table) {
      $res = 'diconfig_target_tables.add(' . dPjs($table) . ', new Array(';
	// need to find the field names and supply them
	$first = true;
	foreach ($colList[$table] as $col) {
	  if ($first)
	    $first = false;
	  else
	    $res .= ",\n";
	  $res .= dPjs($col);
	}
      $res .= '));';
      echo $res . "\n";
    }
  ?>

    function delIt()
    {
      var form = document.changeformat;
      if (confirm("<?php echo $AppUI->_('OK to Delete Format?', UI_OUTPUT_JS);?>")) {
	form.del.value = "<?php echo $diconfig_id; ?>";
	form.submit();
      }
    }

    var sel_from_list = '<?php echo $AppUI->_('-- Select From List -- ', UI_OUTPUT_JS);?>';

</script>

<form name="changeformat" action="index.php?m=dataimport&a=config" method="post">
  <input type="hidden" name="dosql" value="do_config" />
  <input type="hidden" name="del" value="0" />
  <input type="hidden" name="diconfig_id" value="<?php echo $diconfig_id; ?>" />
  <input type="hidden" name="field_count" id="field_count" value="<?php echo $field_count; ?>" />

<table border="0" cellpadding="4" cellspacing="0" width="100%" class="std">
  <tr>
    <td align="right"><?php echo $AppUI->_('Format Name'); ?></td>
    <td><input type="text" class="text" name="diconfig_name" value="<?php echo dPhtml($obj->diconfig_name); ?>" /></td>
  </tr>
  <tr>
    <td align="right"><?php echo $AppUI->_('Field Separator'); ?></td>
    <td><input type="text" class="text" name="diconfig_field_sep" value="<?php echo dPhtml($obj->diconfig_field_sep); ?>" /></td>
  </tr>
  <tr>
    <td align="right"><?php echo $AppUI->_('Strip Quotes'); ?></td>
    <td><input type="checkbox" name="diconfig_quoted" value="1"
      <?php if ($obj->diconfig_quoted)
        echo 'checked="checked"'; ?> /></td>
  </tr>
  <tr>
    <td align="right"><?php echo $AppUI->_('Quote Escape'); ?></td>
    <td><input type="text" class="text" name="diconfig_quote_char" value="<?php echo dPhtml($obj->diconfig_quote_char); ?>" /></td>
  </tr>
  <tr>
    <td align="right"><?php echo $AppUI->_('Make Default'); ?></td>
    <td><input type="checkbox" name="diconfig_default" value="1" <?php
      if ($obj->diconfig_default)
        echo 'checked="checked"'; ?> /></td>
  </tr>
</table>
<table border="0" cellpadding="4" cellspacing="0" width="100%" class="std">
<thead>
  <tr>
    <th width='30%'><?php echo $AppUI->_('Source Field'); ?></th>
    <th width='30%'><?php echo $AppUI->_('Target Table'); ?></th>
    <th width='30%'><?php echo $AppUI->_('Target Field'); ?></th>
    <th width='10%'>&nbsp;</th>
  </tr>
</thead>
<tbody id='row_info'>
<?php
  $id = 0;
  if ($field_count) {
    foreach ($field_list as $field) {
      $id++;
      $row = '<tr id="field_row^' . $id . '"><td><input type="text" class="text" name="dimap_source_field[';
      $row .= $id . ']" id="dimap_source_field^' . $id . '"  value="' . dPhtml($field['dimap_source_field']);
      $row .= '" /></td><td>';
      $row .= diBuildSelect('dimap_target_table', $id, $allowedTables, $field['dimap_target_table'], true, 'diconfig_set_target');
      $row .= '</td><td>';
      $row .= diBuildSelect('dimap_target_field', $id, $colList[$field['dimap_target_table']], $field['dimap_target_field']);
      $row .= '</td><td><input type="checkbox" class="button" name="del_field['.$id . ']" id="del_field^' . $id . '" />';
      $row .= '</td></tr>';
      echo $row . "\n";
    }
  }
?>
</tbody>
</table>
<table border="0" cellpadding="4" cellspacing="0" width="100%" class="std">
  <tr>
    <td align="left">
      <input type="button" class="button" name="add"<?php echo dPaction('diconfig_add_row'); ?> value="<?php echo $AppUI->_('add field'); ?>" />
    </td>
    <td align="center">
      <input type="button" class="button" name="add"<?php echo dPaction('diconfig_del_row'); ?> value="<?php echo $AppUI->_('delete fields'); ?>" />
    </td>
    <td align="right">
      <input type="submit" class="button" name="save" value="<?php echo $AppUI->_('save'); ?>" ?>
    <td>
  </tr>
</table>
</form>
