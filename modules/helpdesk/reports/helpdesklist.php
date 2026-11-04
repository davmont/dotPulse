<?php /* PROJECTS $Id: helpdesklist.php,v 1.3 2011/08/02 06:22:55 hatax Exp $ */
/**
* Generates a report of the helpdesk logs for given dates including task logs
* Based on the original report tasklist.php by jcgonz
*/
//error_reporting( E_ALL );
$do_report = dPgetParam( $_POST, "do_report", 0 );
$log_all = dPgetParam( $_POST, 'log_all', 0 );
$show_closed_items = dPgetParam( $_POST, 'show_closed', 0 );
$log_pdf = dPgetParam( $_POST, 'log_pdf', 0 );
$log_ignore = dPgetParam( $_POST, 'log_ignore', 0 );

$list_start_date = dPgetParam( $_POST, "list_start_date", 0 );
$list_end_date = dPgetParam( $_POST, "list_end_date", 0 );

$period = dPgetParam($_POST, "period", 0);
$period_value = dPgetParam($_POST, "pvalue", 1);

if ($period) {
  $today = new CDate();
  $ts = $today->format(FMT_TIMESTAMP_DATE);
        if (strtok($period, " ") == $AppUI->_("Next"))
                $sign = +1;
        else //if(...)
                $sign = -1;

        $day_word = strtok(" ");
        if ($day_word == $AppUI->_("Day")) 
                $days = $period_value;
        else if ($day_word == $AppUI->_("Week"))
                $days = 7*$period_value;
        else if ($day_word == $AppUI->_("Month"))
                $days = 30*$period_value;

        $start_date = intval( $list_start_date ) ? new CDate( $list_start_date ) : new CDate($ts);
        $end_date = intval( $list_end_date ) ? new CDate( $list_end_date ) : new CDate($ts);

        if ($sign > 0)
                $end_date->addDays((int)$days);
        else
                $start_date->addDays(-(int)$days);

        $do_report = 1;
        
} else {
// create Date objects from the datetime fields
        $start_date = intval( $list_start_date ) ? new CDate( $list_start_date ) : new CDate();
        $end_date = intval( $list_end_date ) ? new CDate( $list_end_date ) : new CDate();
}

if (!$list_start_date) {
	$start_date->addDays(-14);
}
$end_date->setTime( 23, 59, 59 );

?>
<script language="javascript" nonce="<?php echo dPcspNonce(); ?>">
var calendarField = '';

function popCalendar( field ){
	calendarField = field;
	idate = document.editFrm.elements['list_' + field].value;
	window.open( 'index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + idate, 'calwin', 'width=270,height=250,scollbars=false' );
}

/**
 *	@param string Input date in the format YYYYMMDD
 *	@param string Formatted date
 */
function setCalendar( idate, fdate ) {
	fld_date = document.editFrm.elements['list_' + calendarField];
	fld_fdate = document.editFrm.elements[calendarField];
	fld_date.value = idate;
	fld_fdate.value = fdate;
}
</script>

<table cellspacing="0" cellpadding="4" border="0" width="100%" class="std">

<form name="editFrm" action="index.php?m=helpdesk&a=reports" method="post">
<input type="hidden" name="project_id" value="<?php echo $project_id;?>" />
<input type="hidden" name="report_type" value="<?php echo $report_type;?>" />

<tr>
        <td nowrap="nowrap" align="right"><?php echo $AppUI->_('Adjust Range'); ?>:</td>
        <td nowrap="nowrap" colspan="2">
          <input class="button" type="submit" name="period" value="<?php echo $AppUI->_('Previous Month'); ?>" />
          <input class="button" type="submit" name="period" value="<?php echo $AppUI->_('Previous Week'); ?>" />
          <input class="button" type="submit" name="period" value="<?php echo $AppUI->_('Previous Day'); ?>" />
        </td>
        <td nowrap="nowrap">
          <input class="button" type="submit" name="period" value="<?php echo $AppUI->_('Next Day'); ?>" />
          <input class="button" type="submit" name="period" value="<?php echo $AppUI->_('Next Week'); ?>" />
          <input class="button" type="submit" name="period" value="<?php echo $AppUI->_('Next Month'); ?>" />
        </td>
        <td colspan="3"><input class="text" type="field" size="2" name="pvalue" value="1" /> - <?php echo $AppUI->_('Button value multiplier'); ?></td>
</tr>
<tr>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('For Period');?>:</td>
	<td nowrap="nowrap">
		<input type="hidden" name="list_start_date" value="<?php echo $start_date->format( FMT_TIMESTAMP_DATE );?>" />
		<input type="text" name="start_date" value="<?php echo $start_date->format( $df );?>" class="text" disabled="disabled" />
		<a href="#"<?php echo dPaction('popCalendar', array('start_date')); ?>><img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" /></a>
	</td>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('to');?></td>
	<td nowrap="nowrap">
		<input type="hidden" name="list_end_date" value="<?php echo $end_date ? $end_date->format( FMT_TIMESTAMP_DATE ) : '';?>" />
		<input type="text" name="end_date" value="<?php echo $end_date ? $end_date->format( $df ) : '';?>" class="text" disabled="disabled" />
		<a href="#"<?php echo dPaction('popCalendar', array('end_date')); ?>><img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" /></a>
	</td>

	<td nowrap="nowrap">
		<input type="checkbox" name="show_closed" <?php if ($show_closed_items) echo "checked" ?> />
		<?php echo $AppUI->_( 'Include Closed Items' );?>
		<input type="checkbox" name="log_all" <?php if ($log_all) echo "checked" ?> />
		<?php echo $AppUI->_( 'Log All' );?>
		<input type="checkbox" name="log_pdf" <?php if ($log_pdf) echo "checked" ?> />
		<?php echo $AppUI->_( 'Make PDF' );?>
	</td>

	<td align="right" width="50%" nowrap="nowrap">
		<input class="button" type="submit" name="do_report" value="<?php echo $AppUI->_('submit');?>" />
	</td>
</tr>
</form>
</table>

<?php
if ($do_report) {

	$q = new DBQuery;
	$q->addQuery("hi.*,CONCAT(rc.contact_first_name, ' ', rc.contact_last_name) requested_by,CONCAT(ac.contact_first_name, ' ', ac.contact_last_name) assigned_to");
	$q->addTable('helpdesk_items','hi');
	$q->addJoin('users','ru','ru.user_id = hi.item_requestor_id');
	$q->addJoin('contacts','rc','rc.contact_id = ru.user_contact');
	$q->addJoin('users','au','au.user_id = hi.item_assigned_to');
	$q->addJoin('contacts','ac','ac.contact_id = au.user_contact');
	if (!$log_all) {
		$q->addWhere("hi.item_created >= '".$start_date->format( FMT_DATETIME_MYSQL )."'");
		$q->addWhere("hi.item_created <= '".$end_date->format( FMT_DATETIME_MYSQL )."'");
	}
	// The items this user may read, as in the helpdesk list.
	$q->addWhere(getItemPerms());
	$q->addOrder('hi.item_id');
	$Task_List = $q->loadList();

	$task_ids = array();
	$filtered_task_list = array();
	foreach ($Task_List as $Tasks) {
		if ($project_id > 0) {
			if ($Tasks['item_project_id'] != $project_id) {
				continue;
			}
		}
		$task_ids[] = $Tasks['item_id'];
		$filtered_task_list[] = $Tasks;
	}

	// Pre-fetch task logs to eliminate N+1 query
	$Task_Logs_Grouped = array();
	if (count($task_ids) > 0) {
		$q4 = new DBQuery;
		$q4->addQuery("tl.task_log_help_desk_id, tl.task_log_date, tl.task_log_name, tl.task_log_description, CONCAT(rc.contact_first_name, ' ', rc.contact_last_name) created_by");
		$q4->addTable('task_log','tl');
		$q4->addTable('users','ru');
		$q4->addTable('contacts','rc');
		$q4->addWhere('tl.task_log_help_desk_id IN (' . implode(',', $task_ids) . ')');
		$q4->addWhere('ru.user_id = tl.task_log_creator');
		$q4->addWhere('rc.contact_id = ru.user_contact');
		$q4->addOrder('tl.task_log_help_desk_id, tl.task_log_id');
		$logs_result = $q4->loadList();
		foreach ($logs_result as $log) {
			$Task_Logs_Grouped[$log['task_log_help_desk_id']][] = $log;
		}
		$q4->clear();
	}

	$pdfdata = array();
	$columns = array(
		$AppUI->_('Number'),
		$AppUI->_('Created On'),
		$AppUI->_('Requestor')." </b>(".$AppUI->_('Logger').")<b>",
		$AppUI->_('Title'),
		$AppUI->_('Summary'),
		$AppUI->_('Assigned To'),
		$AppUI->_('Status'),
		$AppUI->_('Priority'),
	);
	echo "<table cellspacing=\"1\" cellpadding=\"4\" border=\"0\" class=\"tbl\">";
	echo "<tr>";
	foreach($columns as $column) {
		echo '<th><b>'.$column.'</b></th>';
	}
	echo "</tr>";

	foreach ($filtered_task_list as $Tasks) {
		$start_date = new CDate( $Tasks['item_created'] );
		$end_date = new CDate( $Tasks['item_created'] );

		$Log_Status_desc = isset($sysval_status[$Tasks['item_status']]) ? $sysval_status[$Tasks['item_status']] : '';

		if ( (substr($Log_Status_desc, 0, 6) != 'Closed') || ($show_closed_items) ) {

			$Log_Priority_desc = isset($sysval_priority[$Tasks['item_status']]) ? $sysval_priority[$Tasks['item_status']] : '';

			$str =  "<tr valign=\"top\">";
			$str .= "<td align=\"right\">".$Tasks['item_id']."</td>";
			$str .= "<td align=\"center\">".$start_date->format( $df )."</td>";
			$str .= "<td align=\"center\"><b>".dPhtml($Tasks['item_requestor'])."</b></td>";
//			$str .= "<td align=\"center\">".$Tasks['requested_by']."</td>";
			$str .= "<td align=\"center\">".dPhtml($Tasks['item_title'])."</td>";
			$str .= "<td align=\"center\">".dPhtml($Tasks['item_summary'])."</td>";
			$str .= "<td align=\"center\">".dPhtml($Tasks['assigned_to'])."</td>";
			$str .= "<td align=\"center\">".$Log_Status_desc."</td>";
			$str .= "<td align=\"center\">".$Log_Priority_desc."</td>";
			$str .= "</tr>";
			echo $str;

			$pdfdata[] = array(
				$Tasks['item_id'],
				$start_date->format( $df ),
				$Tasks['requested_by'],
				$Tasks['item_title'],
				$Tasks['item_summary'],
				$Tasks['assigned_to'],
				$Log_Status_desc,
				$Log_Priority_desc,
			);

			$Row_Count = 1;
			if (isset($Task_Logs_Grouped[$Tasks['item_id']])) {
				foreach ($Task_Logs_Grouped[$Tasks['item_id']] as $Task_Log) {

				$log_date = new CDate( $Task_Log['task_log_date'] );

				$str =  "<tr valign=\"top\">";
				$str .= "<td align=\"right\">".$Tasks['item_id']."/".$Row_Count."</td>";
				$str .= "<td align=\"center\">".$log_date->format( $df )."</td>";
				if ($Task_Log['created_by']!='') {
					$str .= "<td align=\"center\">(".$Task_Log['created_by'].")</td>";
				} else {
					$str .= "<td align=\"center\">(<i>unknown</i>)</td>";
				}
				$str .= "<td align=\"center\">".$Task_Log['task_log_name']."</td>";
				$str .= "<td align=\"center\">".$Task_Log['task_log_description']."</td>";
				$str .= "<td align=\"center\">^</td>";
				$str .= "<td align=\"center\">^</td>";
				$str .= "<td align=\"center\">^</td>";
				$str .= "</tr>";
				echo $str;

				$pdfdata[] = array(
					$Tasks['item_id']."/".$Row_Count,
					$log_date->format( $df ),
					$Task_Log['created_by'],
					"^",
					$Task_Log['task_log_description'],
					"^",
					"^",
					"^",
				);
				$Row_Count++;
				}
			}
		}
} // end if do_report
echo "</table>";
if ($log_pdf) {
		if ($project_id){
			$q5 = new DBQuery;
			$q5->addQuery("project_name");
			$q5->addTable('projects');
			$q5->addWhere('project_id = ' . (int)$project_id);
			$pname = 'Project: '.$q5->loadResult();
		}
		else
			$pname = "All Companies and All Projects";

		if ($company_id){
			$q6 = new DBQuery;
			$q6->addQuery("company_name");
			$q6->addTable('companies');
			$q6->addWhere('company_id = ' . (int)$company_id);
			$cname = 'Company: '.$q6->loadResult();
		}
		else
			$cname = "All Companies and All Projects";

		$font_dir = dPgetConfig( 'root_dir' )."/lib/ezpdf/fonts";
		$temp_dir = dPgetConfig( 'root_dir' )."/files/temp";
		$base_url  = dPgetConfig( 'base_url' );
		require_once DP_BASE_DIR . '/classes/dpdf.class.php';

		$pdf = new DotPdf();
		$pdf->ezSetCmMargins( 1, 2, 1.5, 1.5 );
		$pdf->selectFont( "$font_dir/Helvetica.afm" );

		$pdf->ezText( dPgetConfig( 'company_name' ), 12 );
		// $pdf->ezText( dPgetConfig( 'company_name' ).' :: '.dPgetConfig( 'page_title' ), 12 );

		$date = new CDate();
		$pdf->ezText( "\n" . $date->format( $df ) , 8 );

		$pdf->selectFont( "$font_dir/Helvetica-Bold.afm" );
		$pdf->ezText( "\n" . $AppUI->_('Helpdesk Issue Task Log Report'), 12 );
		
		if ($company_id) {
			$pdf->ezText( "$cname", 15 );
		} else {
			$pdf->ezText( "$pname", 15 );
		}	
			
		if ($log_all) {
			$pdf->ezText( "All Helpdesk issue task log entries", 9 );
		} else {
			$pdf->ezText( "Helpdesk issue task log entries from ".$start_date->format( $df ).' to '.$end_date->format( $df ), 9 );
		}
		$pdf->ezText( "\n\n" );

		$title = 'Helpdesk Issue Task Logs';

	        $pdfheaders = array(
		        $AppUI->_('Issue#',UI_OUTPUT_JS),
        		$AppUI->_('Date',UI_OUTPUT_JS),
        		$AppUI->_('Creator',UI_OUTPUT_JS),
		        $AppUI->_('Issue Title',UI_OUTPUT_JS),
        		$AppUI->_('Summary',UI_OUTPUT_JS),
		        $AppUI->_('Assignee',UI_OUTPUT_JS),
	        	$AppUI->_('Status',UI_OUTPUT_JS),
        		$AppUI->_('Priority',UI_OUTPUT_JS),
        	);

		$options = array(
			'showLines' => 1,
			'fontSize' => 8,
			'rowGap' => 1,
			'colGap' => 1,
			'xPos' => 50,
			'xOrientation' => 'right',
			'width'=>'500',
			'cols'=>array(
					0=>array('justification'=>'center','width'=>30),
					1=>array('justification'=>'center','width'=>50),
					3=>array('justification'=>'center','width'=>50),
					2=>array('justification'=>'center','width'=>50),
					4=>array('justification'=>'center','width'=>175),
					5=>array('justification'=>'center','width'=>50),
					6=>array('justification'=>'center','width'=>40),
					7=>array('justification'=>'center','width'=>55),
        	)
		);

		$pdf->ezTable( $pdfdata, $pdfheaders, $title, $options );

		if ($fp = fopen( "$temp_dir/temp$AppUI->user_id.pdf", 'wb' )) {
			fwrite( $fp, $pdf->ezOutput() );
			fclose( $fp );
			echo "<a href=\"$base_url/fileviewer.php?report=pdf\" target=\"pdf\">";
			echo $AppUI->_( "View PDF File" );
			echo "</a>";
		} else {
			echo "Could not open file to save PDF.  ";
			if (!is_writable( $temp_dir )) {
				"The files/temp directory is not writable.  Check your file system permissions.";
			}
		}
	}
}
//Added to close out any oped DBQueries
if (isset($q)) $q->clear();
if (isset($q4)) $q4->clear();
if (isset($q5)) $q5->clear();
if (isset($q6)) $q6->clear();
?>
</table>
