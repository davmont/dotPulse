<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
// Sends a stored document: ?m=mngdocument&a=documentview&suppressHeaders=1&document_id=N.
// Uploads live outside the web root (CFile::filePath()), so this page is the only way to
// read them, and it checks read permission on the record first.

require_once DP_BASE_DIR . '/modules/mngdocument/files.class.php';

$document_id = dPgetIntParam($_GET, 'document_id', 0);
$obj = new CMngDocumentFile();
if (!$document_id || !$obj->load($document_id) || $obj->SGD_type != 0
	|| getDenyRead('mngdocument', $document_id)) {
	$AppUI->redirect('m=public&a=access_denied');
}
$file = $obj->filePath();
if (!is_file($file)) {
	$AppUI->setMsg('fileIdError', UI_MSG_ERROR);
	$AppUI->redirect('m=mngdocument');
}

while (ob_get_level()) {
	ob_end_clean();
}
header('Content-Type: application/octet-stream');
header('X-Content-Type-Options: nosniff');
header('Content-Length: ' . filesize($file));
header('Content-Disposition: attachment; filename="' . str_replace(array('"', '\\', "\r", "\n"), '_', $obj->SGD_name) . '"');
readfile($file);
exit;
