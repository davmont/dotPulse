<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

global $m, $a, $locale_char_set, $AppUI;
$project_id = intval(dPgetParam($_GET, 'project_id', 0));

// Check permissions
$perms = new dPacl();
// Module access is not enough: the chart lists the tasks of one project.
$canView = $perms->checkModule('igantt', 'view') && $perms->checkModule('projects', 'view')
	&& $project_id > 0 && $perms->checkModuleItem('projects', 'view', $project_id);
if (!$canView) {
	$AppUI->redirect("m=public&a=access_denied");
}

$locale_char_set = "UTF-8";

// Fetch tasks for the project
$q = new DBQuery();
$q->addTable('tasks');
$q->addQuery('task_id, task_name, task_start_date, task_end_date, task_percent_complete, task_parent');
$q->addWhere("task_project = $project_id");
$q->addOrder('task_start_date ASC');
$tasks = $q->loadList();
$q->clear();

// Fetch dependencies
// Only this project's dependencies (it used to load every dependency in the system).
$q->addTable('task_dependencies', 'td');
$q->addJoin('tasks', 't', 't.task_id = td.dependencies_task_id');
$q->addQuery('td.dependencies_task_id, td.dependencies_req_task_id');
$q->addWhere("t.task_project = $project_id");
$dependencies_list = $q->loadList();
$dependencies = array();
foreach ($dependencies_list as $dep) {
	$dependencies[$dep['dependencies_task_id']][] = $dep['dependencies_req_task_id'];
}

// Format tasks for Frappe Gantt
$gantt_tasks = array();
if ($tasks) {
	foreach ($tasks as $task) {
		$start_date = substr($task['task_start_date'], 0, 10);
		$end_date = substr($task['task_end_date'], 0, 10);

		// Ensure valid dates
		if (empty($start_date) || $start_date == '0000-00-00')
			$start_date = date('Y-m-d');
		if (empty($end_date) || $end_date == '0000-00-00')
			$end_date = date('Y-m-d', strtotime($start_date . ' + 1 day'));

		$task_deps = isset($dependencies[$task['task_id']]) ? implode(',', $dependencies[$task['task_id']]) : '';

		$gantt_tasks[] = array(
			'id' => $task['task_id'],
			'name' => $task['task_name'],
			'start' => $start_date,
			'end' => $end_date,
			'progress' => intval($task['task_percent_complete']),
			'dependencies' => $task_deps
		);
	}
}

// Convert tasks to JSON
$json_tasks = json_encode($gantt_tasks, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<?php echo $AppUI->getCsrfMeta(); ?>
	<title><?php echo $AppUI->_('Interactive Gantt chart'); ?></title>
	<!-- Frappe Gantt CSS -->
	<link rel="stylesheet" href="<?php echo DP_BASE_URL; ?>/lib/frappe-gantt/frappe-gantt.min.css" />
	<link rel="stylesheet" href="<?php echo DP_BASE_URL; ?>/style/gantt.css" />
	<style>
		body {
			font-family: Arial, sans-serif;
			margin: 20px;
			background-color: #f5f5f6;
		}

		.gantt-container {
			background: #fff;
			padding: 20px;
			border-radius: 8px;
			box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
			overflow-x: auto;
		}

		.header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 20px;
		}

		.controls button {
			padding: 8px 16px;
			margin-left: 10px;
			border: none;
			border-radius: 4px;
			background-color: #1976d2;
			color: white;
			cursor: pointer;
		}

		.controls button:hover {
			background-color: #115293;
		}
	</style>
</head>

<body>

	<div class="header">
		<h2><?php echo $AppUI->_('Interactive Gantt chart'); ?></h2>
		<div class="controls">
			<button type="button"<?php echo dPaction('changeViewMode', array('Quarter Day')); ?>>Quarter Day</button>
			<button type="button"<?php echo dPaction('changeViewMode', array('Half Day')); ?>>Half Day</button>
			<button type="button"<?php echo dPaction('changeViewMode', array('Day')); ?>>Day</button>
			<button type="button"<?php echo dPaction('changeViewMode', array('Week')); ?>>Week</button>
			<button type="button"<?php echo dPaction('changeViewMode', array('Month')); ?>>Month</button>
		</div>
	</div>

	<div class="gantt-container">
		<svg id="gantt"></svg>
	</div>

	<!-- Frappe Gantt JS -->
	<script src="<?php echo DP_BASE_URL; ?>/lib/frappe-gantt/frappe-gantt.min.js"></script>
	<script src="<?php echo DP_BASE_URL; ?>/js/dp-actions.js"></script>
	<script nonce="<?php echo dPcspNonce(); ?>">
		// Inject JSON tasks from PHP
		var tasks = <?php echo $json_tasks; ?>;

		function escapeHtml(s) {
			return String(s).replace(/[&<>"']/g, function (c) {
				return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
			});
		}

		if (tasks.length === 0) {
			document.getElementById('gantt').innerHTML = '<text x="20" y="40">No tasks found for this project.</text>';
		} else {
			// Initialize Gantt Chart
			var gantt = new Gantt("#gantt", tasks, {
				header_height: 50,
				column_width: 30,
				step: 24,
				view_modes: ['Quarter Day', 'Half Day', 'Day', 'Week', 'Month'],
				bar_height: 20,
				bar_corner_radius: 3,
				arrow_curve: 5,
				padding: 18,
				view_mode: 'Day',
				date_format: 'YYYY-MM-DD',
				custom_popup_html: function (task) {
					const end_date = new Date(task.end);
					end_date.setDate(end_date.getDate() - 1); // Display exact day
					return `
					<div style="padding: 10px; background: rgba(0,0,0,0.8); color: white; border-radius: 4px;">
						<strong>${escapeHtml(task.name)}</strong><br>
						Expected to finish by ${end_date.toISOString().split('T')[0]}<br>
						${task.progress}% completed!
					</div>`;
				},
				on_date_change: function (task, start, end) {
					// Adjust end date back by 1 day as Frappe Gantt extends end dates by +1 day visually
					let endDate = new Date(end);
					endDate.setDate(endDate.getDate() - 1);

					// Send AJAX request to update database
					fetch('./index.php?m=igantt&a=update_task_dates&suppressHeaders=1', {
						method: 'POST',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
						body: new URLSearchParams({
							task_id: task.id,
							start_date: start.toISOString().split('T')[0],
							end_date: endDate.toISOString().split('T')[0],
							csrf_token: document.querySelector('meta[name="csrf-token"]').getAttribute('content')
						}).toString()
					})
						.then(response => response.json())
						.then(data => {
							if (data.status === 'success') {
								console.log('Task dates updated successfully!');
							} else {
								alert('Error updating task: ' + data.message);
							}
						})
						.catch(err => {
							console.error('Error:', err);
							alert('Failed to connect to the server.');
						});
				},
				on_progress_change: function (task, progress) {
					console.log(task, progress);
				},
			});

			function changeViewMode(mode) {
				gantt.change_view_mode(mode);
			}
		}
	</script>

</body>

</html>