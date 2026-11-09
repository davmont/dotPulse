<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}


// this is the edit site for our annotations module
// it is automatically appended on the applications main ./index.php by the dPframework

//some java to use the nice calendar ?>
<link rel="stylesheet" type="text/css" media="all" href="<?php echo DP_BASE_URL;?>/lib/calendar/calendar-dp.css" title="blue" />
<!-- import the calendar script -->
<script type="text/javascript" src="<?php echo DP_BASE_URL;?>/lib/calendar/calendar.js"></script>
<!-- import the language module -->
<script type="text/javascript" src="<?php echo DP_BASE_URL;?>/lib/calendar/lang/calendar-<?php echo $AppUI->user_locale; ?>.js"></script>

<?php

// we check for permissions on this module
$canRead = !getDenyRead( $m );		// retrieve module-based readPermission bool flag
$canEdit = !getDenyEdit( $m );		// retrieve module-based writePermission bool flag

GLOBAL $AppUI;

if (!$canRead) {			// lock out users that do not have at least readPermission on this module
	$AppUI->redirect( "m=public&a=access_denied" );
}

$AppUI->savePlace();	//save the workplace state (have a footprint on this site)

/******************************************************************************************************
**  Need to get annotation_project and annotation_date of annotations to show the proper information:			**
**  get the selected Project AND selected date												**
** if the annotation_id is NOT set, there is no entry in annotations,									**
**			-->new annotation_id (autoincrement) with date & foreign_key (=proj_id)					**
*******************************************************************************************************/

// retrieve GET-Parameters via dPframework
// please always use this way instead of hard code (e.g. there have been some problems with REGISTER_GLOBALS=OFF with hard code)
// Unfortunately $project_id is transmitted via form AND via link, so need post and get:
	$project_id= 			intval( dPgetParam( $_REQUEST, "project_id", "-1" ) );
	$annotation_id= 		intval( dPgetParam( $_REQUEST, "annotation_id", "-1" ) );
	$project_status= 		intval( dPgetParam( $_REQEUST, "project_status", "-1" ) );
	$show_owner_id= 		intval( dPgetParam( $_REQUEST, "show_owner_id", "-1" ) );
	$show_date= 			intval( dPgetParam( $_REQUEST, "show_date", "0" ) );
	$show_time=				dPgetParam( $_REQUEST, "show_time", date("H:i") );
	if (!preg_match('/^\d{1,2}:\d{2}$/', $show_time)) {
		$show_time = date("H:i");
	}  // Dont add seconds --> they will be  added before the storage in storage loop
	$changed_show_date= 	intval( dPgetParam( $_REQUEST, "changed_show_date", "0" ) );
	$annotation_id = 		intval( dPgetParam( $_REQUEST, "annotation_id", "-1" ) );
	// Saving (1) and deleting (-1) change data: POST with the CSRF token and permission only.
	$save_annotations = 0;
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$save_annotations = intval( dPgetParam( $_POST, "save_annotations", "0" ) );
		if ($save_annotations == 1 || $save_annotations == -1) {
			$AppUI->verifyCsrfToken();
			if (!getPermission('annotations', $save_annotations == -1 ? 'delete' : 'edit')) {
				$AppUI->redirect( "m=public&a=access_denied" );
			}
		}
	}
	// Annotations belong to a project: the user must see it, and edit it to save or delete.
	// The annotation's stored project counts, not only the one in the request.
	$annotation_project_of = function ($id) {
		$q = new DBQuery();
		$q->addTable('annotations');
		$q->addQuery('annotation_project');
		$q->addWhere('annotation_id = ' . (int)$id);
		return (int)$q->loadResult();
	};
	$anno_need = ($save_annotations == 1 || $save_annotations == -1) ? 'edit' : 'view';
	$anno_projects = array();
	if ($annotation_id > 0) {
		$anno_projects[] = $annotation_project_of($annotation_id);
	}
	if ($project_id > 0) {
		$anno_projects[] = $project_id;
	}
	foreach ($anno_projects as $anno_project) {
		if ($anno_project > 0 && !getPermission('projects', $anno_need, $anno_project)) {
			$AppUI->redirect( "m=public&a=access_denied" );
		}
	}
	$tab= 					intval( dPgetParam( $_REQUEST, "tab", "-1") );   //came from projects view?
	$addnew= 				intval( dPgetParam( $_REQUEST, "addnew", "0") );   //came from projects view?
	// Presets come from the 'details' table of the Details module when it is installed.
	$details_installed = (bool)db_loadResult("SHOW TABLES LIKE '" . db_escape(dPgetConfig('dbprefix', '') . 'details') . "'");
	$annotation_show_date = intval( dPgetParam( $_REQUEST, "annotation_show_date", $show_date ) );  //Dummy for the calendar

// get the date
$show_date=$annotation_show_date;	
if ($changed_show_date != "") $show_date=$changed_show_date;

// Create the object for our annotations
	require_once( $AppUI->getModuleClass ('annotations') );
	$obj = new CAnnotations;
	// Set the id:
		//project_id && date ?? id is knwon, annos are already in DB
		// !project_id && !date ?? id is 0, this indicates a new row to the "store()" method
		if ( $project_id == "-1" ) $obj->annotation_id = "0";   // Insertion
		if ( $project_id ) $obj->annotation_id = $annotation_id;
		// Get the name of project and the owner, if we are NOT entering a new one:
		if ( $project_id != "-1" ) {
			$q = new DBQuery();
			$q -> addTable('projects');
			$q -> addQuery('project_name');
			$q -> addWhere('project_id=' . (int)$project_id);
			$project_name=$q -> loadResult();
			
			$q -> clear();
			$q -> addTable('contacts');
			$q -> addQuery('contact_first_name,contact_last_name');
			$q -> addWhere('contact_id=( SELECT project_owner FROM ' . dPgetConfig('dbprefix', '') . 'projects WHERE project_id=' . (int)$project_id . ' ) ');
			$tmp_project_owner_name = $q -> loadList();
			$project_owner_name = !empty($tmp_project_owner_name[0])
				? $tmp_project_owner_name[0]['contact_last_name'].", ".substr($tmp_project_owner_name[0]['contact_first_name'],0,1)."."
				: '';
		}

// Get some SysVals:
	$priorities 	= dPgetSysVal( 'ProjectPriority' );
	$points 	= dPgetSysVal( 'OpportunitiesPoints' );
	$points = array("-1" => "-") + $points;
	
// format dates
	$df = $AppUI->getPref('SHDATEFORMAT');            //this is the way dates should be handled! 
	if ($show_date=="0" && $annotation_id > 0) {
		$q = new DBQuery();
		$q->addTable('annotations');
		$q->addQuery('annotation_date');
		$q->addWhere('annotation_id = ' . (int)$annotation_id);
		$show_date = substr($q->loadResult(), 0, 10);
	}
	if ($show_date=="0") $show_date = date('Ymd');
	$oDate = new CDate($show_date); //the Date Object

//Text to show instead of empty cells	default="no information entered yet"
	$empty = "";
	
//spezial state for adding a new (show dropdown Project or not)
	$addnew= ( isset($project_id) && $project_id != "-1" && $addnew != "1" ) ? "0" : "1";

//preselect a Project!! ( in add new mode)
	if ($project_id == "-1" && $addnew == "1") $project_id="1";
	
$q = new DBQuery;   //the query container
$msg="Annotation inserted/altered";	//The OK Message, see line around 129

// Dropdown User/Owner select
// retrieving some content using an easy database query
	$q->addTable('contacts');
	$q->addQuery('contact_id, contact_first_name, contact_last_name');
	$q->addOrder('contact_last_name');
	$sql = $q->prepareSelect( $q );
	$q->clear();
	// pass the query to the database, please consider always using the (still poor) database abstraction layer
	$dboUsers = db_loadList( $sql );		// retrieve a list (in form of an indexed array) of opportunities quotes via an abstract db method
	
	//DropDown User/Owner select
		$pm = array( "-1" => "-" );  //the show_all status
		foreach ($dboUsers as $p) {
				$pm+=array($p['contact_id'] => ($p['contact_last_name'].", ".$p['contact_first_name'][0]."."));	//Fill an array for dP arraySelect
		}
		// show the dropdown filter with dP arraySelect
?>
<?php
		// names for selectTeam() (addedit.js)
		$team_names = array();
		foreach ($pm as $k => $v) {
			if ($v != "-") {
				$team_names[] = $v;
			}
		}
?>
	<?php

if ($save_annotations == "1") {
// Get the informations via bind
	if ( !$obj->bind( $_POST ) ) {	//binds objects data getted via POST
		// Now, get values from the annos table to show
		// When we've already got an entry in the database - --> WELL SHOW IT!!    (otherwise there will be empty fields to fill in some detail)
		die('cant save, no Post data');
	}
	// The posted annotation: in a project the user may edit, and it stays in it.
	if ($obj->annotation_id > 0) {
		$stored_project = $annotation_project_of($obj->annotation_id);
		if (!$stored_project || !getPermission('projects', 'edit', $stored_project)) {
			$AppUI->redirect( "m=public&a=access_denied" );
		}
		$obj->annotation_project = $stored_project;
	}
	// If annotation_must is unset, set it to 0!
		if ( !$obj->annotation_must || $obj->annotation_must != "1" ) $obj->annotation_must = 0;
		if ( !$obj->annotation_flag || $obj->annotation_flag != "1" ) $obj->annotation_flag = 0;
	// Delete if no information was entered in the two fields
	
	// Because more and more fields have been added, even annos without prev or next fields are wanted! Don't delete them any more!
		//if ($obj->annotation_previous=="" && $obj->annotation_next=="") $AppUI->redirect( 'm=annotations&a=addedit$save_annotations=-1&show_date='.$show_date.'&project_id='.$project_id );

	// Save the history for the "6Values" (if changed)
		// because it is an update, the values of "6Values" may have changed! --> store a history
		// compare them bevor storing the eventually new ones:	when they (only one of them) are different -> store to history			
			$q->AddTable('annotations_histories');
			$q->AddInsert('annotation_history_id',"0");
			$q->AddInsert('annotation_history_user_id',$AppUI->user_id);
			$q->AddInsert('annotation_history_annotation',$obj->annotation_id);
			$q->AddInsert('annotation_history_project',$project_id);
			$q->AddInsert('annotation_history_strategy',$obj->annotation_strategy);
			$q->AddInsert('annotation_history_sholders',$obj->annotation_sholders);
			$q->AddInsert('annotation_history_risks',$obj->annotation_risks);
			$q->AddInsert('annotation_history_sizing',$obj->annotation_sizing);
			$q->AddInsert('annotation_history_horizontality',$obj->annotation_horizontality);
			$q->AddInsert('annotation_history_costbenefit',$obj->annotation_costbenefit);
			$q->AddInsert('annotation_history_previous',$obj->annotation_previous);
			$q->AddInsert('annotation_history_next',$obj->annotation_next);
			//$q->AddInsert('annotation_history_date',date("Y-m-d H:i"));	-->on update current timestamp
			$q->AddInsert('annotation_history_scope',$obj->annotation_scope);
			$q->AddInsert('annotation_history_resources',$obj->annotation_resources);
			$q->AddInsert('annotation_history_time',$obj->annotation_time);
			$q->AddInsert('annotation_history_scope_desc',$obj->annotation_scope_desc);
			$q->AddInsert('annotation_history_resources_desc',$obj->annotation_resources_desc);
			$q->AddInsert('annotation_history_time_desc',$obj->annotation_time_desc);
			$q->AddInsert('annotation_history_rationale',$obj->annotation_rationale);
			$q->AddInsert('annotation_history_must',$obj->annotation_must);
			$q->AddInsert('annotation_history_must_rationale',$obj->annotation_must_rationale);
			$q->AddInsert('annotation_history_flag',$obj->annotation_flag);
			$q->AddInsert('annotation_history_subject',$obj->annotation_subject);
			// exec() binds the values (prepareInsert() leaves '?' placeholders).
			$q->exec();
			$q->clear();

	// Store the data
	if (!$obj->annotation_id || $obj->annotation_id=="-1") {	//new value --> insert
		$obj->annotation_id = "0";  //the state to indicate "insertion" to the $object->store(); method
		$obj->annotation_date = substr($show_date,0,4)."-".substr($show_date,4,2)."-".substr($show_date,6,2)." ".$show_time.":".date("s");  // The date for storage
		$obj->annotation_project = $project_id;
		//$obj->annotation_date = $show_date." ".$show_time.":00";
		// Set unsetted values to NULL!!
		if ($obj->annotation_strategy == "-1") $obj->annotation_strategy = NULL;
		if ($obj->annotation_sholders == "-1") $obj->annotation_sholders = NULL;
		if ($obj->annotation_risks == "-1") $obj->annotation_risks = NULL;
		if ($obj->annotation_sizing == "-1") $obj->annotation_sizing = NULL;
		if ($obj->annotation_horizontality == "-1") $obj->annotation_horizontality = NULL;
		if ($obj->annotation_costbenefit == "-1") $obj->annotation_costbenefit = NULL;
		
		$obj->store();  // normal store method, defined in the class....
		$msg="Annotation inserted";	//The OK Message, see line around 129
	} else {	//changed value --> update
		$q->clear();
		$q->addTable('annotations');
		$q->addQuery('annotation_date');
		$q->addWhere('annotation_id = ' . (int)$obj->annotation_id);
		$obj->annotation_date = $q->loadResult();
		//die($obj->annotation_date );
		$obj->store();
		$msg="Annotation altered (ID ".$obj->annotation_id.")";	//The OK Message, see line around 129
	}
	
    $save_annotations = "0";
	$AppUI->setMsg( $msg , UI_MSG_OK);
	
	$addnew = "0";		//extra state to show dropdown or show the project by id whether someone comes from the "addnew" button or an existing Annotation to edit
	if ($tab!="-1") {		//tab is only set when origin was projects module, otherwise got back to annotation. (better with the states??)
		$AppUI->redirect( 'm=projects&a=view&project_id='.$project_id.'&tab='.$tab );
	} else {
		$AppUI->redirect( 'm=annotations&project_id='.$project_id);
	}
	
} ELSEIF ($save_annotations == "-1") {  //the delete State !!
	if ( !$obj->bind( $_POST ) ) {	//binds objects data getted via POST
		// Now, get values from the annos table to show
		// When we've already got an entry in the database - --> WELL SHOW IT!!    (otherwise there will be empty fields to fill in some detail)
		die(' cant delete');
	}
	// The posted annotation: in a project the user may edit, and it stays in it.
	if ($obj->annotation_id > 0) {
		$stored_project = $annotation_project_of($obj->annotation_id);
		if (!$stored_project || !getPermission('projects', 'edit', $stored_project)) {
			$AppUI->redirect( "m=public&a=access_denied" );
		}
		$obj->annotation_project = $stored_project;
	}
	$obj->delete();
	$save_annotations="0";
	$AppUI->setMsg( 'Annotations deleted successfully' , UI_MSG_OK);
	if ($tab!="-1") {		//tab is only set when origin was projects module, otherwise got back to annotation. (better with the states??)
		$AppUI->redirect( 'm=projects&a=view&project_id='.$project_id.'&tab='.$tab );
	} else {
		$AppUI->redirect( 'm=annotations&project_id='.$project_id);
	}
}  // Savings finished. These should be moved to do_annotation_aed.php

// load the data
	if ($obj->annotation_id > "0") {
		if ( !$obj->load( $obj->annotation_id, false ) ) {   // Entering a new one??

			$AppUI->setMsg( 'Annotations' );
			$AppUI->setMsg( "invalid ID : ".$obj->annotation_id, UI_MSG_ERROR, true );
			// Not to the saved place: that can be this page, which redirected to itself.
			$AppUI->redirect('m=annotations&project_id=' . (int)$project_id);
		}
		$show_time = substr($obj->annotation_date,11,5);
	}
	// Added for the lookup in -> detail -> opportunity ( 040609 )
		$obj->annotation_project = $project_id;

//prepare the User Interface Design with the dPFramework
// setup the title block with Name, Icon and Help
$titleBlock = new CTitleBlock( 'Annotations - Edit Page', 'annotations.gif', "annotations", "annotations".$a );	// load the icon automatically from ./modules/annotations/images/
//$titleBlock->addCell();     //adds a cell to the titleBlock
$titleBlock->show();		//finally shows the titleBlock

//Here starts the main part

//get all the projects again for the dropdown
	$q->addTable('projects');
	$q->addQuery('project_id, project_name');
	$q->addOrder('project_name');
	$sql = $q->prepareSelect();
	$q->clear();
	$aProjects = db_loadList( $sql );

	//dropdown project select
	$tmpProj = "";
	foreach ($aProjects as $a) {
		//Preselction of Project, if we are in add new mode:
		// Selects the first Project in List (=Table)!
			if ($addnew=="1" && $project_id=="-1") $project_id=$a['project_id'];

		if ($tmpProj=="") {  //create an array for arraySelect of dP
			$tmpProj = array($a['project_id'] => $a['project_name']);	//if loop runs first time --> create the array
		} else {
			$tmpProj += array($a['project_id'] => $a['project_name']);	//if loop runs NOT first time --> add to the existing array
		}
	}

// If unset, set to "unset" 0, because of the image names ( 0.jpg, 1.jpg, 2.jpg, 3.jpg...)
if ($obj->annotation_scope == "") $obj->annotation_scope=0;
if ($obj->annotation_resources == "") $obj->annotation_resources=0;
if ($obj->annotation_time == "") $obj->annotation_time=0;

	
?>

<!-- --------------------------------------------------------------------------------------------------------------------------------- -->
<!-- -------------------------------------------------- Main table & forms ----------------------------------------------------- -->
<!-- -------------------------------------------------- information table ------------------------------------------------------- -->
<!-- --------------------------------------------------------------------------------------------------------------------------------- -->


<table width="100%" cellpadding="2" cellspacing="1" class="std">

	<tr>
		<?php if ($project_id != "-1" && $addnew != "1") {	// Not in "Add New" mode, so display the actual project, owner, date
			$addnew="0"; ?>
				
			<!-- --------------- the headlines -------------- -->	
			<th align="left"><?php echo $AppUI->_('Project'); ?> : <?php echo dPhtml($project_name); ?></th>
			<th align="left"><?php echo $AppUI->_('Project Owner'); ?> : <?php echo dPhtml($project_owner_name); ?></th>
			<th align="left"><?php echo $AppUI->_('Shown Date'); ?> : <?php echo $oDate->format( $df )." ".$show_time; ?></th>
		<?php
		} else {	// We are in "Add New" mode, so make the project, owner and date selectable
			$addnew="1";  //extra state, if entering a new annotation ->project_id-1
			?>
		<!-- ------------------------ Some Filters ------------------------------- -->
		
			<!-- Select the Project -->
			<form action="?m=annotations&a=addedit&addnew=1" name="chooseProject" method="POST">
				<input type="HIDDEN" name="show_date" value="<?php echo $show_date; ?>">
				<input type="HIDDEN" name="project_id" value="<?php echo $project_id; ?>">
				<input type="HIDDEN" name="annotation_scope" value="<?php echo $obj->annotation_scope; ?>">
				<input type="HIDDEN" name="annotation_resources" value="<?php echo $obj->annotation_resources; ?>">
				<input type="HIDDEN" name="annotation_time" value="<?php echo $obj->annotation_time; ?>">
				<input type="HIDDEN" name="annotation_scope_desc" value="<?php echo dPhtml($obj->annotation_scope_desc); ?>">
				<input type="HIDDEN" name="annotation_resources_desc" value="<?php echo dPhtml($obj->annotation_resources_desc); ?>">
				<input type="HIDDEN" name="annotation_time_desc" value="<?php echo dPhtml($obj->annotation_time_desc); ?>">
				<input type="HIDDEN" name="addnew" value="1">
				<th align="left"><?php echo $AppUI->_('Select Project'); ?> : <?php echo arraySelect( $tmpProj,'project_id','size=1 class=text' . dPaction(null, array(), null, array('submit' => 'chooseProject')),$project_id); ?></th>

			<!-- Select the Date -->

				<th align="left"><?php echo $AppUI->_('Select Date'); ?> : 
<!--				<input type="HIDDEN" name="project_id" value="<?php echo $project_id; ?>">
				<input type="HIDDEN" name="annotation_scope" value="<?php echo $obj->annotation_scope; ?>">
				<input type="HIDDEN" name="annotation_resources" value="<?php echo $obj->annotation_resources; ?>">
				<input type="HIDDEN" name="annotation_time" value="<?php echo $obj->annotation_time; ?>">
				<input type="HIDDEN" name="annotation_scope_desc" value="<?php echo dPhtml($obj->annotation_scope_desc); ?>">
				<input type="HIDDEN" name="annotation_resources_desc" value="<?php echo dPhtml($obj->annotation_resources_desc); ?>">
				<input type="HIDDEN" name="annotation_time_desc" value="<?php echo dPhtml($obj->annotation_time_desc); ?>">
-->				<input type="HIDDEN" name="annotation_show_date" value="<?php echo $oDate->format( FMT_TIMESTAMP_DATE );?>" />
				<input type="HIDDEN" name="addnew" value="1">
				<input type="text" class="text" name="show_date" id="date1" style="text-align: center;" value="<?php echo $oDate->format( $df );?>" class="text" disabled="disabled" />
				<a href="#"<?php echo dPaction('popCalendar', array('show_date')); ?>> 
					<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
				</a>
				&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo $AppUI->_('Select Time')." :"; ?>
					<input type="text" class="text" name="show_time" size="5" style="text-align: center;"<?php echo dPaction(null, array(), 'change', array('submit' => 'chooseProject')); ?> value="<?php echo $show_time; ?>">&nbsp;h
				<!-- Add option to show all entries -->
				
				</th>
			</form>
		<?php
		} ?>
	</tr>
</table>
<!-- ---------------------------------------------------------------------------------------------------------- -->
<!-- -------------------------------------------- Editing table -------------------------------------------- -->
<!-- ---------------------------------------------------------------------------------------------------------- -->
<?php 
// No annotation entered? we are in the add new mode!
if ( $obj->annotation_previous == "" && $obj->annotation_next == "" ) $addnew="1";
// No Annotation entered --> replace with standard text
if (!$obj->annotation_previous)  $obj->annotation_previous= $empty ;  //empty is set on top of this page
if (!$obj->annotation_next)  $obj->annotation_next= $empty; ;		  // it's the text to be shown instead of empty Annotations (default = "")

$tmpdigit = dPgetSysVal( 'AnnotationsPoints' );	 //the dP will get the sysval for us  ( replaces only the db_query for this Sysval)

?>

<table width="100%" cellpadding="2" cellspacing="1" class="tbl">
	<colgroup>
	  <col width="40%">	<!-- previous -->
	  <col width="40%">  <!-- next -->
	  <col width="20%">  <!-- next -->
	</colgroup>
	<form name="editFrm"action="index.php?m=annotations&a=addedit" method="POST" >
		<tr>
			<td colspan="3"><center>
				<?php echo $AppUI->_('Subject')." : "; ?>
				<?php echo '<textarea style="font-size:8pt; width: 70%;" name="annotation_subject" rows="1" wrap="physical">'.dPFormSafe( $obj->annotation_subject ).'</textarea>';	// use the method _($param) of the UIclass $AppUI to translate $param automatically
				?></center><br>
			</td>
		</tr>
		<tr><!-- ------------------------- The Headlines --------------------------- -->
			<td nowrap="nowrap" align="center"><?php echo $AppUI->_( 'Previous' );	// use the method _($param) of the UIclass $AppUI to translate $param automatically
										// please remember this! automatic translation by dP is only possible if all strings
										// are handled like this
			?></td>
			<td nowrap="nowrap" align="center"><?php echo $AppUI->_( 'Next' );	// use the method _($param) of the UIclass $AppUI to translate $param automatically
										// please remember this! automatic translation by dP is only possible if all strings
										// are handled like this
			?></td>
			<td nowrap="nowrap" align="center"><?php echo $AppUI->_( 'Participants' )."&nbsp;&nbsp;".'<input type="button" class="button" value="'.$AppUI->_('select from list').'" name="select_team"' . dPaction('selectTeam', array($team_names, array('title' => $AppUI->_('Contacts', UI_OUTPUT_RAW), 'hint' => $AppUI->_('Click to fill in', UI_OUTPUT_RAW), 'close' => $AppUI->_('Close Window', UI_OUTPUT_RAW)))) . '>';	// use the method _($param) of the UIclass $AppUI to translate $param automatically
										// please remember this! automatic translation by dP is only possible if all strings
										// are handled like this
			?></td>
		</tr>
		<tr>
			<!-- ----------------------------- The Annotations -------------------- -->
			<td nowrap="nowrap" align="center"><?php echo '<textarea style="font-size:8pt; width:100%; box-sizing:border-box;" name="annotation_previous" rows="10" wrap="physical">'.dPFormSafe( $obj->annotation_previous ).'</textarea>';	// use the method _($param) of the UIclass $AppUI to translate $param automatically
										// please remember this! automatic translation by dP is only possible if all strings
										// are handled like this
			?>
			</td>
			<td nowrap="nowrap" align="center"><?php echo '<textarea style="font-size:8pt; width:100%; box-sizing:border-box;" name="annotation_next" rows="10" wrap="physical">'.dPFormSafe( $obj->annotation_next ).'</textarea>';	// use the method _($param) of the UIclass $AppUI to translate $param automatically
										// please remember this! automatic translation by dP is only possible if all strings
										// are handled like this
			?></td>
			<td nowrap="nowrap" align="center"><?php echo '<textarea style="font-size:8pt; overflow-y:auto;" name="annotation_team" cols="20" rows="10" wrap="physical">'.dPFormSafe( $obj->annotation_team ).'</textarea>';	// use the method _($param) of the UIclass $AppUI to translate $param automatically
										// please remember this! automatic translation by dP is only possible if all strings
										// are handled like this
			?></td>
		</tr>
		
</table><br>
<!-- --------- Here comes the Scope, Resources, Time fields ---------------- -->
<table width="100%" cellpadding="2" cellspacing="1" class="tbl">
	<colgroup>
	  <col width="16%">	<!-- scope -->
	  <col width="16%">  <!-- resources -->
	  <col width="16%">  <!-- time -->
	  <col width="16%">  <!-- time -->
	  <col width="16%">  <!-- time -->
	  <col width="16%">  <!-- time -->
	</colgroup>
	<tr><!-- ------------------------- The Headlines --------------------------- -->
		<td colspan="2" nowrap="nowrap" align="center"><?php echo $AppUI->_( 'Scope' );	// use the method _($param) of the UIclass $AppUI to translate $param automatically
									// please remember this! automatic translation by dP is only possible if all strings
									// are handled like this
		?></td>
		<td colspan="2" nowrap="nowrap" align="center"><?php echo $AppUI->_( 'Resources' );	// use the method _($param) of the UIclass $AppUI to translate $param automatically
									// please remember this! automatic translation by dP is only possible if all strings
									// are handled like this
		?></td>
		<td colspan="2" nowrap="nowrap" align="center"><?php echo $AppUI->_( 'Time' );	// use the method _($param) of the UIclass $AppUI to translate $param automatically
									// please remember this! automatic translation by dP is only possible if all strings
									// are handled like this
		?></td>
	</tr><tr><td colspan="6">
	<table width="99%" cellpadding="0" cellspacing="0" class="tbl">
		<colgroup>
			<col width="3%">
			<col width="30%">
			<col width="3%">
			<col width="30%">
			<col width="3%">
			<col width="30%">
		</colgroup>
		<td nowrap="nowrap" align="right">
		<?php
			foreach ($tmpdigit as $k => $v) {
					echo ( $obj->annotation_scope == $k ) ? 
						'<div style="color:'.$v.';">'.dPformSafe($v).'<input type="RADIO" name="annotation_scope" value="'.$k.'" style="color:'.$v.';" checked>'."</div>"
							:
						'<div style="color:'.$v.';">'.dPformSafe($v).'<input type="RADIO" name="annotation_scope" value="'.$k.'" style="color:'.$v.';">'."</div>";
			}

		?></td><td>	
		<?php echo '<textarea style="font-size:8pt; width:100%; box-sizing:border-box;" name="annotation_scope_desc" rows="4" wrap="physical">'.dPFormSafe( $obj->annotation_scope_desc ).'</textarea>';	// use the method _($param) of the UIclass $AppUI to translate $param automatically
									// please remember this! automatic translation by dP is only possible if all strings
									// are handled like this
		?>
		</td><td nowrap="nowrap" align="right">
		<?php //echo arraySelect($tmpdigit,'annotation_resources','size="3" class="text" ', $aAnnotations[0]['annotation_resources'] ) ; ?>
		<?php
			foreach ($tmpdigit as $k => $v) {
					echo ( $obj->annotation_resources == $k ) ? 
						'<div style="color:'.$v.';">'.dPformSafe($v).'<input type="RADIO" name="annotation_resources" value="'.$k.'" style="color:'.$v.';" checked>'."</div>"
							:
						'<div style="color:'.$v.';">'.dPformSafe($v).'<input type="RADIO" name="annotation_resources" value="'.$k.'" style="color:'.$v.';">'."</div>";
			}
		?></td><td>
		<?php echo '<textarea style="font-size:8pt; width:100%; box-sizing:border-box;" name="annotation_resources_desc" rows="4" wrap="physical">'.dPFormSafe( $obj->annotation_resources_desc ).'</textarea>';	// use the method _($param) of the UIclass $AppUI to translate $param automatically
									// please remember this! automatic translation by dP is only possible if all strings
									// are handled like this
		?>
		</td><td nowrap="nowrap" align="right">
		<?php //echo arraySelect($tmpdigit,'annotation_time','size="3" class="text" ', $aAnnotations[0]['annotation_time'] ) ; ?>
		<?php
			foreach ($tmpdigit as $k => $v) {
					echo ( $obj->annotation_time == $k ) ? 
						'<div style="color:'.$v.';">'.dPformSafe($v).'<input type="RADIO" name="annotation_time" value="'.$k.'" style="color:'.$v.';" checked>'."</div>"
							:
						'<div style="color:'.$v.';">'.dPformSafe($v).'<input type="RADIO" name="annotation_time" value="'.$k.'" style="color:'.$v.';">'."</div>";
			}
		?></td><td>
		<?php echo '<textarea style="font-size:8pt; width:100%; box-sizing:border-box;" name="annotation_time_desc" rows="4" wrap="physical">'.dPFormSafe( $obj->annotation_time_desc ).'</textarea>';	// use the method _($param) of the UIclass $AppUI to translate $param automatically
									// please remember this! automatic translation by dP is only possible if all strings
									// are handled like this
		?>
		</td>
		</table></td>
	</tr>
</table><br>

<!--************************************************* And the Rating fields and a description ******************************************************-->
<table width="100%" cellpadding="2" cellspacing="1" class="tbl">
	<tr>	<!-- Here comes the Flag -->
		<td width="50%"><center><?php
			$checked = ( $obj->annotation_flag == "1" ) ? "checked" : "";
			echo '<input type="checkbox" name="annotation_flag" value="1" '.$checked.'  >';
			echo $AppUI->_('Flag this Annotation (e.g. escalation, draw attention, mark)');
		echo '</center></td>';
		
		//<!-- the arrayselect of the right sied of addedit -->
		
		//**************************************************************************************************************************************************************/
		// ********** If there are no Values --> try to get them out of older Annos ( Anno -> Details -> Opps ) ***************************************************************************		
			$presets_from = "No Presets! Values already set."; // String to show, where the preset of the 6V is coming from
			// We need the latest anno ( --> Get all, sort DESC) which is different from this one ( annotation_date != this_annos_date )
				if ( $obj->annotation_project > 0 ) { // only if we've already got a project !
						$q = new DBQuery();
						$q->addTable('annotations');
            $q->addQuery('*');
						$q->addWhere('annotation_project=' . intval($obj->annotation_project));
						$q->addWhere('annotation_date != "' . db_escape($obj->annotation_date) . '"');
						$q->addOrder('annotation_date DESC');
						$preset_values = $q->loadHash();
						$q->clear();
					// If something was altered (and there is an older annotation to take the values from):
					if ($preset_values) {
					if (	$obj->annotation_strategy == NULL ||	$obj->annotation_sholders == NULL ||	$obj->annotation_risks == NULL
						||	$obj->annotation_sizing == NULL ||	$obj->annotation_horizontality == NULL ||	$obj->annotation_costbenefit == NULL)
						$presets_from = "From <a href='index.php?m=annotations&a=addedit&project_id=".$preset_values['annotation_project']."&show_date=".str_replace("-","",$preset_values['annotation_date'])."'>
										Annotations ID-".$preset_values['annotation_id']." Date-".$preset_values['annotation_date']."</a>";					
					// Get all annos sorted by date, so the first one is the newest! And it has to be different to THIS anno's date					
					if ($obj->annotation_strategy == NULL) $obj->annotation_strategy = $preset_values['annotation_strategy'];
					if ($obj->annotation_sholders == NULL) $obj->annotation_sholders = $preset_values['annotation_sholders'];
					if ($obj->annotation_risks == NULL) $obj->annotation_risks = $preset_values['annotation_risks'];
					if ($obj->annotation_sizing == NULL) $obj->annotation_sizing = $preset_values['annotation_sizing'];
					if ($obj->annotation_horizontality == NULL) $obj->annotation_horizontality = $preset_values['annotation_horizontality'];
					if ($obj->annotation_costbenefit == NULL) $obj->annotation_costbenefit = $preset_values['annotation_costbenefit'];
					}
				}
			// ********** If there are no Values --> try to get them out of Details   ( Anno -> Details -> Opps ) ***************************************************************************
					// We need the right detail:   detail_project = annotation_project
					if ( $obj->annotation_project > 0 && $details_installed ) { // if there is an related Project  ( ?? Which one should be taken ??)
							$q = new DBQuery();
							$q->addTable('details');
							$q->addQuery('detail_id,detail_project,detail_strategy,detail_sholders,detail_risks,detail_sizing,detail_horizontality,detail_costbenefit');
							$q->addWhere('detail_project=' . intval($obj->annotation_project));
							$preset_values = $q->loadHash();
							$q->clear();
						// If something was altered:
						if (	$obj->annotation_strategy == NULL ||	$obj->annotation_sholders == NULL ||	$obj->annotation_risks == NULL
							||	$obj->annotation_sizing == NULL ||	$obj->annotation_horizontality == NULL ||	$obj->annotation_costbenefit == NULL)
							$presets_from = "From <a href='index.php?m=projects&a=view&project_id=".$preset_values['detail_project']."'>
											Details ID-".$preset_values['detail_id']."</a>";				
						if ($obj->annotation_strategy == NULL) $obj->annotation_strategy = $preset_values['detail_strategy'];
						if ($obj->annotation_sholders == NULL) $obj->annotation_sholders = $preset_values['detail_sholders'];
						if ($obj->annotation_risks == NULL) $obj->annotation_risks = $preset_values['detail_risks'];
						if ($obj->annotation_sizing == NULL) $obj->annotation_sizing = $preset_values['detail_sizing'];
						if ($obj->annotation_horizontality == NULL) $obj->annotation_horizontality = $preset_values['detail_horizontality'];
						if ($obj->annotation_costbenefit == NULL) $obj->annotation_costbenefit = $preset_values['detail_costbenefit'];
					}
			// If there are still no values --> get them out of opportunities
				// ********** If there are no Values --> try to get them out of opportunities   ( Anno -> Details -> Opps ) ***************************************************************************
					if ($obj->annotation_project > 0) { // if project has already been selected
						// We need the opportunity id.    We can get it, if there is an opportuntiy  which relates to this project (annotation_project)!:
							$q = new DBQuery();
							$q->addTable('opportunities_projects');
							$q->addQuery('opportunity_project_opportunities');
							$q->addWhere('opportunity_project_projects=' . intval($obj->annotation_project));
							$preset_values = $q->loadHash();
							$q->clear();
							if ( $preset_values && isset($preset_values['opportunity_project_opportunities']) ) { // If there is data available
							// If something was altered:
							if (	$obj->annotation_strategy == NULL ||	$obj->annotation_sholders == NULL ||	$obj->annotation_risks == NULL
								||	$obj->annotation_sizing == NULL ||	$obj->annotation_horizontality == NULL ||	$obj->annotation_costbenefit == NULL)
								$presets_from = "From <a href='index.php?m=opportunities&a=addedit&opportunity_id=".$preset_values['opportunity_project_opportunities']."'>
												Opportunities ID-".$preset_values['opportunity_project_opportunities']."</a> PID-".$obj->annotation_project;				
							if ( $preset_values['opportunity_project_opportunities'] > 0 ) { // if there is an related Project  ( ?? Which one should be taken ??)
									$q = new DBQuery();
									$q->addTable('opportunities');
									$q->addQuery('opportunity_strategy,opportunity_sholders,opportunity_risks,opportunity_sizing,opportunity_horizontality,opportunity_costbenefit');
									$q->addWhere('opportunity_id=' . intval($preset_values['opportunity_project_opportunities']));
									$preset_values = $q->loadHash();
									$q->clear();
								if ($obj->annotation_strategy == NULL) $obj->annotation_strategy = $preset_values['opportunity_strategy'];
								if ($obj->annotation_sholders == NULL) $obj->annotation_sholders = $preset_values['opportunity_sholders'];
								if ($obj->annotation_risks == NULL) $obj->annotation_risks = $preset_values['opportunity_risks'];
								if ($obj->annotation_sizing == NULL) $obj->annotation_sizing = $preset_values['opportunity_sizing'];
								if ($obj->annotation_horizontality == NULL) $obj->annotation_horizontality = $preset_values['opportunity_horizontality'];
								if ($obj->annotation_costbenefit == NULL) $obj->annotation_costbenefit = $preset_values['opportunity_costbenefit'];
							}
						}
					}
				//**************************************************************************************************************************************************************/
		include '6Vexplanation.php';	// file in same directory with array for explanation
		// overlib(tip, CAPTION, caption, CENTER|LEFT, WIDTH, width) on mouseover, nd() on mouseout;
		// CAPTION is 1, LEFT 5, CENTER 7 and WIDTH 8 in lib/overlib/overlib-shim.js
		if (!function_exists('annotationsTip')) {
			function annotationsTip($text, $position, $width)
			{
				global $AppUI;
				return dPaction('overlib', array('<p>' . str_replace(array("\r\n"), '<br>', $text) . '</p>',
					1, $AppUI->_('Description', UI_OUTPUT_RAW), $position, 8, $width),
					'mouseover mouseout', array('call-mouseout' => 'nd'));
			}
		}
	echo '<td align="center">';
		echo '<table><tr>';
			echo '<td align="center"' . annotationsTip($explanation['strategy'], 7, 400) . '>';
				echo $AppUI->_('Strategy');
				echo arraySelect( $points, 'annotation_strategy', 'class="text" size="1"', array_search($obj->annotation_strategy, $points) );
			echo '</td>';

			echo '<td align="center"' . annotationsTip($explanation['sholders'], 7, 400) . '>';
			echo $AppUI->_('Sholders');
			echo arraySelect( $points, 'annotation_sholders', 'class="text" size="1"', array_search($obj->annotation_sholders, $points) );
			echo '</td>';
			
			echo '<td align="center"' . annotationsTip($explanation['risks'], 7, 350) . '>';
			echo $AppUI->_('Risks');
			echo arraySelect( $points, 'annotation_risks', 'class="text" size="1"', array_search($obj->annotation_risks, $points) );
			echo '</td>';
			
			echo '<td align="center"' . annotationsTip($explanation['sizing'], 5, 350) . '>';
			echo $AppUI->_('Size');
			echo arraySelect( $points, 'annotation_sizing', 'class="text" size="1"', array_search($obj->annotation_sizing, $points) );
			echo '</td>';
		
			echo '<td align="center"' . annotationsTip($explanation['horizontality'], 5, 350) . '>';
			echo $AppUI->_('Horiz');
			echo arraySelect( $points, 'annotation_horizontality', 'class="text" size="1"', array_search($obj->annotation_horizontality, $points) );
			echo '</td>';
			
			echo '<td align="center"' . annotationsTip($explanation['costbenefit'], 5, 350) . '>';
			echo $AppUI->_('Cost/Bene');
			echo arraySelect( $points, 'annotation_costbenefit', 'class="text" size="1"', array_search($obj->annotation_costbenefit, $points) );
			echo '</td>';
		
		echo '</tr></table>';
	echo '</td>';
 ?>
		</td>
	</tr>
	
	<tr>
		<?php // set $checked, try to get presets from last annos, then from details
			if ( $addnew == "1" ) {		// if we are editing this anno, we don't want to get the preset !!
					$q = new DBQuery();
					$q->addTable('annotations');
          $q->addQuery('*');
					$q->addWhere('annotation_project=' . intval($project_id));
					$q->addOrder('annotation_date DESC');
				// Now get the latest values from anno:
					$prev_values = $q->loadHash();
					$q->clear();
					if (isset($prev_values['annotation_must']) && ($prev_values['annotation_must'] == "1" || $prev_values['annotation_must'] == "0")) {
					$obj->annotation_must = $prev_values['annotation_must'];
				} else if ($details_installed) {
						$q = new DBQuery();
						$q->addTable('details');
            $q->addQuery('*');
						$q->addWhere('detail_project=' . intval($project_id));
						$prev_values = $q->loadHash();
						$q->clear();
						if (isset($prev_values['detail_must']) && ($prev_values['detail_must'] == "1" || $prev_values['detail_must'] == "0")) { $obj->annotation_must = $prev_values['detail_must']; }
					// ELse : there are no presets available
				}
			}
			$checked= ( $obj->annotation_must == "1" ) ? "checked" : "";
		?>
		<td align="center">
			<?php // Get the priority out of project table:
				$q->clear();
				$q->addTable('projects');
				$q->addQuery('project_priority');
				$q->addWhere('project_id = ' . (int)$obj->annotation_project);
				$prio = $q->loadResult();
				if ( $obj->annotation_revised_priority == NULL ) $obj->annotation_revised_priority = $prio;
				// Show Projects Priority and Select Revised Priority
				echo $AppUI->_('Project Priority is')." : <b>".$priorities[$prio]."</b>&nbsp;&nbsp;||&nbsp;&nbsp;".$AppUI->_('Revised Priority')." : "; echo arraySelect( $priorities,'annotation_revised_priority','size=1 class=text ',$obj->annotation_revised_priority); ?>			
				
			<br><input type="checkbox" name="annotation_must" value="1" <?php echo $checked; ?>><?php echo $AppUI->_('This is a')."<b>&nbsp;".$AppUI->_('Must do')."</b>";?></td>
		<td align="center"><?php echo ($presets_from != "") ? "<p style=\"font-size: 10px;\">(".$presets_from.")</p>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" : ""; echo $AppUI->_('Rationale');?></td>
	</tr><tr>
		<td align="center" width="50%"><textarea name="annotation_must_rationale" rows="3" style="width:100%; box-sizing:border-box;">
											<?php echo ($obj->annotation_must_rationale != "") ? dPformSafe($obj->annotation_must_rationale) : "";?></textarea></td>
		<td align="center" width="50%">
			<textarea name="annotation_rationale" rows="3" style="width:100%; box-sizing:border-box;"><?php echo dPformSafe($obj->annotation_rationale);?></textarea>
		</td>
	</tr>	
	
</table>
<!-- **********************************************************************************************************************************-->

<!--  Here come the buttons to cancel delete update -->
<table width="100%" class="tbl">
	<tr>
		<colgroup>
		  <col width="33%">
		  <col width="33%">
		  <col width="33%">
		</colgroup>
	
		<!-- UPDATE BUTTON -->		
		<th>
			<input type="HIDDEN" name="tab" value="<?php echo $tab; ?>">
			<input type="HIDDEN" name="save_annotations" value="1">
			<input type="HIDDEN" name="annotation_id" value="<?php echo $obj->annotation_id; ?>">
			<input type="HIDDEN" name="project_id" value="<?php echo $project_id; ?>">
			<input type="HIDDEN" name="show_owner_id" value="<?php echo $show_owner_id; ?>">
			<input type="HIDDEN" name="project_status" value="<?php echo $project_status; ?>">
			<input type="HIDDEN" name="show_date" value="<?php echo $show_date; ?>">
			<input type="HIDDEN" name="show_time" value="<?php echo $show_time; ?>">
			<center>
			<?php echo '<input  class="button" type="button" value="' . (( $addnew=="1" ) ? ' Add New ' : ' Update ') . '"'
				. dPaction('SubmitIt', array(array('scope' => $AppUI->_('Please enter rational for the scope rating', UI_OUTPUT_RAW),
					'resources' => $AppUI->_('Please enter rational for the resources rating', UI_OUTPUT_RAW),
					'time' => $AppUI->_('Please enter rational for the time rating', UI_OUTPUT_RAW)))) . '>'; ?>
			
			</center>
		</th>
	</form>
	
	<!-- CANCEL BUTTON -->
   <?php
	echo ($tab!="-1") ?		//tab is only set when origin was projects module, otherwise go back to annotation. (better with the states??)
		'<form action="./index.php?m=projects&a=view&project_status='.$project_status.'&show_owner_id='.$show_owner_id.'&project_id='.$project_id.'&tab='.$tab.'" method="POST">'
					:
		'<form action="./index.php?m=annotations&project_status='.$project_status.'&show_owner_id='.$show_owner_id.'&project_id='.$project_id.'&project_id='.$project_id.'" method="POST">';
   ?>	
		<th>
			<input type="HIDDEN" name="project_id" value="<?php echo $project_id; ?>">
			<input type="HIDDEN" name="show_owner_id" value="<?php echo $show_owner_id; ?>">
			<input type="HIDDEN" name="project_status" value="<?php echo $project_status; ?>">
			<input type="HIDDEN" name="show_date" value="<?php echo $show_date; ?>">
			<center>
			<input  class="button" type="SUBMIT" value="Back/Cancel">
			</center>
		</th>
	</form>
	<!-- Delete Button -->
		<th>
			<form action="./index.php?m=annotations&a=addedit" method="POST" name="frmDelete">
				<input type="HIDDEN" name="save_annotations" value="-1">  
				<input type="HIDDEN" name="annotation_id" value="<?php echo $obj->annotation_id; ?>">
				<input type="HIDDEN" name="project_id" value="<?php echo $project_id; ?>">
				<input type="HIDDEN" name="show_owner_id" value="<?php echo $show_owner_id; ?>">
				<input type="HIDDEN" name="project_status" value="<?php echo $project_status; ?>">
				<input type="HIDDEN" name="tab" value="<?php echo $tab; ?>">
				<input type="HIDDEN" name="show_date" value="<?php echo $show_date; ?>">
			</form>
			<center>
				<!--    Doesn't need to be in the form tags, because the java onclick will submit document.frmDelete.submit(); !!!  -->
				<input  class="button" type="SUBMIT" value="<?php echo $AppUI->_('Delete'); ?>"<?php echo dPaction('delIt', array(), null, array('confirm' => $AppUI->_('Do you really want to delete this annotation ?', UI_OUTPUT_RAW))); ?>>		
			</center>
		</th>
	</tr>
</table>


