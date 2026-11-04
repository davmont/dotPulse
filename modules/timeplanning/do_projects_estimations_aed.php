<?php
	if (!defined('DP_BASE_DIR')) {
		die('You should not access this file directly.');
	}
	require_once (DP_BASE_DIR . "/modules/timeplanning/model/project_minute.class.php");
	require_once (DP_BASE_DIR . "/modules/timeplanning/model/project_task_estimation.class.php");
	require_once (DP_BASE_DIR . "/modules/timeplanning/model/wbs_item_estimation.class.php");
	
	//save
	// The minute's text, edited as plain text (stored as HTML, see ProjectMinute).
	$description=ProjectMinute::fromPlainText(dPgetParam($_POST, 'description_edit', ''));
	$project_id=$_POST['project_id'];
	$date=$_POST['date'];
	$isEffort=$_POST['isEffort'];
	$isDuration=$_POST['isDuration'];
	$isResource=$_POST['isResource'];
	$isSize=$_POST['isSize'];
	$id=$_POST['minute_id'];
	// Ids are used in SQL below: keep them integers, as checked.
	$project_id = (int)$project_id;
	$id = (int)$id;
	// The user must be able to edit this project, and the records named must belong to it.
	dPrequireProjectEdit($project_id, array(array('project_minutes', 'id', 'project_id', $id)));
	$tab=$_POST['tab'];
	$members=$_POST['membersIds'];
	$pos = strpos($members,",");
	if($pos === false) {
		$memberId=$members;
		$members=array();
		$members[$memberId]=$memberId;
	}else {
		$members=explode(",",$members);
	}
	
	$action=$_POST['action_estimation'];
	if($action=="saveEstimationsData"){
		$q = new DBQuery();
		$q->addQuery('t.task_id');
		$q->addTable('tasks', 't');
		$q->addWhere('task_project = ' . (int)$project_id);
		$sql = $q->prepare();
		$tasks = db_loadList($sql);
		foreach ($tasks as $task) {			
			$task_id=$task['task_id'];
			$duration=$_POST["planned_duration_$task_id"];
			$effort=$_POST["planned_effort_$task_id"];
			$effortUnit=$_POST["planned_effort_unit_$task_id"];
			
			$rolesIds=array();
			$rolesQuantity=array();	
			$numRoles=intval($_POST["roles_num_$task_id"]);
			for($i=0;$i<=$numRoles;$i++){
				if(strpos($_POST["estimatedRolesExcluded_$task_id"], $i."") === false){
					$rolesIds[$i]=$_POST["estimated_role_".$task_id."_".$i];
					$rolesQuantity[$i]=$_POST["estimated_role_quantity_".$task_id."_".$i];
				}
			}
			$projectTaskEstimation = new ProjectTaskEstimation();
			$projectTaskEstimation->store($task_id,$duration,$effort,$effortUnit,$rolesIds,$rolesQuantity); 
		}
		
		$q = new DBQuery();
		$q->addQuery('t.id, t.item_name,t.identation,t.number,is_leaf');
		$q->addTable('project_eap_items', 't');
		$q->addWhere("project_id = $project_id and is_leaf='1' order by sort_order");
		$sql = $q->prepare();
		$items = db_loadList($sql);
		foreach ($items as $item) {
			$eapItemId = $item['id'];
			$size=$_POST["estimated_size_$eapItemId"];
			$sizeUnit=$_POST["estimated_size_unit_$eapItemId"];
			$eapItem= new  WBSItemEstimation();
			$eapItem->store($eapItemId,$size,$sizeUnit);
		}
		
		$AppUI->redirect('m=projects&a=view&project_id='.$project_id."&tab=".$tab);
	}else{
		if($action=="read"){
			$AppUI->redirect('m=projects&a=view&project_id='.$project_id."&tab=".$tab."&minute_id=".$id."&action_estimation=read");
		}else{
			$projectMinute=new ProjectMinute();
			if($action=="delete"){
				$projectMinute->delete($id);
			}else{
				$projectMinute->store($description,$date,$project_id,$id,$isEffort,$isDuration,$isResource,$isSize,$members);
			}
		   $AppUI->redirect('m=projects&a=view&project_id='.$project_id."&tab=".$tab);
		}
	}
?>