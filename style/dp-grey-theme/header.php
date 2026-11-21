<?php /* STYLE/DEFAULT $Id: header.php 6172 2012-06-19 11:32:01Z ajdonnison $ */
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly');
}

$dialog = (int)dPgetParam($_GET, 'dialog', 0);

$page_title = (($dialog) ? '' 
               : (in_array($dPconfig['page_title'], array('dotProject', 'dotPulse')) 
                  ? ('dotPulse ' . $AppUI->getVersion()) 
				  : dPhtml((string)$dPconfig['page_title'])));

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" />
	<meta name="Description" content="dotPulse" />
	<meta name="Version" content="<?php echo @$AppUI->getVersion();?>" />
	<meta http-equiv="Content-Type" content="text/html;charset=<?php 
echo isset($locale_char_set) ? $locale_char_set : 'UTF-8';?>" />
	<title><?php echo dPhtml((string)dPgetConfig('page_title'));?></title>
	<link rel="stylesheet" type="text/css" href="./style/<?php 
echo $uistyle;?>/main.css" media="all" />
	<style type="text/css" media="all">@import "./style/<?php 
echo $uistyle;?>/main.css";</style>
	<link rel="shortcut icon" href="./style/<?php 
echo $uistyle;?>/images/favicon.ico" type="image/ico" />
	<link rel="icon" href="./images/brand/favicon.svg" type="image/svg+xml" />
	<link rel="apple-touch-icon" href="./images/brand/apple-touch-icon.png" />
	<?php 
$AppUI->loadJS(); echo $AppUI->getCsrfMeta(); ?>
	<script type="text/javascript" src="./style/dp-grey-theme/tabs.js"></script>
</head>

<body>
<table width="100%" cellpadding="0" cellspacing="0" border="0">
<?php if (!$dialog) { // no banner in pop-up windows ?>
<tr>
	<td><table width='100%' cellpadding="3" cellspacing="0" border="0"><tr>
	<th style="background: url(style/<?php 
echo $uistyle; ?>/images/titlegrad.jpg" class="banner" align="left"><strong>
	<?php 
echo ('<a href="' . dPformSafe($dPconfig['base_url'], DP_FORM_URI) . '">' . $page_title . '</a>'); 
?>
	</strong>
	<?php if (!$dialog && getPermission('smartsearch', 'access')): ?>
	<form name="frmHeaderSearch" action="?m=smartsearch"  method="post">
		<input class="text" type="text" id="keyword1" name="keyword1" value="<?php echo dPhtml(dPgetCleanParam($_POST, 'keyword1', '')); ?>" accesskey="k" />
		<input class="button" type="submit" value="<?php echo $AppUI->_('Search')?>" />
	</form>
	<?php endif; ?>
	</th>
	<th align="right" width='50'><a href='https://github.com/davmont/dotPulse' <?php 
	echo (($dialog) ? 'target="_blank"' : ''); ?>><img src="./images/brand/icon.svg" width="24" height="24" border="0" alt="dotPulse" title="dotPulse" /></a></th>
	</tr></table></td>
</tr>
<?php } ?>
<?php if (!$dialog) {
	// top navigation menu
	$nav = $AppUI->getMenuModules();
?>
<tr>
	<td class="nav" align="left">
	<table width="100%" cellpadding="3" cellspacing="0">
	<tr class="nav">
		<td>
		<?php
	$links = array();
	foreach ($nav as $module) {
		if (getPermission($module['mod_directory'], 'access')) {
			$links[] = ('<a href="?m=' . dPformSafe($module['mod_directory'], DP_FORM_URI) . '">' 
						. $AppUI->_($module['mod_ui_name']) . '</a>');
		}
	}
	echo implode(' | ', $links);
	echo "\n";
?>
		</td>
        <td nowrap="nowrap" align="right">
		<form name="frm_new" method="get" action="./index.php">
		<table cellpadding="0" cellspacing="0">
		<tr><td>
<?php
	$newItemPermCheck = array('companies' => 'Company',
							  'contacts' => 'Contact',
							  'calendar' => 'Event',
							  'files' => 'File',
							  'projects' => 'Project');
	
	$newItem = array(0=>'- New Item -');
	foreach ($newItemPermCheck as $mod_check => $mod_check_title) {
		if (getPermission($mod_check, 'add')) {
			$newItem[$mod_check] = $mod_check_title;
		}
	}

	echo arraySelect($newItem, 'm', 'style="font-size:10px"' . dPaction('dpSubmitIfValue'), '', true);

	echo ('		<input type="hidden" name="a" value="addedit" />' . "\n");

//build URI string
	if (isset($company_id)) {
		echo ('<input type="hidden" name="company_id" value="' . $company_id . '" />');
	}
	if (isset($task_id)) {
		echo ('<input type="hidden" name="task_parent" value="' . $task_id . '" />');
	}
	if (isset($file_id)) {
		echo ('<input type="hidden" name="file_id" value="' . $file_id . '" />');
	}
?>
		</td></tr>
		</table>
		</form>
		</td>
	</tr>
	</table>
	</td>
</tr>
<tr>
	<td>
		<table cellspacing="0" cellpadding="3" border="0" width="100%">
		<tr>
			<td width="100%"><?php 
	echo ($AppUI->_('Welcome') . ' ' . htmlspecialchars($AppUI->user_first_name, ENT_QUOTES) . ' ' . htmlspecialchars($AppUI->user_last_name, ENT_QUOTES)); 
?></td>
			<td nowrap="nowrap">
				<?php echo dPheaderWidgets(' | '); ?><?php echo dPcontextHelp('Help'); ?> | 
				<a href="./index.php?m=admin&amp;a=viewuser&amp;user_id=<?php 
echo $AppUI->user_id; ?>"><?php echo $AppUI->_('My Info');?></a> | <?php
	if (getPermission('tasks', 'access')) {
?>
				<a href="./index.php?m=tasks&amp;a=todo"><b><?php echo $AppUI->_('Todo'); 
?></b></a> | <?php
	}
	if (getPermission('calendar', 'access')) {
		$now = new CDate();
?>
				<a href="./index.php?m=calendar&amp;a=day_view&amp;date=<?php 
echo $now->format(FMT_TIMESTAMP_DATE);?>"><?php echo $AppUI->_('Today');?></a> | <?php 
	}
?>
				<a href="./index.php?logout=-1"><?php echo $AppUI->_('Logout');?></a>
			</td>
		</tr>
		</table>
	</td>
</tr>
<?php } // END showMenu ?>
</table>

<table width="100%" cellspacing="0" cellpadding="4" border="0">
<tr>
<td valign="top" align="left" width="98%">
<?php
	echo $AppUI->getMsg();
?>
