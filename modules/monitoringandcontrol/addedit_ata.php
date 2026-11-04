<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}
require_once (DP_BASE_DIR . "/modules/monitoringandcontrol/translations.php");
require_once (DP_BASE_DIR . "/modules/monitoringandcontrol/control/controller_ata.class.php");
require_once (DP_BASE_DIR . "/modules/monitoringandcontrol/control/controller_util.class.php");
require_once (DP_BASE_DIR . "/modules/monitoringandcontrol/control/controller_report.class.php");
header("Content-Type:text/html; charset=iso-8859-1",true);

$AppUI->savePlace();
$project_id = dPgetIntParam( $_GET, 'project_id', 0 );
$titulo = $AppUI->_('LBL_NOVA').' '.$AppUI->_('LBL_ATA_SEM_ACENTO');
$titleBlock = new CTitleBlock($titulo, 'graph-up.png', $m, $m . '.' . $a);
$titleBlock->show();

$controllerUtil = new ControllerUtil();
// The form belongs to an existing project.
$project_name = $controllerUtil->getProjectName($project_id);
if (!$project_name) {
	$AppUI->setMsg('Invalid ID', UI_MSG_ERROR);
	$AppUI->redirect('m=projects');
}
$controllerAta = new ControllerAta();
$controllerReport = new ControllerReport();  
?>
<script type="text/javascript" src="./modules/monitoringandcontrol/js/ata.js" ></script>
<script type="text/javascript" src="./lib/jquery/jquery.min.js" ></script>
<script type="text/javascript" src="./modules/monitoringandcontrol/js/util.js"> </script>
<script type="text/javascript" nonce="<?php echo dPcspNonce(); ?>">
$(document).ready(function() {	
        $('#meeting_type').bind('change', function() {
		    var optionValue = $("#meeting_type").val();		
		    switch (optionValue)
		    {
			   case '2':
				 $("table#p1").hide();
				 $("table#p2").show();
				 $("table#p3").hide();	
				break;
		       case '3':
			     $("table#p1").show();
		         $("table#p2").hide();
				 $("table#p3").hide();
		       break;
			   case '4':
			     $("table#p1").hide();
		         $("table#p2").hide();
				 $("table#p3").show();	
				break;
			   case '5':
				 $("table#p1").show();
				 $("table#p2").hide();
				 $("table#p3").show();	
				break;				
		      default:		       
				 $("table#p1").hide();				  
				 $("table#p2").hide();
				 $("table#p3").hide();
		      break;
		    }
   });
});
</script>
<!--  calendar  -->
	<link type="text/css" rel="stylesheet" href="./modules/monitoringandcontrol/js/jsLibraries/dhtmlgoodies_calendar/dhtmlgoodies_calendar.css" media="screen" />
	<script type="text/javascript" src="./modules/monitoringandcontrol/js/jsLibraries/dhtmlgoodies_calendar/dhtmlgoodies_calendar.js"   ></script>
<!-- end calendar  -->  
 <table width="100%" cellspacing="1" cellpadding="1" border="0">
	  <tr>
      		<td align="left" colspan="8"><b> <?php echo $AppUI->_('LBL_MONITORACAO'); ?> : <?php echo $AppUI->_('LBL_ATA'); ?> </b></td>
       </tr>
</table>
		 <form action="?m=monitoringandcontrol&a=do_ata_aed&project_id=<?php echo $project_id; ?>" method="post" name="form_ata" id="form_ata" enctype="multipart/form-data">	    
		<input name="dosql" type="hidden" value="do_ata_aed" />
		<input name="project_id" type="hidden" id="project_id" value="<?php echo $project_id; ?>">
	    <input  type="hidden" name="acao" value="insert"  />
        <input id="items_ids_to_delete" type="hidden" value="" name="items_ids_to_delete">
		<br/>
<table  class="std" width="100%" cellspacing="0" cellpadding="4" border="0">
	  <tr>
      		<td colspan="8">&nbsp;</td>
        </tr>
	  <tr>
      		<td align="right"><?php echo $AppUI->_('LBL_PROJETO'); ?>:</td>
             <td>
                 <input type="text" name="projeto" size="25" id="projeto" readonly="readonly" value="<?php echo dPhtml($project_name[0][0]); ?> " />					
            </td>
             <td ></td>
        </tr>
	  <tr>
      		<td align="right" width="15%"><?php echo $AppUI->_('LBL_DATA'); ?>:</td>
			 <td nowrap="nowrap" width="15%">                 	  
				   <input type="text" class="text"  name="dt_begin"  id="date_edit" maxlength="10" <?php echo dPaction('formatadata', array('$this', '$event'), 'keyup'); ?>/>
				   <img src="./modules/monitoringandcontrol/images/img.gif" id="calendar_trigger" style="cursor:pointer" <?php echo dPaction('mcDisplayCalendar', array('date_edit', 'dd/mm/yyyy', '$this')); ?> />     
                </td>   
      		<td align="right" width="5%"><?php echo $AppUI->_('LBL_HORA'); ?>:</td>
            <td width="5%">
					<select name="hr_begin" size="1" id="hr_begin"> 		
                  		 <?php	
						 	$hours = $controllerUtil->setHours();
						for($i=0;$i<count($hours);$i++){
								echo " <option value='$hours[$i]'> $hours[$i]</option>";										
							}?>          
              		</select>&nbsp;  :
           </td>
            <td width="3%">
					<select name="min_begin" size="1" id="min_begin"> 		
				 		<option value="0">00</option>
                  		 <?php		   		
						 	$minutes = $controllerUtil->setMinutes();
						for($i=0;$i<count($minutes);$i++){
								echo " <option value='$minutes[$i]'> $minutes[$i]</option>";										
							}?>          
              		</select> 
           </td>   
           	<td width="3%"><?php echo $AppUI->_('LBL_AS'); ?></td> 
            <td width="5%">
					<select name="hr_end" size="1" id="hr_end"> 		
				 		<option value="0">00</option>
                  		 <?php		   		
						 	$hours = $controllerUtil->setHours();
						for($i=0;$i<count($hours);$i++){
								echo " <option value='$hours[$i]'> $hours[$i]</option>";										
							}						 ?>          
              		</select> &nbsp;:
           </td>
            <td width="5%">
					<select name="min_end" size="1" id="min_end"> 		
				 		<option value="0">00</option>
                  		 <?php		   		
						 	$minutes = $controllerUtil->setMinutes();
						for($i=0;$i<count($minutes);$i++){
								echo " <option value='$minutes[$i]'> $minutes[$i]</option>";										
							}?>          
              		</select> 
           </td> 
           <td ></td>
      </tr>
	  <tr>
      		<td align="right"><?php echo $AppUI->_('LBL_TITULO'); ?></td>
            <td colspan="8"><input type="text" name="title" id="title" size="90"  /></td>
        </tr>
	  <tr>
            <td>&nbsp;</td>
	        <td ><?php echo $AppUI->_('LBL_USUARIO'); ?>:</td>
	        <td colspan="4"><?php echo $AppUI->_('LBL_PARTICIPANTES'); ?>:</td>
             <td colspan="3"></td>  
      </tr>
      <tr>
            <td>&nbsp;</td>
	        <td >            
                <select name="users" size="4" multiple="multiple" id="users" style="width:190px">
                  		 <?php		   		
				$list = array();	
				$list = $controllerUtil -> getUsers();
					foreach($list as $row){		
						  echo "<option value='" . (int)$row[0] . "' >" . dPhtml($row[1]) . "</option>";						
					}?>     
                </select>
         </td>
                     <td align="center" valign="middle">
                <input type="button"<?php echo dPaction('mcMoveOptions', array('$this', 'participants', 'users')); ?> value="<<">
                <input type="button"<?php echo dPaction('mcMoveOptions', array('$this', 'users', 'participants')); ?> value=">>">
            </td>
	        <td colspan="4">            
                <select name="participants[]" size="4" multiple="multiple" id="participants" style="width:190px">
                </select>
         </td>
             <td colspan="3"></td> 
      </tr>
        <tr>
				<td align="right" ><?php echo $AppUI->_('LBL_ASSUNTO'); ?>:</td>
				<td colspan="8"><textarea  name="subject" cols="106" rows="4" ></textarea></td>
		   </tr>
		</tr>	  
	  <tr valign="top">
      		<td align="right"><?php echo $AppUI->_('LBL_TIPO'); ?>:</td>  
             <td colspan="8">
             		<select name="meeting_type" size="1" id="meeting_type">
			                  <?php
			  			   			 $mt_lista = $controllerAta-> getMeetingType();	
			  					     foreach($mt_lista as $mt_row){
			  							echo  "<option value='" . (int)$mt_row[0] . "' >" . dPhtml($mt_row[1]) . "</option>";		
			  						}
			    				?>  
              		</select> 
                <p> </p>					
             </td>
       </tr>    
     
	  <tr>
      		<td>&nbsp;</td>
             <td colspan="8">
             
					<table class="tbl" width="60%" id="p1" style="display:none; border-radius:6px" >	
                      	 <tr>
                        	   <td colspan="2" style="background-color:#D6EBFF" ><?php echo $AppUI->_('LBL_ITEM_MONITORACAO'); ?>:</td>                        
                        </tr>
                        <?php
						 	$itens = $controllerAta-> getMeetingItem();
                        	foreach($itens as $item){
								?>
									<tr>
										<td width="95%"><?php echo dPhtml($item[1]); ?>    
                                         <input type="hidden" name="meeting_item_id[]" value="<?php echo (int)$item[0]; ?>"   />                                  
                                        </td>
										<td width="5%">                                         
											<select name="item_select_status[]" size="1">
												<option value="0" ><?php echo $AppUI->_('LBL_SIM'); ?></option>
												<option value="1" ><?php echo $AppUI->_('LBL_NAO'); ?></option>
											</select>
										</td>
									</tr>
					<?php }	?>
                    </table>                                  
           </td>
      </tr>
	  <tr>
			<td>&nbsp;</td>
			 <td colspan="8">
				<table class="tbl" width="80%" id="p2" style="display:none; border-radius:6px" >	
				<tr>
	
					<th align="center"><?php echo $AppUI->_('LBL_TAREFA'); ?></th>
					<th align="center"><?php echo $AppUI->_('LBL_DATA_INICIO'); ?></th>
					<th align="center"><?php echo $AppUI->_('LBL_DATA_FIM'); ?></th>
					<th align="center"><?php echo $AppUI->_('LBL_DURACAO'); ?></th>
					<th align="center"><?php echo $AppUI->_('LBL_ENTREGUE'); ?></th>
				 </tr>	     
				    <?php 
					$tasks = $controllerAta ->getTasksFinished($project_id);
					foreach($tasks as $task){?>


					<tr>
						<td  align="left"><?php echo dPhtml($task['task_name']); ?></td>
						<td  align="center"><?php echo $controllerUtil->formatDateTime($task['task_start_date']); ?></td>
						<td  align="center"><?php echo $controllerUtil->formatDateTime($task['task_end_date']); ?></td>
						<td  align="center"><?php echo dPhtml($task['task_duration']); ?></td>		
						<td width="5%">                                         
							<select name="item_select_status_entrega[]" size="1">
								<option value="0" ><?php echo $AppUI->_('LBL_SIM'); ?></option>
								<option value="1" ><?php echo $AppUI->_('LBL_NAO'); ?></option>
							</select>
						</td>
						<input type="hidden" name="task_id_entrega[]" value="<?php echo (int)$task['task_id']; ?>"   />
											
					</tr>				
				<?php } ?>	
				</table>                                  
		   </td>
	  </tr>
		   		
       <tr>
             <td>&nbsp;</td>
             <td colspan="8">
             
        <table class="std"  width="61%" align="left" style="display:none; border-radius:6px" border="0"  id="p3" >	
		<tr>

			<th align="center">%</th>		
			<th align="center"><?php echo $AppUI->_('LBL_TAMANHO'); ?> (<?php echo $AppUI->_('LBL_HORA'); ?>)</th>
			<th align="center"><?php echo $AppUI->_('LBL_IDC'); ?></th>
			<th align="center"><?php echo $AppUI->_('LBL_IDP'); ?></th>
			<th align="center"><?php echo $AppUI->_('LBL_VP'); ?></th>
			<th align="center"><?php echo $AppUI->_('LBL_VA'); ?></th>
			<th align="center"><?php echo $AppUI->_('LBL_CR'); ?></th>
			<th align="center"><?php echo $AppUI->_('LBL_NUMERO_BASELINE'); ?></th>			
			
         </tr>		
				<?php 
					$company_id = 0;
					$companies = $controllerReport ->getProjectCompany($project_id);
					foreach($companies as $comp){
							$company_id = $comp[0];
						}
				
					$cadastrados = $controllerReport -> obterDadosRelatorioGerenciaSenior($company_id);	
					foreach($cadastrados as $cad){
					
					if($cad['idc'] < 0.8){$corIdc="#FF9FA5";}elseif ($cad['idc'] < 1){$corIdc="#FFFFAE";}elseif ($cad['idc'] > 1){$corIdc="#B7FFB7";}
					if($cad['idp'] < 0.8){$corIdp="#FF9FA5";}elseif ($cad['idp'] < 1){$corIdp="#FFFFAE";}elseif ($cad['idp'] > 1){$corIdp="#B7FFB7";} 						
				?>										
					<tr>
						<td  align="center"><?php echo number_format($cad['percentual'], 2, ',', '.'); ?></td>
						<td  align="center"><?php echo dPhtml($cad['tamanho']); ?> </td>
						<td bgcolor="<?php echo $corIdc; ?>" align="center" ><?php echo number_format($cad['idc'], 2, ',', '.'); ?> </td>
						<td bgcolor="<?php echo $corIdp; ?>" align="center" ><?php echo number_format($cad['idp'], 2, ',', '.'); ?> </td>	
						
						<td align="center" ><?php echo number_format($cad['vp'], 2, ',', '.'); ?> </td>
						<td align="center" ><?php echo number_format($cad['va'], 2, ',', '.'); ?> </td>
						<td align="center" ><?php echo number_format($cad['cr'], 2, ',', '.'); ?> </td>
						<td align="center"><?php echo (int)$cad['baseline']; ?> </td>								
						
						<input type="hidden" name="percentual" value="<?php echo dPhtml($cad['percentual']); ?>"   />
						<input type="hidden" name="tamanho" value="<?php echo dPhtml($cad['tamanho']); ?>"   />
						<input type="hidden" name="idc" value="<?php echo dPhtml($cad['idc']); ?>"   />
						<input type="hidden" name="idp" value="<?php echo dPhtml($cad['idp']); ?>"   />
						<input type="hidden" name="va" value="<?php echo dPhtml($cad['va']); ?>"   />
						<input type="hidden" name="vp" value="<?php echo dPhtml($cad['vp']); ?>"   />
						<input type="hidden" name="cr" value="<?php echo dPhtml($cad['cr']); ?>"   />
						<input type="hidden" name="baseline" value="<?php echo (int)$cad['baseline']; ?>"   />
						  
											
			        </tr>				
				<?php } ?>	
					<tr><td colspan='8'align="center">
						<table class="std" width="100%" >
						<tr><td align="center" width="20" style="border-style:solid;border-width:1px" bgcolor="#FF9FA5"></td>
						<td align="left">&lt; 0,8</td>
						<td width="20" style="border-style:solid;border-width:1px" bgcolor="#FFFFAE">&nbsp; &nbsp;</td>
						<td align="left">&lt; 1</td>
						<td width="20" style="border-style:solid;border-width:1px" bgcolor="#B7FFB7">&nbsp; &nbsp;</td>
						<td align="left">&gt; 1</td>
						</tr></table> </td>   						
					</tr>	                    
                    </table>                 
                  </td>                    
           </tr>
		<tr>
			<td colspan="8"><input type="button" value="<?php echo $AppUI->_('Voltar');?>" class="button"<?php echo dPaction(null, array(), null, array('href' => '?m=projects&a=view&project_id=' . (int)$project_id)); ?> /></td>
			<td align="right"><input type="button" value="<?php echo $AppUI->_('Gravar');?>" class="button"<?php echo dPaction('validateMeeting'); ?>  /></td>
		</tr>
</table>


<!--************************** TAB  ***************************************** -->
<table width="100%" cellspacing="0" cellpadding="2" border="0">
	<tbody>
		<tr>
			<td nowrap="nowrap">
				<a href="?m=monitoringandcontrol&amp;a=addedit_ata&amp;project_id=<?php echo $project_id; ?>">
					tabbed
				</a>
			      : <a href="#"<?php echo dPaction(null, array(), 'click'); ?>>
					flat
				</a>
			</td>
		</tr>
	</tbody>
</table>
<table width="100%" cellspacing="0" cellpadding="0" border="0" summary="tabbed view">  
	<tbody> 
    	<tr> <!-- tabs-->
<script type="text/javascript" nonce="<?php echo dPcspNonce(); ?>">
function alteraTab_0(){	
	document.getElementById("pendencias_ata").style.display = "block";	
	document.getElementById('toptab_0').style.background = "url(style/default/images/tabSelectedBg.png)";
  	document.getElementById("pendencias_anteriores").style.display = "none";	
	document.getElementById('toptab_1').style.background = "url(style/default/images/tabBg.png)";	
}	
</script>        
			<td>
            
  				<table cellspacing="0" cellpadding="0" border="0">
				<tbody>                
			<tr>
            
			<td valign="middle">
				<img border="0" alt="" id="lefttab_0" src="./style/default/images/tabSelectedLeft.png">
			</td>
			<td nowrap="nowrap" valign="middle" style="background: url(style/default/images/tabSelectedBg.png);" id="toptab_0">&nbsp;
				<a href=" #"<?php echo dPaction('alteraTab_0'); ?> ><?php echo $AppUI->_('LBL_ACAO_CORRETIVA'); ?></a>&nbsp;
			</td>
			<td valign="middle">
				<img border="0" alt="" src="./style/default/images/tabSelectedRight.png" id="righttab_0">
			</td>
			<td class="tabsp"><img height="1" width="3" alt="" src="./images/shim.gif">
			</td>			

		</tr>       
		</tbody>
		</table>        
			 </td>
		</tr><!-- tabs-->
        
        <tr> 
       		<td class="tabox" width="100%" colspan="69">
            
<div id="pendencias_ata" style="display:block">  <!--toptab_0-->

<script type="text/javascript" nonce="<?php echo dPcspNonce(); ?>">
function popNovaPendencia() {	 															 
	window.open("?m=monitoringandcontrol&a=nova_pendencia&dialog=1&project_id=<?php echo (int)$project_id;?>", "pendencia", "left=300,top=50,height=790,width=700");
}
function deleteRole(rowId){
	var id=rowId;
	var field=document.getElementById("items_ids_to_delete");
	field.value+=field.value==""? id: id;
	var i=document.getElementById(rowId).rowIndex;
	document.getElementById('tbl_pendencias').deleteRow(i);

}
</script>
<script type="text/javascript" src="./modules/monitoringandcontrol/js/pendencias.js"   ></script>

    <p align="left" style="width:85%"> 
   <input type="button" class="button" value="<?php echo $AppUI->_('LBL_CADASTRAR'); ?>" <?php echo dPaction('popNovaPendencia'); ?>  />
    </p>   
    
    
	<table id="tbl_pendencias" class="std"  width="70%" align="center" style="border-radius:5px">	   
		<tr>
            <th align="center"  style="width:25%"><?php echo $AppUI->_('LBL_ACAO_CORRETIVA'); ?></th>
			<th align="center" style="width:15%"><?php echo $AppUI->_('LBL_RESPONSAVEL'); ?> </th>
			<th align="center" style="width:20%"><?php echo $AppUI->_('LBL_PRAZO'); ?> </th>
			<th align="center" style="width:12%"><?php echo $AppUI->_('LBL_STATUS'); ?> </th> 
			<th   style="width:3%">&nbsp; </th> 
        </tr>		
		  <tr id="1000">
			<td ></td> <td ></td> <td ></td> <td ></td> <td ></td></tr>         
     </table>
      </form>
 </div>  <!--pendencias_ata-->
 