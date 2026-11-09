<?php /* PROJECTS $Id: addedit.php 5433 2007-10-17 15:02:46Z gregorerhardt $ */
if (!defined('DP_BASE_DIR')){
	die('You should not access this file directly');
}

$pma_id = dPgetIntParam($_GET, 'pma_id', 0);
$project_name = (string)dPgetParam($_GET, 'project_name', '');


$perms =& $AppUI->acl();
// check permissions for this record
$canEdit = $perms->checkModuleItem($m, 'edit', $pma_id);
$canAuthor = $perms->checkModuleItem($m, 'add');
if ((!$canEdit && $pma_id > 0) || (!$canAuthor && $pma_id == 0)) {
	$AppUI->redirect('m=public&a=access_denied');
}

// load the record data
$row = new CClosure();

if ($pma_id > 0 && !$row->load($pma_id)) {
	$AppUI->setMsg('Post Mortem');
	$AppUI->setMsg('invalidID', UI_MSG_ERROR, true);
//	$AppUI->redirect();
}

$df = $AppUI->getPref('SHDATEFORMAT');
$participants = '';
if($pma_id == 0){
 $q  = new DBQuery;
 $q->addTable('projects');
 $q->addQuery('project_id, project_target_budget, project_start_date, project_end_date, project_name');
 $q->addWhere('project_name=' . $q->quote($project_name));
 $res =& $q->exec();
 // Only a project the user may edit, as saving the post mortem requires.
 if ($res && !$res->EOF && getPermission('projects', 'edit', (int)$res->fields['project_id'])) {
 $row->planned_budget = $res->fields['project_target_budget'];
 $row->project_planned_start_date = $res->fields['project_start_date'];
 $row->project_planned_end_date = $res->fields['project_end_date'];
 $row->project_name = $res->fields['project_name'];
 }
 
$start_date = new CDate($row->project_planned_start_date);

$end_date = intval($row->project_planned_end_date) ? new CDate($row->project_planned_end_date) : null;

$meeting_date = null;

$actual_start_date =  null;

$actual_end_date =  null;

}else{

$start_date = new CDate($row->project_planned_start_date);

$end_date = intval($row->project_planned_end_date) ? new CDate($row->project_planned_end_date) : null;

$meeting_date = intval($row->project_meeting_date) ? new CDate($row->project_meeting_date): null;

$actual_start_date = intval($row->project_start_date) ? new CDate($row->project_start_date): null;

$actual_end_date = intval($row->project_end_date) ? new CDate($row->project_end_date): null;

$participants = $row->participants;
}

// setup the title block
$ttl = $pma_id > 0 ? 'Edit Post Mortem' : 'New Post Mortem';

$titleBlock = new CTitleBlock($ttl, 'closed.png', $m, "$m.$a");
$titleBlock->addCrumb('?m=closure', 'post mortem list');
if ($pma_id != 0) {
	$titleBlock->addCrumb( '?m=closure&amp;a=view&amp;pma_id='.$pma_id, 'view this post mortem' );
}
$titleBlock->show();

/**************** Display *********************/
	require_once(DP_BASE_DIR . '/classes/CustomFields.class.php');
	$custom_fields = New CustomFields( $m, $a, $row->pma_id, 'edit' );

   $q  = new DBQuery;
   $q->addTable('projects');
   $q->addQuery('project_id, project_name');
   $p = $q->loadList();
   $projects = array();
   $i = 0;
   // Projects the user may edit (saving requires it), plus the one this post mortem is about.
   foreach($p as $project){
                     if (getPermission('projects', 'edit', (int)$project['project_id'])
                         || ($pma_id && $project['project_name'] === $row->project_name)) {
                         $projects[$project['project_name']] = $project['project_name'];
                     }
   }

   $q  = new DBQuery;
   $q->addTable('contacts');
   $q->addQuery('contact_first_name, contact_last_name');
   $res =& $q->exec();
   
   $pieces = explode(", ", $participants);
?>
<form name="editFrm" action="?m=closure" method="post">
<input type="hidden" name="dosql" value="do_closure_aed" />
<input type="hidden" name="pma_id" value="<?php echo dPformSafe($pma_id);?>" />


<p align='center' style="color: #000066; font-size: 12pt"><?php echo $AppUI->_('Meeting Settings')?></p>

 <table align=left width='100%' border='0' cellpadding='2' cellspacing='1' class='std'>
 <tr> <td align='left'><?php echo $AppUI->_('Meeting Participants');?></td></tr>

<tr>
<td align='left' width='5%'>
<select multiple size="10" name="list1" style="width:150">

<?php
$i = 0;
for ($res; ! $res->EOF; $res->MoveNext()){
       $contact_first_name = $res->fields['contact_first_name'];
       if(array_search($contact_first_name, $pieces) === false){
?>
                <option value = <?php echo $i;?> >
                        <?php echo dPhtml($contact_first_name); ?>
                </option>
<?php
                $i++;
     }
 }
?>
</select>
</td>
<td align='left'>
<input type="button"<?php echo dPaction('closureMoveLeft', array('$form')); ?> value="<<"/>
<input type="button"<?php echo dPaction('closureMoveRight', array('$form')); ?> value=">>"/>

<select multiple size="10" valign="left" name="list2" style="width:150">
<?php
$p2 = "";
$limit = count($pieces);

$i = 0;
if($limit > 1){
for($i = 0; $i < $limit; $i++){
         $p2  = $p2.$pieces[$i];
?>
                <option value = <?php echo $i;?> >
                        <?php echo dPhtml($pieces[$i]); ?>
                </option>
 <?php
}
}else{
      if($participants){
      ?>
                <option value = <?php echo $i;?> >
                        <?php echo dPhtml($participants); ?>
                </option>
                <?php
      }
}
?>
</select>
</td>
</tr>

<tr>
			<td align="left" ><?php echo $AppUI->_('Meeting Date')?></td>
			<td align="left" >
				<input type="hidden" name="project_meeting_date" id="project_meeting_date" value="<?php
	echo (($meeting_date) ? $meeting_date->format(FMT_TIMESTAMP_DATE) : ''); ?>"/>
			<!-- format(FMT_TIMESTAMP_DATE) -->
				<input type="text" class="text" name="meeting_date" id="date0" value="<?php
	echo (($meeting_date) ? $meeting_date->format($df) : ''); ?>" disabled="disabled" />

				<a href="#"<?php echo dPaction('popCalendar', array('meeting_date', 'meeting_date')); ?>>
					<img src="./images/calendar.gif" width="24" height="12" alt="{dPtranslate word='Calendar'}" border="0" />
				</a>
			</td>
</tr>
 </table>

<p align='center' style="color: #000066; font-size: 12pt"><?php echo $AppUI->_('Project Summary')?></p>

 <table align=left width='100%' border='0' cellpadding='2' cellspacing='1' class='std'>

  	    		<td align='right'><?php echo $AppUI->_('Project Name')?></td>
					<td nowrap="nowrap">
				<?php
echo arraySelect($projects, 'project_name', 'size="1" class="text"', $row->project_name, true); ?>
			</td>

		<tr>

			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Project Planned Start Date')?></td>
			<td nowrap="nowrap">
				<input type="hidden" name="project_planned_start_date" id="project_planned_start_date" value="<?php
	echo (($start_date) ? $start_date->format(FMT_TIMESTAMP_DATE) : ''); ?>"/>
			<!-- format(FMT_TIMESTAMP_DATE) -->
				<input type="text" class="text" name="planned_start_date" id="date1" value="<?php
	echo (($start_date) ? $start_date->format($df) : ''); ?>" disabled="disabled" />

				<a href="#"<?php echo dPaction('popCalendar', array('planned_start_date', 'planned_start_date')); ?>>
					<img src="./images/calendar.gif" width="24" height="12" alt="{dPtranslate word='Calendar'}" border="0" />
				</a>
			</td>

			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Project Start Date')?></td>
			<td nowrap="nowrap">
				<input type="hidden" name="project_start_date" id="project_start_date" value="<?php
	echo (($actual_start_date) ? $actual_start_date->format(FMT_TIMESTAMP_DATE) : ''); ?>"/>
			<!-- format(FMT_TIMESTAMP_DATE) -->
				<input type="text" class="text" name="start_date" id="date2" value="<?php
	echo (($actual_start_date) ? $actual_start_date->format($df) : '');?>" disabled="disabled" />
				<a href="#"<?php echo dPaction('popCalendar', array('start_date', 'start_date')); ?>>
					<img src="./images/calendar.gif" width="24" height="12" alt="{dPtranslate word='Calendar'}" border="0" />
				</a>
			</td>

		</tr>

		<tr>
		
					<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Project Planned End Date')?></td>
			<td nowrap="nowrap">
				<input type="hidden" name="project_planned_end_date" id="project_planned_end_date" value="<?php
	echo (($end_date) ? $end_date->format(FMT_TIMESTAMP_DATE) : ''); ?>"/>
			<!-- format(FMT_TIMESTAMP_DATE) -->
				<input type="text" class="text" name="planned_end_date" id="date3" value="<?php
	echo (($end_date) ? $end_date->format($df) : ''); ?>" disabled="disabled" />

				<a href="#"<?php echo dPaction('popCalendar', array('planned_end_date', 'planned_end_date')); ?>>
					<img src="./images/calendar.gif" width="24" height="12" alt="{dPtranslate word='Calendar'}" border="0" />
				</a>
			</td>

		<td align="right" nowrap="nowrap"><?php echo $AppUI->_('Project End Date');?></td>
		 <td nowrap="nowrap">
				<input type="hidden" name="project_end_date" id="project_end_date" value="<?php
	echo (($actual_end_date) ? $actual_end_date->format(FMT_TIMESTAMP_DATE) : ''); ?>" />
			<!-- format(FMT_TIMESTAMP_DATE) -->
				<input type="text" class="text" name="end_date" id="date4" value="<?php
	echo (($actual_end_date) ? $actual_end_date->format($df) : '');?>" disabled="disabled" />

				<a href="#"<?php echo dPaction('popCalendar', array('end_date', 'end_date')); ?>>
					<img src="./images/calendar.gif" width="24" height="12" alt="{dPtranslate word='Calendar'}" border="0" />
				</a>
			</td>

		</tr>

<tr>
<td align='right'><?php echo $AppUI->_('Project Planned Budget');?></td>
  <td><input name='planned_budget' value="<?php echo dPformSafe($row->planned_budget); ?>"/></td>
<td align='right'><?php echo $AppUI->_('Project Budget');?></td>
  <td><input name='budget' value="<?php echo dPformSafe($row->planned_budget); ?>"/></td>
</tr>

</table>

	<p align='center' style="color: #000066; font-size: 12pt"><?php echo $AppUI->_('Lessons Learned');?></p>
<table cellspacing="1" cellpadding="1" border="0" width="100%" class="std">
	  <tr><td align='right'><?php echo $AppUI->_('Project Strengths')?></td>
  <td><textarea name='project_strength' cols=80 rows=8 ><?php echo dPformSafe($row->project_strength); ?></textarea>
   <br/><?php echo $AppUI->_("List here (each topic with '*') technologies, processes, IDEs or whatever tecniques, tools or services that worked out for you in this project");?>
	</td>
	  <tr><td align='right'><?php echo $AppUI->_('Project Weaknesses');?></td>
  <td><textarea name='project_weaknesses' cols=80 rows=8 ><?php echo dPformSafe($row->project_weaknesses); ?></textarea>
   <br/><?php echo $AppUI->_("List here (each topic with '*') technologies, processes, IDEs or whatever tecniques, tools or services that did not worked out for you in this project");?>
	<tr><td align='right'><?php echo $AppUI->_('Improvement Suggestions');?></td>
  <td><textarea name='improvement_suggestions' cols=80 rows=8 ><?php echo dPformSafe($row->improvement_suggestions); ?></textarea>
  <br/><?php echo $AppUI->_("List here (each topic with '*') the aspects of the project that need to be improved");?>
	<tr><td align='right'><?php echo $AppUI->_('Conclusions');?></td>
  <td><textarea name='conclusions' cols=80 rows=8 ><?php echo dPformSafe($row->conclusions); ?></textarea>
  <br/><?php echo $AppUI->_('General conclusions about the project');?>

  </td>
</tr>
<input type="hidden" name='participants' id='participants' value="<?php	echo dPformSafe($row->participants); ?>"> </input>
  </table>



<table cellspacing="1" cellpadding="1" border="0" width="100%">
<tr>
  <td>
    <input type="button" value="<?php echo $AppUI->_('back');?>"
    class="button"<?php echo dPaction('history.back'); ?> />
  </td>
  <td align="right">
    <input type="button" value="<?php echo $AppUI->_('submit');?>"
    class="button"<?php echo dPaction('closureSubmit'); ?> />
  </td>

</tr>
</table>
</form>

<!-- import the calendar script -->
<script type="text/javascript" src="<?php echo DP_BASE_URL;?>/lib/calendar/calendar.js"></script>
<!-- import the language module -->
<script type="text/javascript" src="<?php echo DP_BASE_URL;?>/lib/calendar/lang/calendar-<?php echo $AppUI->user_locale; ?>.js"></script>

<script type="text/javascript" nonce="<?php echo dPcspNonce(); ?>">

function setShort() {
var f = document.editFrm;
var x = 10;
if (f.project_name.value.length < 11) {
	x = f.project_name.value.length;
}
if (f.project_short_name.value.length == 0) {
	f.project_short_name.value = f.project_name.value.substr(0,x);
}
}

var calendarField = '';
var calWin = null;

function popCalendar( field ){
calendarField = field;
idate = document.editFrm['project_' + field].value;
window.open( 'index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + idate, 'calwin', 'width=280, height=250, scrollbars=no' );
}

/**
*	@param string Input date in the format YYYYMMDD
*	@param string Formatted date
*/
function setCalendar( idate, fdate ) {
	fld_date = document.editFrm['project_' + calendarField];
	fld_fdate = document.editFrm[calendarField];
	fld_date.value = idate;
	fld_fdate.value = fdate;

	// set end date automatically with start date if start date is after end date
	if (calendarField == 'start_date') {
		if( document.editFrm.end_date.value < idate) {
			document.editFrm.project_end_date.value = idate;
			document.editFrm.end_date.value = fdate;
		}
	}
}

function submitItHere() {
   var f = document.editFrm;
	 f.submit();
}

// Buttons of the participants lists and the submit button (were inline handlers).
function closureMoveLeft(form) {
	move(form.list2, form.list1);
}

function closureMoveRight(form) {
	move(form.list1, form.list2);
}

function closureSubmit() {
	move(document.editFrm.list1, document.editFrm.list2);
	submitItHere();
}


function move(MenuOrigem, MenuDestino){

    var arrMenuOrigem = new Array();

    var arrMenuDestino = new Array();

    var arrLookup = new Array();

    var i;

    var stringToPersist = "";

    for (i = 0; i < MenuDestino.options.length; i++){

        arrLookup[MenuDestino.options[i].text] = MenuDestino.options[i].value;

        arrMenuDestino[i] = MenuDestino.options[i].text;
    }

    var fLength = 0;

    var tLength = arrMenuDestino.length;

    for(i = 0; i < MenuOrigem.options.length; i++){

        arrLookup[MenuOrigem.options[i].text] = MenuOrigem.options[i].value;

        if (MenuOrigem.options[i].selected && MenuOrigem.options[i].value != ""){

            arrMenuDestino[tLength] = MenuOrigem.options[i].text;
            tLength++;

        }

        else{

            arrMenuOrigem[fLength] = MenuOrigem.options[i].text;

            fLength++;

        }

    }

    arrMenuOrigem.sort();

    arrMenuDestino.sort();

    MenuOrigem.length = 0;

    MenuDestino.length = 0;

    var c;

    for(c = 0; c < arrMenuOrigem.length; c++){

        var no = new Option();

        no.value = arrLookup[arrMenuOrigem[c]];

        no.text = arrMenuOrigem[c];

        MenuOrigem[c] = no;

    }

    for(c = 0; c < arrMenuDestino.length; c++){

        var no = new Option();

        no.value = arrLookup[arrMenuDestino[c]];

        no.text = arrMenuDestino[c];

        stringToPersist += arrMenuDestino[c];

        if(c != arrMenuDestino.length-1)
             stringToPersist += ", ";

        MenuDestino[c] = no;

   }

   document.getElementById('participants').value = stringToPersist;
}

</script>

