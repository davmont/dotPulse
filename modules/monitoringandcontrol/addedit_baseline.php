<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}
header("Content-Type:text/html; charset=iso-8859-1",true);
$AppUI->savePlace();
$project_id = dPgetIntParam( $_GET, 'project_id', 0 );

$titleBlock = new CTitleBlock('Nova Baseline', 'graph-up.png', $m, $m . '.' . $a);
$titleBlock->show();
require_once (DP_BASE_DIR . "/modules/monitoringandcontrol/translations.php");
require_once (DP_BASE_DIR . "/modules/monitoringandcontrol/control/controller_util.class.php");

$controllerUtil = new ControllerUtil();
// The form belongs to an existing project.
$project_name = $controllerUtil->getProjectName($project_id);
if (!$project_name) {
	$AppUI->setMsg('Invalid ID', UI_MSG_ERROR);
	$AppUI->redirect('m=projects');
}
// Forms of a project: the user must be able to edit it.
dPrequireProjectEdit($project_id);
?>
<script src="./modules/monitoringandcontrol/js/baseline.js" > </script>
 <table width="100%" cellspacing="1" cellpadding="1" border="0">
	  <tr>
			<td align="left" colspan="8"><b> <?php echo $AppUI->_('LBL_MONITORACAO'); ?> : <?php echo $AppUI->_('LBL_BASELINE'); ?> </b></td>
		</tr>
</table>
		  <form action="?m=monitoringandcontrol&a=do_baseline_aed&project_id=<?php echo $project_id; ?>" method="post" name="form_ata" id="form_ata" enctype="multipart/form-data">	    
	<input name="dosql" type="hidden" value="do_baseline_aed" />
	<input name="project_id" type="hidden" id="project_id" value="<?php echo $project_id; ?>">
	<input name="user" type="hidden" id="user" value="1">
		<input  type="hidden" name="acao" value="insert"  />
		<br/>
<table class="std" width="100%" cellspacing="0" cellpadding="4" border="0">
	  <tr>
			<td colspan="8">&nbsp;</td>
		</tr>
	  <tr>
			<td align="right"><?php echo $AppUI->_('LBL_PROJETO'); ?>:</td>
			 <td>
				 <input type="text" name="projeto" size="25" id="projeto" readonly="readonly" value="<?php echo dPhtml($project_name[0][0]); ?> " />					
			</td>
		</tr>
	  <tr>
	  <tr>
			<td align="right"><?php echo $AppUI->_('LBL_NOME'); ?>:</td>
			 <td>
				 <input type="text" name="nmBaseline" size="25" id="nmBaseline"  />					
			</td>
		</tr>
	  <tr>
	  <tr>
			<td align="right"><?php echo $AppUI->_('LBL_VERSAO'); ?>:</td>
			 <td>
				 <input type="text" name="nmVersao" size="25" id="nmVersao"  />					
			</td>
		</tr>
	  <tr>
			<td align="right"><?php echo $AppUI->_('LBL_OBSERVACAO'); ?>:</td>
			 <td>
				 <textarea  name="dsObservacao" rows="4" style="width:100%; box-sizing:border-box;"></textarea>					
			</td>
		</tr>		
	  <tr>	  
		<tr>
			<td colspan="8"><input type="button" value="<?php echo $AppUI->_('Voltar');?>" class="button"<?php echo dPaction(null, array(), null, array('href' => '?m=projects&a=view&project_id=' . (int)$project_id)); ?> /></td>
			<td align="right"><input type="button" value="<?php echo $AppUI->_('Gravar');?>" class="button"<?php echo dPaction('validateBaseline'); ?>  /></td>
			
		</tr>
</table>
