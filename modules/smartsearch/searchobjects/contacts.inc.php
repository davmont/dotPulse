<?php /* SMARTSEARCH$Id: contacts.inc.php 6038 2010-10-03 05:49:01Z ajdonnison $ */
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

/**
* contacts Class
*/
class contacts extends smartsearch 
{
	var $table = 'contacts';
	var $table_module = 'contacts';
	var $table_key = 'contact_id';
	var $table_link = '?m=contacts&amp;a=view&amp;contact_id=';
	var $table_title = 'Contacts';
	var $table_orderby = 'contact_last_name,contact_first_name';
	var $search_fields = array('contact_last_name', 'contact_first_name', 'contact_title', 
	                           'contact_company', 'contact_type', 'contact_email', 
	                           'contact_email2', 'contact_address1', 'contact_address2', 
	                           'contact_city', 'contact_state', 'contact_zip', 'contact_country', 
	                           'contact_notes');
	var $display_fields = array('contact_last_name', 'contact_first_name', 'contact_title', 
	                            'contact_company', 'contact_type', 'contact_email', 
	                            'contact_email2', 'contact_address1', 'contact_address2', 
	                            'contact_city', 'contact_state', 'contact_zip', 'contact_country', 
	                            'contact_notes');
	
	// Only the contacts the contacts list would show this user (private ones to their owner).
	function __construct() {
		global $AppUI;
		if (is_callable('parent::__construct')) {
			parent::__construct();
		}
		require_once $AppUI->getModuleClass('contacts');
		$this->table_extra = CContact::visibleWhere($AppUI->user_id);
	}

	function ccontacts() {
		return new contacts();
	}
}
?>
