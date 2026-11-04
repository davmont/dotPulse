<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
require_once $AppUI->getSystemClass('date');
/* INVENTORY $id: inventory.class.php,v 1.00 2003/11/02 17:32:00 dylan_cuthbert Exp $ */

error_reporting( E_ALL );

require_once( $AppUI->getSystemClass ('dp' ) );


class CInventory extends CDpObject
{
	var $inventory_id = NULL;
	var $inventory_user = NULL;
	var $inventory_company = NULL;
	var $inventory_department = NULL;
	var $inventory_project = NULL;
	var $inventory_category = NULL;
	var $inventory_brand = NULL;
	var $inventory_purchased = NULL;
	var $inventory_cost = NULL;
	var $inventory_rental_period = NULL;
	var $inventory_name = NULL;
	var $inventory_description = NULL;
	var $inventory_serial = NULL;
	var $inventory_asset_no = NULL;
	var $inventory_costcode = NULL;
	var $inventory_parent = NULL;
	var $inventory_assign_from = NULL;
	var $inventory_assign_until = NULL;
// v0.2
	var $inventory_purchase_state = NULL;
	var $inventory_purchase_company = NULL;
	var $inventory_delivered = NULL;
// v0.3
	var $inventory_quantity = NULL;

	function __construct()
	{
		parent::__construct( 'inventory', 'inventory_id' );
	}

	function check()
	{
		if ( !isset( $this->inventory_cost) )
		{
			$this->inventory_cost = 0;
		}
		return NULL;
	}

// calculates the price of this object *and* all its children
// you can pass in the globals item_list and item_list_parents (created by load_all_items
// in utility.php to speed up the process

	function calcChildrenTotal( $idx = 0, $item_list = NULL, $item_list_parents = NULL )
	{
		$total = 0;

		if ( !$idx ) $idx = $this->inventory_id;

// do we have a cache of items?

		if ( count( $item_list ) )
		{
			if ( isset( $item_list_parents[ $idx ] ) )
			{
				reset( $item_list_parents[ $idx ] );
				foreach ( $item_list_parents[ $idx ] as $id )
				{
					$total += $item_list[ $id ][ 'inventory_cost' ];
					$total += $this->calcChildrenTotal( $id, $item_list, $item_list_parents );
				}
			}
		}
		else
		{
			$sql = new DBQuery();
			$sql->addTable('inventory');
			$sql->addQuery('inventory_id,inventory_cost');
			$sql->addWhere("inventory_parent=$idx");

			$child_list = $sql->loadList();

                       $sql->clear();
			foreach ( $child_list as $child )
			{
				$total += $child[ 'inventory_cost' ];
				$total += $this->calcChildrenTotal( $child[ 'inventory_id' ] );
			}
		}

		global $drawn_array;

		$drawn_array[ $idx ] = true;

		return $total;
	}

	function getAssetNo()
	{
		if ( isset( $this->inventory_asset_no ) && $this->inventory_asset_no )
		{
			return $this->inventory_asset_no;
		}
		return sprintf( "%06d", $this->inventory_id );
	}

// if "delete_children" is true then all children are also deleted

	function delete($idx = null, $delete_children = 0, $history_proj = 0)
	{

               $sql = new DBQuery();
               $sql->addTable('inventory');
               $sql->addQuery('inventory_id');
               $sql->addWhere("inventory_parent=$idx");

               $child_list = $sql->loadList();

		foreach ( $child_list as $row )
		{
			if ( $delete_children )	// recursive delete of children
			{
				$this->delete( $row[ "inventory_id" ], $delete_children );
			}
			else
			{
				$child = new CInventory();
				if ( $child->load( $row[ "inventory_id" ] ) )
				{
					$child->inventory_parent = 0;
					$child->store();
				}
			}
		}
		parent::delete( $idx );
	}
}


class CInventoryCategory extends CDpObject
{
	var $inventory_category_id = NULL;
	var $inventory_category_name = NULL;

	function __construct()
	{
		parent::__construct( 'inventory_categories', 'inventory_category_id' );
	}

	function check()
	{
		return NULL;
	}
}


class CInventoryBrand extends CDpObject
{
	var $inventory_brand_id = NULL;
	var $inventory_brand_name = NULL;

	function __construct()
	{
		parent::__construct( 'inventory_brands', 'inventory_brand_id' );
	}

	function check()
	{
		return NULL;
	}
}



?>
