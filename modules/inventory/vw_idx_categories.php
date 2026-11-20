<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

global $m,$category_list,$brand_list, $a, $dPconfig;

error_reporting( E_ALL );

include_once("{$dPconfig['root_dir']}/modules/inventory/utility.php");

// check permissions for this module

$perms =& $AppUI->acl();
$canEdit = $perms->checkModule( $m, "edit" );

?>


<?php
if ( $canEdit )
{
?>

<script nonce="<?php echo dPcspNonce(); ?>">
<!--
// Asks for the new name of a category or brand and posts it (dpPost adds the CSRF token).
function renameWindow( _id, _name, _type )
{
	var label = <?php echo dPjs($AppUI->_('rename', UI_OUTPUT_RAW)); ?>;
	var newName = window.prompt( label + ' ' + _type + ' "' + _name + '"', _name );
	if ( newName === null || newName === '' ) {
		return false;
	}
	var fields = { dosql: 'do_inventory_aed' };
	fields[ 'change_' + _type + '_id' ] = _id;
	fields[ 'change_' + _type + '_name' ] = newName;
	return dpPost( '?m=inventory', fields );
}
-->
</script>

<?php
}
?>


<TABLE BORDER="0" CELLPADDING="0" CELLSPACING="0" WIDTH="100%" CLASS="tbl" style="overflow-wrap:anywhere">
<TBODY>
<TR>
	<TD WIDTH="50%" STYLE="padding: 4px;" VALIGN="top" >
		<TABLE BORDER="0" CELLPADDING="2" CELLSPACING="1" WIDTH="100%" CLASS="tbl" >
			<THEAD>
			<TR>
				<TH></TH>
				<TH><?php echo $AppUI->_( "Category Name" ); ?></TH>
				<TH NOWRAP><?php echo $AppUI->_( "Mark" ); ?></TH>
			</TR>
			</THEAD>
			<TBODY>
			<?php
				
				foreach ( $category_list as $category )
				{
					echo "<TR><TD>";
					if ( $canEdit )
					{
						echo '<A HREF="#"' . dPaction('renameWindow', array((int)$category['inventory_category_id'], $category['inventory_category_name'], 'category')) . '>';
						echo dPshowImage( "./images/icons/stock_edit-16.png", 16, 16, "" );
						echo "</A>";
					}
					echo "</TD><TD WIDTH='100%'>";
					echo dPhtml($category[ 'inventory_category_name' ]);
					echo "</TD><TD ALIGN='center'>";
					echo '<INPUT TYPE="CHECKBOX" NAME="category_'.$category['inventory_category_id'].'">';
					echo "</TD></TR>";
				}
			?>
		</TBODY>	
		</TABLE>
	</TD>
	<TD WIDTH="50%" STYLE="padding: 4px;" VALIGN="top">
		<TABLE BORDER="0" CELLPADDING="2" CELLSPACING="1" WIDTH="100%" CLASS="tbl">
			<THEAD>
			<TR>
				<TH></TH>
				<TH><?php echo $AppUI->_( "Brand Name" ); ?></TH>
				<TH NOWRAP><?php echo $AppUI->_( "Mark" ); ?></TH>
			</TR>
			</THEAD>
			<TBODY>
			<?php
				foreach ( $brand_list as $brand )
				{
					echo "<TR><TD>";
					if ( $canEdit )
					{
						echo '<A HREF="#"' . dPaction('renameWindow', array((int)$brand['inventory_brand_id'], $brand['inventory_brand_name'], 'brand')) . '>';
						echo dPshowImage( "./images/icons/stock_edit-16.png", 16, 16, "" );
						echo "</A>";
					}
					echo "</TD><TD WIDTH='100%'>";
					echo dPhtml($brand[ 'inventory_brand_name' ]);
					echo "</TD><TD ALIGN='center'>";
					echo '<INPUT TYPE="CHECKBOX" NAME="brand_'.$brand['inventory_brand_id'].'">';
					echo "</TD></TR>";
				}
				?>
			</TBODY>	
			</TABLE>
	</TD>
</TR>
</TBODY>
</TABLE>

