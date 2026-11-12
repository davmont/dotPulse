<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

global $m,$df,$item_list,$sorted_item_list,$AppUI, $dPconfig;
global $user_list, $project_list, $company_list, $department_list;
global $sort_state;
global $inventory_view_mode;

if ( !isset( $inventory_view_mode ) ) $inventory_view_mode = "normal";

error_reporting( E_ALL );

// check permissions for the inventory module

$perms =& $AppUI->acl();
$canAccess = $perms->checkModule( $m, "access" );
$canRead   = $perms->checkModule( $m, "view" );
$canEdit   = $perms->checkModule( $m, "edit" );
$canDelete = $perms->checkModule( $m, "delete" );
$canAdd    = $perms->checkModule( $m, "add" );

require_once( $AppUI->getModuleClass( "admin" ) );
require_once( $AppUI->getModuleClass( "projects" ) );
require_once( $AppUI->getModuleClass( "companies" ) );
require_once( $AppUI->getModuleClass( "departments" ) );

$df = $AppUI->getPref('SHDATEFORMAT');

$user_list = array();
$project_list = array();
$company_list = array();
$department_list = array();


include_once("{$dPconfig['root_dir']}/modules/inventory/utility.php");

load_all_items();




?>


<TABLE BORDER="0" CELLPADDING="3" CELLSPACING="1" CLASS="tbl" WIDTH="100%" style="overflow-wrap:anywhere">
<FORM ACTION="?m=inventory" METHOD="post" ID="markForm">
	<THEAD>
	
	<TR>
		<TH><?php
			if ($canEdit)
			{
				if ( $canAdd ) echo $AppUI->_( "Mark" )."/";
				echo $AppUI->_( "Edit" );
			}
			?> </TH>
			<?php
			sortHeader( $AppUI->_( "Asset No" ), "inventory_id" );
			sortHeader( $AppUI->_( "Item Name" )." (".$AppUI->_( "click for details" ).")", "inventory_name" );
			sortHeader( $AppUI->_( "Brand" ), "inventory_brand_name" );
			sortHeader( $AppUI->_( "Category" ), "inventory_category_name" );
			sortHeader( $AppUI->_( "Company" ), "inventory_company_name" );
			sortHeader( $AppUI->_( "Department" ), "inventory_department_name" );
			sortHeader( $AppUI->_( "Assigned to" ), "inventory_user_username" );
			sortHeader( $AppUI->_( "Project" ), "inventory_project_name" );
			sortHeader( $AppUI->_( "Date" ), "inventory_purchased" );
			if ( $inventory_view_mode == "normal" )
			{
				sortHeader( $AppUI->_( "Cost" ), "inventory_totalcost" );
			}
			if ( $inventory_view_mode == "orders" )
			{
				sortHeader( $AppUI->_( "Status" ), "inventory_purchase_state" );
			}
		?>
	</TR>
	</THEAD>
	<TBODY>
	<?php
		
		$child_parent = 0;
		if ( isset( $_GET[ "children" ] ) ) $child_parent = $_GET[ "children" ];
		
	// auto-close children if over 100 items to be displayed
		
		if ( !$child_parent && count( $item_list ) < 100 ) $child_parent = "all";
		
		reset( $sorted_item_list );
		foreach ($sorted_item_list as $item)
		{
			if ( (!$item['inventory_parent'] && (!$child_parent || $child_parent == "all"))
				 || ( $child_parent && $child_parent == $item['inventory_parent' ] ) )
			{
				display_item( $item, ($child_parent != "all" && $child_parent)?1:0, $child_parent );
			}
		}
		
	/* make sure orphaned items are also displayed */
		
		global $drawn_array;
		
		if (!$child_parent || $child_parent=="all" )
		{
			reset( $item_list );
			foreach ($item_list as $item )
			{
				if ( !isset( $drawn_array[ $item[ 'inventory_id' ] ] ) )
				{
					$item[ 'inventory_name' ] = $item[ 'inventory_name' ] . " (" . $AppUI->_( "parent not shown" ) . ")" ;
					display_item( $item, ($item[ 'inventory_parent' ] ) ? 1 : 0 );
				}
			}
		}
	?>
<TR>
	<TD COLSPAN="11">
		<?php
			$num_marked = count( get_marked_inventory() );
		
			if ( $canAdd )
			{
				echo '<INPUT TYPE="submit" class="button" name="remember_marked" VALUE="'.$AppUI->_( "Remember Marked" ).'"> ';
				if ( $num_marked ) echo '<INPUT TYPE="submit" class="button" name="remember_more" VALUE="'.$AppUI->_( "Remember More" ).'"> ';
				echo '<INPUT TYPE="submit" class="button" name="remember_clear" VALUE="'.$AppUI->_( "Clear Marked" ).'"> ';
				echo '<INPUT TYPE="hidden" NAME="dosql" VALUE="do_inventory_aed" />';
			}
		?>
	</TD>
</TR>
</TBODY>
</FORM>
</TABLE>

<?php
			
	$div_begun = false;
			
	if ( $num_marked )
	{
		echo "<div style='font-size: 9px; padding-top: 4px;'>";
		echo $num_marked." ".$AppUI->_( "items remembered" );
		$div_begun = true;
	}
			
	global $company_list;
	load_company_list();
		
	$filter_company = $AppUI->getState( 'InventoryIdxFilterCompany' ) ? $AppUI->getState( 'InventoryIdxFilterCompany' ) : 0;
	$filter_type    = $AppUI->getState( 'InventoryIdxFilterType' ) ? $AppUI->getState( 'InventoryIdxFilterType' ) : 0;
	$filter_index   = $AppUI->getState( 'InventoryIdxFilterIndex' ) ? $AppUI->getState( 'InventoryIdxFilterIndex' ) : 0;
	$filter_search  = $AppUI->getState( 'InventoryIdxFilterSearch' ) ? $AppUI->getState( 'InventoryIdxFilterSearch' ) : 0;
		
	if ( $filter_company )
	{
		if ( !$div_begun )
		{
			echo "<div style='font-size: 9px; padding-top: 4px;'>";
			$div_begun = true;
		}
		else echo " | ";
		
		
		echo $AppUI->_( "filtered by" )." ";
		
		if ( $filter_type !="choose" && $filter_index )
		{
			echo " ".$AppUI->_( $filter_type );
		}
		else
		{
			echo dPhtml( $company_list[ $filter_company ][ 'company_name' ] );
		}

	}
	
	if ( $filter_search )
	{
		echo " (search: ".dPhtml( $filter_search ).")";
	}
	
	if ( $filter_search || $filter_company )
	{
		reset( $_GET );
		
		$params = $_GET;
		unset( $params[ "clearfilter" ] );
		$url = "?".http_build_query( $params );
		if ( $params ) $url .= "&";
		echo " - <a href='".dPhtml( $url."clearfilter=yes&" )."'>";
		echo $AppUI->_("clear filter");
		echo "</a>";
	}
	
	if ( $div_begun ) echo "</div>";
	
?>

