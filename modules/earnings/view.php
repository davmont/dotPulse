<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* earnings $Id: view.php,v 1.2 2004/09/13 02:23:00 stradius Exp $ */
$earning_id = intval( dPgetParam( $_GET, "earning_id", 0 ) );

// retrieve any state parameters
if (isset( $_GET['tab'] )) {
	$AppUI->setState( 'EarnVwTab', $_GET['tab'] );
}
$tab = $AppUI->getState( 'EarnVwTab' ) !== NULL ? $AppUI->getState( 'EarnVwTab' ) : 0;

// load the record data
$dbprefix = dPgetConfig('dbprefix', '');
$sql = "SELECT i1.earning_id, i1.earning_user_id, i1.earning_date, i1.earning_num, i1.earning_submit_company_id, i1.earning_submit_contact, 
i1.earning_submit_email, i1.earning_submit_phone, i1.earning_submit_address1, i1.earning_submit_address2, i1.earning_submit_city, i1.earning_submit_state, 
i1.earning_submit_zip, i1.earning_terms, i1.earning_comments, i1.earning_title, 
i1.earning_total, i1.earning_submitted, i1.earning_submitted_comment, i1.earning_approved, i1.earning_approved_by,
i1.earning_approved_comment, i1.earning_paid, i1.earning_paid_comment, c.company_name, u.user_username, ct.contact_first_name AS user_first_name, ct.contact_last_name AS user_last_name, u.user_type, ct.contact_email AS user_email
FROM {$dbprefix}earnings i1, {$dbprefix}companies c, {$dbprefix}users u, {$dbprefix}contacts ct
WHERE c.company_id = i1.earning_submit_company_id AND u.user_id = i1.earning_user_id AND ct.contact_id = u.user_contact AND i1.earning_id = $earning_id
GROUP BY i1.earning_id";

$obj = null;
if (!db_loadObject( $sql, $obj )) {
	$AppUI->setMsg( 'earning' );
	$AppUI->setMsg( "invalidID", UI_MSG_ERROR, true );
	$AppUI->redirect('m=earnings');
}
// Its owner, or an approver (user type below 7), as the module's lists and inv_aed.php allow.
if ((int)$obj->earning_user_id !== (int)$AppUI->user_id
		&& !($AppUI->user_type > 0 && $AppUI->user_type < 7)) {
	$AppUI->redirect('m=public&a=access_denied');
}
$AppUI->savePlace();

$timecard = 0;
// If document from employee, switch to timecard display where appropriate
if ( $obj->user_type == "7" ) {
	$timecard = 1;
}

// get the prefered date format
$df = $AppUI->getPref('SHDATEFORMAT');

// create Date objects from the datetime fields
$earning_date = intval( $obj->earning_date ) ? new CDate( $obj->earning_date ) : null;
$submitted_date = intval( $obj->earning_submitted ) ? new CDate ( $obj->earning_submitted ) : null;
$approved_date = intval( $obj->earning_approved ) ? new CDate( $obj->earning_approved ) : null;
$paid_date = intval( $obj->earning_paid ) ? new CDate( $obj->earning_paid ) : null;

// create process state field
$changeLock = strcmp($obj->earning_submitted, '0000-00-00 00:00:00');

// setup the title block
$msg = '';
// customize to user-type
if ( $AppUI->user_type != 7 && !$timecard) {
	$titleBlock = new CTitleBlock( 'View Invoice', 'earnings.gif', $m, "$m.$a" );
	$titleBlock->addCrumb( "?m=earnings", "invoices list" );
	if ($canEdit) {
		if ( !$changeLock ) { 
			$titleBlock->addCrumb( "?m=earnings&a=addedit&earning_id=$earning_id", "edit this invoice" );
			if ($canEdit && ( strcmp($submitted_date,'') == 0 ) ) {
				$titleBlock->addCrumbDelete( 'delete invoices', $canDelete, $msg );
			}
		}
	}
} else {
	$titleBlock = new CTitleBlock( 'View Timecard', 'earnings.gif', $m, "$m.$a" );
	$titleBlock->addCrumb( "?m=earnings", "timecards list" );
	if ($canEdit) {
		if ( !$changeLock ) { 
			$titleBlock->addCrumb( "?m=earnings&a=addedit&earning_id=$earning_id", "edit this timecard" );
			if ($canEdit && ( strcmp($submitted_date,'') == 0 ) ) {
				$titleBlock->addCrumbDelete( 'delete timecard', $canDelete, $msg );
			}
		}
	}
}
$titleBlock->show();
?>
<script nonce="<?php echo dPcspNonce(); ?>">
var calendarField = '';

function remInvItems(){
	if ( confirm("Remove Selected Items?") ) {
		document.frmRemInv.submit();
	}
}

function updRates(){
	if ( confirm("Update earning With New Rates?") ) {
		// Have to Exist In Same Form as Removals
		document.frmRemInv.inv_dosql.value="updrates";
		document.frmRemInv.submit();
	}
}

function addInvItems(){
	var checkTest = 0;
	for(var i=0;i<document.frmAddInv.length;i++) {
		if ( document.frmAddInv[i].checked == true ) {
			checkTest = 1;
		}
	}
	if ( checkTest ) {
		if ( confirm("Add Selected Line Items?") ) {
			document.frmAddInv.submit();
		}
	} else {
		alert("You must select an item to add first.");
	}
}

function addAdHoc(){
	if (document.frmAdHoc.adhoc_earning_item_description.value.length < 1) {
		alert( "Please enter a description for your line item.");
		document.frmAdHoc.adhoc_earning_item_description.focus();
	} else {
		document.frmAdHoc.submit();
	}
}

function postSubmit() {
	if ( confirm("Submit For Approval?") ) {
		document.frmSubmit.submit();
	}
}

function postApprove() {
	if ( confirm("Approve for Payment?") ) {
		document.frmApprove.submit();
	}
}

function postDecline() {
	if (document.frmApprove.earning_approved_comment.value.length < 1) {
		alert( "A comment is required if you decline a payment request.");
		document.frmApprove.earning_approved_comment.focus();
	} else {
		if ( confirm("Decline Payment?") ) {
			document.frmApprove.inv_dosql.value="postdecline";
			document.frmApprove.submit();
		}
	}
}

function postPaid() {
	if ( confirm("Mark as Paid?") ) {
		document.frmPaid.submit();
	}
}

function popCalendar( field ){
	calendarField = field;
	idate = document.frmAdHoc[field].value;
	window.open( 'index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + idate, 'calwin', 'top=250,left=250,width=250, height=220, scollbars=false' );
}

function selAllItems() {
	for(var i=0;i<document.frmAddInv.length;i++) {
		document.frmAddInv[i].checked = true;
	}
}

function unselAllItems() {
	for(var i=0;i<document.frmAddInv.length;i++) {
		document.frmAddInv[i].checked = false;
	}
}

/**
 *	@param string Input date in the format YYYYMMDD
 *	@param string Formatted date
 */
function setCalendar( idate, fdate ) {
	fld_date = document.frmAdHoc[calendarField];
	fld_fdate = document.frmAdHoc[calendarField + '_formatted'];
	fld_date.value = idate;
	fld_fdate.value = fdate;
}

function delIt() {
	if (confirm( "Are you sure you want to delete this form? All history will be lost and all line items within it will be released for use on other forms." )) {
		document.frmDelete.submit();
	}
}
</script>
<table border="0" cellpadding="4" cellspacing="0" width="100%" class="std">
<form name="frmDelete" action="?m=earnings&a=inv_aed&earning_id=<?php echo $earning_id; ?>" method="post">
	<input type="hidden" name="inv_dosql" value="killearning" />
</form>
<tr>
	<td width="50%" valign="top">
		<strong><?php echo $AppUI->_('Form Details');?></strong>
		<table cellspacing="1" cellpadding="2" border="0" width="100%">
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Date');?>:</td>
			<td class="hilite"><?php echo $obj->earning_date ? $earning_date->format( $df ) : '-';?></td>
		</tr>
		<?php if ( $AppUI->user_type != 7 && !$timecard) { ?>
			<tr>
				<td align="right" nowrap><?php echo $AppUI->_('Invoice Number');?>:</td>
				<td class="hilite"><?php echo dPhtml($obj->earning_num); ?></td>
			</tr>
		<?php } else { ?>
			<tr>
				<td align="right" nowrap><?php echo $AppUI->_('Timecard Number');?>:</td>
				<td class="hilite"><?php echo dPhtml($obj->earning_num); ?></td>
			</tr>
		<?php } ?>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Title');?>:</td>
			<td class="hilite"><?php echo dPhtml(stripslashes($obj->earning_title)); ?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('From');?>:</td>
			<td class="hilite"><?php echo dPhtml($obj->user_first_name . " " . $obj->user_last_name . " [ID: " . $obj->earning_user_id . "]");?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('To Company');?>:</td>
			<td class="hilite" width="100%"><?php echo dPhtml($obj->company_name); ?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Address');?>:</td>
			<td class="hilite"><?php echo dPhtml($obj->earning_submit_address1);?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('');?>:</td>
			<td class="hilite"><?php echo dPhtml($obj->earning_submit_address2);?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('City');?>:</td>
			<td class="hilite"><?php echo dPhtml($obj->earning_submit_city); ?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('State');?>:</td>
			<td class="hilite"><?php echo dPhtml($obj->earning_submit_state); ?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Zip');?>:</td>
			<td class="hilite"><?php echo dPhtml($obj->earning_submit_zip); ?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Contact (name)');?>:</td>
			<td class="hilite"><?php echo dPhtml($obj->earning_submit_contact); ?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Contact Phone');?>:</td>
			<td class="hilite"><?php echo dPhtml($obj->earning_submit_phone); ?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Notify Email');?>:</td>
			<td class="hilite"><?php echo dPhtml($obj->earning_submit_email); ?></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Terms');?>:</td>
			<td class="hilite"><?php echo dPhtml($obj->earning_terms); ?></td>
		</tr>
		</table>
	</td>
	<td width="50%" rowspan="9" valign="top">
		<b><?php echo $AppUI->_('Earnings Process');?></b><br/>
		&nbsp;&nbsp;Step 1: Submit for Approval<br/>
		&nbsp;&nbsp;Step 2: Approved By Manager (becomes printable)<br/>
		<?php if ( $AppUI->user_type != 7 && !$timecard) { ?>
			&nbsp;&nbsp;Step 3: Mark Paid When You Receive Payment
		<?php } ?>
		&nbsp;<p/>
		<table cellspacing="0" cellpadding="0" border="0" width="100%">
		<?php if ( strcmp($obj->earning_submitted, '0000-00-00 00:00:00') == 0 ) { ?>
		<tr>
			<td align="right" valign="top" width="150" nowrap><?php echo $AppUI->_('Submit&nbsp;For&nbsp;Approval');?>:&nbsp;<form name="frmSubmit" action="?m=earnings&a=inv_aed&earning_id=<?php echo $earning_id; ?>" method="post">
				<input type="hidden" name="inv_dosql" value="postsubmit">
				<input type="hidden" name="earning_submit_contact" value="<?php echo dPhtml($obj->earning_submit_contact); ?>">
				<input type="hidden" name="earning_num" value="<?php echo dPhtml($obj->earning_num); ?>">
				<input type="hidden" name="earning_submit_email" value="<?php echo dPhtml($obj->earning_submit_email); ?>">
				<input type="hidden" name="earning_title" value="<?php echo dPhtml(stripslashes($obj->earning_title)); ?>">
				<input type="hidden" name="earning_comments" value="<?php echo dPhtml(stripslashes($obj->earning_comments)); ?>">
				<input type="hidden" name="earning_submitted_comment" value="<?php echo dPhtml(stripslashes($obj->earning_submitted_comment)); ?>">
				<input type="hidden" name="earning_id" value="<?php echo dPhtml($obj->earning_id); ?>">
			</td>
			<td valign="top"><i>You may add comments to send to the approver below.</i></td>
		</tr>
		<tr>
			<td></td>
			<td valign="top"><input type="text" name="earning_submitted_comment" value="<?php echo dPhtml(stripslashes($obj->earning_submitted_comment)); ?>" size="30" maxlength="250">&nbsp;<input type="button" name="btnPostSubmit" value=" submit " <?php echo dPaction('postSubmit'); ?>></form></td>
		</tr>
		<?php } else { ?>
		<tr>
			<td align="right" valign="top" width="150" nowrap><?php echo $AppUI->_('Submitted');?>:&nbsp;</td>
			<td valign="top">
				<?php echo $submitted_date->format( $df );
				echo "<br/>";
				echo "Comments&nbsp;Made&nbsp;By&nbsp;Submitter:&nbsp;<i>" . dPhtml(stripslashes($obj->earning_submitted_comment)) . "</i><br/>";?>
			</td>
		</tr>
		<?php } ?>
		<tr><td>&nbsp;</td><td></td></tr>
		<?php if ( ( strcmp($obj->earning_approved, '0000-00-00 00:00:00') == 0 ) && ( strcmp($obj->earning_submitted, '0000-00-00 00:00:00') != 0 ) && ( $AppUI->user_type < 7 ) ) { ?>
		<tr>
			<td align="right" valign="top" nowrap><?php echo $AppUI->_('Approve');?>:&nbsp;
				<form name="frmApprove" action="?m=earnings&a=inv_aed&earning_id=<?php echo $earning_id; ?>" method="post">
				<input type="hidden" name="inv_dosql" value="postapprove">
				<input type="hidden" name="earning_submit_contact" value="<?php echo dPhtml($obj->earning_submit_contact); ?>">
				<input type="hidden" name="earning_user_name" value="<?php echo dPhtml($obj->user_first_name . " " . $obj->user_last_name); ?>">
				<input type="hidden" name="earning_user_email" value="<?php echo dPhtml($obj->user_email); ?>">
				<input type="hidden" name="earning_num" value="<?php echo dPhtml($obj->earning_num); ?>">
				<input type="hidden" name="earning_submit_email" value="<?php echo dPhtml($obj->earning_submit_email); ?>">
				<input type="hidden" name="earning_title" value="<?php echo dPhtml(stripslashes($obj->earning_title)); ?>">
				<input type="hidden" name="earning_comments" value="<?php echo dPhtml(stripslashes($obj->earning_comments)); ?>">
				<input type="hidden" name="earning_submitted_comment" value="<?php echo dPhtml(stripslashes($obj->earning_submitted_comment)); ?>">
				<input type="hidden" name="earning_id" value="<?php echo dPhtml($obj->earning_id); ?>">
			</td>
			<td valign="top"><i>You may add comments below.</i></td>
		</tr>
		<tr>
			<td></td>
			<td valign="top"><input type="text" name="earning_approved_comment" value="<?php echo dPhtml(stripslashes($obj->earning_approved_comment)); ?>" size="30" maxlength="250">&nbsp;
				<input type="button" name="btnPostApprove" value=" yes " <?php echo dPaction('postApprove'); ?>>&nbsp;
				<input type="button" name="btnPostDecline" value=" no " <?php echo dPaction('postDecline'); ?>>
				</form>
			</td>
		</tr>
		<?php } else { ?>
		<tr>
			<td align="right" nowrap valign="top"><?php echo $AppUI->_('Approval');?>:&nbsp;</td>
			<td valign="top">
			<?php 
			if ( strcmp($obj->earning_approved, '0000-00-00 00:00:00' ) == 0 ) { 
				// Was Not Approved
				if ( strcmp($obj->earning_approved_comment, '' ) != 0 ) {
					// But was declined. ?>
					Approval Declined.<br/> Comments&nbsp;From&nbsp;Manager:<i>&nbsp;<?php echo dPhtml(stripslashes($obj->earning_approved_comment)); ?></i>
				<?php 
					} else { 
						// Either not yet submitted OR waiting for approval
						if ( strcmp($obj->earning_submitted, '0000-00-00 00:00:00') == 0 ) { ?>
							<i>Not Yet Submitted.</i>
						<?php } else { ?>
							<i>Awaiting Approval. <?php echo dPhtml(stripslashes($obj->earning_approved_comment)); ?></i>
						<?php } ?>
				<?php } ?>
			<?php } else {
				echo $approved_date->format( $df );
				echo "<br/>";
			?>
				<i><?php echo dPhtml(stripslashes($obj->earning_approved_comment)); ?></i>
			<?php } ?>
			</td>
		</tr>
		<?php } ?>
		<?php if ( $AppUI->user_type != 7 && !$timecard) { ?>
			<tr><td>&nbsp;</td><td></td></tr>
			<?php if ( ( strcmp($obj->earning_paid, '0000-00-00 00:00:00') == 0 ) && ( strcmp($obj->earning_approved, '0000-00-00 00:00:00') != 0 ) ){ ?>
			<tr>
				<td align="right" valign="top" nowrap><?php echo $AppUI->_('Paid');?>:&nbsp;<form name="frmPaid" action="?m=earnings&a=inv_aed&earning_id=<?php echo $earning_id; ?>" method="post"><input type="hidden" name="inv_dosql" value="postpaid"></td>
				<td valign="top"><i>You may add comments when you mark this item paid.</td>
			</tr>
			<tr>
				<td></td>
				<td valign="top"><input type="text" name="earning_paid_comment" value="<?php echo dPhtml(stripslashes($obj->earning_paid_comment)); ?>" size="30" maxlength="250">&nbsp;<input type="button" name="btnPostPaid" value=" mark paid " <?php echo dPaction('postPaid'); ?>></form></td>
			</tr>
			<?php } else { ?>
			<tr>
				<td valign="top" align="right" nowrap><?php echo $AppUI->_('Paid');?>:&nbsp;</td>
				<td valign="top">
				<?php 
				if ( strcmp($obj->earning_paid, '0000-00-00 00:00:00' ) == 0 ) { ?>
					<i>Not Approved. <?php echo dPhtml(stripslashes($obj->earning_paid_comment)); ?></i>
				<?php } else { 
					echo $paid_date->format( $df );
					echo "<br/>";
				?>
					<i><?php echo dPhtml(stripslashes($obj->earning_paid_comment)); ?></i>
				<?php } ?>
				</td>
			</tr>
			<?php } ?>
		<?php } else { ?>
			<tr><td>&nbsp;</td><td></td></tr>
		<?php } ?>
		</table>
	</td>
	</tr>
	<tr>
		<td colspan="2">
			<strong><?php echo $AppUI->_('Comments On This Form');?></strong><br/>
			<table cellspacing="0" cellpadding="2" border="0" width="100%">
				<tr>
					<td class="hilite">
						<?php echo str_replace( chr(10), "<br />", dPhtml(stripslashes($obj->earning_comments))); ?>&nbsp;
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>


<?php
if ($tab == 1) {
	$_GET['earning_status'] = -1;
}
$query_string = "?m=earnings&a=view&earning_id=$earning_id";
// tabbed information boxes
$tabBox = new CTabBox( "?m=earnings&a=view&earning_id=$earning_id", "", $tab );
$tabBox->add( DP_BASE_DIR . '/modules/earnings/items', 'This Form' );
if ( !$changeLock ) { 
	$tabBox->add( DP_BASE_DIR . '/modules/earnings/items', 'Unused Log Entries' );
}

// settings for earning_items
$f = 'all';
$min_view = true;

$tabBox->show();
?>
