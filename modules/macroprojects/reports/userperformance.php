<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

$do_report = dPgetParam($_POST, 'do_report', 0);
$log_start_date = dPgetParam($_POST, 'log_start_date', 0);
$log_end_date = dPgetParam($_POST, 'log_end_date', 0);
$log_all = dPgetParam($_POST['log_all'], 0);
$group_by_unit = dPgetParam($_POST['group_by_unit'],'day');

// create Date objects from the datetime fields
$start_date = intval($log_start_date) ? new CDate($log_start_date) : new CDate();
$end_date = intval($log_end_date) ? new CDate($log_end_date) : new CDate();

if (!$log_start_date) {
	$start_date->addDays(-14);
}
$end_date->setTime(23, 59, 59);
?>

<script language="javascript" nonce="<?php echo dPcspNonce(); ?>">
var calendarField = '';

function popCalendar(field) {
	calendarField = field;
	idate = document.editFrm['log_' + field].value;
	window.open('index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + idate, 'calwin', 'width=250, height=220, scrollbars=no, status=no');
}

/**
 *	@param string Input date in the format YYYYMMDD
 *	@param string Formatted date
 */
function setCalendar(idate, fdate) {
	fld_date = document.editFrm['log_' + calendarField];
	fld_fdate = document.editFrm[calendarField];
	fld_date.value = idate;
	fld_fdate.value = fdate;
}
</script>

<form name="editFrm" action="index.php?m=macroprojects&a=reports" method="post">
<input type="hidden" name="macroproject_id" value="<?php echo $macroproject_id;?>" />
<input type="hidden" name="report_type" value="<?php echo $report_type;?>" />

<table cellspacing="0" cellpadding="4" border="0" width="100%" class="std">


<tr>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('For period');?>:</td>
	<td nowrap="nowrap">
		<input type="hidden" name="log_start_date" value="<?php echo $start_date->format(FMT_TIMESTAMP_DATE);?>" />
		<input type="text" name="start_date" value="<?php echo $start_date->format($df);?>" class="text" disabled="disabled" />
		<a href="#"<?php echo dPaction('popCalendar', array('start_date')); ?>>
			<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
		</a>
	</td>
	<td align="right" nowrap="nowrap"><?php echo $AppUI->_('to');?></td>
	<td nowrap="nowrap">
		<input type="hidden" name="log_end_date" value="<?php echo $end_date ? $end_date->format(FMT_TIMESTAMP_DATE) : '';?>" />
		<input type="text" name="end_date" value="<?php echo $end_date ? $end_date->format($df) : '';?>" class="text" disabled="disabled" />
		<a href="#"<?php echo dPaction('popCalendar', array('end_date')); ?>>
			<img src="./images/calendar.gif" width="24" height="12" alt="<?php echo $AppUI->_('Calendar');?>" border="0" />
		</a>
	</td>

	<td nowrap="nowrap">
		<input type="checkbox" name="log_all" id="log_all" <?php if ($log_all) echo 'checked'; ?> />
		<label for="log_all"><?php echo $AppUI->_('Log All');?></label>
	</td>

	<td align="right" width="50%" nowrap="nowrap">
		<input class="button" type="submit" name="do_report" value="<?php echo $AppUI->_('submit');?>" />
	</td>
</tr>

</table>
</form>

<?php
if ($do_report) {
	
	// Let's figure out which users we have
	$q = new DBQuery;
	$q->addTable('users', 'u');
	$q->addQuery('u.user_id, u.user_username, contact_first_name, contact_last_name');
	$q->leftJoin('contacts', 'c', 'u.user_contact = c.contact_id');
	$user_list = $q->loadHashList('user_id');
	
	// Now which tasks will we need and the real allocated hours (estimated time / number of users)
	// Also we will use tasks with duration_type = 1 (hours) and those that are not marked
	// as milstones
	// GJB: Note that we have to special case duration type 24 and this refers to the hours in a day, NOT 24 hours
	$working_hours = $dPconfig['daily_working_hours'];

	$q = new DBQuery;
	$q->addTable('tasks', 't');
	$q->addTable('user_tasks', 'ut');
	$q->addQuery('t.task_id, t.task_percent_complete');
	$q->addQuery('round(t.task_duration * IF(t.task_duration_type = 24, ' . (int)$working_hours . ', t.task_duration_type), 2) as total_hours');
	$q->addQuery('SUM(ut.perc_assignment) as total_perc, COUNT(ut.user_id) as user_count');
	$q->addWhere("t.task_id = ut.task_id AND t.task_milestone = '0'");
	if ($macroproject_id != 0) {
		$q->addWhere(makeWhereClauseEachProjectOfAMacroProject($macroproject_id, 'task_project ='));
	}
	if (!$log_all) {
		$q->addWhere('t.task_start_date >= \'' . $start_date->format(FMT_DATETIME_MYSQL) . '\'');
		$q->addWhere('t.task_start_date <= \'' . $end_date->format(FMT_DATETIME_MYSQL) . '\'');
	}
	$q->addGroup('t.task_id');
	$task_list = $q->loadHashList('task_id');

	$user_task_map = array();
	$user_log_map = array();
	if (count($task_list)) {
		$task_ids = array_keys($task_list);
		$q->clear();
		$q->addTable('user_tasks');
		$q->addQuery('user_id, task_id, perc_assignment');
		$q->addWhere('task_id IN (' . implode(',', $task_ids) . ')');
		$ut_list = $q->loadList();
		foreach ($ut_list as $ut) {
			$user_task_map[$ut['user_id']][$ut['task_id']] = $ut['perc_assignment'];
		}

		$q->clear();
		$q->addTable('task_log');
		$q->addQuery('task_log_creator, task_log_task, SUM(task_log_hours) as hours');
		$q->addWhere('task_log_task IN (' . implode(',', $task_ids) . ')');
		$q->addGroup('task_log_creator, task_log_task');
		$log_list = $q->loadList();
		foreach ($log_list as $log) {
			$user_log_map[$log['task_log_creator']][$log['task_log_task']] = $log['hours'];
		}
	}
?>

<table cellspacing="1" cellpadding="4" border="0" class="tbl">
	<tr>
		<th colspan='2'><?php echo $AppUI->_('User');?></th>
		<th><?php echo $AppUI->_('Hours allocated'); ?></th>
		<th><?php echo $AppUI->_('Hours worked'); ?></th>
		<th><?php echo $AppUI->_('% of work done (based on duration)'); ?></th>
		<th><?php echo $AppUI->_('User Efficiency (based on completed tasks)'); ?></th>
	</tr>

<?php
	if (count($user_list)) {
		$percentage_sum = $hours_allocated_sum = $hours_worked_sum = 0;
		$sum_total_hours_allocated = $sum_total_hours_worked = 0;
		$sum_hours_allocated_complete = $sum_hours_worked_complete = 0;
	
		foreach ($user_list as $user_id => $user) {
			$total_hours_allocated = $total_hours_worked = 0;
			$hours_allocated_complete = $hours_worked_complete = 0;

			$tasks_assigned = isset($user_task_map[$user_id]) ? $user_task_map[$user_id] : array();
			
			foreach ($tasks_assigned as $task_id => $perc) {
				if (isset($task_list[$task_id])) {
					$hours_worked = round(isset($user_log_map[$user_id][$task_id]) ? $user_log_map[$user_id][$task_id] : 0, 2);
					$complete = ($task_list[$task_id]['task_percent_complete'] == 100);

					$task_total_hours = $task_list[$task_id]['total_hours'];
					$total_perc = $task_list[$task_id]['total_perc'];
					if ($total_perc > 0) {
						$hours_allocated = round(($task_total_hours * $perc) / $total_perc, 2);
					} else {
						$hours_allocated = round($task_total_hours / $task_list[$task_id]['user_count'], 2);
					}
                    
					if ($complete) {
						$hours_allocated_complete += $hours_allocated;
						$hours_worked_complete += $hours_worked;
					}
					
					$total_hours_allocated += $hours_allocated;
					$total_hours_worked    += $hours_worked;
				}
			}
			
			$sum_total_hours_allocated += $total_hours_allocated;
			$sum_total_hours_worked    += $total_hours_worked;

			$sum_hours_allocated_complete += $hours_allocated_complete;
			$sum_hours_worked_complete    += $hours_worked_complete;
			
			if ($total_hours_allocated > 0 || $total_hours_worked > 0) {
				$percentage = 0;
				$percentage_e = 0;
				if ($total_hours_worked>0) {
					$percentage = ($total_hours_worked/$total_hours_allocated)*100;
					if ($hours_worked_complete > 0)
						$percentage_e = ($hours_allocated_complete/$hours_worked_complete)*100;
				}
				?>
				<tr>
					<td><?php echo '('.$user['user_username'].') </td><td> '.$user['contact_first_name'].' '.$user['contact_last_name']; ?></td>
					<td align='right'><?php echo $total_hours_allocated; ?> </td>
					<td align='right'><?php echo $total_hours_worked; ?> </td>
					<td align='right'><?php echo number_format($percentage, 0); ?>% </td>
					<td align='right'><?php echo number_format($percentage_e, 0); ?>% </td>
				</tr>
				<?php
			}
		}
		$sum_percentage = 0;
                $sum_efficiency = 0;
		if ($sum_total_hours_worked > 0) {
			$sum_percentage = ($sum_total_hours_worked/$sum_total_hours_allocated)*100;
			if ($sum_hours_worked_complete > 0)
				$sum_efficiency = ($sum_hours_allocated_complete/$sum_hours_worked_complete)*100;
		}
		?>
			<tr>
				<td colspan='2'><?php echo $AppUI->_('Total'); ?></td>
				<td align='right'><?php echo $sum_total_hours_allocated; ?></td>
				<td align='right'><?php echo $sum_total_hours_worked; ?></td>
				<td align='right'><?php echo number_format($sum_percentage,0); ?>%</td>
				<td align='right'><?php echo number_format($sum_efficiency,0); ?>%</td>
			</tr>
		<?php
	} else {
		?>
		<tr>
		    <td><p><?php echo $AppUI->_('There are no tasks that fulfill selected filters');?></p></td>
		</tr>
		<?php
	}
}
?>
</table>
