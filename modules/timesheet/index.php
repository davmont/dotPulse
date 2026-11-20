<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

$timesheet_id = isset($_GET['timesheet_id']) ? $_GET['timesheet_id'] : 0; 
 
// check permissions 
$denyRead = getDenyRead( $m, $timesheet_id ); 
$denyEdit = getDenyEdit( $m, $timesheet_id ); 
 
if ($denyRead) { 
	$AppUI->redirect( "m=help&a=access_denied" );
}
$AppUI->savePlace();

require_once( "./classes/date.class.php" );
$df = $AppUI->getPref( 'SHDATEFORMAT' );
$rollover_day = LOCALE_FIRST_DAY; // set the start of the week.  1 for monday, 0 for sunday.

#################################### [ view section UI code ] ########################

/**
* Gets the start of the week based on date passed
* @param $date date to find start of week
* @param $rollover_day day on which week starts.  1 for monday, 0 for sunday.
* @return object Date the new Date object
*/
function getStartOfWeek($date, $rollover_day) {
	$today_weekday = $date -> getDayOfWeek();

	$new_start_offset = $rollover_day - (int)$today_weekday;
	$date -> subtractSeconds($new_start_offset * 24 * 60 * 60 * -1);
	
	return $date;
}

/**
* Convert hours to time, add leading zeroes.
* @param $hours hours to convert to time
* @return string of the hours and minutes (00:00)
*/
function hoursToTime($total_hours) {
	
	//since we're trying to convert an unknown float to an integer
	//we need to round number to a safe amount of decimal digits
	//to avoid floating point arithmetic mishaps.  In particular
	//without rounding intval( 1.9999... ) gives 1
	//supposedly 7 is a safe number of digits(?) -- it will allow
	//precision of up to 0.000006 minutes (0.00036 sec)
	$total_hours = round( $total_hours, 7 );
	$hours = intval( $total_hours );

	$minutes = (($total_hours - $hours) * 60);
	$time['hour'] = $hours;
	$time['minute'] = $minutes;
	
	return padTime( (object) $time );
}

function splitTime( $time ) {
    $hhmm = explode( ":", $time );
    
    // Always hour and minute (0 for an empty or malformed time).
    return (object) array(
        'hour' => count($hhmm) > 1 ? (int) $hhmm[0] : 0,
        'minute' => count($hhmm) > 1 ? (int) $hhmm[1] : 0,
    );
}

function padTime( $time ) {
    return sprintf( "%02.0f:%02.0f", $time->hour, $time->minute );
}

/**
* truncate() Simple function to shorten a string and add an ellipsis
* 
* @param string $string Origonal string
* @param integer $max Maximum length
* @param string $rep Replace with... (Default = '' - No elipsis -)
* @return string
* @author David Duong and Alex :)
**/
function truncate ($string, $max = 50, $rep = '') {
	if ( strlen($string) < $max + strlen($rep) ) {
		return $string;
	} else {
		$leave = $max - strlen ($rep);
		return substr_replace($string, $rep, $leave);
	}
} 

?>
<script type="text/javascript" nonce="<?php echo dPcspNonce(); ?>">
function sendIt() {
	if (confirm( "Is all the information and time on this timesheet correct and accurate?\n" )) {
		var form = document.TIDedit;
		form.submit();
	} 
}
</script>
<?php 
$titleBlock = new CTitleBlock("Timesheet", 'applet3-48.png', $m, "$m.$a");
$titleBlock->show();
//TODO : Check this "bad HTML Code?
?>
<!-- ################# Bad HTML Code -->
<?php

//get week to be displayed (#weeks ago current week being 0)
if (isset( $_GET['wk'] ) ) {
	$AppUI->setState( 'TmsWk', (int) $_GET['wk'] );
}
$wk = max(0, (int) $AppUI->getState('TmsWk'));

if ( $wk == 0 )
	$week_text = $AppUI->_("Current week");
else if ( $wk == 1 )
	$week_text = $AppUI->_("Last week");
else
	$week_text = "$wk " . $AppUI->_("weeks ago");

if (isset( $_GET['tab'] )) {
	$AppUI->setState( 'TmsVwTab', (int) $_GET['tab'] );
}
$tab = $AppUI->getState( 'TmsVwTab' ) !== NULL ? $AppUI->getState( 'TmsVwTab' ) : 0;

$tabBox = new CTabBox( "?m=timesheet", "./modules/timesheet/", $tab );
$tabBox->add( 'vw_general', 'General' );
$tabBox->add( 'vw_tasks_todo', 'Tasks' );		
$tabBox->show();

?>


<form name="TIDedit" action="./index.php?m=timesheet&a=dosql" method="post"> 

<table width="100%" border="0" bgcolor="#f4efe3" cellpadding="3" cellspacing="1" class="tbl"> 
<tr>
	<th norap="nowrap" colspan="7"><?php echo $week_text ?></th>
</tr>
<tr>
	<th nowrap="nowrap"><?php echo $AppUI->_('Date') ?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_('In') ?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Out') ?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Break') ?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Total') ?></th>
	<th nowrap="nowrap"><?php echo $AppUI->_('Total Logged') ?></th>
	<th nowrap="nowrap" width="65%"> <br></th>
</tr>
 
<?php

$time_set = new CDate ();
$time_set->addDays( -7 * $wk );
$time_set = getStartOfWeek($time_set, $rollover_day);
$date_start = $time_set -> format("%Y-%m-%d");
$total_time_worked = new CDate('0000-00-00 00:00:00');
$total_hours_logged = 0;

// loop through the week
for ($day = 0; $day < 7; $day++) {
$timeIn = $timeOut = $timeBreak = null;
$time_daily = (object) array('hour' => 0, 'minute' => 0);
	
$date_temp = $time_set -> format("%Y-%m-%d");

//pull data for this timesheet
$q = new DBQuery();
$q -> addTable('timesheet');
$q -> addQuery('*');
$q -> addWhere("timesheet_date = '$date_temp' AND user_id = $AppUI->user_id");

$result = $q->exec();
$timesheet_row = $q -> fetchRow() ?: array(); // no row yet for this day

$q -> clear();
$q -> addTable('task_log','l');
$q -> addQuery('l.*, u.user_username, t.task_name, t.task_id, t.task_description, p.project_short_name, p.project_id, p.project_description, c.company_name');
$q -> addJoin('users', 'u', 'user_id=task_log_creator');
$q -> addJoin('tasks', 't', 'task_id = task_log_task');
$q -> addJoin('projects','p','project_id = task_project');
$q -> addJoin('companies','c','company_id = project_company');
$q -> addWhere("task_log_date = '$date_temp'and user_id = $AppUI->user_id");
$q -> addOrder('task_log_date');

$logs = $q -> loadList();
$s = '';
$logged_hours = 0;

foreach ($logs as $log_row) {
	$logged_hours += $log_row["task_log_hours"];
	$s .= "<tr>";
	$s .= "\n\t\t<td><a href=\"?m=tasks&a=view&task_id={$log_row['task_log_task']}&tab=1&task_log_id=".@$log_row['task_log_id'].'" style="cursor: hand;" title="edit log">'
			. "\n\t\t\t". dPshowImage( './images/icons/stock_edit-16.png', 16, 16, 'edit log' )
			. "\n\t\t</a></td>";
	$s .= "<td nowrap=\"nowrap\" align=\"left\"><a href=\"index.php?m=projects&a=view&project_id={$log_row['project_id']}\" title=\"" . dPhtml($log_row['project_description']) . "\">" . dPhtml($log_row['project_short_name']) . "</a> -> ";
	$s .= "<a href=\"index.php?m=tasks&a=view&task_id={$log_row['task_id']}\" title=\"" . dPhtml($log_row['task_description']) . "\">" . dPhtml(truncate($log_row["task_name"], 30, '...')) . "</a> -> " . dPhtml(truncate($log_row["task_log_name"], 30, '...')) . "</td>\n\t";
	$s .= '<td nowrap="nowrap" class="numerical">' . sprintf("%.2f", $log_row["task_log_hours"]) . "</td>\n";
	$s .= '</tr>';
}

?> 
<tr> 
	<td nowrap><?php 
		echo $time_set->format("%A %b %e, %Y");
		echo '<input type="hidden" name="timesheet_date[]" value="' . (!empty($timesheet_row["timesheet_date"]) ? $timesheet_row["timesheet_date"] : $time_set->format("%Y-%m-%d")) . '">';
		?>
		<input name="timesheet_id[]" value="<?php echo (int) ($timesheet_row["timesheet_id"] ?? 0); ?>" type="hidden">
	</td> 
	<td nowrap><input type="text" size="5" name="timesheet_time_in[]" value="<?php 
		if ($timesheet_row) {
			$timeIn = splitTime( $timesheet_row["timesheet_time_in"] );
			if ($timeIn->hour || $timeIn->minute) echo padTime($timeIn);
		}
		?>">
	</td> 
	<td nowrap><input type="text" size="5" name="timesheet_time_out[]" value="<?php 
		if ($timesheet_row) {
			$timeOut = splitTime( $timesheet_row["timesheet_time_out"] );
			if ($timeOut->hour || $timeOut->minute) echo padTime($timeOut);
		}
		?>">
	</td> 
	<td nowrap><input type="text" size="5" name="timesheet_time_break[]" value="<?php 
		if ($timesheet_row) {
			$timeBreak = splitTime( $timesheet_row["timesheet_time_break"] );
			if ($timeBreak->hour || $timeBreak->minute) echo padTime($timeBreak);
		}
		?>">
	</td> 
	<td nowrap align="center"><?php 
		if ($timeIn && $timeOut && ($timeOut->hour || $timeOut->minute) && ($timeIn->hour || $timeIn->minute)) {
			$break = $timeBreak ? $timeBreak->hour * 60 + $timeBreak->minute : 0;
			$minutes_daily = ($timeOut->hour - $timeIn->hour) * 60 + ($timeOut->minute - $timeIn->minute) - $break;
			
			$time_daily->hour = (int)( $minutes_daily / 60 );
			$time_daily->minute = $minutes_daily % 60;
			
			//echo $timeOut->format("%H:%M");
			echo padTime( $time_daily );
		} else echo '--';
		?>
	</td>
	<td nowrap align="center">
	<?php 
		if ($logged_hours) {
			// convert hours logged to time logged...
			echo hoursToTime($logged_hours);
		} else echo '--';
	?>
	</td>
	<td nowrap align="center">
		<table border="0" bgcolor="#f4efe3" cellpadding="3" cellspacing="1" class="tbl" width="100%">
		<tr>
			<td colspan="2" nowrap="nowrap" width="100%"><b>Task</b></td>
			<td nowrap="nowrap"><b>Hours</b></td>
		</tr>
		<?php echo $s ?>
		</table>
	
	</td>
</tr> 
<?php 
// total the total hours
$time_set -> addSeconds(24 * 60 * 60);
//$total_time_worked->addSeconds($timeOut->hour * 60 * 60 + $timeOut->minute * 60);
$total_time_worked->hour += $time_daily->hour + floor( ($total_time_worked->minute + $time_daily->minute) / 60 );
$total_time_worked->minute = ( $total_time_worked->minute + $time_daily->minute ) % 60;
$total_hours_logged += $logged_hours;

}
?>
<tr>
	<td nowrap><br></td>
	<td nowrap align="left" colspan="3"><b>Total:</b></td>
	<td nowrap align="center"><b><?php echo padTime( $total_time_worked ); ?></b></td>
	<td nowrap align="center"><b><?php echo hoursToTime($total_hours_logged); ?></b></td>
	<td nowrap align="right"><input type="submit" name="update" value="Update" class="button"></td>
</tr>
</table> 
</form>