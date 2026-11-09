<?php /* FILES $Id: do_file_co.php 6149 2012-01-09 11:58:40Z ajdonnison $ */
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

//addfile sql
$file_id = intval(dPgetParam($_POST, 'file_id', 0));
$coReason = dPgetCleanParam($_POST, 'file_co_reason', '');

$co_cancel = intval(dPgetParam($_POST, 'co_cancel', 0));

$not = (bool)dPgetParam($_POST, 'notify', '0');
$notcont = (bool)dPgetParam($_POST, 'notify_contacts', '0');

$obj = new CFile();
// Edit permission on this file, as files/co.php checks before showing the form.
if (!$file_id || !getPermission('files', 'edit', $file_id) || !$obj->load($file_id)) {
	$AppUI->setMsg('Access denied.', UI_MSG_ERROR);
	$AppUI->redirect('m=public&a=access_denied');
}
// A file checked out by someone else (or marked final) stays locked: only the user who
// checked it out, a system administrator or the project owner may take it over or cancel it.
if (!empty($obj->file_checkout) && $obj->file_checkout != $AppUI->user_id
		&& !getPermission('system', 'edit') && !$obj->canAdmin()) {
	$AppUI->setMsg('Access denied.', UI_MSG_ERROR);
	$AppUI->redirect('m=public&a=access_denied');
}

//set checkout messages
if ($msg = $obj->checkout($AppUI->user_id, $coReason)) {
	$AppUI->setMsg($msg, UI_MSG_ERROR);
} else {
	$AppUI->setMsg('File checkout completed', UI_MSG_OK);
	//Notification
	if ($not) {
		$obj->notify();
	}
	if ($notcont) {
		$obj->notifyContacts();
	}
}

//Checkout Cancellation
if ($co_cancel) {
	if ($msg = $obj->checkout('', '')) {
		$AppUI->setMsg($msg, UI_MSG_ERROR);
	} else {
		$AppUI->setMsg('File checkout reverted', UI_MSG_OK);
	}
}

if (($msg = $obj->store())) {
	$AppUI->setMsg($msg, UI_MSG_ERROR);
}

$params = 'file_id=' . $file_id;
$session_id = SID;

session_write_close();
// are the params empty
// Fix to handle cookieless sessions
if ($session_id != "") {
	$params .= "&" . $session_id;
}

if ($co_cancel != 1) {
//header("Refresh: 0; URL=fileviewer.php?$params");
	// $params is an int id plus the server's session id; dPjs() quotes it for the script.
	echo ('<script type="text/javascript" nonce="' . dPcspNonce() . '">fileloader = window.open('
	      . dPjs('fileviewer.php?' . $params)
	      . ', "mywindow","location=1,status=1,scrollbars=0,width=20,height=20");'
	      . 'fileloader.moveTo(0,0);</script>');
}

?>
