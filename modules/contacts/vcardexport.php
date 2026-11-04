<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// get GETPARAMETER for contact_id
$contact_id = intval($_GET['contact_id']);

$canRead = getPermission('contacts', 'view', $contact_id);
if (!$canRead) {
	$AppUI->redirect("m=public&a=access_denied");
}

if (isset($_GET['contact_id']) && !($_GET['contact_id']=='')) {

	//pull data for this contact
	$q  = new DBQuery;
	$q->addTable('contacts');
	$q->addJoin('companies', 'cp', 'cp.company_id = contact_company');
	$q->addWhere("contact_id = $contact_id");
	$contacts = $q->loadList();
	// A private contact is only for its owner, as on the contact page (view.php).
	if (empty($contacts) || ($contacts[0]['contact_private'] && $contacts[0]['contact_owner']
	                          && $contacts[0]['contact_owner'] != $AppUI->user_id)) {
		$AppUI->redirect("m=public&a=access_denied");
	}

	require_once $AppUI->getSystemClass('vcard');
	$text = CVcard::fromContact($contacts[0],
		$dPconfig['company_name'].' '.$dPconfig['page_title'].': '.$dPconfig['site_domain']);

	//send http-output with this vCard

	// BEGIN extra headers to resolve IE caching bug (JRP 9 Feb 2003)
	// [http://bugs.php.net/bug.php?id=16173]
		header("Pragma: ");
		header("Cache-Control: ");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");  //HTTP/1.1
		header("Cache-Control: post-check=0, pre-check=0", false);
	// END extra headers to resolve IE caching bug

	header("MIME-Version: 1.0");
	header("Content-Type: text/x-vcard");
	$filename = preg_replace('/[^\w.-]+/u', '', $contacts[0]['contact_last_name'] . $contacts[0]['contact_first_name']);
	header('Content-Disposition: attachment; filename="' . ($filename === '' ? 'contact' : $filename) . '.vcf"');
	echo $text;
} else {
$AppUI->setMsg("contactIdError", UI_MSG_ERROR);
	$AppUI->redirect();
}
?>
