<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// Personal dashboard: what the logged-in user has to do and where their projects stand.
require_once $AppUI->getModuleClass('dashboard');

$AppUI->savePlace();
$dash = new CDashboard($AppUI->user_id);
$tasks = $dash->myTasks();
$projects = $dash->myProjects();
$hours = $dash->hoursThisWeek();
$events = $dash->upcomingEvents();

$df = $AppUI->getPref('SHDATEFORMAT');
$tf = $AppUI->getPref('TIMEFORMAT');
$fmtDate = function ($value, $format) {
	if (!$value || strpos($value, '0000-00-00') === 0) {
		return '';
	}
	$d = new CDate($value);
	return dPhtml($d->format($format));
};
$count = array('overdue' => 0, 'week' => 0, 'later' => 0, 'none' => 0);
foreach ($tasks as $t) {
	$count[$t['due']]++;
}
$week_hours = array_sum($hours);
$statuses = dPgetSysVal('ProjectStatus');
$canAddLog = getPermission('task_log', 'add');

$titleBlock = new CTitleBlock('Dashboard', 'dashboard.png', $m, "$m.$a");
$titleBlock->show();
?>
<link rel="stylesheet" type="text/css" href="./modules/dashboard/dashboard.css" />
<div class="dash">
	<p class="dash-hello"><?php echo $AppUI->_('Hello') . ', ' . dPhtml($AppUI->user_first_name); ?>.
		<?php
		if ($count['overdue'] == 1) {
			echo $AppUI->_('You have 1 overdue task.');
		} else if ($count['overdue']) {
			echo sprintf($AppUI->_('You have %d overdue tasks.'), $count['overdue']);
		} else if ($tasks) {
			echo $AppUI->_('Nothing overdue.');
		} else {
			echo $AppUI->_('No open tasks assigned to you.');
		}
		?></p>

	<div class="dash-tiles">
		<a class="dash-tile" href="#dash-tasks"><span class="dash-num"><?php echo count($tasks); ?></span>
			<?php echo $AppUI->_('Open tasks'); ?></a>
		<a class="dash-tile<?php echo $count['overdue'] ? ' dash-alert' : ''; ?>" href="#dash-overdue"><span class="dash-num"><?php echo $count['overdue']; ?></span>
			<?php echo $AppUI->_('Overdue'); ?></a>
		<a class="dash-tile" href="#dash-week"><span class="dash-num"><?php echo $count['week']; ?></span>
			<?php echo $AppUI->_('Due in 7 days'); ?></a>
		<a class="dash-tile" href="#dash-hours"><span class="dash-num"><?php echo dPhtml(round($week_hours, 1)); ?></span>
			<?php echo $AppUI->_('Hours logged this week'); ?></a>
	</div>

	<div class="dash-cols">
		<div class="dash-main">
			<h2 id="dash-tasks"><?php echo $AppUI->_('My tasks'); ?></h2>
			<?php if (!$tasks) { ?>
				<p class="dash-empty"><?php echo $AppUI->_('No open tasks assigned to you.'); ?></p>
			<?php } else { ?>
			<table class="tbl dash-table" width="100%">
				<tr>
					<th><?php echo $AppUI->_('Task'); ?></th>
					<th><?php echo $AppUI->_('Project'); ?></th>
					<th><?php echo $AppUI->_('Finish'); ?></th>
					<th><?php echo $AppUI->_('Progress'); ?></th>
					<th>&nbsp;</th>
				</tr>
				<?php
				$labels = array(
					'overdue' => $AppUI->_('Overdue'),
					'week' => $AppUI->_('Due in 7 days'),
					'later' => $AppUI->_('Later'),
					'none' => $AppUI->_('No finish date'),
				);
				$last = null;
				foreach ($tasks as $t) {
					if ($t['due'] !== $last) {
						$last = $t['due'];
						echo '<tr class="dash-group dash-group-' . $last . '"><td colspan="5" id="dash-' . $last . '">'
							. $labels[$last] . '</td></tr>';
					}
					$pc = (int)$t['task_percent_complete'];
					?>
				<tr class="dash-due-<?php echo $t['due']; ?>">
					<td>
						<?php if ($t['task_milestone']) { ?><span class="dash-milestone" title="<?php echo $AppUI->_('Milestone'); ?>">&#9670;</span><?php } ?>
						<?php if ($t['task_priority'] > 0) { ?><span class="dash-priority" title="<?php echo $AppUI->_('High priority'); ?>">!</span><?php } ?>
						<a href="?m=tasks&amp;a=view&amp;task_id=<?php echo (int)$t['task_id']; ?>"><?php echo dPhtml($t['task_name']); ?></a>
					</td>
					<td><span class="dash-swatch" style="background:#<?php echo dPhtml(preg_replace('/[^0-9a-fA-F]/', '', $t['project_color_identifier'])); ?>"></span>
						<a href="?m=projects&amp;a=view&amp;project_id=<?php echo (int)$t['task_project']; ?>"><?php echo dPhtml($t['project_name']); ?></a></td>
					<td class="dash-date"><?php echo $fmtDate($t['task_end_date'], $df); ?></td>
					<td class="dash-progress"><span class="dash-bar" title="<?php echo $pc; ?>%"><span style="width:<?php echo $pc; ?>%"></span></span> <?php echo $pc; ?>%</td>
					<td><?php if ($canAddLog && getPermission('tasks', 'edit', $t['task_id'])) { ?>
						<a class="dash-action" href="?m=tasks&amp;a=view&amp;task_id=<?php echo (int)$t['task_id']; ?>&amp;tab=1"><?php echo $AppUI->_('Log hours'); ?></a>
					<?php } ?></td>
				</tr>
				<?php } ?>
			</table>
			<?php } ?>
		</div>

		<div class="dash-side">
			<h2><?php echo $AppUI->_('My projects'); ?></h2>
			<?php if (!$projects) { ?>
				<p class="dash-empty"><?php echo $AppUI->_('No active projects.'); ?></p>
			<?php } ?>
			<?php foreach ($projects as $p) {
				$pc = (float)$p['percent_complete'];
				$late = $p['project_end_date'] && strpos($p['project_end_date'], '0000-00-00') !== 0
					&& $p['project_end_date'] < date('Y-m-d H:i:s') && $pc < 100;
				?>
			<div class="dash-project">
				<div class="dash-project-head">
					<span class="dash-swatch" style="background:#<?php echo dPhtml(preg_replace('/[^0-9a-fA-F]/', '', $p['project_color_identifier'])); ?>"></span>
					<a href="?m=projects&amp;a=view&amp;project_id=<?php echo (int)$p['project_id']; ?>"><?php echo dPhtml($p['project_name']); ?></a>
					<span class="dash-status"><?php echo $AppUI->_($statuses[$p['project_status']] ?? ''); ?></span>
				</div>
				<span class="dash-bar dash-bar-wide" title="<?php echo dPhtml(round($pc)); ?>%"><span style="width:<?php echo dPhtml(min(100, max(0, $pc))); ?>%"></span></span>
				<div class="dash-project-meta">
					<?php echo dPhtml(round($pc)); ?>% &middot;
					<?php echo sprintf($AppUI->_('%d open tasks'), $p['open_tasks']); ?>
					<?php if ($p['overdue_tasks']) { ?>&middot; <span class="dash-late"><?php echo sprintf($AppUI->_('%d overdue'), $p['overdue_tasks']); ?></span><?php } ?>
					<?php if ($p['project_end_date'] && strpos($p['project_end_date'], '0000-00-00') !== 0) { ?>
						&middot; <span class="<?php echo $late ? 'dash-late' : ''; ?>"><?php echo $AppUI->_('Finish'); ?>: <?php echo $fmtDate($p['project_end_date'], $df); ?></span>
					<?php } ?>
				</div>
			</div>
			<?php } ?>

			<h2 id="dash-hours"><?php echo $AppUI->_('This week'); ?></h2>
			<?php $max = max(8, max($hours)); ?>
			<div class="dash-week" role="img" aria-label="<?php echo sprintf($AppUI->_('%s hours logged this week'), round($week_hours, 1)); ?>">
				<?php foreach ($hours as $day => $h) {
					$d = new CDate($day . ' 00:00:00');
					$today = ($day == date('Y-m-d'));
					?>
				<div class="dash-day<?php echo $today ? ' dash-today' : ''; ?>" title="<?php echo dPhtml($d->format($df) . ': ' . $h . ' h'); ?>">
					<span class="dash-day-h"><?php echo $h ? dPhtml(round($h, 1)) : ''; ?></span>
					<span class="dash-day-bar"><span style="height:<?php echo round(100 * $h / $max); ?>%"></span></span>
					<span class="dash-day-name"><?php echo dPhtml(mb_substr($AppUI->_($d->format('%a'), UI_OUTPUT_RAW), 0, 3)); ?></span>
				</div>
				<?php } ?>
			</div>

			<h2><?php echo $AppUI->_('Coming up'); ?></h2>
			<?php if (!$events) { ?>
				<p class="dash-empty"><?php echo $AppUI->_('No events in the next 7 days.'); ?></p>
			<?php } else { ?>
			<ul class="dash-events">
				<?php foreach ($events as $e) { ?>
				<li><span class="dash-date"><?php echo $fmtDate($e['event_start_date'], $df . ' ' . $tf); ?></span>
					<a href="?m=calendar&amp;a=view&amp;event_id=<?php echo (int)$e['event_id']; ?>"><?php echo dPhtml($e['event_title']); ?></a></li>
				<?php } ?>
			</ul>
			<?php } ?>
		</div>
	</div>
</div>
