<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
// The interactive Gantt chart of a project, in its own window (suppressHeaders). It is the
// core chart (classes/gantt_renderer.class.php, js/dp-gantt.js): the tasks the user may see,
// and dragging a bar saves it through tasks/do_gantt_dates.php (edit permission on the
// task, times of day kept, dependent tasks moved), with Esc to cancel and Undo.
global $AppUI, $uistyle;
require_once DP_BASE_DIR . '/classes/gantt_renderer.class.php';

$project_id = dPgetIntParam($_GET, 'project_id', 0);
$perms = new dPacl();
// Module access is not enough: the chart lists the tasks of one project.
$canView = $perms->checkModule('igantt', 'view') && $perms->checkModule('projects', 'view')
	&& $project_id > 0 && $perms->checkModuleItem('projects', 'view', $project_id);
if (!$canView) {
	$AppUI->redirect("m=public&a=access_denied");
}
$rows = GanttRenderer::projectTaskRows($project_id);
?>
<!DOCTYPE html>
<html lang="<?php echo dPhtml(substr((string)$AppUI->user_locale, 0, 2) ?: 'en'); ?>"<?php echo dPappearanceAttributes($AppUI->checkFileName($uistyle)); ?>>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php echo $AppUI->getCsrfMeta(); ?>
	<title><?php echo $AppUI->_('Interactive Gantt chart'); ?></title>
	<?php GanttRenderer::assets(); ?>
	<?php echo dPthemeStylesheets($AppUI->checkFileName($uistyle)); ?>
	<link rel="icon" href="./images/brand/favicon.svg" type="image/svg+xml" />
	<style>
		body { font-family: var(--dp-font, Arial, sans-serif); margin: 20px; background-color: var(--dp-ground, #f5f5f6); color: var(--dp-ink, #23262B); }
		.gantt-container { background: var(--dp-surface, #fff); padding: 20px; border-radius: 8px; box-shadow: 0 4px 6px var(--dp-shadow, rgba(0, 0, 0, 0.1)); }
		h2 { margin: 0 0 16px; }
	</style>
</head>
<body class="dp-dialog">
	<h2><?php echo $AppUI->_('Interactive Gantt chart'); ?></h2>
	<div class="gantt-container">
		<?php GanttRenderer::render('igantt-chart', $rows, '', 'Day', true); ?>
	</div>
	<?php GanttRenderer::scripts(); ?>
</body>
</html>
