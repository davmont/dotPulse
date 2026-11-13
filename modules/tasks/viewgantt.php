<?php /* TASKS viewgantt.php - frappe-gantt renderer */
if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}

global $min_view, $m, $a, $user_id, $tab, $tasks, $sortByName, $project_id, $filter_task_list, $caller;

require_once DP_BASE_DIR . '/classes/gantt_renderer.class.php';

$base_url = dPgetConfig('base_url');
$min_view = defVal(@$min_view, false);

$project_id = (int)defVal(@$_GET['project_id'], 0);

$sdate = dPgetParam($_POST, 'sdate', 0);
$edate = dPgetParam($_POST, 'edate', 0);

$showNoMilestones = dPgetParam($_POST, 'showNoMilestones', '0');
$showNoMilestones = (($showNoMilestones != '0') ? '1' : $showNoMilestones);

$ganttTaskFilter = intval(dPgetParam($_REQUEST, 'ganttTaskFilter', '0'));

$sortByName = dPgetParam($_REQUEST, 'sortByName');
if ($sortByName == '1') {
    $sortByName = dPgetParam($_POST, 'sortByName', '1');
} else {
    $sortByName = dPgetParam($_POST, 'sortByName', '0');
}
$sortByName = (($sortByName != '0') ? '1' : $sortByName);

if ($a == 'todo') {
    if (isset($_POST['show_form'])) {
        $AppUI->setState('TaskDayShowArc',  dPgetParam($_POST, 'showArcProjs', 0));
        $AppUI->setState('TaskDayShowLow',  dPgetParam($_POST, 'showLowTasks', 0));
        $AppUI->setState('TaskDayShowHold', dPgetParam($_POST, 'showHoldProjs', 0));
        $AppUI->setState('TaskDayShowDyn',  dPgetParam($_POST, 'showDynTasks', 0));
        $AppUI->setState('TaskDayShowPin',  dPgetParam($_POST, 'showPinned', 0));
    }
    $showArcProjs  = $AppUI->getState('TaskDayShowArc', 0);
    $showLowTasks  = $AppUI->getState('TaskDayShowLow', 1);
    $showHoldProjs = $AppUI->getState('TaskDayShowHold', 0);
    $showDynTasks  = $AppUI->getState('TaskDayShowDyn', 0);
    $showPinned    = $AppUI->getState('TaskDayShowPin', 0);
} else {
    $showPinned    = dPgetParam($_POST, 'showPinned',    '0');
    $showPinned    = (($showPinned    != '0') ? '1' : $showPinned);
    $showArcProjs  = dPgetParam($_POST, 'showArcProjs',  '0');
    $showArcProjs  = (($showArcProjs  != '0') ? '1' : $showArcProjs);
    $showHoldProjs = dPgetParam($_POST, 'showHoldProjs', '0');
    $showHoldProjs = (($showHoldProjs != '0') ? '1' : $showHoldProjs);
    $showDynTasks  = dPgetParam($_POST, 'showDynTasks',  '0');
    $showDynTasks  = (($showDynTasks  != '0') ? '1' : $showDynTasks);
    $showLowTasks  = dPgetParam($_POST, 'showLowTasks',  '0');
    $showLowTasks  = (($showLowTasks  != '0') ? '1' : $showLowTasks);
}

/**
 * Build filter_task_list for the task filter dropdown.
 */
$filter_task_list = array();
$q = new DBQuery;
$q->addTable('projects');
$q->addQuery('project_id, project_color_identifier, project_name'
    . ', project_start_date, project_end_date');
$q->addJoin('tasks', 't1', 'project_id = t1.task_project');
$q->addWhere('project_status != 7');
$q->addGroup('project_id');
$q->addOrder('project_name');
$projects = $q->loadHashList('project_id');
$q->clear();

$q->addTable('tasks', 't');
$q->addJoin('projects', 'p', 'p.project_id = t.task_project');
$q->addQuery('t.task_id, task_parent, task_name, task_start_date, task_end_date'
    . ', task_duration, task_duration_type, task_priority, task_percent_complete'
    . ', task_order, task_project, task_milestone, project_name, task_dynamic');
$q->addWhere('project_status != 7 AND task_dynamic = 1');
if ($project_id) {
    $q->addWhere('task_project = ' . (int)$project_id);
}
$task_acl = new CTask;
$task_acl->setAllowedSQL($AppUI->user_id, $q);
$proTasks = $q->loadHashList('task_id');
$q->clear();

$orrarr[] = array('task_id' => 0, 'order_up' => 0, 'order' => '');
foreach ($proTasks as $row) {
    $projects[$row['task_project']]['tasks'][] = $row;
}
unset($proTasks);

$parents = array();
if (!function_exists('showfiltertask')) {
    function showfiltertask(&$a, $level = 0) {
        global $filter_task_list, $parents;
        $filter_task_list[] = array($a, $level);
        $parents[$a['task_parent']] = true;
    }
}
if (!function_exists('findfiltertaskchild')) {
    function findfiltertaskchild(&$tarr, $parent, $level = 0) {
        global $projects, $filter_task_list;
        $level = $level + 1;
        $n = count($tarr);
        for ($x = 0; $x < $n; $x++) {
            if ($tarr[$x]['task_parent'] == $parent && $tarr[$x]['task_parent'] != $tarr[$x]['task_id']) {
                showfiltertask($tarr[$x], $level);
                findfiltertaskchild($tarr, $tarr[$x]['task_id'], $level);
            }
        }
    }
}

foreach ($projects as $p) {
    global $parents, $task_id;
    $parents = array();
    $tasks = $p['tasks'] ?? [];
    $tnums = count($tasks);
    for ($i = 0; $i < $tnums; $i++) {
        $t = $tasks[$i];
        if (!(isset($parents[$t['task_parent']]))) {
            $parents[$t['task_parent']] = false;
        }
        if ($t['task_parent'] == $t['task_id']) {
            showfiltertask($t);
            findfiltertaskchild($tasks, $t['task_id']);
        }
    }
    foreach ($parents as $id => $ok) {
        if (!($ok)) {
            findfiltertaskchild($tasks, $id);
        }
    }
}

// Date range
$scroll_date = 1;
$display_option = dPgetParam($_POST, 'display_option', 'all');
$df = $AppUI->getPref('SHDATEFORMAT');

if ($display_option == 'custom') {
    $start_date = ((intval($sdate)) ? new CDate($sdate) : new CDate());
    $end_date   = ((intval($edate)) ? new CDate($edate) : new CDate());
} else {
    $start_date = new CDate();
    $start_date->day = 1;
    $end_date = new CDate($start_date);
    $end_date->addMonths($scroll_date);
}

// Title block
if (!@$min_view) {
    $titleBlock = new CTitleBlock('Gantt Chart', 'applet-48.png', $m, "$m.$a");
    $titleBlock->addCrumb('?m=tasks', 'tasks list');
    $titleBlock->addCrumb(('?m=projects&amp;a=view&amp;project_id=' . $project_id), 'view this project');
    $titleBlock->show();
}
?>
<script language="javascript" type="text/javascript" nonce="<?php echo dPcspNonce(); ?>">
	// <![CDATA[
	var calendarField = "";

	function popCalendar(field) {
		calendarField = field;
		idate = document.editFrm[field].value;
		window.open('?m=public&' + 'a=calendar&' + 'dialog=1&' + 'callback=setCalendar&' + 'date=' + idate,
			"calwin", "width=250, height=230, scrollbars=no, status=no");
	}
	function setCalendar(idate, fdate) {
		fld_date = document.editFrm[calendarField];
		fld_fdate = document.editFrm["show_" + calendarField];
		fld_date.value = idate;
		fld_fdate.value = fdate;
	}
	function scrollPrev() {
		f = document.editFrm;
		<?php
		$new_start = new CDate($start_date);
		$new_start->day = 1;
		$new_end = new CDate($end_date);
		$new_start->addMonths(-$scroll_date);
		$new_end->addMonths(-$scroll_date);
		echo ('f.sdate.value=' . dPjs($new_start->format(FMT_TIMESTAMP_DATE)) . ';');
		echo ('f.edate.value=' . dPjs($new_end->format(FMT_TIMESTAMP_DATE)) . ';');
		?>
		document.editFrm.display_option.value = "custom";
		f.submit()
	}
	function scrollNext() {
		f = document.editFrm;
		<?php
		$new_start = new CDate($start_date);
		$new_start->day = 1;
		$new_end = new CDate($end_date);
		$new_start->addMonths($scroll_date);
		$new_end->addMonths($scroll_date);
		echo ('f.sdate.value=' . dPjs($new_start->format(FMT_TIMESTAMP_DATE)) . ';');
		echo ('f.edate.value=' . dPjs($new_end->format(FMT_TIMESTAMP_DATE)) . ';');
		?>
		document.editFrm.display_option.value = "custom";
		f.submit()
	}
	function submitCustomDate() {
		document.editFrm.display_option.value = "custom";
		document.editFrm.submit();
	}
	function showThisMonth() {
		document.editFrm.display_option.value = "this_month";
		document.editFrm.submit();
	}
	function showFullProject() {
		document.editFrm.display_option.value = "all";
		document.editFrm.submit();
	}
	function toggleLayer(whichLayer) {
		var elem = document.getElementById(whichLayer);
		var vis = elem.style;
		vis.display = (vis.display == '' || vis.display == 'block') ? 'none' : 'block';
	}
	function submitIt() {
		document.editFrm.submit();
	}
	function doMenu(item) {
		obj = document.getElementById(item);
		col = document.getElementById("x" + item);
		if (obj.style.display == "none") {
			obj.style.display = "block";
			col.textContent = <?php echo dPjs($AppUI->_('Hide Additional Gantt Options', UI_OUTPUT_RAW)); ?>;
		} else {
			obj.style.display = "none";
			col.textContent = <?php echo dPjs($AppUI->_('Show Additional Gantt Options', UI_OUTPUT_RAW)); ?>;
		}
	}
	//]]>
</script>

<div id="displayOptions" style="display:block">
	<br />
	<form name="editFrm" method="post" action="?<?php
	echo 'm=' . $m . '&amp;a=' . $a . '&amp;tab=' . $tab . '&amp;project_id=' . $project_id; ?>">
		<input type="hidden" name="display_option" value="<?php echo dPhtml($display_option); ?>" />
		<input type="hidden" name="caller" value="<?php echo $a; ?>" />
		<table border="0" align="center" class="tbl" cellpadding="2" cellspacing="0" width="100%">
			<tr>
				<td align="right"><em><?php echo $AppUI->_('Date Filter'); ?>:</em></td>
				<td align="right">
					<table border="0" cellpadding="4" cellspacing="0">
						<tr>
							<td align="left" valign="top" width="20">
								<?php if ($display_option != "all") { ?>
									<a href="#"<?php echo dPaction('scrollPrev'); ?>>
										<img src="./images/prev.gif" width="16" height="16"
											alt="<?php echo $AppUI->_('previous'); ?>" border="0" />
									</a>
								<?php } ?>
							</td>
							<td align="right" nowrap="nowrap"><?php echo $AppUI->_('From'); ?>:</td>
							<td align="left" nowrap="nowrap">
								<input type="hidden" name="sdate"
									value="<?php echo $start_date->format(FMT_TIMESTAMP_DATE); ?>" />
								<input type="text" class="text" name="show_sdate"
									value="<?php echo $start_date->format($df); ?>" size="12" disabled="disabled" />
								<a href="#"<?php echo dPaction('popCalendar', array('sdate')); ?>>
									<img src="./images/calendar.gif" width="24" height="12" alt="" border="0" />
								</a>
							</td>
							<td align="right" nowrap="nowrap"><?php echo $AppUI->_('To'); ?>:</td>
							<td align="left" nowrap="nowrap">
								<input type="hidden" name="edate"
									value="<?php echo $end_date->format(FMT_TIMESTAMP_DATE); ?>" />
								<input type="text" class="text" name="show_edate"
									value="<?php echo $end_date->format($df); ?>" size="12" disabled="disabled" />
								<a href="#"<?php echo dPaction('popCalendar', array('edate')); ?>>
									<img src="./images/calendar.gif" width="24" height="12" alt="" border="0" />
								</a>
							</td>
							<td align="left">
								<input type="button" class="button"
									value="<?php echo $AppUI->_('submit custom date'); ?>"
									<?php echo dPaction('submitCustomDate'); ?> />
							</td>
							<td align="right" valign="top" width="20">
								<?php if ($display_option != "all") { ?>
									<a href="#"<?php echo dPaction('scrollNext'); ?>>
										<img src="./images/next.gif" width="16" height="16"
											alt="<?php echo $AppUI->_('next'); ?>" border="0" />
									</a>
								<?php } ?>
							</td>
						</tr>
					</table>
				</td>
				<td colspan="2">&nbsp;</td>
			</tr>
			<tr>
				<td align="right"><em><?php echo $AppUI->_('Quick Date Filter'); ?>:</em></td>
				<td colspan="3" align="left">
					<table border="0" cellpadding="0" cellspacing="0">
						<tr>
							<td align="right">
								<input type="button" style="width: 110px;" class="button"
									value="<?php echo $AppUI->_('show this month'); ?>"
									<?php echo dPaction('showThisMonth'); ?> />
								&nbsp;
							</td>
							<td align="right">
								<input type="button" style="width: 110px;" class="button"
									value="<?php echo $AppUI->_('show full project'); ?>"
									<?php echo dPaction('showFullProject'); ?> />
								&nbsp;
							</td>
						</tr>
					</table>
				</td>
			</tr>

			<tr>
				<td align="right"><em><?php echo $AppUI->_('Task Filter'); ?>:</em></td>
				<td align="right">
					<table border="0" cellpadding="4" cellspacing="0">
						<tr>
							<td width="210">
								<select name="ganttTaskFilter" id="ganttTaskFilter" class="text"
									<?php echo dPaction('submitIt'); ?> size="1">
									<?php
									echo '<option value="0" ' . (($ganttTaskFilter == '' || $ganttTaskFilter == 0) ? ' selected="selected">' : '>') . '&lt;' . $AppUI->_('Show all tasks') . '&gt; </option>';
									for ($i = 0; $i < count($filter_task_list); $i++) {
										$filter_task_name  = $filter_task_list[$i][0]['task_name'];
										$filter_task_level = $filter_task_list[$i][1];
										// mb_strcut on UTF-8 pages: a split character would make dPhtml() return ''.
										$filter_task_cut   = (strcasecmp((string)@$GLOBALS['locale_char_set'], 'utf-8') 
										                      ? 'substr' : function ($s, $o, $l) { return mb_strcut($s, $o, $l, 'UTF-8'); });
										$filter_task_name  = dPhtml((strlen($filter_task_name) > 71) ? $filter_task_cut($filter_task_name, 0, (68 - $filter_task_level)) . '...' : $filter_task_name);
										for ($ii = 1; $ii <= $filter_task_level; $ii++) {
											$filter_task_name = '&nbsp;&nbsp;' . $filter_task_name;
										}
										echo ('<option value="' . $filter_task_list[$i][0]['task_id'] . '"'
											. (($ganttTaskFilter == $filter_task_list[$i][0]['task_id']) ? ' selected="selected">' : '>')
											. $filter_task_name . '</option>');
									} ?>
								</select>
							</td>
						</tr>
					</table>
				</td>
				<td colspan="2">&nbsp;</td>
			</tr>

			<tr align="left">
				<th colspan="4" align="left"><em><a class="hdr" href="#"<?php echo dPaction('doMenu', array('ganttoptions')); ?>
							id="xganttoptions"><?php echo $AppUI->_('Show Additional Gantt Options'); ?></a></em></th>
			</tr>
			<tr align="left">
				<td colspan="4">
					<table border="0" id="ganttoptions" style="display:none" width="100%" align="center">
						<tr>
							<td width="100%">
								<table border="0" cellpadding="2" cellspacing="0" width="100%" align="center">
									<tr>
										<td>&nbsp;<?php echo $AppUI->_('Tasks'); ?>&nbsp;:</td>
										<td valign="top">
											<input type="checkbox" name="sortByName" id="sortByName" <?php echo (($sortByName == 1) ? 'checked="checked"' : ''); ?> />
											<label for="sortByName"><?php echo $AppUI->_('Sort by Name'); ?></label>
										</td>
										<td valign="top">
											<input type="checkbox" name="showNoMilestones" id="showNoMilestones" <?php echo (($showNoMilestones == 1) ? 'checked="checked"' : ''); ?> />
											<label for="showNoMilestones"><?php echo $AppUI->_('Hide Milestones'); ?></label>
										</td>
										<td colspan="2" valign="middle">&nbsp;
											<input type="button" style="float:right;width:110px;" class="button"
												value="<?php echo $AppUI->_('submit'); ?>"
												<?php echo dPaction('submitIt'); ?> />
										</td>
									</tr>
									<?php if ($a == 'todo') { ?>
										<input type="hidden" name="show_form" value="1" />
									<tr>
										<td>&nbsp;<?php echo $AppUI->_('To Do Options'); ?>:&nbsp;</td>
										<td valign="bottom" nowrap="nowrap">
											<input type="checkbox" name="showPinned" id="showPinned" <?php echo $showPinned ? 'checked="checked"' : ''; ?> />
											<label for="showPinned"><?php echo $AppUI->_('Pinned Only'); ?></label>
										</td>
										<td valign="bottom" nowrap="nowrap">
											<input type="checkbox" name="showArcProjs" id="showArcProjs" <?php echo $showArcProjs ? 'checked="checked"' : ''; ?> />
											<label for="showArcProjs"><?php echo $AppUI->_('Archived Projects'); ?></label>
										</td>
										<td valign="bottom" nowrap="nowrap">
											<input type="checkbox" name="showHoldProjs" id="showHoldProjs" <?php echo $showHoldProjs ? 'checked="checked"' : ''; ?> />
											<label for="showHoldProjs"><?php echo $AppUI->_('Projects on Hold'); ?></label>
										</td>
										<td valign="bottom" nowrap="nowrap">
											<input type="checkbox" name="showDynTasks" id="showDynTasks" <?php echo $showDynTasks ? 'checked="checked"' : ''; ?> />
											<label for="showDynTasks"><?php echo $AppUI->_('Dynamic Tasks'); ?></label>
										</td>
										<td valign="bottom" nowrap="nowrap">
											<input type="checkbox" name="showLowTasks" id="showLowTasks" <?php echo $showLowTasks ? 'checked="checked"' : ''; ?> />
											<label for="showLowTasks"><?php echo $AppUI->_('Low Priority Tasks'); ?></label>
										</td>
									</tr>
									<?php } ?>
								</table>
							</td>
						</tr>
					</table>
				</td>
			</tr>
		</table>
	</form>
</div>
<br />
<?php

// -------- Task data query --------
$q = new DBQuery;

if ($a == 'todo') {
    $todo_user_id = intval(defVal(@$_REQUEST['user_id'], $AppUI->user_id));
    $q->addTable('tasks', 't');
    $q->innerJoin('projects', 'p', 'p.project_id = t.task_project');
    $q->innerJoin('user_tasks', 'ut', 'ut.task_id = t.task_id AND ut.user_id = ' . (int)$todo_user_id);
    $q->leftJoin('user_task_pin', 'tp', 'tp.task_id = t.task_id AND tp.user_id = ' . (int)$todo_user_id);
    $q->addQuery('t.task_id, t.task_name, t.task_start_date, t.task_end_date, t.task_percent_complete, t.task_milestone');
    $q->addWhere('(t.task_percent_complete < 100 OR t.task_percent_complete IS NULL)');
    $q->addWhere('t.task_status = 0');
    if (!$showArcProjs)  $q->addWhere('p.project_status <> 7');
    if (!$showLowTasks)  $q->addWhere('t.task_priority >= 0');
    if (!$showHoldProjs) $q->addWhere('p.project_status != 4');
    if (!$showDynTasks)  $q->addWhere('t.task_dynamic != 1');
    if ($showPinned)     $q->addWhere('tp.task_pinned = 1');
    $q->addOrder($sortByName ? 't.task_name ASC' : 't.task_end_date ASC, t.task_priority DESC');
} else {
    $q->addTable('tasks', 't');
    $q->addJoin('projects', 'p', 'p.project_id = t.task_project');
    $q->addQuery('t.task_id, t.task_name, t.task_start_date, t.task_end_date, t.task_percent_complete, t.task_milestone');
    $q->addWhere('p.project_status != 7');
    $q->addWhere('t.task_status > -1');
    if ($project_id)              $q->addWhere('t.task_project = ' . intval($project_id));
    if ($showNoMilestones == '1') $q->addWhere('t.task_milestone != 1');
    // custom period or this month (the dates set above); 'all' shows everything
    if ($display_option != 'all') {
        $q->addWhere('t.task_start_date <= "' . $end_date->format('%Y-%m-%d') . ' 23:59:59"');
        $q->addWhere('(t.task_end_date   >= "' . $start_date->format('%Y-%m-%d') . ' 00:00:00" OR t.task_end_date = "0000-00-00 00:00:00")');
    }
    if ($ganttTaskFilter > 0) {
        $filt_task = new CTask();
        $filt_task->peek($ganttTaskFilter);
        $children = $filt_task->getDeepChildren();
        $ids = array_map('intval', array_merge([$ganttTaskFilter], (array)$children));
        $q->addWhere('t.task_id IN (' . implode(',', $ids) . ')');
    }
    $q->addOrder($sortByName ? 't.task_name ASC' : 'p.project_id, t.task_start_date ASC');
}

$task_acl2 = new CTask;
$task_acl2->setAllowedSQL($AppUI->user_id, $q);
$raw_tasks = $q->loadList();
$q->clear();

$gantt_tasks = GanttRenderer::taskRows($raw_tasks);

if (!empty($gantt_tasks)) {
    GanttRenderer::render('dp-task-gantt', $gantt_tasks, '', 'Week');
} else {
    echo '<p>' . $AppUI->_('No tasks to display') . '</p>';
}
?>
<br />
