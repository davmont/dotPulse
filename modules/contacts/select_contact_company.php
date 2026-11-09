<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

	$table_name = dPgetCleanParam($_GET, "table_name", "companies");
	// Only the two tables below: the name goes into SQL and into the form action.
	if ($table_name != "departments") {
		$table_name = "companies";
	}

	switch($table_name) {
		case "companies":
			$company_id        = 0;
			$id_field          = "company_id";
			$name_field        = "company_name";
			$selection_string  = "Company";
			$filter            = null;
			$additional_get_information = "";
			break;
		case "departments":
			$company_id        = (int) dPgetCleanParam($_GET, 'company_id', 0);
			$id_field          = "dept_id";
			$name_field        = "dept_name";
			$selection_string  = "Department";
			$filter            = "dept_company = " . $company_id;
			$additional_get_information = "company_id=" . $company_id;
			break;
	}
	
	$q  = new DBQuery;
	$q->addTable($table_name);
	$q->addQuery("$id_field, $name_field");
	if ($filter != null) { $q->addWhere($filter); }
	$q->addOrder($name_field);
	// Only companies and departments the user may view (a department also needs its company).
	$perm_module = ($table_name == "departments") ? "departments" : "companies";
	if ($table_name == "departments" && $company_id && !getPermission('companies', 'view', $company_id)) {
		$q->addWhere('1 = 0');
	}
	$company_list = array("0" => "");
	foreach ($q->loadHashList() as $list_id => $list_name) {
		if (getPermission($perm_module, 'view', $list_id)) {
			$company_list[$list_id] = $list_name;
		}
	}

?>

<?php
	$posted_id = (int) dPgetCleanParam($_POST, $id_field, 0);
	if ($posted_id != 0 && !isset($company_list[$posted_id])) {
		$AppUI->redirect('m=public&a=access_denied');
	}
	if ($posted_id != 0) {
		$q  = new DBQuery;
		$q->addTable($table_name);
		$q->addQuery('*');
		$q->addWhere("$id_field=" . $posted_id);
		$sql = $q->prepare();
		$q->clear();
		db_loadHash($sql, $r_data);
		$data_update_script = "";
		$update_address     = isset($_POST["overwrite_address"]);
			
		if ($table_name == "companies") {
			$update_fields = array();
			if ($update_address) {
				$update_fields = array("company_address1" => "contact_address1",
				                       "company_address2" => "contact_address2",
				                       "company_city"     => "contact_city",
				                       "company_state"    => "contact_state",
				                       "company_zip"      => "contact_zip",
				                       "company_phone1"   => "contact_phone",
				                       "company_phone2"   => "contact_phone2",
				                       "company_fax"   => "contact_fax");
			}
			$data_update_script = "opener.setCompany('" . $posted_id . "', " . dPjs($r_data[$name_field]) . ");\n";
		} else if ($table_name == "departments") {
			$update_fields = array("dept_id"     => "contact_department");
			if ($update_address) {
				$update_fields = array("dept_address1" => "contact_address1",
				                       "dept_address2" => "contact_address2",
				                       "dept_city"     => "contact_city",
				                       "dept_state"    => "contact_state",
				                       "dept_zip"      => "contact_zip",
				                       "dept_phone"   => "contact_phone",
				                       "dept_fax"   => "contact_fax");
			}
			$data_update_script = "opener.setDepartment('" . $posted_id . "', " . dPjs($r_data[$name_field]) . ");\n";
		}
	
		// Let's figure out which fields are going to
		// be updated
		foreach ($update_fields as $record_field => $contact_field) {
			$data_update_script .= "opener.document.changecontact.$contact_field.value = " . dPjs($r_data[$record_field]) . ";\n";
		}
		?>
			<script language='javascript' type="text/javascript" nonce="<?php echo dPcspNonce(); ?>">
				<?php echo $data_update_script; ?>
				self.close();
			</script>
		<?php
	} else {
		?>
		
		<form name="frmSelector" action="?m=contacts&amp;a=select_contact_company&amp;dialog=1&amp;table_name=<?php echo $table_name."&amp;$additional_get_information"; ?>" method="post">
			<table cellspacing="0" cellpadding="3" border="0">
			<tr>
				<td colspan="2">
			<?php
				echo $AppUI->_('Select').' '.$AppUI->_($selection_string).':<br />';
				// The list takes the window height left by the buttons (themes differ in font size).
				echo arraySelect($company_list, $id_field, ' size="10" style="height: calc(100vh - 110px); min-height: 4em;"', $company_id);
			?>
				</td>
			</tr>
			<tr>
				<td>
					<input type="button" class="button" value="<?php echo $AppUI->_('cancel');?>"<?php echo dPaction('window.close'); ?> />
				</td>
				<td align="right">
					<input type="checkbox" name="overwrite_address" id="overwrite_address" /> <label for="overwrite_address"><?php echo $AppUI->_("Overwrite contact address information"); ?></label>
					<input type="submit" class="button" value="<?php echo $AppUI->_('Select', UI_CASE_LOWER);?>" />
				</td>
			</tr>
			</table>
		</form>
	<?php
	}
?>
