<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
/* earnings $Id: addedit.php,v 1.2 2004/09/13 02:23:00 stradius Exp $ */
/**
* earnings :: Add/Edit earning
*/

//var_dump($AppUI);

if (!$canEdit) {
	$AppUI->redirect( "m=public&a=access_denied" );
}

$AppUI->savePlace();

// get a list of permitted companies
require_once( $AppUI->getModuleClass ('companies' ) );
$row = new CCompany();
$companies = $row->getAllowedRecords( $AppUI->user_id, 'company_id,company_name', 'company_name' );
//$companies = arrayMerge( array( '0'=>'' ), $companies );

// format dates
$df = $AppUI->getPref('SHDATEFORMAT');

if ( isset( $_GET["earning_id"] ) ) {
	// Edit Mode
	$form_mode = 1;
	$earning_id = intval( dPgetParam( $_GET, "earning_id", 0 ) );

	// load the record data
	$dbprefix = dPgetConfig('dbprefix', '');
	$sql = "SELECT i1.earning_id, i1.earning_user_id, i1.earning_date, i1.earning_num, i1.earning_submit_company_id, i1.earning_submit_contact, 
	i1.earning_submit_email, i1.earning_submit_phone, i1.earning_submit_address1, i1.earning_submit_address2, i1.earning_submit_city, i1.earning_submit_state, 
	i1.earning_submit_zip, i1.earning_terms, i1.earning_comments, i1.earning_title, 
	i1.earning_total, i1.earning_submitted, i1.earning_submitted_comment, i1.earning_approved, 
	i1.earning_approved_comment, i1.earning_paid, i1.earning_paid_comment, c.company_name, u.user_username 
	FROM {$dbprefix}earnings i1, {$dbprefix}companies c, {$dbprefix}users u
	WHERE c.company_id = i1.earning_submit_company_id AND u.user_id = i1.earning_user_id AND i1.earning_id = $earning_id
	GROUP BY i1.earning_id";
	//echo $sql;
	
	$obj = null;
	if (!db_loadObject( $sql, $obj )) {
		$AppUI->setMsg( 'earning' );
		$AppUI->setMsg( "invalidID", UI_MSG_ERROR, true );
		// Not back to the saved place: that can be this page, which loops.
		$AppUI->redirect('m=earnings');
	}
	// Only its owner edits an earning (inv_aed.php's editinv rule).
	if ((int)$obj->earning_user_id !== (int)$AppUI->user_id) {
		$AppUI->redirect('m=public&a=access_denied');
	}
	$AppUI->savePlace();
} else {
	// Add Mode
	$form_mode = 0;
	// Create $obj with blank values
	$obj = new stdClass();
	$obj->earning_date=date("Ymd");
	$obj->earning_num='';
	$obj->earning_title='';
	$obj->earning_submit_contact='Manager';
	$obj->earning_submit_company_id=intval($AppUI->user_company);
	$obj->earning_submit_address1='';
	$obj->earning_submit_address2='';
	$obj->earning_submit_city='';
	$obj->earning_submit_state='';
	$obj->earning_submit_zip='';
	$obj->earning_submit_phone='';
	$obj->earning_submit_email='';
	$obj->earning_terms='NET30';
	$obj->earning_comments='';
}
$earning_date = intval( $obj->earning_date ) ? new CDate( $obj->earning_date ) : new CDate();

// setup the title block
$msg = '';
// customize by user-type
if ( $AppUI->user_type != 7 ) {
	if ($form_mode == 1 ) {
		$titleBlock = new CTitleBlock( 'Edit Invoice', 'earnings.gif', $m, "$m.$a" );
		$titleBlock->addCrumb( "?m=earnings", "invoices list" );
		if ($canEdit) {
			if ($canEdit and !intval($obj->earning_submitted)) {
				$titleBlock->addCrumbDelete( 'delete invoice', $canDelete, $msg );
			}
		}
	} else {
		$titleBlock = new CTitleBlock( 'Add Invoice', 'earnings.gif', $m, "$m.$a" );
		$titleBlock->addCrumb( "?m=earnings", "invoices list" );
	}
} else {
	if ($form_mode == 1 ) {
		$titleBlock = new CTitleBlock( 'Edit Timecard', 'earnings.gif', $m, "$m.$a" );
		$titleBlock->addCrumb( "?m=earnings", "timecards list" );
		if ($canEdit) {
			if ($canEdit and !intval($obj->earning_submitted)) {
				$titleBlock->addCrumbDelete( 'delete timecard', $canDelete, $msg );
			}
		}
	} else {
		$titleBlock = new CTitleBlock( 'Add Timecard', 'earnings.gif', $m, "$m.$a" );
		$titleBlock->addCrumb( "?m=earnings", "timecards list" );
	}
}
$titleBlock->show();
?>
<script nonce="<?php echo dPcspNonce(); ?>">
var calendarField = '';
var calWin = null;

function popCalendar( field ){
	calendarField = field;
	idate = document.editFrm[field].value;
	window.open( 'index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + idate, 'calwin', 'top=250,left=250,width=250, height=220, scollbars=false' );
}

/**
 *	@param string Input date in the format YYYYMMDD
 *	@param string Formatted date
 */
function setCalendar( idate, fdate ) {
	fld_date = document.editFrm[calendarField];
	fld_fdate = document.editFrm[calendarField + '_formatted'];
	fld_date.value = idate;
	fld_fdate.value = fdate;
}

function submitIt(){
	if (document.editFrm.earning_num.value.length < 1) {
		alert( "A unique form number is required.  Please enter one or use the 'suggest' button.");
		document.editFrm.earning_num.focus();
	} else {
		// Now Verify the Email Address
		var testField = trim(document.editFrm.earning_submit_email.value);
		var emailError=0;
		// Since One Is Given, We'll Validate It
		var indexOfAtSign = testField.indexOf("@");
		if (indexOfAtSign > 0) {
			// Check for dot-something
			var indexOfDot = testField.lastIndexOf(".");
			// Make sure that the last dot is after @ and something is after last dot.
			if (( indexOfDot < indexOfAtSign ) || (testField.length < indexOfDot+2)) {

				emailError=1;
			}
		} else {
			emailError=1;
		}

		if ( emailError ) {
			alert ('Invalid Email Address. A valid email address is required for notification.');
			document.editFrm.earning_submit_email.focus();
			return;
		} else {
			if ( confirm("Commit these changes to the document?") ) {
					document.editFrm.submit();
			}
		}
	}
}

// Basic String Trimming Function
function trim(s) {
	while (s.substring(0,1) == ' ') {
		s = s.substring(1,s.length);
		}
	while (s.substring(s.length-1,s.length) == ' ') {
		s = s.substring(0,s.length-1);
		}
	return s;
}

function suggestNum(num) {
	document.editFrm.earning_num.value = num;
}

function selectCompany(companyID) {
	document.editFrm.earning_submit_company_id.value=companyID;
	document.editFrm.earning_submit_address1.value="";
	document.editFrm.earning_submit_address2.value="";
	document.editFrm.earning_submit_city.value="";
	document.editFrm.earning_submit_state.value="";
	document.editFrm.earning_submit_zip.value="";
	document.editFrm.earning_submit_phone.value="";
}

// The title block's delete link.
function delIt() {
	if (confirm( "Are you sure you want to delete this form? All history will be lost and all line items within it will be released for use on other forms." )) {
		document.frmDelete.submit();
	}
}
</script>
<?php if ( $form_mode == 1 ) { ?>
<form name="frmDelete" action="?m=earnings&a=inv_aed&earning_id=<?php echo (int)$earning_id; ?>" method="post">
	<input type="hidden" name="inv_dosql" value="killearning" />
</form>
<?php } ?>
<table border="1" cellpadding="4" cellspacing="0" width="100%" class="std">
<?php if ( $form_mode == 1 ) { ?>
	<form name="editFrm" action="?m=earnings&a=inv_aed&earning_id=<?php echo $earning_id;?>" method="post">
	<input type="hidden" name="inv_dosql" value="editinv"/>
<?php } else { ?>
	<form name="editFrm" action="?m=earnings&a=inv_aed&earning_id=0" method="post">
	<input type="hidden" name="inv_dosql" value="addinv"/>
<?php } ?>
<tr>
	<td width="50%" valign="top">
		<strong><?php echo $AppUI->_('Form Details');?></strong>
		<table cellspacing="1" cellpadding="2" border="0" width="100%">
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Date');?></td>
			<td>
				<input type="hidden" name="earning_date" value="<?php echo $earning_date->format( FMT_TIMESTAMP_DATE );?>">
				<input type="text" name="earning_date_formatted" size="12" value="<?php echo $earning_date->format( $df );?>" class="text" disabled="disabled" />&nbsp;<a href="#"<?php echo dPaction('popCalendar', array('earning_date')); ?>><img align="middle" src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0"></a>&nbsp;<i>Click the button to select a different date.</i>
			</td>
		</tr>
		<tr>
			<?php if ( $AppUI->user_type != 7 ) { ?>
				<td align="right" nowrap><?php echo $AppUI->_('Invoice Number');?></td>
			<?php } else { ?>
				<td align="right" nowrap><?php echo $AppUI->_('Timecard Number');?></td>
			<?php } ?>
			<td><input type="text" class="text" name="earning_num" value="<?php echo dPformSafe( $obj->earning_num );?>" size="30" maxlength="35" />&nbsp;
			<input type="button" name="btnSuggNum" value=" suggest " <?php echo dPaction('suggestNum', array(date("YmdHi") . "-" . $AppUI->user_id)); ?>>&nbsp;<i>Number must be unique.  It is highly recommended that you use the suggest button.</i></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Title');?></td>
			<td ><input type="text" class="text" name="earning_title" value="<?php echo dPformSafe( $obj->earning_title );?>" size="40" maxlength="40" />&nbsp;<i>Example: "Hourly work from 08/01/2004 to 08/15/2004."</i></td>
		</tr>
		<tr>
			<?php if ( $AppUI->user_type < 7 ) { ?>
			<td align="right" nowrap><?php echo $AppUI->_('To Company');?></td>
			<td width="100%">
			<input type="hidden" name="earning_submit_company_id" value="<?php echo $obj->earning_submit_company_id;?>">
			<?php
				echo arraySelect( $companies, 'earning_submit_company_select', 'class="text" size="1"' . dPaction('selectCompany', array('$value')), $obj->earning_submit_company_id );
			?>
			</td>
			<?php }
			if ( $AppUI->user_type > 5 ) { ?>
			<td colspan="2">
				<input type="hidden" name="earning_submit_company_id" value="<?php echo $obj->earning_submit_company_id;?>">				
			</td>
			<?php } ?>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Contact (name)');?></td>
			<td ><input type="text" class="text" name="earning_submit_contact" value="<?php echo dPformSafe( $obj->earning_submit_contact );?>" size="40" maxlength="50" /></td>
		</tr>
		<tr>
			<td align="right" nowrap><?php echo $AppUI->_('Notify Email');?></td>
			<td ><input type="text" class="text" name="earning_submit_email" value="<?php echo dPformSafe( $obj->earning_submit_email );?>" size="40" maxlength="200" />&nbsp;<i>A notification email will be sent to this address when you submit it for approval.</i></td>
		</tr>
		<tr>
			<?php if ( $AppUI->user_type == 7 ) { ?>
			<td colspan="2">
				<input type="hidden" name="earning_terms" value="N/A">
			</td>
			<?php } else { ?>
			<td align="right" nowrap><?php echo $AppUI->_('Terms');?></td>
			<td>
				<input type="text" name="earning_terms" value="<?php echo dPformSafe($obj->earning_terms); ?>">&nbsp;<i>Purely for your notations.</i>
			</td>
			<?php } ?>
		</tr>
		<tr>
			<td>
			</td>
			<td>
				<?php if ( $AppUI->user_type < 7 ) { ?>
				<table bgcolor="#CCCCCC">
					<tr>
						<td valign="top"><b>OVERRIDE ADDRESS:</b></td>
						<td valign="top">
							Leave this section blank if you want to use the default address for the selected company.
						</td>
					</tr>
					<tr>
						<td align="right" nowrap><?php echo $AppUI->_('Address');?></td>
						<td ><input type="text" class="text" name="earning_submit_address1" value="<?php echo dPformSafe( $obj->earning_submit_address1  );?>" size="50" maxlength="50" /></td>
					</tr>
					<tr>
						<td align="right" nowrap><?php echo $AppUI->_('');?></td>
						<td ><input type="text" class="text" name="earning_submit_address2" value="<?php echo dPformSafe( $obj->earning_submit_address2  );?>" size="50" maxlength="50" /></td>
					</tr>
					<tr>
						<td align="right" nowrap><?php echo $AppUI->_('City');?></td>
						<td ><input type="text" class="text" name="earning_submit_city" value="<?php echo dPformSafe( $obj->earning_submit_city  );?>" size="30" maxlength="30" /></td>
					</tr>
					<tr>
						<td align="right" nowrap><?php echo $AppUI->_('State');?></td>
						<td ><input type="text" class="text" name="earning_submit_state" value="<?php echo dPformSafe( $obj->earning_submit_state );?>" size="30" maxlength="30" /></td>
					</tr>
					<tr>
						<td align="right" nowrap><?php echo $AppUI->_('Zip');?></td>
						<td ><input type="text" class="text" name="earning_submit_zip" value="<?php echo dPformSafe( $obj->earning_submit_zip  );?>" size="11" maxlength="11" /></td>
					</tr>
					<tr>
						<td align="right" nowrap><?php echo $AppUI->_('Phone');?></td>
						<td ><input type="text" class="text" name="earning_submit_phone" value="<?php echo dPformSafe( $obj->earning_submit_phone  );?>" size="15" maxlength="15" /></td>
					</tr>
			</table>
			<?php } else { ?>
				<input type="hidden" name="earning_submit_address1" value="">
				<input type="hidden" name="earning_submit_address2" value="">
				<input type="hidden" name="earning_submit_city" value="">
				<input type="hidden" name="earning_submit_state" value="">
				<input type="hidden" name="earning_submit_zip" value="">
				<input type="hidden" name="earning_submit_phone" value="">
			<?php } ?>
		</tr>
		</table>
	</td>
	</tr>
	<tr>
		<td colspan="2">
			<strong><?php echo $AppUI->_('Comments On This Form');?></strong>&nbsp;<i>These comments will be permanently added to the form.  Example: "This is for the Newsweek Project."</i><br/>
			<table cellspacing="0" cellpadding="2" border="0" width="100%">
				<tr>
					<td>
						<input type="text" name="earning_comments" value="<?php echo dpFormSafe($obj->earning_comments); ?>" size="100" maxlength="240">
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<table border="0" cellspacing="0" cellpadding="3" width="100%">
<tr>
	<td height="40" width="35%">
	</td>
	<td height="40" width="30%">&nbsp;</td>
	<td  height="40" width="35%" align="right">
		<table>
		<tr>
			<td>
				<input class="button" type="button" name="btnCancel" value=" cancel " <?php echo dPaction(null, array(), null, array('href' => '?m=earnings&a=view&earning_id=' . (isset($earning_id) ? (int)$earning_id : 0))); ?> />
			</td>
			<td>
				<input class="button" type="button" name="btnSave" value=" save " <?php echo dPaction('submitIt'); ?> />
			</td>
		</tr>
		</table>
	</td>
</tr>
</table>
</form>
</body>
</html>
