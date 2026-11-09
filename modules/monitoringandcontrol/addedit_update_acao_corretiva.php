<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}
header("Content-Type:text/html; charset=iso-8859-1",true);
$AppUI->savePlace();
$task_id = dPgetIntParam( $_GET, 'task_id', 0 );
$change_id =  dPgetIntParam($_POST,'change_id');
$project_id = dPgetIntParam( $_POST, 'project_id', 0 );

require_once (DP_BASE_DIR . "/modules/monitoringandcontrol/control/controller_acao_corretiva.class.php");
require_once (DP_BASE_DIR . "/modules/monitoringandcontrol/control/controller_ata.class.php");
require_once (DP_BASE_DIR . "/modules/monitoringandcontrol/control/controller_util.class.php");
$titulo = $AppUI->_('LBL_EDITAR')." ".$AppUI->_('LBL_ACAO_CORRETIVA');
$titleBlock = new CTitleBlock("$titulo", 'graph-up.png', $m, $m . '.' . $a);
$titleBlock->show();

$controllerAta = new ControllerAta();
$controllerAcaoCorretiva = new ControllerAcaoCorretiva();
$controllerUtil = new ControllerUtil();
$records = $controllerAcaoCorretiva->getChangeRequestById($change_id);
if (!$records) {
	$AppUI->setMsg('Invalid ID', UI_MSG_ERROR);
	$AppUI->redirect($project_id ? 'm=projects&a=view&project_id=' . $project_id : 'm=projects');
}
// The change request's own project, which the user must be able to edit.
$project_id = (int)$records['project_id'];
dPrequireProjectEdit($project_id);
?>
<script type="text/javascript" src="./modules/monitoringandcontrol/js/validateChangeRequest.js"></script>
<script type="text/javascript" src="./modules/monitoringandcontrol/js/ata.js"></script>
<script type="text/javascript" src="./modules/monitoringandcontrol/js/util.js"> </script>
<!--  calendar  -->
	<link type="text/css" rel="stylesheet" href="./modules/monitoringandcontrol/js/jsLibraries/dhtmlgoodies_calendar/dhtmlgoodies_calendar.css" media="screen" />
	<script type="text/javascript" src="./modules/monitoringandcontrol/js/jsLibraries/dhtmlgoodies_calendar/dhtmlgoodies_calendar.js"   ></script>
<!-- end calendar  -->   <table width="100%" cellspacing="1" cellpadding="1" border="0">
	  <tr>
      		<td align="left" colspan="8"><b> <?php echo $AppUI->_('LBL_MONITORACAO'); ?> : <?php echo $AppUI->_('LBL_ACAO_CORRETIVA'); ?></b></td>

        </tr>
</table>
	<!--	  <form action="" method="post" name="form_ata" id="form_ata" enctype="multipart/form-data"> -->
		  <form action="?m=monitoringandcontrol&a=do_acao_corretiva_aed&change_id=<?php echo (int)$change_id; ?>" method="post" name="form_acao_corretiva" id="form_form_acao_corretiva" enctype="multipart/form-data">	    
	<input name="dosql" type="hidden" value="do_acao_corretiva_aed" />        
	    <input  type="hidden" name="acao" value="updateRow"  />
        <input  type="hidden" name="change_id" value="<?php echo (int)$change_id; ?>" />
		<input  type="hidden" name="project_id" value="<?php echo (int)$project_id; ?>" /> 
		
		<br/>
<table class="std" width="100%" cellspacing="0" cellpadding="4" border="0">
	  <tr>
      		<td colspan="8">&nbsp;</td>
        </tr>
	  <tr>
      		<td align="right" style="width:10%"><?php echo $AppUI->_('LBL_PROJETO'); ?>:</td>
             <td  style="width:90%" >
             		<?php
							$project_name = $controllerUtil->getProjectName($records['project_id']) ; 						
					 ?>
                 <input type="text" name="projeto" size="20" id="projeto" readonly="readonly" value="<?php echo dPhtml($project_name[0][0]); ?> " />			
            </td>
                <td colspan="5">&nbsp;
                </td>
        </tr>
	  <tr>
      		<td align="right" style="width:10%" ><?php echo $AppUI->_('LBL_TAREFA'); ?>:</td>
             <td  style="width:90%">
                 <input type="text" name="tarefa" size="20" id="tarefa" readonly="readonly" value="<?php echo dPhtml($records['task_name']); ?> " />	
				 <input name="task_id" type="hidden" id="task_id" value="<?php echo (int)$records['task_id']; ?>" /> 
            </td>  
               <td colspan="5">&nbsp;</td>           
        </tr>
	  <tr>
      		<td align="right" style="width:10%" ><?php echo $AppUI->_('LBL_IMPACTO'); ?> (<?php echo $AppUI->_('LBL_HORA'); ?>):</td>
             <td  style="width:90%" >

                 <input name="impact" type="text" id="impact" size="3" maxlength="3"  value="<?php echo dPhtml($records['change_impact']); ?>" />					
            </td>
        <td colspan="5">&nbsp;</td>
        </tr> 
     <tr>
            <td align="right"><?php echo $AppUI->_('LBL_STATUS'); ?>:</td>
	        <td >                        
                <select name="status" size="1"  id="status" >
                	<?php
					    $select = Array(0=>$AppUI->_('LBL_SELECIONE')."...",1=>$AppUI->_('LBL_ABERTO'),2=>$AppUI->_('LBL_FECHADO'),3=>$AppUI->_('LBL_DESENVOLVIMENTO'),4=>$AppUI->_('LBL_CANCELADO'));
						for($i=0;$i<count($select);$i++){
							  if($records['change_status'] ==  $i){								 
								  $option =  $select[$i];
							     echo "<option value='".(int)$records['change_status']."' selected>".$option."</option>";			 
							  }else
						 		 echo "<option value='".$i."' >".$select[$i]."</option>";	
						  }
					?>             
                </select>
         </td>
 	     	</tr>          
     		 <tr>
                 <td align="right" ><?php echo $AppUI->_('LBL_DESCRICAO_DESVIO'); ?>:</td><td  colspan="6"><textarea  name="description" cols="60" rows="4" ><?php  echo dPhtml($records['change_description']);	 ?></textarea></td>
           </tr>
          
          <tr>
           		<td align="right" ><?php echo $AppUI->_('LBL_CAUSA'); ?>:</td><td  colspan="7"><textarea  name="cause" cols="60" rows="4" ><?php echo dPhtml($records['change_cause']); ?></textarea></td>
           </tr>
          
          <tr>
           		<td align="right" ><?php echo $AppUI->_('LBL_ACAO_CORRETIVA'); ?>:</td><td  colspan="7"><textarea  name="acao_corretiva" cols="60" rows="4" ><?php echo dPhtml($records['change_request']); ?></textarea></td>
           </tr>

      <tr>
            <td align="right"><?php echo $AppUI->_('LBL_RESPONSAVEL'); ?>:</td>
	        <td >            
                <select name="user" size="1"  id="user" >
   					<option value="0"><?php echo $AppUI->_('LBL_SELECIONE'); ?>...</option>  
                  		 <?php		   		
								$list = array();	
								$list = $controllerUtil -> getUsers();
								foreach($list as $row){		
									if($records['user_id'] == $row[0]){
							    	  echo "<option value='".(int)$records['user_id']."' selected>".dPhtml($row[1])."</option>";	
									}else{
									  echo "<option value='".(int)$row[0]."' >" . dPhtml($row[1]) . "</option>";		
									}
								}
						 ?>     
                </select>
         </td>
      </tr>
      <tr>
            <td align="right"><?php echo $AppUI->_('LBL_ATA'); ?>:</td>            
	        <td >            
                <select name="ata" size="1"  id="ata" >
   					<option value="0"><?php echo $AppUI->_('LBL_SELECIONE'); ?>...</option>  
                  		 <?php		   		
							$list = array();	
							$list = $controllerAta -> getMeeting($project_id);
							foreach($list as $row){								
								  $data = $controllerUtil -> formatDate($row['dt_meeting_begin']); 								  
									if($records['meeting_id'] == $row[0]){
							    	  echo "<option value='".(int)$records['meeting_id']."' selected>" . dPhtml($data) . " - " . dPhtml($row[3]) . "</option>";	
									}else{
									  echo " <option value='".(int)$row['meeting_id']."' >" . dPhtml($data) . " - " . dPhtml($row['dt_meeting_begin']) . "</option>";	
									}									
							}							
						 ?>     
                </select>
         </td>
      </tr>
      <tr>
             <td align="right" ><?php echo $AppUI->_('LBL_PRAZO'); ?>:</td>
			 <td nowrap="nowrap" >   
             <?php	$date_limit =  $controllerUtil -> formatDate($records['change_date_limit']) 	?>              	  
				   <input type="text" class="text"  name="date_limit"  id="date_edit"   value="<?php echo dPhtml($date_limit); ?>" maxlength="10" <?php echo dPaction('formatadata', array('$this', '$event'), 'keyup'); ?>/>
				   <img src="./modules/monitoringandcontrol/images/img.gif" id="calendar_trigger" style="cursor:pointer" <?php echo dPaction('mcDisplayCalendar', array('date_edit', 'dd/mm/yyyy', '$this')); ?> />     
                </td>   
      </tr>      
	  <tr>
      		<td>&nbsp;</td>
             <td >     
           </td>
		<tr>
			<td colspan="8"><input type="button" value="<?php echo $AppUI->_('Voltar');?>" class="button"<?php echo dPaction('history.back', array(-1)); ?> /></td>
			<td align="right"><input type="submit" value="<?php echo $AppUI->_('Gravar');?>" class="button"<?php echo dPaction('validateChangeRequest'); ?> /></td>
		</tr>
</table>