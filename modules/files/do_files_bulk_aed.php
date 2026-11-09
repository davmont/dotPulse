<?php /* fileS $Id: do_files_bulk_aed.php 6149 2012-01-09 11:58:40Z ajdonnison $ */
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

global $AppUI;
$selected = dPgetParam($_POST, 'bulk_selected_file', 0);
$redirect = dPgetCleanParam($_POST, 'redirect', '');
// 'O' (or nothing) leaves the project or folder as it is; otherwise an id.
$bulk_file_project = dPgetParam($_POST, 'bulk_file_project', 'O');
$bulk_file_folder = dPgetParam($_POST, 'bulk_file_folder', 'O');
$move_project = ($bulk_file_project !== '' && $bulk_file_project !== 'O');
$move_folder = ($bulk_file_folder !== '' && $bulk_file_folder !== 'O');
$bulk_file_project = (int)$bulk_file_project;
$bulk_file_folder = (int)$bulk_file_folder;

// The target project and folder must be ones the user may put files in, as the file
// form allows (project view, folder edit).
if (($move_project && $bulk_file_project && !getPermission('projects', 'view', $bulk_file_project))
		|| ($move_folder && $bulk_file_folder && !getPermission('file_folders', 'edit', $bulk_file_folder))) {
	$AppUI->setMsg('Access denied.', UI_MSG_ERROR);
	$AppUI->redirect('m=public&a=access_denied');
}

if (is_array($selected) && count($selected)) {
	foreach (array_keys($selected) as $file_id) {
		$file_id = (int)$file_id;
		if (!$file_id) {
			continue;
		}
		// Each file is changed: the user must be allowed to edit it.
		dPrequireWritePermission('files', $file_id);
		$upd_file = new CFile();
		if (!$upd_file->load($file_id)) {
			continue;
		}
		if ($move_project && $upd_file->file_project != $bulk_file_project) {
			// move the file on filesystem if the affiliated project was changed
			$oldProject = $upd_file->file_project;
			$upd_file->file_project = $bulk_file_project;
			if (!$upd_file->moveFile($oldProject, $upd_file->file_real_filename)) {
				$AppUI->setMsg('At least one File could not be moved', UI_MSG_ERROR);
			}
		}
		if ($move_folder) {
			$upd_file->file_folder = $bulk_file_folder;
		}
		if ($move_project || $move_folder) {
			$upd_file->store();
		}
	}
}
$AppUI->redirect($redirect);
?>
