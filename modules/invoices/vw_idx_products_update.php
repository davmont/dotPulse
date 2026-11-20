<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* PRODUCTS $Id: vw_idx_products_update.php,v 1.1.1.1 2004/04/01 16:08:45 aardvarkads Exp $ */
GLOBAL $AppUI, $invoice_id, $obj;

// check permissions
$canEdit = !getDenyEdit( 'invoices', $invoice_id );
if (!$canEdit) {
	$AppUI->redirect( "m=public&a=access_denied" );
}

$product_id = intval( dPgetParam( $_GET, 'product_id', 0 ) );
$log = new CProduct();
if ($product_id) {
	$log->load( $product_id );
} else {
	$log->product_invoice = $invoice_id;
}

// select cost codes for pull down: the billing codes, as in the task log
// (there is no costcodes table)
$q = new DBQuery();
$q -> addTable('billingcode');
$q -> addQuery('billingcode_id, billingcode_name');
$q -> addWhere('billingcode_status = 0');
$q -> addOrder('billingcode_name');
$product_costcodes = arrayMerge( array( 0=>$AppUI->_('Select')), $q -> loadHashList() );


if ($canEdit) {
// Product Update Form
	if ($product_id) {
		echo $AppUI->_( "Edit Log" );
	} else {
		echo $AppUI->_( "Add Log" );
	}
?>
<script type="text/javascript" nonce="<?php echo dPcspNonce(); ?>">
// Copies the chosen cost code and its name into the text fields (was an inline onchange).
function invoiceSetCostcode(sel) {
	var f = sel.form;
	f.product_costcode.value = sel.options[sel.selectedIndex].value;
	f.product_name.value = sel.options[sel.selectedIndex].text;
}
</script>
<table cellspacing="1" cellpadding="2" border="0" width="100%">
<form name="editFrm" action="?m=invoices&a=view&invoice_id=<?php echo $invoice_id;?>" method="post">
	<input type="hidden" name="uniqueid" value="<?php echo uniqid("");?>" />
	<input type="hidden" name="dosql" value="do_updateproduct" />
	<input type="hidden" name="product_id" value="<?php echo (int)$log->product_id;?>" />
	<input type="hidden" name="product_invoice" value="<?php echo (int)$log->product_invoice;?>" />
<tr>
	<td align="right">
		<?php echo $AppUI->_('Cost Code');?>
	</td>
	<td>
<?php
		echo arraySelect( $product_costcodes, 'product_costcodes', 'size="1" class="text"' . dPaction('invoiceSetCostcode', array('$this')), '' );
?>
		&nbsp;->&nbsp; <input type="text" class="text" name="product_costcode" value="<?php echo dPformSafe($log->product_costcode);?>" maxlength="8" size="8" />
	</td>
</tr>
<tr>
	<td align="right"><?php echo $AppUI->_('Description');?></td>
	<td>
		<input type="text" class="text" name="product_name" value="<?php echo dPformSafe($log->product_name);?>" maxlength="255" size="30" />
	</td>
</tr>
<tr>
	<td align="right">
		<?php echo $AppUI->_('Qty');?>
	</td>
	<td>
		<input type="text" class="text" name="product_qty" value="<?php echo dPformSafe($log->product_qty);?>" maxlength="8" size="6" />
	</td>
</tr>
<tr>
	<td align="right">
		<?php echo $AppUI->_('Rate');?>
	</td>
	<td>
		<input type="text" class="text" name="product_price" value="<?php echo dPformSafe($log->product_price);?>" maxlength="8" size="6" />
	</td>
</tr>
<tr>
	<td colspan="2" valign="bottom" align="right">
		<input type="button" class="button" value="<?php echo $AppUI->_('update product');?>"<?php echo dPaction('updateProduct'); ?> />
	</td>
</tr>

</form>
</table>
<?php } ?>
