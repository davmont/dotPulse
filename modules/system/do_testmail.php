<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// Sends a message with the saved mail settings, right away, and shows why it failed.
if (!getPermission('system', 'edit')) {
	$AppUI->redirect('m=public&a=access_denied');
}
require_once $AppUI->getSystemClass('libmail');

$to = trim(dPgetCleanParam($_POST, 'test_mail_to', ''));
$mail = new Mail;
if (!$mail->ValidEmail($to)) {
	$AppUI->setMsg('Please enter a valid email address.', UI_MSG_ERROR);
	$AppUI->redirect('m=system&a=systemconfig&section=mail');
}
$mail->defer = false; // not through the queue: the result is shown now

$transport = dPgetConfig('mail_transport', 'php');
$details = $AppUI->_('Transport', UI_OUTPUT_RAW) . ': ' . $transport;
if ($transport === 'smtp') {
	$port = (int)dPgetConfig('mail_port', 25);
	$details .= "\n" . $AppUI->_('Server', UI_OUTPUT_RAW) . ': ' . dPgetConfig('mail_host', 'localhost') . ':' . $port
		. "\nTLS: " . (dPgetConfig('mail_smtp_tls') ? ($port === 465 ? 'SMTPS' : 'STARTTLS') : $AppUI->_('No', UI_OUTPUT_RAW));
}
$details .= "\n" . $AppUI->_('mail_from_title', UI_OUTPUT_RAW) . ': '
	. (dPgetConfig('mail_from') ? dPgetConfig('mail_from') : $AppUI->_('(each user\'s address)', UI_OUTPUT_RAW));

$mail->Subject($AppUI->_('Test email', UI_OUTPUT_RAW), 'UTF-8');
$mail->Body($AppUI->_('This is a test message. If you are reading it, dotPulse can send email.', UI_OUTPUT_RAW)
	. "\n\n" . $details . "\n", 'UTF-8');
$mail->From('"' . trim($AppUI->user_first_name . ' ' . $AppUI->user_last_name) . '" <' . $AppUI->user_email . '>');
$mail->To($to, true);

if ($mail->Send()) {
	$AppUI->setMsg($AppUI->_('Test email sent to') . ' ' . $to, UI_MSG_OK);
} else {
	$AppUI->setMsg($AppUI->_('Test email not sent') . ': ' . $mail->Error(), UI_MSG_ERROR);
}
$AppUI->redirect('m=system&a=systemconfig&section=mail');
