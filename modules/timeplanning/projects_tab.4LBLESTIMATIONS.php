<?php
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}
$AppUI->savePlace();
global $task_id, $obj;
require_once (DP_BASE_DIR . "/modules/timeplanning/view/translations.php");
require_once (DP_BASE_DIR . "/modules/timeplanning/model/project_minute.class.php");
require_once (DP_BASE_DIR . "/modules/timeplanning/control/controller_project_minute.class.php");
$controllerProjectMinute=new ControllerProjectMinute();
$minuteId=dPgetIntParam($_GET, 'minute_id', 0);
// Only a minute of the project shown (whose view permission projects/view.php checked).
if ($minuteId > 0 && !dPrecordsBelongTo(dPgetIntParam($_GET, 'project_id', 0),
		array(array('project_minutes', 'id', 'project_id', $minuteId)))) {
	$minuteId = 0;
}
$date=""; 
$description="";
if($minuteId!='-1' && $minuteId!=""){
	$projectMinute=new ProjectMinute();
	$projectMinute->load($minuteId);
	$date=$projectMinute->getDate();
	$description=ProjectMinute::plainText($projectMinute->getDescription());
	$isEffort=$projectMinute->isEffort();
	$isDuration=$projectMinute->isDuration();
	$isResource=$projectMinute->isResource();
	$isSize=$projectMinute->isSize();
	$members=$projectMinute->getMembers();
	
}

?>

<script src="./modules/timeplanning/js/estimations.js"></script>
<form action="?m=timeplanning&a=view&project_id=<?php echo intval(dPgetParam($_GET, 'project_id', 0)); ?>"  method="POST" name="minute_form" id="minute_form" >
	<input name="dosql" type="hidden" value="do_projects_estimations_aed" />
	<input type="hidden" name="minute_id" id="minute_id" value="<?php echo $minuteId ?>">
	<input type="hidden" name="project_id" value="<?php echo intval(dPgetParam($_GET, 'project_id', 0)); ?>">
	<input type="hidden" name="tab" value="<?php echo intval(dPgetParam($_GET, 'tab', 0)); ?>">
	<input type="hidden" name="membersIds" id="membersIds">
	<input type="hidden" name="action_estimation" id="action_estimation" value="">
	
	<div>
		<table align="center" width="80%">
			<tr>
				<td>
					<input type="button" class="button" value="<?php echo $AppUI->_('LBL_CREATE_MINUTE'); ?>"<?php echo dPaction('openEstimationReport'); ?>>
				</td>
			</tr>
		</table>
		
		<?php require_once (DP_BASE_DIR . "/modules/timeplanning/view/project_mitute_form.php"); ?>
		
		<table class="std" align="center" width="80%" style="border-radius:10px">
		  <caption> <b><?php echo $AppUI->_('LBL_MINUTES'); ?></b></caption>
			<tr>
				<th>
					<?php echo $AppUI->_('LBL_ID'); ?>
				</th>
				<th>
					<?php echo $AppUI->_('LBL_DATE'); ?>
				</th>
				<th>
					<?php echo $AppUI->_('LBL_DESCRIPTION'); ?>
				</th>
				<th>
					<?php echo $AppUI->_('LBL_EDIT'); ?>
				</th>
				<th>
					&nbsp;
				</th>
			</tr>
			
			<?php
				$minutes=$controllerProjectMinute->getProjectMinutes(dPgetIntParam($_GET, 'project_id', 0));			
				foreach ($minutes as $minute) {
				?>
					<tr>
						<?php
							$date=$minute->getDate();
							$id=$minute->getId();
							// First line of the minute.
							$description=explode("\n",ProjectMinute::plainText($minute->getDescription()));
							$description=$description[0];
							
						?>
						<td valign='top'><?php echo $id;?></td>
						<td valign='top'><?php echo dPhtml($date);?></td>
						<td valign='top'><?php echo dPhtml($description);?></td>
						<td valign='top'> <img src="./modules/timeplanning/images/view_icon.gif" style="cursor:pointer"<?php echo dPaction('openReport', array((int)$id)); ?>></td>
						<td><input type="button" name="Deletar" value="<?php echo $AppUI->_('LBL_EXCLUSION'); ?>"<?php echo dPaction('deleteReport', array((int)$id)); ?> class="button"></td>
					</tr>
			<?php } ?>
		
		</table>


	<br>
	<br>
		<?php require (DP_BASE_DIR . "/modules/timeplanning/view/project_tasks_estimation_form.php"); ?>
	</div>
</form>
<br />
<table class="std" align="center" width="80%" style="border-radius:10px">
<tr><th><?php echo $AppUI->_('LBL_DEV_SCHEDULE'); ?></th></tr>
<tr>
	<td>
		<form action="?m=timeplanning&a=view" method="post" name="form_mdp" id="form_mdp">
			<input name="dosql" type="hidden" value="do_schedule_development" />
			<input type='hidden' value='<?php  echo intval(dPgetParam($_GET, 'project_id', 0));  ?>' name="project_id" />
			<?php echo $AppUI->_('LBL_ACTION_SCHEDULE_DEV'); ?>
			<br/>
			<div align="right" >
				<input type="submit" class="button" value="<?php echo $AppUI->_('LBL_EXECUTE'); ?>" />
			</div>
		</form>
	</td>
</tr>
</table>