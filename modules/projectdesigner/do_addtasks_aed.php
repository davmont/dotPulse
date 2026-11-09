<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

global $AppUI;

//Lets store the panels view options of the user:
$pdo = new CProjectDesignerOptions();
$pdo->pd_option_user = $AppUI->user_id;
$pdo->pd_option_view_project = (int)dPgetParam( $_POST, 'opt_view_project', 0 );
$pdo->pd_option_view_gantt = (int)dPgetParam( $_POST, 'opt_view_gantt', 0 );
$pdo->pd_option_view_tasks = (int)dPgetParam( $_POST, 'opt_view_tasks', 0 );
$pdo->pd_option_view_actions = (int)dPgetParam( $_POST, 'opt_view_actions', 0 );
$pdo->pd_option_view_addtasks = (int)dPgetParam( $_POST, 'opt_view_addtsks', 0 );
$pdo->pd_option_view_files = (int)dPgetParam( $_POST, 'opt_view_files', 0 );
$pdo->store();

//Lets store the task lines
$elements = $_POST;
// The tasks go into this project: one the user can see and add tasks to, as the task form
// requires (it was taken from the request unchecked).
$project_id = (int)$elements['project'];
if (!$project_id || !getPermission('projects', 'view', $project_id) || !getPermission('tasks', 'add')) {
	$AppUI->setMsg('Access denied.', UI_MSG_ERROR);
	$AppUI->redirect('m=public&a=access_denied');
}
foreach ($elements as $element => $on) {
      if ((substr($element, 0, 14) == 'add_task_line_') && ($on != '')) {
            
            $tline = new CTask();
            
            $tline->task_id = 0;
            $tline->task_name = $elements['add_task_name_'.$on];
            $tline->task_project = $project_id;
            $start_date = '';
            if ($elements['add_task_start_date_'.$on]) {
                  $date = new CDate($elements['add_task_start_date_'.$on]);
                  $start_date = $date->format(FMT_DATETIME_MYSQL);
            }
            $tline->task_start_date = $start_date;
            $end_date = '';
            if ($elements['add_task_end_date_'.$on]) {
                  $date = new CDate($elements['add_task_end_date_'.$on]);
                  $end_date = $date->format(FMT_DATETIME_MYSQL);
            }
            $tline->task_end_date = $end_date;
            $tline->task_duration = $elements['add_task_duration_'.$on];
            $tline->task_duration_type = $elements['add_task_durntype_'.$on];
            $tline->task_priority = $elements['add_task_priority_'.$on];
            $tline->task_type = $elements['add_task_type_'.$on];
            $tline->task_access = $elements['add_task_access_'.$on];
            $tline->task_description = $elements['add_task_description_'.$on];
            $tline->task_owner = "{$AppUI->user_id}";
            if ($elements['add_task_extra_'.$on]=='1')
                  $tline->task_milestone = '1';
            elseif ($elements['add_task_extra_'.$on]=='2')
                  $tline->task_dynamic = '1';
            elseif ($elements['add_task_extra_'.$on]=='3')
                  $tline->task_status = '-1';
                              
            if (($msg = $tline->store())) {
      		$nrerrors++;
      	}
//      print_r($msg);die;
      }
}
$AppUI->redirect('m=projectdesigner&project_id='.$project_id);
?>
