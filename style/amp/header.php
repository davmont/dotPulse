<?php $dialog = dPgetParam($_GET,'dialog',0); if($dialog) { $page_title = ''; } else { $page_title = $dPconfig['page_title'].'&nbsp;'.$AppUI->getVersion(); } ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html;charset=<?php echo isset($locale_char_set) ? $locale_char_set : 'ISO-8859-1'; ?>" />
<title><?php echo @dPgetConfig('page_title'); ?></title>
<link rel="stylesheet" type="text/css" href="./style/<?php echo $uistyle; ?>/main.css" media="all" />
<style type="text/css" media="all">
@import "./style/<?php echo $uistyle; ?>/main.css";
</style>
<link rel="shortcut icon" href="./style/<?php echo $uistyle; ?>/img/favicon.ico" type="image/ico" />
<link rel="icon" href="./images/brand/favicon.svg" type="image/svg+xml" />
<link rel="apple-touch-icon" href="./images/brand/apple-touch-icon.png" />
<?php @$AppUI->loadJS(); echo $AppUI->getCsrfMeta(); ?>
<?php include("./style/".$uistyle."/color.php"); ?>
</head>
<body>
<?php if(!$dialog) { $nav = $AppUI->getMenuModules(); $perms =& $AppUI->acl(); ?>
<style type="text/css" media="all"> 
#container_content { top:160px; min-width:950px; } 
</style>
<div id="container_login">
	<div class="content"> <?php echo $AppUI->_('Welcome'); ?> <strong><?php echo htmlspecialchars($AppUI->user_first_name, ENT_QUOTES)."&nbsp;".htmlspecialchars($AppUI->user_last_name, ENT_QUOTES); ?></strong> &bull; <?php echo dPheaderWidgets(' &bull; '); ?><?php echo dPcontextHelp('Help'); ?> &bull; <a href="./index.php?m=admin&a=viewuser&user_id=<?php echo $AppUI->user_id; ?>"><?php echo $AppUI->_('My Info'); ?></a> &bull;
		<?php if($perms->checkModule('calendar','access')) { $now = new CDate(); ?>
		<b><a href="./index.php?m=tasks&a=todo"><?php echo $AppUI->_('Todo'); ?></a></b> &bull; <a href="./index.php?m=calendar&a=day_view&date=<?php echo $now->format(FMT_TIMESTAMP_DATE); ?>"><?php echo $AppUI->_('Today'); ?></a>
		<?php } ?>
		&bull; <a href="./index.php?logout=-1"><?php echo $AppUI->_('Logout'); ?></a></div>
</div>
<div id="container_header">
	<ul>
		<?php foreach($nav as $module) { if($perms->checkModule($module['mod_directory'],'access')) { if($m == $module['mod_directory']) { ?>
		<li class="open">
			<div class="content"><?php echo $AppUI->_($module['mod_ui_name']); ?></div>
		</li>
		<?php } else { ?>
		<li<?php echo dPaction(null, array(), null, array('href' => '?m=' . $module['mod_directory'])); ?>>
			<div class="content"><?php echo $AppUI->_($module['mod_ui_name']); ?></div>
		</li>
		<?php } } } ?>
	</ul>
</div>
<div id="container_new_item">
	<form name="frm_new" method="get" action="./index.php">
		<?php $newItem = array(""=>'- New Item -'); $newItem["companies"] = "Company"; $newItem["contacts"] = "Contact"; $newItem["calendar"] = "Event"; $newItem["files"] = "File"; $newItem["projects"] = "Project"; echo arraySelect($newItem,'m',dPaction('dpSubmitIfValue'),'',true); ?>
		<input type="hidden" name="a" value="addedit" />
		<?php if(isset($company_id)) { ?>
		<input type="hidden" name="company_id" value="<?php echo $company_id ?>" />
		<?php } if(isset($task_id)) { ?>
		<input type="hidden" name="task_parent" value="<?php echo $task_id ?>" />
		<?php } if(isset($file_id)) { ?>
		<input type="hidden" name="file_id" value="<?php echo $file_id ?>" />
		<?php } ?>
	</form>
</div>
<div id="container_company_name">
	<div class="company_name"><a href="<?php echo $dPconfig['base_url'] ?>"><?php echo $dPconfig['company_name'] ?></a></div>
</div>
<div id="container_content">
<table cellpadding="0" cellspacing="0" border="0" width="100%">
<tr>
	<td><img src="style/<?php echo $uistyle;?>/img/tl.png" /></td>
	<td bgcolor="#ffffff" height="8px"></td>
	<td><img src="style/<?php echo $uistyle;?>/img/tr.png" /></td>
</tr>
<tr>
	<td bgcolor="#ffffff" width="8px"></td>
	<td bgcolor="#ffffff"><?php } else { ?>
		<style type="text/css" media="all"> 
body { background-image:none; background-color:#FFF; } 
</style>
		<?php } ?>
		<div class="content">
		<?php echo $AppUI->getMsg(); ?>