<?php /* TASKS $Id: viewgantt.php 6149 2012-01-09 11:58:40Z ajdonnison $ - frappe-gantt renderer */
if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}

global $AppUI, $company_id, $dept_ids, $department, $min_view, $m, $a, $user_id, $tab, $dPconfig;
global $m_orig, $a_orig;

require_once DP_BASE_DIR . '/classes/gantt_renderer.class.php';
require_once $AppUI->getModuleClass('projects');

$min_view   = defVal($min_view, false);
$project_id = intval(dPgetParam($_GET, 'project_id', 0));
$user_id    = intval(dPgetParam($_GET, 'user_id', $AppUI->user_id));

$sdate           = dPgetCleanParam($_POST, 'sdate', 0);
$edate           = dPgetCleanParam($_POST, 'edate', 0);
$showInactive    = (int)dPgetParam($_POST, 'showInactive',    '0');
$showLabels      = (int)dPgetParam($_POST, 'showLabels',      '0');
$sortTasksByName = (int)dPgetParam($_POST, 'sortTasksByName', '0');
$showAllGantt    = (int)dPgetParam($_POST, 'showAllGantt',    '0');
$showTaskGantt   = (int)dPgetParam($_POST, 'showTaskGantt',   '0');
$addPwOiD        = (int)dPgetParam($_POST, 'add_pwoid', isset($addPwOiD) ? $addPwOiD : 0);
$m_orig = $m;
$a_orig = $a;

if ($showLabels    != '0') $showLabels    = '1';
if ($showInactive  != '0') $showInactive  = '1';
if ($showAllGantt  != '0') $showAllGantt  = '1';

if (isset($_POST['proFilter'])) {
    $AppUI->setState('ProjectIdxFilter', $_POST['proFilter']);
}
$proFilter = (($AppUI->getState('ProjectIdxFilter') !== NULL)
              ? $AppUI->getState('ProjectIdxFilter') : '-1');

$projectStatus = dPgetSysVal('ProjectStatus');
$projFilter = arrayMerge(array('-1' => 'All Projects', '-2' => 'All w/o in progress',
                               '-3' => (($AppUI->user_id == $user_id) ? 'My projects'
                                        : "User's projects")), $projectStatus);
if (!(empty($projFilter_extra))) {
    $projFilter = arrayMerge($projFilter, $projFilter_extra);
}
natsort($projFilter);

$scroll_date    = 1;
// the company and department tabs ($min_view) have no date form: show all there
$display_option = dPgetCleanParam($_POST, 'display_option', @$min_view ? 'all' : 'this_month');
$df             = $AppUI->getPref('SHDATEFORMAT');

if ($display_option == 'custom') {
    $start_date = intval($sdate) ? new CDate($sdate) : new CDate();
    $end_date   = intval($edate) ? new CDate($edate) : new CDate();
} else {
    $start_date = new CDate();
    $start_date->day = 1;
    $end_date = new CDate($start_date);
    $end_date->addMonths($scroll_date);
}

if (!@$min_view) {
    $titleBlock = new CTitleBlock('Gantt Chart', 'applet3-48.png', $m, "$m.$a");
    $titleBlock->addCrumb(('?m=' . $m), 'projects list');
    $titleBlock->show();
}
?>

<script type="text/javascript" language="javascript" nonce="<?php echo dPcspNonce(); ?>">
var calendarField = '';

function popCalendar(field) {
	calendarField = field;
	idate = document.editFrm[field].value;
	window.open('?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + idate, 'calwin', 'width=250, height=220, scrollbars=no, status=no');
}
function setCalendar(idate, fdate) {
	fld_date = document.editFrm[calendarField];
	fld_fdate = document.editFrm['show_' + calendarField];
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
echo "f.sdate.value='" . $new_start->format(FMT_TIMESTAMP_DATE) . "';";
echo "f.edate.value='" . $new_end->format(FMT_TIMESTAMP_DATE) . "';";
?>
	document.editFrm.display_option.value = 'custom';
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
echo "f.sdate.value='" . $new_start->format(FMT_TIMESTAMP_DATE) . "';";
echo "f.edate.value='" . $new_end->format(FMT_TIMESTAMP_DATE) . "';";
?>
	document.editFrm.display_option.value = 'custom';
	f.submit()
}
function showThisMonth() {
	document.editFrm.display_option.value = "this_month";
	document.editFrm.submit();
}
function showFullProject() {
	document.editFrm.display_option.value = "all";
	document.editFrm.submit();
}
function showCustomPeriod() {
	document.editFrm.display_option.value = "custom";
	document.editFrm.submit();
}
</script>
<table class="tbl" width="100%" border="0" cellpadding="4" cellspacing="0" summary="projects view gantt">
<tr>
	<td>
		<form name="editFrm" method="post" action="?<?php
$url_query_string = '';
foreach ($_GET as $key => $val) {
    if (is_array($val)) {
        continue;
    }
    $url_query_string .= (($url_query_string) ? '&amp;' : '') . rawurlencode($key) . '=' . rawurlencode($val);
}
echo ($url_query_string);
?>">
		<input type="hidden" name="display_option" value="<?php echo dPhtml($display_option); ?>" />
		<table border="0" cellpadding="4" cellspacing="0" class="tbl" summary="select dates for graphs">
		<tr>
			<td align="left" valign="top" width="20">
<?php if ($display_option != "all") { ?>
				<a href="#"<?php echo dPaction('scrollPrev'); ?>>
				<img src="./images/prev.gif" width="16" height="16" alt="<?php
    echo $AppUI->_('previous'); ?>" border="0" />
				</a>
<?php } ?>
			</td>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('From'); ?>:</td>
			<td align="left" nowrap="nowrap">
				<input type="hidden" name="sdate" value="<?php
echo $start_date->format(FMT_TIMESTAMP_DATE); ?>" />
				<input type="text" class="text" name="show_sdate" value="<?php
echo $start_date->format($df); ?>" size="12" disabled="disabled" />
				<a href="#"<?php echo dPaction('popCalendar', array('sdate')); ?>>
				<img src="./images/calendar.gif" width="24" height="12" alt="" border="0" />
				</a>
			</td>
			<td align="right" nowrap="nowrap"><?php echo $AppUI->_('To'); ?>:</td>
			<td align="left" nowrap="nowrap">
				<input type="hidden" name="edate" value="<?php
echo $end_date->format(FMT_TIMESTAMP_DATE); ?>" />
				<input type="text" class="text" name="show_edate" value="<?php
echo $end_date->format($df); ?>" size="12" disabled="disabled" />
				<a href="#"<?php echo dPaction('popCalendar', array('edate')); ?>>
				<img src="./images/calendar.gif" width="24" height="12" alt="" border="0" />
				</a>
			</td>
			<td valign="top">
				<?php
echo arraySelect($projFilter, 'proFilter', 'size="1" class="text"', $proFilter, true); ?>
			</td>
			<td valign="top">
				<input type="checkbox" name="showInactive" id="showInactive" value='1' <?php
echo (($showInactive == 1) ? 'checked="checked"' : ""); ?> /><label for="showInactive"><?php
echo $AppUI->_('Show Archived'); ?></label>
			</td>
			<td valign="top">
				<input type="checkbox" value='1' name="showAllGantt" id="showAllGantt" <?php
echo (($showAllGantt == 1) ? 'checked="checked"' : ""); ?> /><label for="showAllGantt"><?php
echo $AppUI->_('Show Tasks'); ?></label>
			</td>
			<td valign="top">
				<input type="checkbox" value='1' name="sortTasksByName" id="sortTasksByName" <?php
echo (($sortTasksByName == 1) ? 'checked="checked"' : ""); ?> /><label for="sortTasksByName"><?php
echo $AppUI->_('Sort Tasks By Name'); ?></label>
			</td>
			<td align="left">
				<input type="button" class="button" value="<?php
echo $AppUI->_('submit'); ?>"<?php echo dPaction('showCustomPeriod'); ?> />
			</td>
			<td align="right" valign="top" width="20">
<?php if ($display_option != "all") { ?>
			<a href="#"<?php echo dPaction('scrollNext'); ?>>
				<img src="./images/next.gif" width="16" height="16" alt="<?php
echo $AppUI->_('next'); ?>" border="0" />
			</a>
<?php } ?>
			</td>
		</tr>
		<tr>
			<td align="center" valign="bottom" colspan="12">
				<?php
echo ('<a href="#"' . dPaction('showThisMonth') . '>' . $AppUI->_('show this month')
    . '</a> : <a href="#"' . dPaction('showFullProject') . '>' . $AppUI->_('show all') . '</a><br />');
?>
			</td>
		</tr>
		</table>
		</form>

<?php
// -------- Project data query --------
$q    = new DBQuery;
$pjobj        = new CProject;
$working_hours = $dPconfig['daily_working_hours'];

$owner_ids = [];
if ($addPwOiD && $department > 0) {
    $q->addTable('users');
    $q->addQuery('user_id');
    $q->addJoin('contacts', 'c', 'c.contact_id = user_contact');
    $q->addWhere('c.contact_department = ' . (int)$department);
    $owner_ids = $q->loadColumn();
    $q->clear();
}

$q->addTable('projects', 'p');
$q->addQuery('DISTINCT p.project_id, project_color_identifier, project_name, project_start_date'
    . ', project_end_date, project_status'
    . ', SUM(task_duration * task_percent_complete * IF(task_duration_type = 24, '
    . $working_hours . ', task_duration_type))'
    . ' / NULLIF(SUM(task_duration * IF(task_duration_type = 24, '
    . $working_hours . ', task_duration_type)), 0) AS project_percent_complete');
$q->addJoin('tasks', 't1', 'p.project_id = t1.task_project');
$q->addJoin('companies', 'c1', 'p.project_company = c1.company_id');

if ($department > 0) {
    $q->addJoin('project_departments', 'pd', 'pd.project_id = p.project_id');
    if (!$addPwOiD) {
        $q->addWhere('pd.department_id = ' . (int)$department);
    } else {
        $q->addWhere('p.project_owner IN (' . ((!empty($owner_ids)) ? implode(',', $owner_ids) : 0) . ')');
    }
} elseif ($company_id != 0 && !$addPwOiD) {
    $q->addWhere('(project_company = ' . (int)$company_id . ' OR project_company_internal = ' . (int)$company_id . ')');
}

if ($proFilter == '-4')       $q->addWhere('project_status != 7');
elseif ($proFilter == '-3')   $q->addWhere('project_owner = ' . (int)$user_id);
elseif ($proFilter == '-2')   $q->addWhere('project_status != 3');
elseif ($proFilter != '-1')   $q->addWhere('project_status = ' . intval($proFilter));

if ($user_id && $m_orig == 'admin' && $a_orig == 'viewuser') {
    $q->addWhere('project_owner = ' . (int)$user_id);
}
if ($showInactive != '1') $q->addWhere('project_status != 7');

$pjobj->setAllowedSQL($AppUI->user_id, $q, null, 'p');
$q->addGroup('p.project_id');

// custom period or this month (the dates set above); 'all' shows everything
if ($display_option != 'all') {
    $q->addWhere('p.project_start_date <= "' . $end_date->format('%Y-%m-%d') . ' 23:59:59"');
    $q->addWhere('(p.project_end_date >= "' . $start_date->format('%Y-%m-%d') . ' 00:00:00"'
        . ' OR p.project_end_date = "0000-00-00 00:00:00")');
}
$q->addOrder($sortTasksByName ? 'project_name ASC' : 'project_name ASC');
$projects_list = $q->loadList();
$q->clear();

// Build frappe-gantt format
$gantt_tasks = [];
foreach ((array)$projects_list as $p) {
    $start = substr($p['project_start_date'], 0, 10);
    $end   = substr($p['project_end_date'],   0, 10);
    if (empty($start) || $start == '0000-00-00') $start = date('Y-m-d');
    if (empty($end)   || $end   == '0000-00-00' || $end <= $start) {
        $end = date('Y-m-d', strtotime($start . ' +1 day'));
    }
    $gantt_tasks[] = [
        'id'           => 'p' . $p['project_id'],
        'name'         => $p['project_name'],
        'start'        => $start,
        'end'          => $end,
        'progress'     => intval($p['project_percent_complete'] + 0),
        'dependencies' => ''
    ];

    if ($showAllGantt) {
        $q->addTable('tasks', 't');
        $q->addQuery('t.task_id, t.task_name, t.task_start_date, t.task_end_date, t.task_percent_complete');
        $q->addWhere('t.task_project = ' . intval($p['project_id']));
        $q->addOrder($sortTasksByName ? 't.task_name ASC' : 't.task_end_date ASC');
        $ptasks = $q->loadList();
        $q->clear();
        foreach ((array)$ptasks as $t) {
            $ts = substr($t['task_start_date'], 0, 10);
            $te = substr($t['task_end_date'],   0, 10);
            if (empty($ts) || $ts == '0000-00-00') $ts = $start;
            if (empty($te) || $te == '0000-00-00' || $te <= $ts) {
                $te = date('Y-m-d', strtotime($ts . ' +1 day'));
            }
            $gantt_tasks[] = [
                'id'           => 't' . $t['task_id'],
                'name'         => '  ↳ ' . $t['task_name'],
                'start'        => $ts,
                'end'          => $te,
                'progress'     => intval($t['task_percent_complete']),
                'dependencies' => ''
            ];
        }
    }
}

if (!empty($gantt_tasks)) {
    GanttRenderer::render('dp-projects-gantt', $gantt_tasks, '', 'Week', true);
} else {
    echo '<p>' . $AppUI->_('No projects found') . '</p>';
}
?>
	</td>
</tr>
</table>
