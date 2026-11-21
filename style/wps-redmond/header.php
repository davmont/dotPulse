<?php /* STYLE/DEFAULT $Id: header.php,v 1.37 2005/02/26 01:09:15 gregorerhardt Exp $ */
$dialog = dPgetParam( $_GET, 'dialog', 0 );
if ($dialog)
	$page_title = '';
else
	$page_title = dPhtml((string)$dPconfig['page_title']) . '&nbsp;' . $AppUI->getVersion();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta name="Description" content="WebbPlatsen Sober" />
	<meta name="Version" content="<?php echo @$AppUI->getVersion();?>" />
	<meta http-equiv="Content-Type" content="text/html;charset=<?php echo isset( $locale_char_set ) ? $locale_char_set : 'ISO-8859-1';?>" />
	<title><?php echo dPhtml((string)dPgetConfig('page_title'));?></title>
	<link rel="stylesheet" type="text/css" href="./style/<?php echo $uistyle;?>/main.css" media="all" />
	<style type="text/css" media="all">@import "./style/<?php echo $uistyle;?>/main.css";</style>
	<link rel="shortcut icon" href="./style/<?php echo $uistyle;?>/images/favicon.ico" type="image/ico" />
	<link rel="icon" href="./images/brand/favicon.svg" type="image/svg+xml" />
	<link rel="apple-touch-icon" href="./images/brand/apple-touch-icon.png" />
	<?php @$AppUI->loadJS(); echo $AppUI->getCsrfMeta(); ?>
</head>

<body>
<table width="100%" cellpadding="0" cellspacing="0" border="0">
<?php if (!$dialog) { // no banner in pop-up windows ?>
<tr>
	<td><table class="banner" width='100%' cellpadding="3" cellspacing="0" border="0" background="style/<?php echo $uistyle;?>/images/titlegrad.jpg"><tr>
	<th align="left"><strong><?php
		echo "<a style='color: white' href='{$dPconfig['base_url']}'>$page_title</a>";
	?></strong></th>
	<th align="right" width='95'><a href='https://github.com/davmont/dotPulse' <?php if ($dialog) echo "target='_blank'"; ?>><img src="./images/brand/icon.svg" width="24" height="24" border="0" alt="dotPulse" title="dotPulse" /></a></th>
	</tr></table></td>
</tr>
<?php } ?>
<?php if (!$dialog) {
	// top navigation menu
	$nav = $AppUI->getMenuModules();
	$perms =& $AppUI->acl();
?>
<tr>
	<td class="nav" align="left">
	<table width="100%" cellpadding="3" cellspacing="0" width="100%">
	<tr>
		<td>
		<?php
		$links = array();
		foreach ($nav as $module) {
			if ($perms->checkModule($module['mod_directory'], 'access')) {
				$links[] = '<a href="?m='.$module['mod_directory'].'">'.$AppUI->_($module['mod_ui_name']).'</a>';
			}
		}
		echo implode( ' | ', $links );
		echo "\n";
		?>
		</td>
		<form name="frm_new" method="get" action="./index.php">
<?php
	echo '        <td nowrap="nowrap" align="right">';
	$newItem = array( ""=>'- New Item -' );
	$newItem["companies"] = "Company";
	$newItem["contacts"] = "Contact";
	$newItem["calendar"] = "Event";
	$newItem["files"] = "File";
	$newItem["projects"] = "Project";

	echo arraySelect( $newItem, 'm', 'style="font-size:10px"' . dPaction('dpSubmitIfValue'), '', true);

	echo "</td>\n";
	echo "        <input type=\"hidden\" name=\"a\" value=\"addedit\" />\n";

//build URI string
	if (isset( $company_id )) {
		echo '<input type="hidden" name="company_id" value="'.$company_id.'" />';
	}
	if (isset( $task_id )) {
		echo '<input type="hidden" name="task_parent" value="'.$task_id.'" />';
	}
	if (isset( $file_id )) {
		echo '<input type="hidden" name="file_id" value="'.$file_id.'" />';
	}
?>
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
			<td width="100%"><?php echo $AppUI->_('Welcome') . ' ' . htmlspecialchars($AppUI->user_first_name, ENT_QUOTES) . ' ' . htmlspecialchars($AppUI->user_last_name, ENT_QUOTES); ?></td>
			<td nowrap="nowrap">
				<?php echo dPheaderWidgets(' | '); ?><?php echo dPcontextHelp( 'Help' );?> |
				<a href="./index.php?m=admin&a=viewuser&user_id=<?php echo $AppUI->user_id;?>"><?php echo $AppUI->_('My Info');?></a> |
<?php
	if ($perms->checkModule('calendar', 'access')) {
		$now = new CDate();
?>                              <b><a href="./index.php?m=tasks&a=todo"><?php echo $AppUI->_('Todo');?></a></b> |
				<a href="./index.php?m=calendar&a=day_view&date=<?php echo $now->format( FMT_TIMESTAMP_DATE );?>"><?php echo $AppUI->_('Today');?></a> |
<?php } ?>
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