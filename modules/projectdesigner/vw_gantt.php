<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
global $project_id, $m;
global $st_projects_arr;

$df = $AppUI->getPref('SHDATEFORMAT');
$projectPriority = dPgetSysVal( 'ProjectPriority' );
$projectStatus = dPgetSysVal( 'ProjectStatus' );
?>
<table width="100%" border="0" cellpadding="5" cellspacing="1">
<tr>
    <td align="center" colspan="20">
<?php
      require_once DP_BASE_DIR . '/classes/gantt_renderer.class.php';
      GanttRenderer::render('dp-pd-gantt', GanttRenderer::projectTaskRows($project_id), '', 'Week', true);
?>
</td>
</table>
