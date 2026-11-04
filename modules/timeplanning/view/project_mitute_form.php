<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
?>
	<?php $actionEstimation = dPgetCleanParam($_GET, 'action_estimation', '');?>
	<div id="ata_div" width="100%" style="display:<?php echo $actionEstimation=="read"?'block':'none'; ?>">
		<script type="text/javascript" nonce="<?php echo dPcspNonce(); ?>">
			// Calendar button below (run through js/dp-actions.js).
			function tpDisplayCalendar(fieldId, format, button) {
				displayCalendar(document.getElementById(fieldId), format, button);
			}
		</script>
		 <!-- calendar goodies -->
		<link type="text/css" rel="stylesheet" href="./modules/timeplanning/js/jsLibraries/dhtmlgoodies_calendar/dhtmlgoodies_calendar.css?random=20051112" media="screen"></LINK>
		<SCRIPT type="text/javascript" src="./modules/timeplanning/js/jsLibraries/dhtmlgoodies_calendar/dhtmlgoodies_calendar.js?random=20060118"></script>
		

			<table class="std" align="center" width="80%" style="border: 1px solid black;border-radius:10px ">
				
				<tr>
					<td nowrap><?php echo $AppUI->_('LBL_TYPE'); ?>:</td>
					<td nowrap>
						<input type="checkbox" name="isEffort" value="1" <?php echo $isEffort==""?"":"checked"; ?>><?php echo $AppUI->_('LBL_EFFORT'); ?>  &nbsp;&nbsp;&nbsp;&nbsp;
						<input type="checkbox" name="isDuration" value="1" <?php echo $isDuration==""?"":"checked"; ?>><?php echo $AppUI->_('LBL_DURATION'); ?>   &nbsp;&nbsp;&nbsp;&nbsp;
						<input type="checkbox" name="isResource" value="1" <?php echo $isResource==""?"":"checked"; ?>><?php echo $AppUI->_('LBL_RESOURCES'); ?>
						<input type="checkbox" name="isSize" value="1" <?php echo $isSize==""?"":"checked"; ?>><?php echo $AppUI->_('LBL_SIZE'); ?>
					</td>	
				</tr>
				
				<tr>
					<td nowrap><?php echo $AppUI->_('LBL_DATE'); ?>:</td>
					<td nowrap >
						<input type="hidden" name="date" id="date">
						<input type="text" class="text" disabled name="date_edit" value="<?php echo dPhtml($date); ?>" id="date_edit">
						<img src="./modules/timeplanning/images/img.gif" id="calendar_trigger" style="cursor:pointer"<?php echo dPaction('tpDisplayCalendar', array('date_edit', 'dd/mm/yyyy', '$this')); ?>>
						
					</td>
					
				</tr>
				
				<tr>
					<td nowrap valign="top" colspan="2">
							<?php
								$project_id= dPgetParam($_GET, 'project_id', 0);
								$all_users = $controllerProjectMinute->getAllUsers();
								//update allocated users list with his names
								if(!is_array($members)){
									$members=array();
								}
								foreach($members as $member){
									$key=$member."";//make key as string
									$members[$key]=$all_users[$key];
								}
								//clear any empty option
								for($i=0;$i<sizeof($members);$i++){
									if($members[$i]==""){
										unset($members[$i]);
									}
								}
								//remove fromall users array the users already allocated
								$all_users=array_diff($all_users, $members);
							?>

							<table width="100%"  cellpadding="4" cellspacing="0" >
								<tr>
									<td valign="top" align="center">
										<table cellspacing="0" cellpadding="2" border="0">
											<tr>
												<td>
													<fieldset style="border: 1px solid black;border-radius:10px ">
														<legend><?php echo $AppUI->_('LBL_ALL_MEMBERS'); ?></legend>
															<?php echo arraySelect($all_users, 'all_members', 'style="width:290px" size="10" class="text"  ', null); ?>
													</fieldset>
												</td>
												<td>
													<fieldset style="border: 1px solid black;border-radius:10px ">
														<legend><?php echo $AppUI->_('LBL_PARTICIPANTS'); ?></legend>
														<?php echo arraySelect($members, 'selected_members', 'style="width:290px" size="10" class="text" id="selected_members" ', null); ?>			
													</fieldset>
												</td>
											</tr>
											<tr>
												<td>
													
												</td>
												<td>
												</td>
											</tr>
											<tr>
												<td colspan="2" align="center">
													<table>
														<tr>
															<td align="right"><input type="button" class="button" value="&gt;"<?php echo dPaction('addMember', array('minute_form', 'all_members', 'selected_members')); ?> /></td>	
															<td align="left"><input type="button" class="button" value="&lt;"<?php echo dPaction('removeMember', array('minute_form', 'all_members', 'selected_members')); ?> /></td>					
														</tr>
													</table>
												</td>
											</tr>
										</table>
									</td>
								</tr>
							</table>
					</td>
				</tr>
				<tr>
					<td valign="top" nowrap colspan="2" align="center">
						<?php echo $AppUI->_('LBL_REPORT'); ?><br>
						<textarea id="description_edit" name="description_edit" rows="20" cols="80" style="width:90%"><?php echo dPhtml($description); ?></textarea>
					</td>
				</tr>
				<tr>
					<td colspan="2" align="center">
						<input type="button" name="Salvar" value="<?php echo $AppUI->_('LBL_SAVE'); ?>"<?php echo dPaction('saveReport'); ?> class="button">
						<input type="button" name="Fechar" value="<?php echo $AppUI->_('LBL_CLOSE'); ?>"<?php echo dPaction('closeReport'); ?> class="button">
					</td>
				</tr>
			</table>
	</div>