<?php  /* earnings $Id: index.php,v 1.2 2004/09/13 02:23:00 stradius Exp $ */
$AppUI->savePlace();

// retrieve any state parameters
// Such as which tab was last selected and which columns have been selected for sorting the output.
if (isset($_GET['tab'])) {
	$AppUI->setState('EarnIdxTab', $_GET['tab']);
}
$tab = $AppUI->getState('EarnIdxTab') !== NULL ? $AppUI->getState('EarnIdxTab') : 0;
$active = intval(!$AppUI->getState('EarnIdxTab'));

if (isset($_POST['company_id'])) {
	$AppUI->setState('EarnIdxCompany', $_POST['company_id']);
}
$company_id = $AppUI->getState('EarnIdxCompany') !== NULL ? $AppUI->getState('EarnIdxCompany') : $AppUI->user_company;

if (isset($_GET['orderby'])) {
	$AppUI->setState('EarnIdxOrderBy', dPvalidateOrder($_GET['orderby'], array('earning_date',
		'earning_num', 'company_name', 'earning_submit_contact', 'earning_terms', 'user_id'), 'earning_date'));
}
$orderby = $AppUI->getState('EarnIdxOrderBy') ? $AppUI->getState('EarnIdxOrderBy') : 'earning_date';
if ($orderby == "earning_date") {
	$orderby = "earning_date DESC";
}
// Only the "to approve" list joins users; the earnings table has the user as earning_user_id.
if ($orderby == "user_id") {
	$orderby = "earning_user_id";
}

// retrieve list of earnings and their line_items
// the tab selected tells which query to use
$tab = (int)$tab;
// The manager lists (all users' earnings) are for approvers only, as inv_aed.php's guard:
// user types 1 to 6. Timecard users (type 7) have no "paid" list.
$earn_manager = ($AppUI->user_type >= 1 && $AppUI->user_type <= 6);
if ($tab < 0 || ($tab >= 4 && !$earn_manager) || ($tab == 3 && $AppUI->user_type == 7) || $tab > 6) {
	$tab = 0;
}

switch ($tab) {
	case 0:
		// Preparing/New earnings
		$sql = "SELECT earnings.*, companies.company_name FROM " . dPgetConfig('dbprefix') . "earnings AS earnings, " . dPgetConfig('dbprefix') . "companies AS companies WHERE earning_submitted = '0000-00-00 00:00:00' AND earning_submit_company_id = company_id AND earning_user_id=" . $AppUI->user_id . " ORDER BY " . $orderby . ";";
		break;
	case 1:
		// Submitted earnings
		$sql = "SELECT earnings.*, companies.company_name FROM " . dPgetConfig('dbprefix') . "earnings AS earnings, " . dPgetConfig('dbprefix') . "companies AS companies WHERE earning_submitted != '0000-00-00 00:00:00' AND earning_approved = '0000-00-00 00:00:00' AND earning_submit_company_id = company_id AND earning_user_id=" . $AppUI->user_id . " ORDER BY " . $orderby . ";";
		break;
	case 2:
		// Approved earnings
		$sql = "SELECT earnings.*, companies.company_name FROM " . dPgetConfig('dbprefix') . "earnings AS earnings, " . dPgetConfig('dbprefix') . "companies AS companies WHERE earning_approved != '0000-00-00 00:00:00' AND earning_paid = '0000-00-00 00:00:00' AND earning_submit_company_id = company_id AND earning_user_id=" . $AppUI->user_id . " ORDER BY " . $orderby . ";";
		break;
	case 3:
		// Paid earnings
		$sql = "SELECT earnings.*, companies.company_name FROM " . dPgetConfig('dbprefix') . "earnings AS earnings, " . dPgetConfig('dbprefix') . "companies AS companies WHERE earning_paid != '0000-00-00 00:00:00' AND earning_submit_company_id = company_id AND earning_user_id=" . $AppUI->user_id . " ORDER BY " . $orderby . ";";
		break;
	case 4:
		// Awaiting Approval
		$sql = "SELECT earnings.*, companies.company_name, contacts.contact_first_name as user_first_name, contacts.contact_last_name as user_last_name FROM " . dPgetConfig('dbprefix') . "earnings AS earnings, " . dPgetConfig('dbprefix') . "companies AS companies, " . dPgetConfig('dbprefix') . "users AS users, " . dPgetConfig('dbprefix') . "contacts AS contacts WHERE earning_approved = '0000-00-00 00:00:00' AND earning_submitted != '0000-00-00 00:00:00' AND earning_submit_company_id = company_id AND earning_user_id = user_id AND users.user_contact = contacts.contact_id" . " ORDER BY " . $orderby . ";";
		break;
	case 5:
		// Items User Approved
		$sql = "SELECT earnings.*, companies.company_name, contacts.contact_first_name as user_first_name, contacts.contact_last_name as user_last_name FROM " . dPgetConfig('dbprefix') . "earnings AS earnings, " . dPgetConfig('dbprefix') . "companies AS companies, " . dPgetConfig('dbprefix') . "users AS users, " . dPgetConfig('dbprefix') . "contacts AS contacts WHERE earning_approved != '0000-00-00 00:00:00' AND earning_submitted != '0000-00-00 00:00:00' AND earning_submit_company_id = company_id AND earning_user_id = user_id AND users.user_contact = contacts.contact_id" . " AND earning_approved_by = '" . $AppUI->user_id . "' ORDER BY " . $orderby . ";";
		break;
	case 6:
		// Approved History
		$sql = "SELECT earnings.*, companies.company_name, contacts.contact_first_name as user_first_name, contacts.contact_last_name as user_last_name FROM " . dPgetConfig('dbprefix') . "earnings AS earnings, " . dPgetConfig('dbprefix') . "companies AS companies, " . dPgetConfig('dbprefix') . "users AS users, " . dPgetConfig('dbprefix') . "contacts AS contacts WHERE earning_approved != '0000-00-00 00:00:00' AND earning_submitted != '0000-00-00 00:00:00' AND earning_submit_company_id = company_id AND earning_user_id = user_id AND users.user_contact = contacts.contact_id" . " ORDER BY " . $orderby . ";";
		break;
}
$earnings = db_loadList($sql);

// setup the title block
// customize to user-type
//$titleBlock->addCrumb( "?m=tasks&a=todo", "my todo" );
if ($AppUI->user_type != 7) {
	$titleBlock = new CTitleBlock('Earnings', 'earnings.gif', $m, "$m.$a");
	if ($canEdit) {
		$titleBlock->addCell(
			'<input type="submit" class="button" value="' . $AppUI->_('new invoice') . '">',
			'',
			'<form action="?m=earnings&a=addedit" method="post">',
			'</form>'
		);
	}
} else {
	$titleBlock = new CTitleBlock('Timecards', 'earnings.gif', $m, "$m.$a");
	if ($canEdit) {
		$titleBlock->addCell(
			'<input type="submit" class="button" value="' . $AppUI->_('new timecard') . '">',
			'',
			'<form action="?m=earnings&a=addedit" method="post">',
			'</form>'
		);
	}
}
$titleBlock->show();

// tabbed information boxes
$tabBox = new CTabBox("?m=earnings&orderby=$orderby", DP_BASE_DIR . "/modules/earnings/", $tab);
$tabBox->add('vw_idx', $AppUI->_('New/Edit'));
$tabBox->add('vw_idx', $AppUI->_('Awaiting Approval'));
$tabBox->add('vw_idx', $AppUI->_('Approved'));
if ($AppUI->user_type != 7) {
	$tabBox->add('vw_idx', $AppUI->_('Paid'));
}
if ($earn_manager) {
	$tabBox->add('vw_idx', $AppUI->_('MGR: To Approve'));
	$tabBox->add('vw_idx', $AppUI->_('MGR: I Approved'));
	$tabBox->add('vw_idx', $AppUI->_('MGR: All Approved'));
}

//var_dump($AppUI);

$tabBox->show();

?>