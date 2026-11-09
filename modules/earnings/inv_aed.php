<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* earnings $Id: inv_aed.php,v 1.2 2004/09/13 02:23:00 stradius Exp $ */
$earning_id = intval( dPgetParam( $_GET, "earning_id", 0 ) );
$dbprefix = dPgetConfig('dbprefix', '');

require_once( $AppUI->getSystemClass( 'libmail' ) );

// Respond To inv_dosql Commands in _POST
if (isset( $_POST['inv_dosql'] ) ) {
	// Every command changes data, and this view is not named do_*, so index.php's
	// write-handler check does not apply: token and edit permission here.
	$AppUI->verifyCsrfToken();
	if (!getPermission('earnings', 'edit')) {
		$AppUI->redirect('m=public&a=access_denied');
	}

	// Each command acts on one earning (the URL's; an ad hoc item names its own) and is
	// allowed by who the user is to it, as the module's pages offer the forms: its owner
	// edits and submits it until it is approved; an approver (user type below 7) decides
	// on others' submitted earnings; the owner or an approver marks an approved one paid.
	// Module edit permission was the only check: anyone could approve their own earning.
	$inv_cmd = (string)dPgetParam($_POST, 'inv_dosql', '');
	if ($inv_cmd == 'adhoc') {
		$earning_id = (int)dPgetParam($_POST, 'adhoc_earning_parent_id', 0);
	}
	$is_approver = ($AppUI->user_type > 0 && $AppUI->user_type < 7);
	$earning_row = null;
	if ($earning_id) {
		$q = new DBQuery();
		$q->addTable('earnings');
		$q->addQuery('earning_user_id, earning_submitted, earning_approved, earning_paid'
			. ', earning_submit_contact, earning_submit_email');
		$q->addWhere('earning_id = ' . (int)$earning_id);
		$earning_row = $q->loadHash();
	}
	$set = function ($date) {
		return $date && $date != '0000-00-00 00:00:00';
	};
	if ($inv_cmd == 'addinv') {
		$allowed = true;
	} else if (!$earning_row) {
		$allowed = false;
	} else {
		$own = ((int)$earning_row['earning_user_id'] == (int)$AppUI->user_id);
		$approved = $set($earning_row['earning_approved']);
		switch ($inv_cmd) {
			case 'postsubmit':
				$allowed = $own && !$approved;
				break;
			case 'postapprove':
			case 'postdecline':
				$allowed = $is_approver && !$own && $set($earning_row['earning_submitted']) && !$approved;
				break;
			case 'postpaid':
				$allowed = ($own || $is_approver) && $approved && !$set($earning_row['earning_paid']);
				break;
			case 'updrates':
				$allowed = ($own || $is_approver) && !$approved;
				break;
			default: // adhoc, remitem, additem, editinv, killearning
				$allowed = $own && !$approved;
		}
	}
	if (!$allowed) {
		$AppUI->setMsg('Access denied.', UI_MSG_ERROR);
		$AppUI->redirect('m=public&a=access_denied');
	}
	// Figure out what kind

	// Add AdHoc Item
	if ($_POST['inv_dosql'] == "adhoc") {
		// Add items from AdHoc Form To earning
		$sql="INSERT INTO {$dbprefix}earnings_items (earning_parent_id, earning_tasklog_id, earning_item_date, earning_item_description, earning_item_hours,earning_item_costcode,earning_item_rate) VALUES (";
		// earning_parent_id
		$sql .= "'" . (int)$_POST["adhoc_earning_parent_id"] . "',";
		// earning_tasklog_id (n/a)
		$sql .= "'" . (int)$_POST["adhoc_earning_tasklog_id"] . "',";
		// earning_item_date (formatted for MySQL)
		$sql .= "'" . db_escape($_POST["adhoc_earning_item_date"]) . "',";
		// earning_item_description
		$sql .= "'" . db_escape($_POST["adhoc_earning_item_description"]) . "',";
		// earning_item_hours
		$sql .= (float)$_POST["adhoc_earning_item_hours"] . ",";
		// earning_item_costcode
		$sql .= "'n/a',";
		// earning_item_rate
		$sql .= (float)$_POST["adhoc_earning_item_rate"];
		// end sql statement.
		$sql .= ");";
		if (!db_exec( $sql )) {
		}
		$AppUI->redirect();
	}

	// Remove Items That Were Checked
	if ($_POST['inv_dosql'] == "remitem") {
		// Remove earning Item(s) From earning
		$remove_items = "";
		foreach ($_POST as $item) {
			@list($item_action,$item_value) = explode(":",(string)$item);
			if ( $item_action == "remove" ) {
				if ( intval($item_value) > 0 ) {
					if ( strlen($remove_items) > 0 ) {
						$remove_items .= ",";
					}
					$remove_items .= (int)$item_value;
				}
			}
		}
		$sql = "DELETE FROM {$dbprefix}earnings_items WHERE earning_items_id IN (";
		$sql .= ($remove_items !== '' ? $remove_items : '0') . ") AND earning_parent_id = " . (int)$earning_id . ";";
		if (!db_exec( $sql )) {
		}
		$AppUI->redirect();
	}

	// Update Rate Changes On Items
	if ($_POST['inv_dosql'] == "updrates") {
		$cases = array();
		$ids = array();
		foreach ($_POST as $key => $value) {
			$parts = explode(":", $key);
			if (count($parts) < 2) continue;
			$item_action = $parts[0];
			$item_rec = $parts[1];

			if ( $item_action == "rate" ) {
				$cases[] = "WHEN " . (int)$item_rec . " THEN " . (float)$value;
				$ids[] = (int)$item_rec;
			}
		}

		if (count($ids) > 0) {
			$sql = "UPDATE {$dbprefix}earnings_items SET earning_item_rate = CASE earning_items_id ";
			$sql .= implode(" ", $cases);
			$sql .= " END WHERE earning_items_id IN (" . implode(",", $ids) . ") AND earning_parent_id = " . (int)$earning_id;
			if (!db_exec( $sql )) {
			}
		}
		$AppUI->redirect();
	}


	// Add Items That Were Checked
	if ($_POST['inv_dosql'] == "additem") {
		$add_items = "";
		foreach ($_POST as $item) {
			@list($item_action,$item_value) = explode(":",(string)$item);
			if ( $item_action == "add" ) {
				if ( intval($item_value) > 0 ) {
					if ( strlen($add_items) > 0 ) {
						$add_items .= ",";
					}
					$add_items .= (int)$item_value;
				}
			}
		}
		// First gather all the selected Task_log Items
		$sql="SELECT task_log.*, tasks.*, projects.* FROM {$dbprefix}task_log AS task_log, {$dbprefix}tasks AS tasks, {$dbprefix}projects AS projects WHERE task_id = task_log_task AND project_id = task_project AND task_log_id IN (";
		// Only the user's own task logs can be billed.
		$sql .= ($add_items !== '' ? $add_items : '0') . ") AND task_log_creator = " . (int)$AppUI->user_id . ";";
		$arc= db_exec( $sql );
		$add_records = array();
		while ($row = db_fetch_assoc($arc)) {
			$add_records[$row["task_log_id"]] = $row;
		}
		foreach($add_records as $x) {
			$sql="INSERT INTO {$dbprefix}earnings_items (earning_parent_id, earning_tasklog_id, earning_item_date, earning_item_description, earning_item_hours,earning_item_costcode,earning_item_rate) VALUES (";
			// earning_parent_id
			$sql .= "'" . $earning_id . "',";
			// earning_tasklog_id (n/a)
			$sql .= "'" . $x["task_log_id"] . "',";
			// earning_item_date (formatted for MySQL)
			$sql .= "'" . $x["task_log_date"] . "',";
			// earning_item_description
			$sql .= "'" . db_escape($x["task_log_description"]) . "',";
			// earning_item_hours
			$sql .= $x["task_log_hours"] . ",";
			// earning_item_costcode
			$sql .= "'" . db_escape($x["task_log_costcode"]) . "',";
			// earning_item_rate
			$sql .= '0';
			// end sql statement.
			$sql .= ");";
			if (!db_exec( $sql )) {
			}
		}
		$relocate = "m=earnings&a=view&earning_id=" . $earning_id . "&tab=0";
		$AppUI->redirect($relocate);
	}

	// Kill An earning and All Its Supporting earning Items
	if ($_POST['inv_dosql'] == "killearning") {
		// Confirmation Has Already Been Received
		// Delete All Supporting earning_Items First
		$sql = "DELETE FROM {$dbprefix}earnings_items WHERE earning_parent_id = " . $earning_id . ";";
		if (!db_exec( $sql )) {
		}

		// Then Delete earning Itself
		$sql = "DELETE FROM {$dbprefix}earnings WHERE earning_id = " . $earning_id . ";";
		//echo $sql;
		if (!db_exec( $sql )) {
		}

		// Then redirect application back to earnings Main Module
		$relocate = "m=earnings";
		$AppUI->redirect($relocate);

	}	

	// Update An Existing earning
	if ($_POST['inv_dosql'] == "editinv") {
		if (db_loadResult("SELECT COUNT(*) FROM {$dbprefix}earnings WHERE earning_num = '" . db_escape(dPgetCleanParam($_POST, 'earning_num', '')) . "' AND earning_id <> " . (int)$earning_id)) {
			$AppUI->setMsg('Error: Duplicate earning Number?', UI_MSG_ERROR);
			$AppUI->redirect();
		}
		// Create SQL UPDATE command from POST variables
		$sql = "UPDATE {$dbprefix}earnings SET";
		$sql .= " earning_date='" . db_escape($_POST["earning_date"]) . "',";
		$sql .= " earning_num='" . db_escape($_POST["earning_num"]) . "',";
		$sql .= " earning_submit_contact='" . db_escape($_POST["earning_submit_contact"]) . "',";
		$sql .= " earning_submit_company_id='" . (int)$_POST["earning_submit_company_id"] . "',";
		$sql .= " earning_submit_email='" . db_escape($_POST["earning_submit_email"]) . "',";
		$sql .= " earning_terms='" . db_escape($_POST["earning_terms"]) . "',";
		$sql .= " earning_comments='" . db_escape($_POST["earning_comments"]) . "',";
		$sql .= " earning_title='" . db_escape($_POST["earning_title"]) . "',";
		if ( strcmp($_POST["earning_submit_address1"], "") == 0 ) {
			// The earning address override hasn't been used so let's fill it in automatically
			// Gather Company Details
			$sql2="SELECT companies.* FROM {$dbprefix}companies AS companies WHERE company_id='" . (int)$_POST["earning_submit_company_id"] . "';";
			$company_rec = array();
			db_loadHash($sql2, $company_rec);
			$sql .= " earning_submit_address1='" . db_escape(isset($company_rec["company_address1"]) ? $company_rec["company_address1"] : "") . "',";
			$sql .= " earning_submit_address2='" . db_escape(isset($company_rec["company_address2"]) ? $company_rec["company_address2"] : "") . "',";
			$sql .= " earning_submit_city='" . db_escape(isset($company_rec["company_city"]) ? $company_rec["company_city"] : "") . "',";
			$sql .= " earning_submit_state='" . db_escape(isset($company_rec["company_state"]) ? $company_rec["company_state"] : "") . "',";
			$sql .= " earning_submit_zip='" . db_escape(isset($company_rec["company_zip"]) ? $company_rec["company_zip"] : "") . "',";
			$sql .= " earning_submit_phone='" . db_escape(isset($company_rec["company_phone1"]) ? $company_rec["company_phone1"] : "") . "'";
		} else {
			$sql .= " earning_submit_address1='" . db_escape($_POST["earning_submit_address1"]) . "',";
			$sql .= " earning_submit_address2='" . db_escape($_POST["earning_submit_address2"]) . "',";
			$sql .= " earning_submit_city='" . db_escape($_POST["earning_submit_city"]) . "',";
			$sql .= " earning_submit_state='" . db_escape($_POST["earning_submit_state"]) . "',";
			$sql .= " earning_submit_zip='" . db_escape($_POST["earning_submit_zip"]) . "',";
			$sql .= " earning_submit_phone='" . db_escape($_POST["earning_submit_phone"]) . "'";
		}
		$sql .= " WHERE earning_id=" . $earning_id . ";";
		if (!db_exec( $sql )) {
			$AppUI->setMsg( "Error: Duplicate earning Number?", UI_MSG_ERROR );
			$AppUI->redirect();
		}

		// Then redirect application back to earnings Main Module
		$relocate = "m=earnings&a=view&earning_id=" . $earning_id;
		$AppUI->redirect($relocate);
	}

	// Add A New earning
	if ($_POST['inv_dosql'] == "addinv") {
		// earning_num is unique: say so instead of letting the INSERT fail.
		if (db_loadResult("SELECT COUNT(*) FROM {$dbprefix}earnings WHERE earning_num = '" . db_escape(dPgetCleanParam($_POST, 'earning_num', '')) . "'")) {
			$AppUI->setMsg('Error: Duplicate earning Number?', UI_MSG_ERROR);
			$AppUI->redirect();
		}
		// Create SQL INSERT command from POST variables
		$sql = "INSERT INTO {$dbprefix}earnings (earning_user_id, earning_date, earning_num, earning_submit_contact, earning_submit_company_id,";
		$sql .= "earning_submit_email, earning_terms, earning_comments, earning_title, earning_submit_address1, earning_submit_address2,";
		$sql .= "earning_submit_city, earning_submit_state, earning_submit_zip, earning_submit_phone";
		$sql .= ") VALUES ( ";
		$sql .= "'" .$AppUI->user_id . "',";
		$sql .= "'" .db_escape($_POST["earning_date"]) . "',";
		$sql .= "'" . db_escape($_POST["earning_num"]) . "',";
		$sql .= "'" . db_escape($_POST["earning_submit_contact"]) . "',";
		$sql .= "'" . (int)$_POST["earning_submit_company_id"] . "',";
		$sql .= "'" . db_escape($_POST["earning_submit_email"]) . "',";
		$sql .= "'" . db_escape($_POST["earning_terms"]) . "',";
		$sql .= "'" . db_escape($_POST["earning_comments"]) . "',";
		$sql .= "'" . db_escape($_POST["earning_title"]) . "',";
		if ( strcmp($_POST["earning_submit_address1"], "") == 0 ) {
			// The earning address override hasn't been used so let's fill it in automatically
			// Gather Company Details
			$sql2="SELECT companies.* FROM {$dbprefix}companies AS companies WHERE company_id='" . (int)$_POST["earning_submit_company_id"] . "';";
			$company_rec = array();
			db_loadHash($sql2, $company_rec);
			$sql .= "'" . db_escape(isset($company_rec["company_address1"]) ? $company_rec["company_address1"] : "") . "',";
			$sql .= "'" . db_escape(isset($company_rec["company_address2"]) ? $company_rec["company_address2"] : "") . "',";
			$sql .= "'" . db_escape(isset($company_rec["company_city"]) ? $company_rec["company_city"] : "") . "',";
			$sql .= "'" . db_escape(isset($company_rec["company_state"]) ? $company_rec["company_state"] : "") . "',";
			$sql .= "'" . db_escape(isset($company_rec["company_zip"]) ? $company_rec["company_zip"] : "") . "',";
			$sql .= "'" . db_escape(isset($company_rec["company_phone1"]) ? $company_rec["company_phone1"] : "") . "'";
		} else {
			$sql .= "'" . db_escape($_POST["earning_submit_address1"]) . "',";
			$sql .= "'" . db_escape($_POST["earning_submit_address2"]) . "',";
			$sql .= "'" . db_escape($_POST["earning_submit_city"]) . "',";
			$sql .= "'" . db_escape($_POST["earning_submit_state"]) . "',";
			$sql .= "'" . db_escape($_POST["earning_submit_zip"]) . "',";
			$sql .= "'" . db_escape($_POST["earning_submit_phone"]) . "'";
		}
		$sql .= ");";
		if (!db_exec( $sql )) {
		}

		// Then redirect application back to earnings Main Module
		$relocate = "m=earnings";
		$AppUI->redirect($relocate);
	}

	// Processing Activity Updates
	if ( ($_POST['inv_dosql'] == "postsubmit") || ($_POST['inv_dosql'] == "postapprove") || ($_POST['inv_dosql'] == "postdecline") || ($_POST['inv_dosql'] == "postpaid") ) {
		$notifyMail = new Mail;
		// The owner's name and e-mail come from the database, not the request: the
		// request named any address for the system to write to.
		$q = new DBQuery();
		$q->addTable('users', 'u');
		$q->leftJoin('contacts', 'c', 'c.contact_id = u.user_contact');
		$q->addQuery("CONCAT_WS(' ', c.contact_first_name, c.contact_last_name) AS name, c.contact_email AS email");
		$q->addWhere('u.user_id = ' . (int)$earning_row['earning_user_id']);
		$owner = $q->loadHash();
		$owner_name = $owner ? $owner['name'] : '';
		$owner_email = $owner ? $owner['email'] : '';
		switch ($_POST['inv_dosql']) {
			case "postsubmit":
				$sql="UPDATE {$dbprefix}earnings SET earning_submitted_comment='" . db_escape($_POST["earning_submitted_comment"]) . "', earning_submitted='" . date("Ymd") . "', earning_approved='', earning_approved_comment='' WHERE earning_id=" . $earning_id . ";";
				$notifyMail->From( $AppUI->user_first_name . " " . $AppUI->user_last_name . " <" . $AppUI->user_email . ">" );
				$notifyMail->To( $earning_row['earning_submit_contact'] . " <" . $earning_row['earning_submit_email'] . ">" );
				$notifyMail->Subject( "Approval Requested from " . $AppUI->user_first_name . " " . $AppUI->user_last_name . "." );
				$message = "A pay request (timecard or invoice) has been placed into the queue for your approval.  A synopsis follows:\n\n";
				$message .= "Request Number: " . $_POST['earning_num'] . "\n";
				$message .= "Title: " . stripslashes($_POST['earning_title']) . "\n";
				$message .= "Comments: " . stripslashes($_POST['earning_comments']) . "\n";
				$message .= "Submittal Comments: " . stripslashes($_POST['earning_submitted_comment']) . "\n\n";
				$message .= "You can review and process this request by clicking the following link:\n";
				$message .= "\t" . $dPconfig['base_url'] . "/index.php?m=earnings&a=view&earning_id=" . $_POST['earning_id'] . "\n\n";
				$message .= "Thanks.\n\n";
				$notifyMail->Body( $message );
				//$notifyMail->Cc("");
				//$notifyMail->Bcc("");
				$notifyMail->Priority(3);
				break;
			case "postapprove":
				$sql="UPDATE {$dbprefix}earnings SET earning_approved_comment='" . db_escape($_POST["earning_approved_comment"]) . "', earning_approved='" . date("Ymd") . "', earning_approved_by=" . $AppUI->user_id . " WHERE earning_id=" . $earning_id . ";";
				$notifyMail->From( $AppUI->user_first_name . " " . $AppUI->user_last_name . " <" . $AppUI->user_email . ">" );
				$notifyMail->To( $owner_name . " <" . $owner_email . ">" );
				$notifyMail->Subject( "Approval Received from " . $AppUI->user_first_name . " " . $AppUI->user_last_name . "." );
				$message = "Your pay request (timecard or invoice) was approved.  A synopsis follows:\n\n";
				$message .= "Request Number: " . $_POST['earning_num'] . "\n";
				$message .= "Title: " . stripslashes($_POST['earning_title']) . "\n";
				$message .= "Comments: " . stripslashes($_POST['earning_comments']) . "\n";
				$message .= "Submittal Comments: " . stripslashes($_POST['earning_submitted_comment']) . "\n";
				$message .= "Approval Comments: " . stripslashes($_POST['earning_approved_comment']) . "\n\n";
				$message .= "You can review the pay request by clicking the following link:\n";
				$message .= "\t" . $dPconfig['base_url'] . "/index.php?m=earnings&a=view&earning_id=" . $_POST['earning_id'] . "\n\n";
				$message .= "Thanks.\n\n";
				$notifyMail->Body( $message );
				//$notifyMail->Cc("");
				//$notifyMail->Bcc("");
				$notifyMail->Priority(3);
				break;
			case "postpaid":
				$sql="UPDATE {$dbprefix}earnings SET earning_paid_comment='" . db_escape($_POST["earning_paid_comment"]) . "', earning_paid='" . date("Ymd") . "' WHERE earning_id=" . $earning_id . ";";
				break;
			case "postdecline":
				$sql="UPDATE {$dbprefix}earnings SET earning_approved_comment='" . db_escape($_POST["earning_approved_comment"]) . "', earning_submitted='0000-00-00 00:00:00' WHERE earning_id=" . $earning_id . ";";
				$notifyMail->From( $AppUI->user_first_name . " " . $AppUI->user_last_name . " <" . $AppUI->user_email . ">" );
				$notifyMail->To( $owner_name . " <" . $owner_email . ">" );
				$notifyMail->Subject( "Request Declined from " . $AppUI->user_first_name . " " . $AppUI->user_last_name . "." );
				$message = "Your pay request (timecard or invoice) was declined.  A synopsis follows:\n\n";
				$message .= "Request Number: " . $_POST['earning_num'] . "\n";
				$message .= "Title: " . stripslashes($_POST['earning_title']) . "\n";
				$message .= "Comments: " . stripslashes($_POST['earning_comments']) . "\n";
				$message .= "Submittal Comments: " . stripslashes($_POST['earning_submitted_comment']) . "\n";
				$message .= "Approval Comments: " . stripslashes($_POST['earning_approved_comment']) . "\n\n";
				$message .= "You can review and/or resubmit the pay request by clicking the following link:\n";
				$message .= "\t" . $dPconfig['base_url'] . "/index.php?m=earnings&a=view&earning_id=" . $_POST['earning_id'] . "\n\n";
				$message .= "Thanks.\n\n";
				$notifyMail->Body( $message );
				//$notifyMail->Cc("");
				//$notifyMail->Bcc("");
				$notifyMail->Priority(3);
				break;
		}
		//echo $sql;
		if (!db_exec( $sql )) {
		}
		$notifyMail->Send();

		// Then redirect application back to earnings Main Module
		$relocate = "m=earnings";
		$AppUI->redirect($relocate);
	}

}



