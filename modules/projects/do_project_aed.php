<?php /* PROJECTS $Id: do_project_aed.php 6149 2012-01-09 11:58:40Z ajdonnison $ */
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

$obj = new CProject();
$msg = '';

if (!$obj->bind($_POST)) {
	$AppUI->setMsg($obj->getError(), UI_MSG_ERROR);
	$AppUI->redirect();
}
// Fields a module calculates are not taken from the form (store() skips null fields).
foreach (projectCalculatedFields() as $field) {
	$obj->$field = null;
}

require_once($AppUI->getSystemClass('CustomFields'));
// convert dates to SQL format first
if ($obj->project_start_date) {
	$date = new CDate($obj->project_start_date);
	$obj->project_start_date = $date->format(FMT_DATETIME_MYSQL);
}
if ($obj->project_end_date) {
	$date = new CDate($obj->project_end_date);
	$date->setTime(23, 59, 59);
	$obj->project_end_date = $date->format(FMT_DATETIME_MYSQL);
}
if ($obj->project_actual_end_date) {
	$date = new CDate($obj->project_actual_end_date);
	$obj->project_actual_end_date = $date->format(FMT_DATETIME_MYSQL);
}

// let's check if there are some assigned departments to project
if (!dPgetParam($_POST, "project_departments", 0)) {
	$obj->project_departments = implode(",", array_map("intval", (array)dPgetParam($_POST, "dept_ids", array())));
}

$del = (int)dPgetParam($_POST, 'del', 0);
// Permission on this record, as its addedit page checks.
dPrequireWritePermission('projects', dPgetParam($_POST, 'project_id', 0), $del);

if (!$del) {
	$stored = null;
	if ($obj->project_id) {
		$stored = new CProject();
		if (!$stored->load($obj->project_id)) {
			$AppUI->setMsg('Project');
			$AppUI->setMsg('invalidID', UI_MSG_ERROR, true);
			$AppUI->redirect();
		}
	}
	// The creator is who added the project, never a form value.
	$obj->project_creator = $stored ? $stored->project_creator : $AppUI->user_id;
	// The companies must be ones the user may see, as the form lists them; a project
	// keeps the company it already has.
	foreach (array('project_company', 'project_company_internal') as $field) {
		$obj->$field = (int)$obj->$field;
		if ($obj->$field && !($stored && $obj->$field == $stored->$field)
				&& !getPermission('companies', 'view', $obj->$field)) {
			$AppUI->setMsg('Access denied.', UI_MSG_ERROR);
			$AppUI->redirect('m=public&a=access_denied');
		}
	}
}

// prepare (and translate) the module name ready for the suffix
if ($del) {
	$project_id = (int)dPgetParam($_POST, 'project_id', 0);
	$canDelete = $obj->canDelete($msg, $project_id);
	if (!$canDelete) {
		$AppUI->setMsg($msg, UI_MSG_ERROR);
		$AppUI->redirect();
	}
	if (($msg = $obj->delete())) {
		$AppUI->setMsg($msg, UI_MSG_ERROR);
		$AppUI->redirect();
	} else {
		$AppUI->setMsg("Project deleted", UI_MSG_ALERT);
		$AppUI->redirect("m=projects");
	}
}
else {

	if (($msg = $obj->store())) {
		$AppUI->setMsg($msg, UI_MSG_ERROR);
	} else {
		$isNotNew = @$_POST['project_id'];

		// Copying a project's tasks reads them: only from a project the user can see.
		if (($importTask_projectId = (int)dPgetParam($_POST, 'import_tasks_from', '0'))
				&& getPermission('projects', 'view', $importTask_projectId)) {
			$scale_project = (int)dPgetParam($_POST, 'scale_project', '0');
			$obj->importTasks($importTask_projectId, $scale_project);
		}
		$AppUI->setMsg($isNotNew ? 'Project updated' : 'Project inserted', UI_MSG_OK, true);

 		$custom_fields = New CustomFields($m, 'addedit', $obj->project_id, 'edit');
 		$custom_fields->bind($_POST);
 		$sql = $custom_fields->store($obj->project_id); // Store Custom Fields
	}
	$AppUI->redirect();
}
?>
