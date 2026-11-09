<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
	/**
	* Ok, here is the deal. There is no need to create a new table for internal messages if we have 
	* a task table that happens to have all the information we require for this purpose. So I'll
	* use the tasks table with the task_project set to 0
	*/
	define("READ_MESSAGES_TAB", 3);
	define("SENT_MESSAGES_TAB", 2);
	define("COMPOSE_MESSAGE_TAB", 1);
	//error_reporting(E_ALL);
	global $AppUI;
	
	require_once($AppUI->getModuleClass("tasks"));
	
	if(!function_exists("getUsersArray")) {
	    // Compatibility with dP 1.x
	    $q = new DBQuery();
	    $q -> addTable('users','u');
	    $q -> addTable('contacts','c');	    
	    $q -> addQuery("user_id, concat_ws(' ', contact_first_name, contact_last_name)");
	    $q -> addJoin('permissions','p','u.user_id = p.permission_user');
	    $q -> addGroup('user_id');
	    $q -> addOrder('contact_first_name');
	    $q -> addWhere('!isnull(p.permission_user)');
	    $q -> addWhere('u.user_contact = c.contact_id');    
	    
    	$user_hash = $q -> loadHashList();
	} else {
        $user_hash = array();
        foreach(getUsersArray() as $user_id => $user_data){
            $user_name = trim($user_data["contact_first_name"]." ".$user_data["contact_last_name"]);
            $user_hash[$user_id] = empty($user_name) ? $user_data["user_username"] : $user_name;
        }
	}
	
	// Let's start showing off the information
	$titleBlock = new CTitleBlock( 'Internal messages', 'messages.png', $m, "$m.$a" );
	$titleBlock->addCell("Internal messages");
	$titleBlock->show();
	
	if( ($message_tab = dpGetParam($_GET, "tab", -1)) != -1){
		$AppUI->setState("message_tab", $_GET["tab"]);
	} else {
		$message_tab 		= $AppUI->getState("message_tab");
		if(!$message_tab) {
			$message_tab = 0;
		}
	}
	
	$show_read_messages = false;
	$show_sent_messages = false;
	
	if (dpGetParam($_POST, "task_description", "") != "") {
		// A message is a new task outside any project: only its title and text come from
		// the request. Binding the whole POST let a task_id overwrite any existing task.
		$new_message = new CTask();
		$new_message->bind(array(
			'task_name' => dPgetParam($_POST, 'task_name', ''),
			'task_description' => dPgetParam($_POST, 'task_description', ''),
		));
		$new_message->task_id = 0;
		$new_message->task_project = 0;
		$new_message->task_start_date = date('Y-m-d H:i:s');
		$new_message->task_owner = $AppUI->user_id;
		
		$error_message = $new_message->store();
		if(is_null($error_message)){
			$q = new DBQuery();
			$q -> setDelete('user_tasks');
			$q -> addWhere("task_id ='" . (int)$new_message->task_id . "'");
			$q -> exec();			
			
			$q -> clear();
			$q -> addTable('user_tasks');
			$q -> addInsert('task_id', $new_message->task_id);
			$q -> addInsert('user_id', dPgetIntParam($_POST, 'recipient_user_id', 0));
			$q -> exec();			
			
			$q -> clear();
			$q -> addTable('contacts','c');
			$q -> addTable('users','u');
			$q -> addQuery('c.contact_email');
			$q -> addWhere('u.user_contact = c.contact_id');
			$q -> addWhere("u.user_id = '" . (int)$_POST["recipient_user_id"] . "'");
			$recipient_email = $q -> loadResult();
			
			if(dPgetParam($_POST, "send_email", "") != "" && $recipient_email != ""){
        		$mail = new Mail();
        		$mail->Subject(dPgetConfig("company_name")." - ".$AppUI->_("Internal message"));
        		
        		$body = dPgetConfig("company_name")." - ".$AppUI->_("Internal message");
        		$body .= "\n\n";
        		$body .= $new_message->task_name."\n";
        		$body .= $new_message->task_description;
        		$body .= "\n-----";
        		
        		$mail->Body($body);
        		$mail->From ( '"' . $AppUI->user_first_name . " " . $AppUI->user_last_name 
        			. '" <' . $AppUI->user_email . '>'
        		);
        		
        		$mail->To($recipient_email);
        		if ($mail->ValidEmail($recipient_email)) {
        		  $mail->send();
        		}
        		
        		$AppUI->setState("send_email_checked", "checked");
			} else {
			    $AppUI->setState("send_email_checked", "");
			}
			
			$AppUI->setMsg("Message sent succesfully");
		} else {
			$AppUI->setMsg("Message was not sent [$error_message]", UI_MSG_ERROR);
		}
	}
	
	if(dpGetParam($_GET, "message_id", 0) > 0) {
		$view_message = new CTask();
		$view_message->load($_GET["message_id"]);
		
		// This is for security reasons
		$q = new DBQuery();
		$q -> addTable('user_tasks');
		$q -> addQuery('count(user_id)');
		$q -> addWhere("task_id = '" . (int)$view_message->task_id . "'");
		$q -> addWhere("user_id = '" . (int)$AppUI->user_id . "'");
		
		$user_present_in_recipients = $q -> loadResult();
	
		if(!$user_present_in_recipients && $view_message->task_owner != $AppUI->user_id) {
			unset($view_message);
		}
		
		// Marking, resending, deleting and converting change the message: POST
		// with the CSRF token only (the message id stays in the URL).
		$message_action = ($_SERVER['REQUEST_METHOD'] === 'POST') ? dPgetCleanParam($_POST, "action", "") : "";
		if($message_action != "" && isset($view_message)){
			$AppUI->verifyCsrfToken();
			$unset_view_message = false;
			switch($message_action) {
				case "mark_as_read":
					if($user_present_in_recipients){
						$view_message->task_status   = '-1';
						$view_message->task_priority = 0;
						$message_tab                 = 0;
						$unset_view_message          = true;
					}
					break;
				case "mark_as_unread":
					if($user_present_in_recipients){
						$view_message->task_status = '0';
					}
					break;
				case "resend_message":
					if($view_message->task_owner == $AppUI->user_id){
						$view_message->task_status     = '0';
						$view_message->task_start_date = date("Y-m-d H:i:s");
					}
					break;
				case "delete":
					if($view_message->task_owner == $AppUI->user_id || $user_present_in_recipients){
						$view_message->delete();
						$unset_view_message = true;
					}
					break;
				case "convert_to_task":
					if($user_present_in_recipients){
						$view_message->task_project = $_POST["project_id"];
						$view_message->task_status  = '0';
					}
					break;
			}
			
			$view_message->store();
			if($unset_view_message){
				unset($view_message);
			}
		}
		
		if(isset($view_message)) {
			if($view_message->task_owner == $AppUI->user_id){
				$message_tab = SENT_MESSAGES_TAB;
				
			} else if($view_message->task_status == -1){
				$message_tab = READ_MESSAGES_TAB;
			}
		}
		
	}
	
	if(dPgetParam($_GET, "reply_to_message_id", 0) > 0){
		$q = new DBQuery();
		$q -> addTable('user_tasks');
		$q -> addQuery('count(user_id)');
		$q -> addWhere("task_id = '" . (int)$_GET["reply_to_message_id"] . "'");
		$q -> addWhere("user_id = '" . (int)$AppUI->user_id . "'");
		$user_present_in_recipients = $q -> loadResult();
		if($user_present_in_recipients){
			$message_tab = COMPOSE_MESSAGE_TAB;
			$reply_to_message_id = $_GET["reply_to_message_id"];
		}
	}
	
	$AppUI->setState("message_tab", $message_tab);
	switch($message_tab){
		case SENT_MESSAGES_TAB:
			$show_sent_messages = true;
			break;
		case READ_MESSAGES_TAB:
			$show_read_messages = true;
			break;
	}
	
	$tabBox = new CTabBox("?m=messages", dPgetConfig('root_dir')."/modules/messages/", $message_tab);
	$tabBox->add("vw_messages", "Incoming messages");
	$tabBox->add("compose_message", "Compose message");
	$tabBox->add("vw_messages", "Sent messages");
	$tabBox->add("vw_messages", "Viewed messages");
	$tabBox->show();
	
?>
