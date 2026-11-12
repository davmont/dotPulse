<?php
if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}

include "functions.php";


############################################################################
// format dates
$df = $AppUI->getPref('SHDATEFORMAT');

// setup the title block

$titleBlock = new CTitleBlock("LBL_COMMUNICATIONS", 'applet3-48.png', $m, "$m.$a");
$titleBlock->addCrumb("?m=$m", "LBL_LIST");
if ($canDelete && $communication_id) {
    $titleBlock->addCrumbDelete("LBL_DELETE", $canDelete, $msg);
}
$titleBlock->show();
?>

<script nonce="<?php echo dPcspNonce(); ?>">
        
    function submitIt(){
        var f = document.uploadFrm;
        f.project.value = document.uploadFrm.project.value;
        f.frequency.value = document.uploadFrm.frequency.value;
        f.channel.value = document.uploadFrm.channel.value;
        f.responsible.value = document.uploadFrm.responsible.value;
        //alert(f.responsible.value);
        f.submit();
    }
        
    function delIt() {
        if (confirm("<?php echo $AppUI->_("LBL_ANSWER_DELETE", UI_OUTPUT_JS); ?>")) {
            var f = document.uploadFrm;
            f.del.value='1';
            f.submit();
        }
    }
        
    function AddReceptor(a, b) {
        var get = GetForm();
        dpPost('?m=communication&a=addedit&communication_id=' + encodeURIComponent(b) + get, { radd: a });
    }
    function DelReceptor(a, b) {            
        var get = GetForm();
        dpPost('?m=communication&a=addedit&communication_id=' + encodeURIComponent(b) + get, { rdel: a });
    }
    function AddIssuing(a, b) {            
        var get = GetForm();
        dpPost('?m=communication&a=addedit&communication_id=' + encodeURIComponent(b) + get, { raddi: a });
    }
    function DelIssuing(a, b) {
        var get = GetForm();
        dpPost('?m=communication&a=addedit&communication_id=' + encodeURIComponent(b) + get, { rdeli: a });
    }        
        
    function GetForm() {
        var get = '';
        var project = document.uploadFrm.project.value;
        var title = document.uploadFrm.communication_title.value;            
        var communication = document.uploadFrm.communication_information.value;
        var channel = document.uploadFrm.channel.value;
        var frequency = document.uploadFrm.frequency.value;
        var restrictions = document.uploadFrm.communication_restrictions.value;
        var communication_date = document.uploadFrm.communication_date.value;
        var responsible = document.uploadFrm.responsible.value;
        if (project != '') {
            get += '&project=' + encodeURIComponent(project);
        }
        if (title != '') {
            get += '&title=' + encodeURIComponent(title);
        }
        if (communication != '') {
            get += '&communication=' + encodeURIComponent(communication);
        }
        if (channel != '') {
            get += '&channel=' + encodeURIComponent(channel);
        }
        if (frequency != '') {
            get += '&frequency=' + encodeURIComponent(frequency);
        }
        if (restrictions != '') {
            get += '&restrictions=' + encodeURIComponent(restrictions);
        }
        if (communication_date != '') {
            get += '&communication_date=' + encodeURIComponent(communication_date);
        }
         if (responsible != '') {
            get += '&responsible=' + encodeURIComponent(responsible);
        }
        return get;
    }
        
    function setdate(id, b){
        var get = GetForm();                
        window.location = '?m=communication&a=addedit&communication_id='+b+'&date='+id+get;
                   
    }
        
</script>


<table width="100%" border="0" cellpadding="3" cellspacing="3" class="std" charset=UTF-8>
    <form name="uploadFrm" action="?m=communication" method="post">
        <input type="hidden" name="dosql" value="do_communication_aed" />
        <input type="hidden" name="del" value="0" />        
        <input type="hidden" name="communication_id" value="<?php echo $communication_id; ?>" />
        <tr>
            <td width="100%" valign="top" align="left">
                <table cellspacing="1" cellpadding="2" width="60%" charset=UTF-8>
                    <tr><td align="left" nowrap="nowrap"><?php echo $AppUI->_("LBL_PROJECT"); ?>:</td>
                        <td><select id="project" name="project" style="min-width:150px">
                            <?php
                            foreach ($projects as $registro) {
                                if (isset($_GET['project'])) {
                                    $value = $_GET['project'];
                                } else {
                                    $value = @$obj->communication_project_id;
                                }
                            echo '<option value="' . $registro['project_id'] . '" ' . ($registro['project_id'] == $value ? 'selected="selected"' : '') . '>'. dPhtml($registro['project_name']) . '</option>';}
                            ?>
                            </select>
                        </td>
                    </tr>
                    <tr><td align="left" nowrap="nowrap"><?php echo $AppUI->_("LBL_TITLE"); ?>:</td>
                        <td><input type="text" align="left" name="communication_title" cols="50" rows="1" value="<?php echo (isset($_GET['title']) ? htmlspecialchars((string)$_GET['title'], ENT_QUOTES) : htmlspecialchars((string)@$obj->communication_title, ENT_QUOTES)); ?>"></input></td>
                    </tr>
                    <tr><td align="left" nowrap="nowrap"><?php echo $AppUI->_("LBL_COMMUNICATION"); ?>:</td>
                        <td align="left"><textarea name="communication_information" cols="100" rows="3" class="textarea" style="width:100%; box-sizing:border-box"><?php echo (isset($_GET['communication']) ? htmlspecialchars((string)$_GET['communication'], ENT_QUOTES) : htmlspecialchars((string)@$obj->communication_information, ENT_QUOTES)); ?></textarea></td>
                    </tr>  
                    <?php
                    if ($communication_id!=0) {
                        foreach ($remitters as $registros) {
                        echo '<tr><td>'; echo $AppUI->_("LBL_ISSUING"); echo ': </td><td> <select id="issuing" name="issuing" style="min-width:150px">';
                            foreach ($rlista as $registro) {
                            echo '<option value="' . $registro['contact_id'] . '" ' . ($registro['contact_id'] == $registros['contact_id'] ? 'selected="selected"' : '') . '>'
                                  . dPhtml($registro['contact_first_name'] . ' ' . $registro['contact_last_name']) . '</option>';
                            }
                        echo '</select><input type="button" value="x" style="color: #aa0000; font-weight: bold"' . dPaction('DelIssuing', array((int)$registros['contact_id'], (int)$communication_id)) . '/></td></tr>';
                        }
                    } else {
                        foreach ($_SESSION['emitters'] as $registro_session) {
                        echo '<tr><td>'; echo $AppUI->_("LBL_ISSUING"); echo ': </td><td> <select id="issuing" name="issuing" style="min-width:150px">';
                            foreach ($rlista as $registro) {
                            echo '<option value="' . $registro['contact_id'] . '" ' . ($registro_session == $registro['contact_id'] ? 'selected="selected"' : '') . '>'
                                  . dPhtml($registro['contact_first_name'] . ' ' . $registro['contact_last_name']) . '</option>';
                            }
                       echo '</select><input type="button" value="x" style="color: #aa0000; font-weight: bold"' . dPaction('DelIssuing', array((int)$registro_session, (int)$communication_id)) . '/></td></tr>';
                        }
                    }
                    ?>
                    <tr><td><?php echo $AppUI->_("LBL_ISSUING");?>: </td>                        
                        <td><select id="issuing" name="issuing" style="min-width:150px"<?php echo dPaction('AddIssuing', array('$value', (int)$communication_id)); ?>>
                            <option value=""><?php echo $AppUI->_("LBL_ADD")?></option>
                    <?php
                    foreach ($rlista as $registro) {
                        echo '<option value="' . $registro['contact_id'] . '">' . dPhtml($registro['contact_first_name'] . ' ' . $registro['contact_last_name']) . '</option>';
                    }
                    ?> </select></td>
                    </tr>
                    <tr><td></td><td><hr></hr></td></tr>

                   <?php
                   if ($communication_id!=0) {
                        foreach ($rreceptors as $registros) {
                        echo '<tr><td>'; echo $AppUI->_("LBL_RECEPTOR"); echo ': </td><td><select id="receptor" name="receptor" style="min-width:150px">';
                            foreach ($rlista as $registro) {
                            echo '<option value="' . $registro['contact_id'] . '" ' . ($registro['contact_id'] == $registros['contact_id'] ? 'selected="selected"' : '') . '>'
                                 . dPhtml($registro['contact_first_name'] . ' ' . $registro['contact_last_name']) . '</option>';
                            }
                        echo '</select><input type="button" value="x" style="color: #aa0000; font-weight: bold"' . dPaction('DelReceptor', array((int)$registros['contact_id'], (int)$communication_id)) . '/></td></tr>';
                        }
                   } else {
                        foreach ($_SESSION['receptors'] as $registro_session) {
                        echo '<tr><td>'; echo $AppUI->_("LBL_RECEPTOR"); echo ':</td><td><select id="receptor" name="receptor" style="min-width:150px">';
                            foreach ($rlista as $registro) {
                            echo '<option value="' . $registro['contact_id'] . '" ' . ($registro_session == $registro['contact_id'] ? 'selected="selected"' : '') . '>'
                                  . dPhtml($registro['contact_first_name'] . ' ' . $registro['contact_last_name']) . '</option>';
                            }
                        echo '</select><input type="button" value="x" style="color: #aa0000; font-weight: bold"' . dPaction('DelReceptor', array((int)$registro_session, (int)$communication_id)) . '/></td></tr>';
                        }
                  }
                  ?>
                    <tr><td><?php echo $AppUI->_("LBL_RECEPTOR")?></td>                        
                        <td><select id="receptor" name="receptor" style="min-width:150px"<?php echo dPaction('AddReceptor', array('$value', (int)$communication_id)); ?>>
                            <option value=""><?php echo $AppUI->_("LBL_ADD")?></option>
                  <?php
                  foreach ($rlista as $registro) {
                  echo '<option value="' . $registro['contact_id'] . '">' . dPhtml($registro['contact_first_name'] . ' ' . $registro['contact_last_name']) . '</option>';}
                  ?></select></td></tr>
                    
                  </tr><tr><td></td><td><hr></hr></td></tr>
                  
                  <tr> 
                  <td><?php echo $AppUI->_("LBL_CHANNEL")?>: </td>
                  <td><select id="channel" name="channel" style="min-width:150px">
                      <option value=""><?php echo $AppUI->_("LBL_SELECT")?></option>
                  <?php
                  foreach ($channels as $registro) {
                    if (isset($_GET['channel'])) {
                        $value = $_GET['channel'];
                    } else {
                        $value = $obj->communication_channel_id;
                    }
                    echo '<option value="' . $registro['communication_channel_id'] . '" ' . ($registro['communication_channel_id'] == $value ? 'selected="selected"' : '') . '>' . dPhtml($registro['communication_channel']) . '</option>';}
                  ?></select></td>
                  </tr>
                  <tr><td><?php echo $AppUI->_("LBL_FREQUENCY")?>: </td>
                      <td><select id="frequency" name="frequency" style="min-width:150px"<?php echo dPaction('setdate', array('$value', (int)$communication_id)); ?>>
                          <option value=""><?php echo $AppUI->_("LBL_SELECT")?></option>
                  <?php
                  if (isset($_GET['frequency'])) {
                      $value = $_GET['frequency'];
                  } else {
                      $value = @$obj->communication_frequency_id;
                  }
                  foreach ($frequency as $registro) {
                    echo '<option value="' . $registro['communication_frequency_id'] . '" ' . ($registro['communication_frequency_id'] == $value ? 'selected="selected"' : '') . '>' . dPhtml($registro['communication_frequency']) . '</option>';
                  }
                  ?></select>
                    <span style="margin-left:15px; <?php echo ($showdate ? '' : 'display:none;'); ?>"><?php echo $AppUI->_("LBL_DATE")?>: </span>
                    <input type="text" style="<?php echo ($showdate ? '' : 'display:none;'); ?> margin-left: 10px;" value="<?php echo ($communication_id == 0 ? htmlspecialchars((string)dPgetParam($_GET, 'communication_date', ''), ENT_QUOTES) : htmlspecialchars((string)@$obj->communication_date, ENT_QUOTES));  ?>" name="communication_date">
                    </td>
                 </tr>
                 <tr><td align="left" nowrap="nowrap"><?php echo $AppUI->_("LBL_RESTRICTIONS");?>: </td>
                     <td align="left"><textarea name="communication_restrictions" cols="100" rows="3" class="textarea" style="width:100%; box-sizing:border-box"><?php echo ($communication_id == 0 ? htmlspecialchars((string)dPgetParam($_GET, 'restrictions', ''), ENT_QUOTES) : htmlspecialchars((string)@$obj->communication_restrictions, ENT_QUOTES)); ?></textarea></td>
                 </tr>
                 <td align="left" nowrap="nowrap"><?php echo $AppUI->_("LBL_RESPONSIBLE");?>: </td> 
                     <td><select id="responsible" name="responsible" style="min-width:150px">
                         <option value=""><?php echo $AppUI->_("LBL_SELECT")?></option>
                  <?php
                  foreach ($rlista as $registro) {
                    if (isset($_GET['responsible'])) {
                        $value = $_GET['responsible'];
                    } else {
                        $value = $obj->communication_responsible_authorization;
                    }
                    echo '<option value="' . $registro['contact_id'] . '" ' . ($registro['contact_id'] == $value ? 'selected="selected"' : '') . '>' . dPhtml($registro['contact_first_name'] . ' ' . $registro['contact_last_name']) . '</option>';}
                  ?></select></td>
                  <tr><td><span style="margin-left:0px"><?php echo $AppUI->_("LBL_SEND")?></span></td></tr>
                
            </table>
        </td>
    </tr>
    <tr>
        <td width="10%">
            <table border="0" width="100%">
            <tr>
                <td><input class="button" type="button" name="cancel" value="<?php echo $AppUI->_("LBL_CANCEL"); ?>"<?php echo dPaction(null, array(), null, array('confirm' => $AppUI->_('LBL_ANSWER_CANCEL', UI_OUTPUT_RAW), 'href' => './index.php?m=communication')); ?> />
                </td>
            </tr>
            </table>
        </td>
        <td align="right">
            <input type="button" class="button" value="<?php echo $AppUI->_("LBL_SUBMIT"); ?>"<?php echo dPaction('submitIt'); ?> />
        </td>
    </tr>
   </form>
</table>
