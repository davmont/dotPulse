<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
// one site for both adding and editing opportunities's quote items
// besides the following lines show the possiblities of the dPframework

// retrieve GET-Parameters via dPframework
// please always use this way instead of hard code (e.g. there have been some problems with REGISTER_GLOBALS=OFF with hard code)
	$opportunity_id = intval( dPgetParam( $_GET, "opportunity_id", 0 ) );
	// Set when the form is opened from a company or a project (prefills them).
	$company_id = intval( dPgetParam( $_GET, "company_id", 0 ) );
	$project_id = intval( dPgetParam( $_GET, "project_id", 0 ) );
	$id_contexts = intval( dPgetParam( $_GET, "id_contexts", 1));
	$show_owner_id= (isset($_POST['show_owner_id'])) ? intval( dPgetParam( $_POST, "show_owner_id", -1)) : intval( dPgetParam( $_GET, "show_owner_id", -1));
	$opportunity_owner= intval(dPgetParam( $_REQUEST, "opportunity_owner", 0));
	$opportunity_sponsor= intval(dPgetParam( $_REQUEST, "opportunity_sponsor", 0));
//	$multisystemselect= (isset($_POST['multisystemselect'])) ? intval( dPgetParam( $_POST, "multisystemselect", 0)) : intval( dPgetParam( $_GET, "multisystemselect", 0));

// check permissions for this record
	$canEdit = !getDenyEdit( $m, $opportunity_id );
	if (!$canEdit) {
		$AppUI->redirect( "m=public&a=access_denied" );
	}

//get the user preferred way to show dates:
	$df = $AppUI->getPref('SHDATEFORMAT');
	$tf = $AppUI->getPref('TIMEFORMAT');

// A query Object
	$q  = new DBQuery;

// get a list of permitted companies
	require_once( $AppUI->getModuleClass ('companies' ) );
	$row = new CCompany();
	$companies = $row->getAllowedRecords( $AppUI->user_id, 'company_id,company_name', 'company_name' );
	$companies = arrayMerge( array( '0'=>'' ), $companies );

// get a list of permitted projects
	require_once( $AppUI->getModuleClass ('projects' ) );
	$rot = new CProject();
	$projects = $rot->getAllowedRecords( $AppUI->user_id, 'project_id,project_name', 'project_name' );
	$projects = arrayMerge( array( '0'=>'' ), $projects );

//Get necessary Sysvals (added by installation of opportunities) // Internal arrays - key, value
	$priorities = dPgetSysVal( 'ProjectPriority' );
	$sizings 	= dPgetSysVal( 'OpportunitiesSizings' );
	$status		= dPgetSysVal( 'OpportunitiesStatus' );
	$points 	= dPgetSysVal( 'OpportunitiesPoints' );
	$yesno 		= dPgetSysVal( 'OpportunitiesYesNo' );
	$ba 		= dPgetSysVal( 'OpportunitiesBA' );
	
	/*  Left for better overview		obsolete( now in sysvals, see above)
	$priorities = array(1 => '-', 2 => 'Must', 3 => 'High', 4 => 'Medium', 5 => 'Low', 6 => 'Option'); // enum with limited options
	$sizings = array(1 => '-', 2 => 'XLarge', 3 => 'Large', 4 => 'Medium', 5 => 'Small', 6 => 'XSmall');
	$contexts = array(1 => '-', 2 => 'Institution', 3 => 'Management', 4 => 'Front Office', 5 => 'Middle Office', 6 => 'Back Office', 7 => 'Support');
	$status = array(1 => 'Open', 2 => 'Analysis', 3 => 'ToProject', 4 => 'Archived');
$points = array(0 => '0', 1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5');
$yesno = array(1 => 'no', 2 => 'yes');
	$pm = array(1 => '-', 2 => 'Abdel', 3 => 'Jose', 4 => 'Monica', 5 => 'Silvia', 6 => 'Sophie');
	$ba = array(1 => '-', 2 => 'FIN', 3 => 'MIS', 4 => 'VC', 5 => 'GS', 6 => 'OTH');
*/

// get the users
	$q->clear();
	$q->addTable('contacts');
	$q->addQuery('contact_id');
	$q->addQuery('contact_first_name');
	$q->addQuery('contact_last_name');
	$q->addOrder('contact_last_name');
	$sql = $q->prepareSelect();
	$Tmp_pm = db_loadList( $sql );
	$pm = array( 0 => "-" );
	foreach ( $Tmp_pm as $t) {
		//if ($pm!="") 
		$pm += array($t['contact_id'] => ( $t['contact_last_name'].", ".$t['contact_first_name'] ));
		//if ($pm=="") $pm = array($t['contact_id'] => ( $t['contact_last_name'].", ".$t['contact_first_name'] ));
	}

// use the object oriented design of dP for loading the quote that should be edited
// therefore create a new instance of the Opportunities Class
// bind the informations (variables) retrieved via post to the opportunities object
	$obj = new COpportunities();

// load the record data in case of that this script is used to edit the quote qith opportunity_id (transmitted via GET)
	if (!$obj->load( $opportunity_id, false ) && $opportunity_id > 0) {
		// show some error messages using the dPFramework if loadOperation failed
		// these error messages are nicely integrated with the frontend of dP
		// use detailed error messages as often as possible
		$AppUI->setMsg( 'Opportunities' );
		$AppUI->setMsg( "invalidID", UI_MSG_ERROR, true );
		$AppUI->redirect();					// go back to the calling location
	}
// Companies link
	if ($opportunity_id == 0 && $company_id > 0) {
		$obj->opportunity_orig = $company_id;
	}
// add in the existing company if, for some reason, it is dis-allowed
	if ($opportunity_id && !array_key_exists( $obj->opportunity_orig, $companies )) {
		$q->addTable('companies');
		$q->addQuery('company_name');
		$q->addWhere('company_id = ' . (int)$obj->opportunity_orig);
		$sql = $q->prepare();
		$q->clear();
		$companies[$obj->opportunity_orig] = db_loadResult($sql);
	}

// unserialize the multiselection array ( for selection ) :
	$multisystemselect = unserialize( (string)$obj->opportunity_ba, array('allowed_classes' => false) );
	if (!is_array($multisystemselect)) {
		$multisystemselect = array();	// none stored yet
	}
	
// Projects link
	if ($opportunity_id == 0 && $project_id > 0) {
		$obj->opportunity_project = $project_id;
	}

// add in the existing project if for some reason it is dis-allowed   //seems to be obsolete
/*
if ($opportunity_id && !array_key_exists( $obj->opportunity_project, $projects )) {
	$q->addTable('projects');
	$q->addQuery('project_name');
	$q->addWhere('project_id = '.$obj->$opportunity_project);
	$sql = $q->prepare();
	$q->clear();
	$projects[$obj->opportunity_project] = db_loadResult($sql);
} */

// check if this record has dependancies to prevent deletion
	$msg = '';
	$canDelete = $obj->canDelete( $msg, $opportunity_id );		// this is not relevant for COpportunities objects
	
// setup the title block
// Fill the title block either with 'Edit' or with 'New' depending on if opportunity_id has been transmitted via GET or is empty
	$ttl = $opportunity_id > 0 ? "Edit Opportunity" : "New Opportunity";
	$titleBlock = new CTitleBlock( $ttl, 'opportunities.png', $m, "$m.$a" );

// also have a breadcrumb here
// breadcrumbs facilitate the navigation within dP as they did for haensel and gretel in the identically named fairytale
	$titleBlock->addCrumb( '?m=opportunities&show_owner_id='.$show_owner_id, "opportunities list" );
	//if ($opportunity_id) $titleBlock->addCrumb( '?m=opportunities&a=vw_childofproject&show_owner_id='.$show_owner_id.'&opportunity_id='.$opportunity_id, 'edit related projects' );

if ($canEdit && $opportunity_id > 0) {
	$titleBlock->addCrumbDelete( 'delete opportunity', $canDelete, $msg );	// please notice that the text 'delete quote' will be automatically
																			// prepared for translation by the dPFramework
}
$titleBlock->show();

// some javaScript code to submit the form and set the delete object flag for the form processing
?>
<form name="editRelProj" method="POST" action="index.php?m=opportunities&a=vw_childofproject">
	<input type="hidden" name="opportunity_id" value="<?php echo (int)$opportunity_id; ?>">
</form>
<form name="ChangedValuesDummy" method="POST">	<!-- dummy to use the values in java also -->
	<input type="hidden" name="opportunity_orig" 		value="<?php echo dPformSafe($obj->opportunity_orig); ?>" >
	<input type="hidden" name="opportunity_desc" value="<?php echo dPformSafe($obj->opportunity_desc); ?>" >
	<input type="hidden" name="opportunity_must" 	value="<?php echo dPformSafe($obj->opportunity_must); ?>" >
	<input type="hidden" name="opportunity_must_rationale" 	value="<?php echo dPformSafe($obj->opportunity_must_rationale); ?>" >
	<input type="hidden" name="opportunity_boundaries" 	value="<?php echo dPformSafe($obj->opportunity_boundaries); ?>" >
	<input type="hidden" name="opportunity_curway" 		value="<?php echo dPformSafe($obj->opportunity_curway); ?>" >
	<input type="hidden" name="opportunity_preyn" 		value="<?php echo dPformSafe($obj->opportunity_preyn); ?>" >
	<input type="hidden" name="opportunity_precost" 	value="<?php echo dPformSafe($obj->opportunity_precost); ?>" >
	<input type="hidden" name="opportunity_preout" 		value="<?php echo dPformSafe($obj->opportunity_preout); ?>" >
	<input type="hidden" name="opportunity_comments" 	value="<?php echo dPformSafe($obj->opportunity_comments); ?>" >
	<input type="hidden" name="opportunity_strategy" 	value="<?php echo dPformSafe($obj->opportunity_strategy); ?>" >
	<input type="hidden" name="opportunity_sholders" 	value="<?php echo dPformSafe($obj->opportunity_sholders); ?>" >
	<input type="hidden" name="opportunity_risks" 		value="<?php echo dPformSafe($obj->opportunity_risks); ?>" >
	<input type="hidden" name="opportunity_sizing" 		value="<?php echo dPformSafe($obj->opportunity_sizing); ?>" >
	<input type="hidden" name="opportunity_horizontality" value="<?php echo dPformSafe($obj->opportunity_horizontality); ?>" >
	<input type="hidden" name="opportunity_costbenefit" value="<?php echo dPformSafe($obj->opportunity_costbenefit); ?>" >
	<input type="hidden" name="opportunity_status" 		value="<?php echo dPformSafe($obj->opportunity_status); ?>" >
	<input type="hidden" name="opportunity_owner" 		value="<?php echo dPformSafe($obj->opportunity_owner); ?>" >
	<input type="hidden" name="opportunity_sponsor" 	value="<?php echo dPformSafe($obj->opportunity_sponsor); ?>" >
	<input type="hidden" name="opportunity_rationale" 	value="<?php echo dPformSafe($obj->opportunity_rationale); ?>" >
	<input type="hidden" name="opportunity_background" 	value="<?php echo dPformSafe($obj->opportunity_background); ?>" >
</form>
<script language="javascript" nonce="<?php echo dPcspNonce(); ?>">
	var error=0;
	var answer=false;	

	function submitIt() {

		var f = document.editFrm;
		error=0;
		if (isNaN(f.opportunity_precost.value)) {
			alert('Engaged Cost must be a number');	
			error=1;
		}
		if (f.opportunity_name.value == "" ) {
			alert('Please enter a name for this opportunity');
			error=1;
		}
		if ((f.opportunity_orig.value == "") || (f.opportunity_orig.value == "0" )) {
			alert('Please select an originator');
			error=1;
		}
		var rationale = '';
		rationale = rationale + f.opportunity_must_rationale.value;
		if ( f.opportunity_must.checked == true && rationale.replace(/^\s+|\s+$/g, '') == "" ) {
			error=1;
			alert('Please enter a rationale for a "Must Do"');
		}
		if (error==0) {
			f.submit();
		}
	}

	function delIt() {
		if (confirm( <?php echo dPjs($AppUI->_('Really delete this object?', UI_OUTPUT_RAW)); ?> )) {	// notice that we prepare for translation here
			var f = document.editFrm;
			f.del.value='1';
			f.submit();
		}
	}

	function IsNumeric() {
		if (isNaN(document.editFrm.opportunity_precost.value)) {
			document.editFrm.opportunity_precost.select();
			alert('Must be a Number');
		}
	}

	function comparison() {
		var f = document.editFrm;
		var o = document.ChangedValuesDummy;
		var rationale = '';
		rationale = rationale + f.opportunity_must_rationale.value;
		var orationale = '';
		orationale = orationale + o.opportunity_must_rationale.value;
		
		if (f.opportunity_orig.value != o.opportunity_orig.value
			|| f.opportunity_desc.value != o.opportunity_desc.value || f.opportunity_must.checked != o.opportunity_must.checked
			|| rationale.replace(/^\s+|\s+$/g, '') != orationale.replace(/^\s+|\s+$/g, '')
			|| f.opportunity_boundaries.value != o.opportunity_boundaries.value || f.opportunity_curway.value != o.opportunity_curway.value
			|| f.opportunity_preyn.value != o.opportunity_preyn.value || f.opportunity_precost.value != o.opportunity_precost.value
			|| f.opportunity_preout.value != o.opportunity_preout.value || f.opportunity_comments.value != o.opportunity_comments.value
			|| f.opportunity_strategy.value != o.opportunity_strategy.value
			|| f.opportunity_sholders.value != o.opportunity_sholders.value || f.opportunity_risks.value != o.opportunity_risks.value
			|| f.opportunity_sizing.value != o.opportunity_sizing.value || f.opportunity_horizontality.value != o.opportunity_horizontality.value
			|| f.opportunity_costbenefit.value != o.opportunity_costbenefit.value
			|| f.opportunity_costbenefit.value != o.opportunity_costbenefit.value
			|| f.opportunity_status.value != o.opportunity_status.value
			|| f.opportunity_owner.value != o.opportunity_owner.value || f.opportunity_sponsor.value != o.opportunity_sponsor.value
			|| f.opportunity_background.value != o.opportunity_background.value ) {
			return 1;
		}
		return 0;
	}
	
	function CheckForInputRelProjs() {
		error = comparison();
		if (error==1) { answer = confirm("You're leaving the page without saving. Are you Sure?"); }
		if (error==0 || answer==true) { document.editRelProj.submit(); }
	}
</script>

<!-- main Table and Overview -->
<!-- Here comes the fields off all features of an Opportunity -->
<table cellspacing="0" cellpadding="4" border="0" width="100%" class="std">
<form name="editFrm" action="./index.php?m=opportunities&a=addedit&show_owner_id=<?php echo $show_owner_id; ?>&opportunity_id=<?php echo (int)$obj->opportunity_id; ?>" method="post">
	<input type="hidden" name="something_changed" value="0" />
	<input type="hidden" name="show_owner_id" value="<?php echo $show_owner_id; ?>" />
	<input type="hidden" name="dosql" value="do_opportunities_aed" />
	<input type="hidden" name="del" value="0" />
	<input type="hidden" name="opportunity_id" value="<?php echo (int)$opportunity_id;?>" />
	<tr>
		<td>
			<input class="button" type="button" name="canceltop" value="<?php echo $AppUI->_('cancel');?>"<?php echo dPaction(null, array(), null, array('confirm' => 'Are you sure you want to cancel.', 'href' => './index.php?m=opportunities&show_owner_id=' . (int)$show_owner_id)); ?> />
		</td>
		<td align="right">
			<input class="button" type="button" name="btnFuseActiontop" value="<?php echo $AppUI->_('submit');?>"<?php echo dPaction('submitIt'); ?> />
		</td>
	</tr>
<tr>
	<td width="50%" valign="top">
		<table cellspacing="0" cellpadding="2" border="0">

		<tr>
			<td>
			<hr>
			</td>
			<td>
			<hr>
			</td>
		</tr>

		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Opportunity');?></td>
			<td width="100%">
			<input type="text" name="opportunity_name" value="<?php echo dPformSafe($obj->opportunity_name);?>" size="60" maxlength="60" class="text" style="width:100%; box-sizing:border-box" />
			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Originator');?></td>
			<td width="100%" nowrap="nowrap">
			<?php 
				echo arraySelect( $companies, 'opportunity_orig', 'name="opportunity_orig" class="text" size="1"', $obj->opportunity_orig );
			?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap">
			<?php 	echo $AppUI->_('Sponsor');
				echo '</td><td>'.arraySelect( $pm, 'opportunity_sponsor', 'class="text" size="1"', $obj->opportunity_sponsor);
					echo $AppUI->_('Leader').arraySelect( $pm, 'opportunity_owner', 'class="text" size="1"', $obj->opportunity_owner);
			?>
			</td>
		</tr>
		<tr><td>&nbsp;</td><td>
			<?php $checked = ($obj->opportunity_must == "1") ? "checked" : ""; ?>
			<input type="checkbox" value="1" name="opportunity_must" <?php echo $checked; ?>><?php echo $AppUI->_('This Opportunity is a')." <b>".$AppUI->_('Must Do')."</b>"; ?>

			<?php echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$AppUI->_('Priority');?>
			
			<?php 
			echo arraySelect( $priorities, 'opportunity_priority', 'class="text" size="1"', $obj->opportunity_priority );
			//echo $AppUI->_('Context');
			//echo arraySelect( $contexts, 'opportunity_context', 'class="text" size="1"', $obj->opportunity_context);
			?>
			</td>
		</tr>
		<tr><td align="right" nowrap="nowrap"> <?php echo $AppUI->_('Rationale');?></td>
			<td width="100%">
				<textarea type="TEXT" name="opportunity_must_rationale" cols="70" style="width:100%; box-sizing:border-box" rows="4"><?php echo dPformSafe($obj->opportunity_must_rationale); ?></textarea>
			</td>
		</tr>

		<!--- need a extra row to improve the look-alike -->
			<tr><td>&nbsp;</td></tr>
		<tr>
			<td>
			<hr>
			</td>
			<td>
			<hr>
			</td>
		</tr>
		
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Description');?></td>
			<td width="100%">
			<textarea name="opportunity_desc" cols="70" style="width:100%; box-sizing:border-box; height:100px; font-size:8pt"><?php echo dPformSafe($obj->opportunity_desc); ?></textarea>
			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Boundaries');?></td>
			<td width="100%">
			<textarea name="opportunity_boundaries" cols="70" style="width:100%; box-sizing:border-box; height:50px; font-size:8pt"><?php echo dPformSafe($obj->opportunity_boundaries);?></textarea>
			</td> 
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Background');?></td>
			<td width="100%">
			<textarea name="opportunity_background" cols="70" style="width:100%; box-sizing:border-box; height:50px; font-size:8pt"><?php echo dPformSafe($obj->opportunity_background);?></textarea>
			</td> 
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Current Way');?></td>
			<td width="100%">
			<textarea name="opportunity_curway" cols="70" style="width:100%; box-sizing:border-box; height:50px; font-size:8pt"><?php echo dPformSafe($obj->opportunity_curway);?></textarea>
			</td>
		</tr>
		<tr>
			<td>
			<hr>
			</td>
			<td>
			<hr>
			</td>
		</tr>

		<tr>
			<td align="right" nowrap="nowrap"> <?php echo $AppUI->_('Status');?></td>
			<td width="100%">
			<?php 
			echo arraySelect( $status, 'opportunity_status', 'class="text" size="1"', $obj->opportunity_status );
			?> 
		<!-- </tr> -->

			<?php echo $AppUI->_('Analyst');?>
			<?php 
			echo arraySelect( $pm, 'opportunity_pm', 'class="text" size="1"', $obj->opportunity_pm );
			?> 
		</tr>
		
		<tr>
			<td>
			<hr>
			</td>
			<td>
			<hr>
			</td>
		</tr>
		
		</table>
	</td>
	
	<!-- right side of screen -->
	
	<td width="50%" valign="top">
		<table cellspacing="0" cellpadding="2" border="0">

		<tr> <!-- adding spacers -->
			<td>
			<hr>
			</td>
			<td>
			<hr>
			</td>
		</tr>
		<tr><td>&nbsp;</td><td><table><tr>
		<?php
			include '6Vexplanation.php';
			echo '<td nowrap="nowrap"' . dPaction('overlib', array('<p>' . str_replace(array("\r\n"), '<br>', $explanation['strategy']) . '</p>', 1, $AppUI->_('Description', UI_OUTPUT_RAW), 7, 8, 400), 'mouseover mouseout', array('call-mouseout' => 'nd')) . '>';			
				echo $AppUI->_('Strategy');
				echo arraySelect( $points, 'opportunity_strategy', 'class="text" size="1"', array_search($obj->opportunity_strategy, $points) );
			echo '</td>';
			
			echo '<td nowrap="nowrap"' . dPaction('overlib', array('<p>' . str_replace(array("\r\n"), '<br>', $explanation['sholders']) . '</p>', 1, $AppUI->_('Description', UI_OUTPUT_RAW), 7, 8, 400), 'mouseover mouseout', array('call-mouseout' => 'nd')) . '>';
				echo $AppUI->_('Sholders');
				echo arraySelect( $points, 'opportunity_sholders', 'class="text" size="1"', array_search($obj->opportunity_sholders, $points) );
			echo '</td>';
			
			echo '<td nowrap="nowrap"' . dPaction('overlib', array('<p>' . str_replace(array("\r\n"), '<br>', $explanation['risks']) . '</p>', 1, $AppUI->_('Description', UI_OUTPUT_RAW), 7, 8, 350), 'mouseover mouseout', array('call-mouseout' => 'nd')) . '>';
				echo $AppUI->_('Risks');
				echo arraySelect( $points, 'opportunity_risks', 'class="text" size="1"', array_search($obj->opportunity_risks, $points) );			
			echo '</td>';
			echo '</tr><tr>'; // three per row: six in one row do not fit a 1024 px window

			echo '<td nowrap="nowrap"' . dPaction('overlib', array('<p>' . str_replace(array("\r\n"), '<br>', $explanation['sizing']) . '</p>', 1, $AppUI->_('Description', UI_OUTPUT_RAW), 5, 8, 350), 'mouseover mouseout', array('call-mouseout' => 'nd')) . '>';
				echo $AppUI->_('Size');
				echo arraySelect( $points, 'opportunity_sizing', 'class="text" size="1"', array_search($obj->opportunity_sizing, $points) );
			echo '</td>';
			
			echo '<td nowrap="nowrap"' . dPaction('overlib', array('<p>' . str_replace(array("\r\n"), '<br>', $explanation['horizontality']) . '</p>', 1, $AppUI->_('Description', UI_OUTPUT_RAW), 5, 8, 350), 'mouseover mouseout', array('call-mouseout' => 'nd')) . '>';
				echo $AppUI->_('Horiz');
				echo arraySelect( $points, 'opportunity_horizontality', 'class="text" size="1"', array_search($obj->opportunity_horizontality, $points) );
			echo '</td>';
			
			echo '<td nowrap="nowrap"' . dPaction('overlib', array('<p>' . str_replace(array("\r\n"), '<br>', $explanation['costbenefit']) . '</p>', 1, $AppUI->_('Description', UI_OUTPUT_RAW), 5, 8, 350), 'mouseover mouseout', array('call-mouseout' => 'nd')) . '>';
				echo $AppUI->_('Benefit/Cost');
				echo arraySelect( $points, 'opportunity_costbenefit', 'class="text" size="1"', array_search($obj->opportunity_costbenefit, $points) );
			echo '</td>';
		echo '</tr></table>';
		?></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Rationale');?></td>
			<td width="100%">
			<?php
			// the following textarea will either be empty (new quote) or contain the quotes content (edit)
			?>
			<textarea name="opportunity_rationale" cols="70" style="width:100%; box-sizing:border-box; height:50px; font-size:8pt"><?php echo dPformSafe($obj->opportunity_rationale);?></textarea>
			</td>
		</tr>
		<tr>
			<td>
			<hr>
			</td>
			<td>
			<hr>
			</td>
		</tr>

		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Pre-study');?></td>
			<td width="100%">
			<?php
			echo arraySelect( $yesno, 'opportunity_preyn', 'class="text" size="1"', $obj->opportunity_preyn );
			echo $AppUI->_('Engaged cost');
			?>
			<input type="text" name="opportunity_precost" value="<?php echo dPformSafe($obj->opportunity_precost);?>" size="30" maxlength="30"<?php echo dPaction('IsNumeric', array(), 'blur'); ?> class="text" />
			</td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('PreStudy Outcome');?></td>
			<td width="100%">
			<textarea name="opportunity_preout" cols="70" style="width:100%; box-sizing:border-box; height:50px; font-size:8pt"><?php echo dPformSafe($obj->opportunity_preout);?></textarea>
			</td>
		</tr>
		
		<tr>
			<td>
			<hr>
			</td>
			<td>
			<hr>
			</td>
		</tr>
		
		<tr><td>&nbsp;</td><td align="left" nowrap="nowrap"><?php echo $AppUI->_('System'); echo "&nbsp;&nbsp;&nbsp;&nbsp;".$AppUI->_('Related Projects'); ?> </td></tr>
		<tr><td align="right" nowrap="nowrap">&nbsp;</td><td>
			<?php 			
			// the selection for the system
				echo '<select name="multisystemselect[]" size="7" style="width:50px;" multiple="multiple">';  //[] to show php that this will be an array ! !
				foreach ($ba as $key => $value) {
					$didselect = 0;									
					foreach ($multisystemselect as $m) {
						if ($m == $key) {
							echo '<option value="'.dPhtml($key).'" selected>'.dPhtml($value).'</option>';
							$didselect = 1;
							continue 2;				
						}
					}
					if ($didselect == 0) echo '<option value="'.dPhtml($key).'">'.dPhtml($value).'</option>';
				}
				echo '</select>'; 
				
				//echo arraySelect( $ba, 'multisystemselect[]', 'class="text" size="4" multiple="multiple"', $multisystemselect );
			// The selection for related Projects
				// get all related projects:
					$q->clear();
					$q->addTable('opportunities_projects');
					$q->addQuery('opportunity_project_projects');
					$q->addWhere('opportunity_project_opportunities = ' . (int)$opportunity_id);
					$aRelated = $q->loadList();
					$q->clear();
				$i = 0;
				echo '<select type="TEXT" name="EmptyName" size="7" style="width:100%; max-width:360px; box-sizing:border-box; color: #A0A0A0; " MULTIPLE="MULTIPLE"' . dPaction('CheckForInputRelProjs', array(), 'click') . '>';
					foreach ($aRelated as $r) {
						echo '<option value="'.(int)$r['opportunity_project_projects'].'">'.dPhtml(@$projects[$r['opportunity_project_projects']]).'</option>';
						$i++;
					}
					if ( $i <= 0 ) echo '<option value="s">click to add</option>';
				echo '</select>'; 
				
			?></td>
			</tr>

		<tr>
			<td>
			<hr>
			</td>
			<td>
			<hr>
			</td>
		</tr>

			
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Observations');?></td>
			<td width="100%">
			<textarea name="opportunity_comments" cols="70" style="width:100%; box-sizing:border-box; height:50px; font-size:8pt"><?php echo dPformSafe($obj->opportunity_comments);?></textarea>
			</td>
		</tr>

		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Proposal');?></td>
			<td width="100%">
			<textarea name="opportunity_proposal" cols="70" style="width:100%; box-sizing:border-box; height:50px; font-size:8pt"><?php echo dPformSafe($obj->opportunity_proposal);?></textarea>
			</td>
		</tr>
			
		<tr>
			<td>
			<hr>
			</td>
			<td>
			<hr>
			</td>
		</tr>		
		<tr>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Creation Date');?></td>
			<td width="100%">
			<?php $CreationDate= ($opportunity_id=="0") ? new CDate(date('Ymd')) : new CDate($obj->opportunity_created); ?>
			<input type="hidden" name="opportunity_created" value="<?php echo $CreationDate->format( FMT_TIMESTAMP_DATE );?>" size="25" maxlength="25" class="text" />
			<input style="text-align:center;" type="text" disabled="disabled" name="opportunity_created_only_to_show_here" value="<?php echo $CreationDate->format( $df );?>" size="25" maxlength="25" class="text" />
			<?php echo $AppUI->_('Last Modified');
				//will be updated via the mysql -> table_field is "ON UPDATE CURRENT TIMESTAMP"
			$lastUpdate = new CDate($obj->opportunity_lastupd);
			echo ($obj->opportunity_lastupd=="") ?
				'<input style="text-align:center;" type="text" disabled="disabled" name="opportunity_lastupd" value="'.$CreationDate->format( $df ).'" size="25" maxlength="25" class="text" />'
					:
				'<input style="text-align:center;" type="text" disabled="disabled" name="opportunity_lastupd" value="'.$lastUpdate->format( $df ).", ".$lastUpdate->format( $tf ).'" size="25" maxlength="25" class="text" />';
			?>	
			</td>
		</tr>
		<tr>
			<td>
			<hr>
			</td>
			<td>
			<hr>
			</td>
		</tr>

		
		</table>
	</td>
</tr>
<tr>
	<td>
		<input class="button" type="button" name="btnFuseAction" value="<?php echo $AppUI->_('submit');?>"<?php echo dPaction('submitIt'); ?> />
	</td>
	<td align="right">
		<input class="button" type="button" name="cancel" value="<?php echo $AppUI->_('cancel');?>"<?php echo dPaction(null, array(), null, array('confirm' => 'Are you sure you want to cancel.', 'href' => './index.php?m=opportunities&show_owner_id=' . (int)$show_owner_id)); ?> />
	</td>
</tr>

</table>
</form>

<?php
//by cK

//If opportunity_id=="" we are in  "edit NEW opps" --> Thus don't show related projects
if ($opportunity_id) {

/*****************************************************************************************************************************
**	List all the projects ...
**
*****************************************************************************************************************************/
//The DB Query
	$opportunity_id = intval( dPgetParam( $_GET, "opportunity_id", 0 ) );

	$q->clear();
//get all projects	
	$q->addTable('projects');
	$q->addQuery('project_id, project_name, project_owner, project_description, project_company, project_start_date, project_end_date');
	$q->addQuery('project_status, project_color_identifier, project_percent_complete');
//join opportunities
	$q->leftJoin('opportunities_projects','op','(project_id=opportunity_project_projects AND opportunity_project_opportunities='.$opportunity_id.')');
	$q->addQuery('opportunity_project_projects, opportunity_project_description');
//join companies
	$q->leftJoin('companies','co','(company_id=project_company)');
	$q->addQuery('company_name, company_id, company_description');
//join users (for owner names)
	$q->leftJoin('contacts','us','(contact_id=project_owner)');
	$q->addQuery('contact_first_name, contact_last_name');
// make the SQL statement
	$sql = $q->prepareSelect();
	$aProjects = db_loadList( $sql );
	$q->clear();

//get the project progress (because its not project percentage complete out of projekts table
	$q->addTable('tasks','t');
	$q->addQuery('t.task_project');
	//$q->addQuery('t.task_percent_complete');
	//$q->addQuery('t.task_duration_type');
	//$q->addQuery('t.task_id');
	//$q->addQuery('t.task_parent');
	$q->addQuery('SUM( t.task_duration * t.task_percent_complete * IF( t.task_duration_type =24, 8.0, t.task_duration_type ) ) / SUM( t.task_duration * IF( t.task_duration_type =24, 8.0, t.task_duration_type ) ) AS project_percent_complete');
	$q->addQuery('SUM( t.task_duration * IF( t.task_duration_type =24, 8.0, t.task_duration_type ) ) AS project_duration');
	$q->addWhere('t.task_id = t.task_parent');
	$q->addGroup('t.task_project');

	$sql = $q->prepareSelect( $q );
	$q->clear();
	$aComplete = db_loadList( $sql );
	
//getting the SysVal "States" to use it with the arrayselect command  -->
	$ProjectStatus = dPgetSysVal( 'ProjectStatus' );
?>

<!--   Main Table -->
<table cellspacing="0" cellpadding="4" border="1" width="100%" class="tbl">
	<colgroup>
		<col width="2%"> <!-- color identifier -->
		<col width="10%"> <!-- Company -->
		<col width="20%"> <!-- Project name -->
		<col width="28%"> <!-- Relation Desc -->
		<col width="5%"> <!-- Project start -->
		<col width="5%"> <!-- Project end -->
		<col width="10%"> <!-- Project owner -->
		<col width="5%"> <!-- Project status-->
	</colgroup>
	
	<tr>
		<th colspan="9"> <?php echo $AppUI->_('Related Project(s):'); ?> </th>

	</tr>
	
	<tr>
		<th> <?php echo $AppUI->_('Color'); ?> </th>
		<th> <?php echo $AppUI->_('Company'); ?> </th>
		<th> <?php echo $AppUI->_('Project'); ?> </th>
		<th> <?php echo $AppUI->_('Relation Description'); ?> </th>
		<th> <?php echo $AppUI->_('Start'); ?> </th>
		<th> <?php echo $AppUI->_('End'); ?> </th>
		<th> <?php echo $AppUI->_('Owner'); ?> </th>
		<th> <?php echo $AppUI->_('Status'); ?> </th>
	</tr>
	

	<input type="HIDDEN" name="lastProjID" value="<?php echo count($aProjects); ?>">
	<?php
		foreach ($aProjects as $p) {
			
			$start_date = new CDate($p['project_start_date']);
			$end_date = new CDate($p['project_end_date']);
			if ($p['opportunity_project_projects']) {
				echo '<tr>';
				// The Project color and progress	
					echo '<td bgcolor="#'.dPhtml($p['project_color_identifier']).'"><center>';	
						$inserted=0;
						foreach ($aComplete as $c) {
							if ($c['task_project'] == $p['project_id']) {
								echo sprintf('%.1f%%', $c['project_percent_complete']);	// use the method _($param) of the UIclass $AppUI to translate $param automatically
							$inserted=1;
							break;
							}
						}
						if ($inserted==0) echo sprintf('%.1f%%', '0');						
					echo '</center></td>';
				//End Color & Progress
				// the company names with link and description:
					echo '<td>';
						echo '<a href="?m=companies&a=view&company_id='.(int)$p['company_id'].'"'
						   . dPaction('overlib', array('<div><p>' . str_replace(array("\r\n", "\n", "\r"), '</p><p>', dPhtml($p['company_description'])) . '</p></div>', 1, $AppUI->_('Description', UI_OUTPUT_RAW), 7), 'mouseover mouseout', array('call-mouseout' => 'nd'))
						   . '>'.dPFormSafe( $p['company_name'] ).'</a>';	// use the method _($param) of the UIclass $AppUI to translate $param automatically
					echo '</td>';				

				// the projects with link and description			
					echo '<td>';
						echo '<a href="?m=projects&a=view&project_id='.(int)$p['project_id'].'"'
						   . dPaction('overlib', array('<div><p>' . str_replace(array("\r\n", "\n", "\r"), '</p><p>', dPhtml($p['project_description'])) . '</p></div>', 1, $AppUI->_('Description', UI_OUTPUT_RAW), 7), 'mouseover mouseout', array('call-mouseout' => 'nd'))
						   . '>'.dPFormSafe( $p['project_name'] ).'</a>';	// use the method _($param) of the UIclass $AppUI to translate $param automatically
					echo '</td>';
				// The description of the relation ( between Proj and Opps ) 
					if ($p['opportunity_project_description']) {
						echo '<td>'.dPFormSafe(substr($p['opportunity_project_description'],0,100));
							echo (strlen(dPFormSafe($p['opportunity_project_description'])) >100 ) ? ' (...)' : '';
						echo '</td>';
					} else {
						echo "<td>&nbsp;</td>"; 
					}

					echo ($p['project_start_date']) ? '<td>'.$start_date->format( $df ).'</td>' : '<td>&nbsp;</td>' ;
					echo ($p['project_end_date'])   ? '<td>'.$end_date->format( $df ).'</td>'   : '<td>&nbsp;</td>' ;
					echo '<td>'.dPhtml($p['contact_last_name']).', '.dPhtml(substr($p['contact_first_name'],0,1)).'.'.'</td>';
					echo '<td>'.dPhtml(@$ProjectStatus[$p['project_status']]).'</td>';
				echo '</tr>';
			}
		}
	?>
</table>

<?php
} //end if
?>
