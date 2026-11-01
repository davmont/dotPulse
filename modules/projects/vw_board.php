<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// Board tab of a project: its active tasks (not task groups) in three columns by progress,
// To do (0 %), In progress and Done (100 %). Cards move by drag and drop or with their
// arrow buttons (js/dp-board.js), which post to tasks/do_board_move.php; only tasks the user
// may edit can move.
global $AppUI, $project_id;

$mine = (int)dPgetParam($_GET, 'board_mine', 0) == 1;
$df = $AppUI->getPref('SHDATEFORMAT');
$now = date('Y-m-d H:i:s');

$q = new DBQuery;
$q->addTable('tasks', 't');
$q->addJoin('projects', 'p', 'p.project_id = t.task_project');
$q->addQuery('t.task_id, t.task_name, t.task_project, t.task_owner, t.task_access, t.task_end_date, '
	. 't.task_percent_complete, t.task_priority, t.task_milestone');
$q->addWhere('t.task_project = ' . (int)$project_id);
$q->addWhere('t.task_status = 0 AND t.task_dynamic <> 1');
if ($mine) {
	$q->innerJoin('user_tasks', 'mine', 'mine.task_id = t.task_id AND mine.user_id = ' . (int)$AppUI->user_id);
}
$project = new CProject;
$project->setAllowedSQL($AppUI->user_id, $q, null, 'p');
$allowed = new CTask;
foreach ($allowed->getAllowedSQL($AppUI->user_id, 't.task_id') as $where) {
	$q->addWhere($where);
}
$q->addOrder("CASE WHEN t.task_end_date IS NULL OR t.task_end_date = '0000-00-00 00:00:00' THEN 1 ELSE 0 END, "
	. 't.task_end_date, t.task_priority DESC, t.task_id');
$rows = $q->loadList();

$columns = array('todo' => array(), 'doing' => array(), 'done' => array());
$ids = array();
foreach ($rows as $row) {
	$task = new CTask;
	foreach (array('task_id', 'task_access', 'task_project', 'task_owner') as $k) {
		$task->$k = $row[$k];
	}
	if (!getPermission('tasks', 'view', $row['task_id']) || !$task->canAccess($AppUI->user_id)) {
		continue;
	}
	$row['editable'] = getPermission('tasks', 'edit', $row['task_id']);
	$columns[CTask::boardColumn((int)$row['task_percent_complete'])][] = $row;
	$ids[] = (int)$row['task_id'];
}

// Assignees of the shown tasks, by task.
$assignees = array();
if ($ids) {
	$q = new DBQuery;
	$q->addTable('user_tasks', 'ut');
	$q->addJoin('users', 'u', 'u.user_id = ut.user_id');
	$q->addJoin('contacts', 'c', 'c.contact_id = u.user_contact');
	$q->addQuery('ut.task_id, u.user_id, c.contact_first_name, c.contact_last_name, u.user_username');
	$q->addWhere('ut.task_id IN (' . implode(',', $ids) . ')');
	$q->addOrder('c.contact_first_name, c.contact_last_name');
	foreach ($q->loadList() as $a) {
		$name = trim($a['contact_first_name'] . ' ' . $a['contact_last_name']);
		$assignees[$a['task_id']][] = $name !== '' ? $name : $a['user_username'];
	}
}

$titles = array('todo' => 'To do', 'doing' => 'In progress', 'done' => 'Done');
$base = '?m=projects&amp;a=view&amp;project_id=' . (int)$project_id;
?>
<link rel="stylesheet" type="text/css" href="./modules/projects/board.css" />
<div class="dp-board-bar">
	<?php if ($mine) { ?>
		<a href="<?php echo $base; ?>&amp;board_mine=0"><?php echo $AppUI->_('Show all tasks'); ?></a>
	<?php } else { ?>
		<a href="<?php echo $base; ?>&amp;board_mine=1"><?php echo $AppUI->_('Show only my tasks'); ?></a>
	<?php } ?>
	<span class="dp-board-hint"><?php echo $AppUI->_('Drag a card to another column, or use its arrows, to change its progress.'); ?></span>
	<span class="dp-board-status" role="status" aria-live="polite"></span>
</div>
<div class="dp-board" data-dp-board-url="?m=tasks&amp;a=do_board_move&amp;suppressHeaders=1"
	data-dp-board-error="<?php echo $AppUI->_('The task could not be moved.'); ?>">
<?php foreach ($columns as $col => $cards) { ?>
	<section class="dp-col" data-col="<?php echo $col; ?>" aria-label="<?php echo $AppUI->_($titles[$col]); ?>">
		<h3><?php echo $AppUI->_($titles[$col]); ?> <span class="dp-count"><?php echo count($cards); ?></span></h3>
		<div class="dp-cards">
		<?php foreach ($cards as $t) {
			$pc = (int)$t['task_percent_complete'];
			$end = $t['task_end_date'];
			$hasEnd = $end && strpos($end, '0000-00-00') !== 0;
			$late = $hasEnd && $end < $now && $pc < 100;
			?>
			<article class="dp-card<?php echo $t['editable'] ? ' dp-card-editable' : ''; ?>" data-task="<?php echo (int)$t['task_id']; ?>"
				<?php echo $t['editable'] ? 'draggable="true"' : ''; ?>>
				<div class="dp-card-title">
					<?php if ($t['task_milestone']) { ?><span class="dp-card-milestone" title="<?php echo $AppUI->_('Milestone'); ?>">&#9670;</span><?php } ?>
					<?php if ($t['task_priority'] > 0) { ?><span class="dp-card-priority" title="<?php echo $AppUI->_('High priority'); ?>">!</span><?php } ?>
					<a href="?m=tasks&amp;a=view&amp;task_id=<?php echo (int)$t['task_id']; ?>"><?php echo dPhtml($t['task_name']); ?></a>
				</div>
				<div class="dp-card-meta">
					<?php if ($hasEnd) {
						$d = new CDate($end);
						?><span class="dp-card-date<?php echo $late ? ' dp-card-late' : ''; ?>"><?php echo dPhtml($d->format($df)); ?></span><?php
					} ?>
					<span class="dp-card-percent"><?php echo $pc; ?>%</span>
				</div>
				<?php if (!empty($assignees[$t['task_id']])) { ?>
				<div class="dp-card-people"><?php echo dPhtml(implode(', ', $assignees[$t['task_id']])); ?></div>
				<?php } ?>
				<?php if ($t['editable']) { ?>
				<div class="dp-card-moves">
					<button type="button" class="dp-move" data-dir="-1" title="<?php echo $AppUI->_('Move left'); ?>" aria-label="<?php echo $AppUI->_('Move left'); ?>">&#9664;</button>
					<button type="button" class="dp-move" data-dir="1" title="<?php echo $AppUI->_('Move right'); ?>" aria-label="<?php echo $AppUI->_('Move right'); ?>">&#9654;</button>
				</div>
				<?php } ?>
			</article>
		<?php } ?>
		</div>
	</section>
<?php } ?>
</div>
