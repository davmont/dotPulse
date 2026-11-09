<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

	/*
	 *	do_custom_field_aed.php
	 *
	 */
	require_once($AppUI->getSystemClass('CustomFields'));

	$edit_field_id = dpGetParam($_POST, "field_id", NULL);

	if ($edit_field_id != NULL)
	{
		$edit_field_id = (int)$edit_field_id;
		$edit_module = (string)dpGetParam($_POST, "module", '');
		// Custom fields belong to an installed module: accept only a module name.
		$q = new DBQuery();
		$q->addTable('modules');
		$q->addQuery('mod_name');
		$q->addWhere('LOWER(mod_name) = ?', mb_strtolower($edit_module));
		if (!$q->loadResult()) {
			$AppUI->setMsg('Module', UI_MSG_ERROR);
			$AppUI->setMsg('invalidID', UI_MSG_ERROR, true);
			$AppUI->redirect('m=system&a=custom_field_editor');
		}
		$q->clear();
		$field_name = dpGetParam($_POST, "field_name", NULL);
		$field_description = db_escape(dpGetParam($_POST, "field_description", NULL));
		$field_htmltype = dpGetParam($_POST, "field_htmltype", NULL);
		$field_datatype = dpGetParam($_POST, "field_datatype", "alpha");
		$field_extratags = db_escape(dpGetParam($_POST, "field_extratags", NULL));
		$field_order = dpGetParam($_POST, "field_order", 0);

		$list_select_items = dpGetParam($_POST, "select_items", NULL);

		$custom_fields = New CustomFields(mb_strtolower($edit_module), 'addedit', null, null);


		if ($edit_field_id == 0)
		{
			$fid = $custom_fields->add($field_name, $field_description, $field_htmltype, $field_datatype, $field_extratags, $msg, $field_order);
		}
		else
		{
			$fid = $custom_fields->update($edit_field_id, $field_name, $field_description, $field_htmltype, $field_datatype, $field_extratags, $msg, $field_order);
		}
	
		// Add or Update a Custom Field
		if ($msg)
		{
			$AppUI->setMsg($AppUI->_('Error adding custom field:').$msg, UI_MSG_ALERT, true);
		}
		else
		{
			if ($field_htmltype == "select")
			{
				$opts = New CustomOptionList($fid);
				$opts->setOptions($list_select_items);

				if ($edit_field_id == 0)
				{
					$o_msg = $opts->store();
				}
				else
				{
					// To update each list would be a lot more complex than rewriting it
					// So it is, but it is needed in order for it to work properly. (Pedro A. Bug 1163)
					$o_msg = $opts->store();
				}

				if ($o_msg)
				{
					// Select List Failed - Delete CustomField also
				}
	
			}	

			$AppUI->setMsg($AppUI->_('Custom field added successfully'), UI_MSG_OK, true);
		}
	}
?>
