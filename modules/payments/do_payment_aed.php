<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* PAYMENTS $Id: do_payment_aed.php,v 1.1.1.1 2004/04/01 16:20:41 aardvarkads Exp $ */
$del = (int)dPgetParam( $_POST, 'del', 0 );
$obj = new CPayment();
$msg = '';

if (!$obj->bind( $_POST )) {
	$AppUI->setMsg( $obj->getError(), UI_MSG_ERROR );
	$AppUI->redirect();
}

$payment_id = (int)$obj->payment_id;
// Permission on this record, as its addedit page checks.
dPrequireWritePermission('payments', $payment_id, $del);

if ($payment_id) {
	$existing = new CPayment();
	if (!$existing->load($payment_id)) {
		$AppUI->setMsg( 'Payment' );
		$AppUI->setMsg( 'invalidID', UI_MSG_ERROR, true );
		$AppUI->redirect();
	}
	// The form shows an existing payment's company without letting it change.
	$obj->payment_company = $existing->payment_company;
}
$payment_company = (int)$obj->payment_company;
if (!$del && $payment_company && !getPermission('companies', 'view', $payment_company)) {
	$AppUI->setMsg('Access denied.', UI_MSG_ERROR);
	$AppUI->redirect('m=public&a=access_denied');
}

// prepare (and translate) the module name ready for the suffix
$AppUI->setMsg( 'Payment' );
if ($del) {
	if (!$obj->canDelete( $msg )) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect();
	}
	if (($msg = $obj->delete())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect();
	} else {
		$AppUI->setMsg( 'deleted', UI_MSG_ALERT, true );
		$AppUI->redirect( '', -1 );
	}
} else {
	if (($msg = $obj->store())) {
		$AppUI->setMsg( $msg, UI_MSG_ERROR );
		$AppUI->redirect();
	}
	$AppUI->setMsg( $payment_id ? 'updated' : 'added', UI_MSG_OK, true );

	// The invoices ticked on the form. Only those the form offers count: invoices of the
	// payment's company that are the user's own or have no owner, and that are unpaid or
	// already linked to this payment. Others are ignored, so a payment cannot mark any
	// invoice paid.
	$invoice_list = dPgetParam( $_POST, 'invoice_list', array() );
	$wanted = array();
	foreach ((is_array($invoice_list) ? $invoice_list : array()) as $invoice_id) {
		$invoice_id = (int)$invoice_id;
		if ($invoice_id > 0) {
			$wanted[$invoice_id] = $invoice_id;
		}
	}
	$allowed = array();
	if ($wanted) {
		$q = new DBQuery();
		$q->addTable('invoices', 'inv');
		$q->leftJoin('invoice_payment', 'ip', 'ip.invoice_id = inv.invoice_id AND ip.payment_id = '
			. (int)$obj->payment_id);
		$q->addQuery('DISTINCT inv.invoice_id');
		$q->addWhere('inv.invoice_id IN (' . implode(',', $wanted) . ')');
		$q->addWhere('inv.invoice_company = ' . $payment_company);
		$q->addWhere('(inv.invoice_owner = ' . (int)$AppUI->user_id
			. ' OR inv.invoice_owner IS NULL OR inv.invoice_owner = 0)');
		$q->addWhere('(inv.invoice_status = 0 OR ip.invoice_id IS NOT NULL)');
		$allowed = $q->loadColumn();
	}
	$obj->updatePaymentsInvoices( is_array($allowed) ? $allowed : array() );
	$AppUI->redirect();
}
?>
