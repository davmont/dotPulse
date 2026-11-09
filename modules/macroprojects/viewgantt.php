<?php /* MACRO_PROJECTS viewgantt.php - frappe-gantt renderer */
if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}

global $AppUI, $company_id, $dept_ids, $department, $min_view, $m, $a, $user_id, $tab, $dPconfig;
global $m_orig, $a_orig;

require_once DP_BASE_DIR . '/classes/gantt_renderer.class.php';
require_once $AppUI->getModuleClass('macroprojects');

$min_view        = defVal($min_view, false);
$macroproject_id = intval(dPgetParam($_GET, 'macroproject_id', 0));
$user_id         = intval(dPgetParam($_GET, 'user_id', $AppUI->user_id));

$sdate           = dPgetParam($_POST, 'sdate', 0);
$edate           = dPgetParam($_POST, 'edate', 0);
$showInactive    = dPgetParam($_POST, 'showInactive',    '0');
$showLabels      = dPgetParam($_POST, 'showLabels',      '0');
$sortTasksByName = dPgetParam($_POST, 'sortTasksByName', '0');
$showAllGantt    = dPgetParam($_POST, 'showAllGantt',    '0');
$showTaskGantt   = dPgetParam($_POST, 'showTaskGantt',   '0');
$addPwOiD        = dPgetParam($_POST, 'add_pwoid', isset($addPwOiD) ? $addPwOiD : 0);
$m_orig = $m;
$a_orig = $a;

if ($showLabels   != '0') $showLabels   = '1';
if ($showInactive != '0') $showInactive = '1';
if ($showAllGantt != '0') $showAllGantt = '1';

if (isset($_POST['macroproFilter'])) {
    $AppUI->setState('MacroProjectIdxFilter', $_POST['macroproFilter']);
}
$macroproFilter = (($AppUI->getState('MacroProjectIdxFilter') !== NULL)
                  ? $AppUI->getState('MacroProjectIdxFilter') : '-1');

$macroProjectStatus = dPgetSysVal('MacroProjectStatus');
$macroprojFilter = arrayMerge(array('-1' => 'All MacroProjects', '-2' => 'All w/o in progress',
                               '-3' => (($AppUI->user_id == $user_id) ? 'My macroprojects'
                                        : "User's macroprojects")), $macroProjectStatus);
if (!(empty($macroprojFilter_extra))) {
    $macroprojFilter = arrayMerge($macroprojFilter, $macroprojFilter_extra);
}
natsort($macroprojFilter);

$scroll_date    = 1;
$display_option = dPvalidateOrder(dPgetParam($_POST, 'display_option', 'this_month'), array('this_month', 'all', 'custom'), 'this_month');
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
    $titleBlock->addCrumb(('?m=' . $m), 'macroprojects list');
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
echo 'f.sdate.value=' . dPjs($new_start->format(FMT_TIMESTAMP_DATE)) . ';';
echo 'f.edate.value=' . dPjs($new_end->format(FMT_TIMESTAMP_DATE)) . ';';
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
echo 'f.sdate.value=' . dPjs($new_start->format(FMT_TIMESTAMP_DATE)) . ';';
echo 'f.edate.value=' . dPjs($new_end->format(FMT_TIMESTAMP_DATE)) . ';';
?>
	document.editFrm.display_option.value = 'custom';
	f.submit()
}
function showThisMonth() {
	document.editFrm.display_option.value = "this_month";
	document.editFrm.submit();
}
function showFullMacroProject() {
	document.editFrm.display_option.value = "all";
	document.editFrm.submit();
}
function macroprojectsGanttCustom() {
	document.editFrm.display_option.value = "custom";
	this.form.submit();
}
</script>
<table class="tbl" width="100%" border="0" cellpadding="4" cellspacing="0" summary="macroprojects view gantt">
<tr>
	<td>
		<form name="editFrm" method="post" action="?<?php
echo htmlspecialchars(http_build_query($_GET), ENT_QUOTES);
?>">
		<input type="hidden" name="display_option" value="<?php echo $display_option; ?>" />
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
echo arraySelect($macroprojFilter, 'macroproFilter', 'size="1" class="text"', $macroproFilter, true); ?>
			</td>
			<td valign="top">
				<input type="checkbox" name="showInactive" id="showInactive" value='1' <?php
echo (($showInactive == 1) ? 'checked="checked"' : ""); ?> /><label for="showInactive"><?php
echo $AppUI->_('Show Archived'); ?></label>
			</td>
			<td valign="top">
				<input type="checkbox" value='1' name="sortTasksByName" id="sortTasksByName" <?php
echo (($sortTasksByName == 1) ? 'checked="checked"' : ""); ?> /><label for="sortTasksByName"><?php
echo $AppUI->_('Sort By Name'); ?></label>
			</td>
			<td align="left">
				<input type="button" class="button" value="<?php
echo $AppUI->_('submit'); ?>"<?php echo dPaction('macroprojectsGanttCustom'); ?> />
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
    . '</a> : <a href="#"' . dPaction('showFullMacroProject') . '>' . $AppUI->_('show all') . '</a><br />');
?>
			</td>
		</tr>
		</table>
		</form>

<?php
// -------- MacroProject data query --------
$q    = new DBQuery;
$mpobj        = new CMacroProject;

$owner_ids = [];
if ($addPwOiD && $department > 0) {
    $q->addTable('users');
    $q->addQuery('user_id');
    $q->addJoin('contacts', 'c', 'c.contact_id = user_contact');
    $q->addWhere('c.contact_department = ' . (int)$department);
    $owner_ids = $q->loadColumn();
    $q->clear();
}

$q->addTable('macroprojects', 'mp');
$q->addQuery('DISTINCT mp.macroproject_id, macroproject_color_identifier, macroproject_name'
    . ', macroproject_start_date, macroproject_end_date, macroproject_percent_complete'
    . ', macroproject_status');
$q->addJoin('companies', 'c1', 'mp.macroproject_company = c1.company_id');

if ($department > 0) {
    $q->addJoin('macroproject_departments', 'mpd', 'mpd.macroproject_id = mp.macroproject_id');
    if (!$addPwOiD) {
        $q->addWhere('mpd.department_id = ' . (int)$department);
    } else {
        $q->addWhere('mp.macroproject_owner IN (' . ((!empty($owner_ids)) ? implode(',', $owner_ids) : 0) . ')');
    }
} elseif ($company_id != 0 && !$addPwOiD) {
    $q->addWhere('macroproject_company = ' . (int)$company_id);
}

if ($macroproFilter == '-4')      $q->addWhere('macroproject_status != 7');
elseif ($macroproFilter == '-3')  $q->addWhere('macroproject_owner = ' . (int)$user_id);
elseif ($macroproFilter == '-2')  $q->addWhere('macroproject_status != 3');
elseif ($macroproFilter != '-1')  $q->addWhere('macroproject_status = ' . intval($macroproFilter));

if ($user_id && $m_orig == 'admin' && $a_orig == 'viewuser') {
    $q->addWhere('macroproject_owner = ' . (int)$user_id);
}
if ($showInactive != '1') $q->addWhere('macroproject_status != 7');

$mpobj->setAllowedSQL($AppUI->user_id, $q, null, 'mp');
$q->addGroup('mp.macroproject_id');

if ($display_option == 'custom') {
    $q->addWhere('mp.macroproject_start_date <= "' . $end_date->format('%Y-%m-%d') . ' 23:59:59"');
    $q->addWhere('(mp.macroproject_end_date >= "' . $start_date->format('%Y-%m-%d') . ' 00:00:00"'
        . ' OR mp.macroproject_end_date = "0000-00-00 00:00:00")');
}
$q->addOrder($sortTasksByName ? 'macroproject_name ASC' : 'macroproject_name ASC');
$mp_list = $q->loadList();
$q->clear();

// Build frappe-gantt format
$gantt_tasks = [];
foreach ((array)$mp_list as $mp) {
    $start = substr($mp['macroproject_start_date'], 0, 10);
    $end   = substr($mp['macroproject_end_date'],   0, 10);
    if (empty($start) || $start == '0000-00-00') $start = date('Y-m-d');
    if (empty($end)   || $end   == '0000-00-00' || $end <= $start) {
        $end = date('Y-m-d', strtotime($start . ' +1 day'));
    }
    $gantt_tasks[] = [
        'id'           => 'mp' . $mp['macroproject_id'],
        'name'         => $mp['macroproject_name'],
        'start'        => $start,
        'end'          => $end,
        'progress'     => intval($mp['macroproject_percent_complete'] + 0),
        'dependencies' => ''
    ];
}

if (!empty($gantt_tasks)) {
    GanttRenderer::render('dp-macroprojects-gantt', $gantt_tasks, '', 'Week');
} else {
    echo '<p>' . $AppUI->_('No macroprojects found') . '</p>';
}
?>
	</td>
</tr>
</table>
