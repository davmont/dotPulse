<?php /* STYLE/DOTPULSE: page header, top bar and navigation */
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly');
}
$dialog = (int) dPgetParam($_GET, 'dialog', 0);
$current_module = isset($m) ? $m : '';
?>
<!DOCTYPE html>
<html lang="<?php echo dPhtml(substr((string)$AppUI->user_locale, 0, 2) ?: 'en'); ?>">

<head>
	<meta http-equiv="Content-Type"
		content="text/html;charset=<?php echo isset($locale_char_set) ? $locale_char_set : 'UTF-8'; ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="Description" content="dotPulse" />
	<meta name="Version" content="<?php echo @$AppUI->getVersion(); ?>" />
	<title><?php echo @dPgetConfig('page_title'); ?></title>
	<link rel="stylesheet" type="text/css" href="./style/<?php echo $uistyle; ?>/main.css" media="all" />
	<link rel="shortcut icon" href="./style/<?php echo $uistyle; ?>/images/favicon.ico" type="image/ico" />
	<link rel="icon" href="./images/brand/favicon.svg" type="image/svg+xml" />
	<link rel="apple-touch-icon" href="./images/brand/apple-touch-icon.png" />
	<?php @$AppUI->loadJS(); echo $AppUI->getCsrfMeta(); ?>
</head>

<body<?php echo $dialog ? ' class="dp-dialog"' : ''; ?>>
<?php if (!$dialog) { // no top bar in pop-up windows
	$nav = $AppUI->getMenuModules();
	$newItemPermCheck = array(
		'companies' => 'Company',
		'contacts' => 'Contact',
		'calendar' => 'Event',
		'files' => 'File',
		'projects' => 'Project'
	);
	$newItem = array(0 => '- New Item -');
	foreach ($newItemPermCheck as $mod_check => $mod_check_title) {
		if (getPermission($mod_check, 'add')) {
			$newItem[$mod_check] = $mod_check_title;
		}
	}
?>
	<header class="dp-top">
		<div class="dp-top-in">
			<a class="dp-logo" href="<?php echo dPhtml($dPconfig['base_url']); ?>/index.php"><img src="./images/brand/logo.svg" width="110" height="30" alt="<?php echo dPhtml(dPgetConfig('page_title', 'dotPulse')); ?>" /></a>
			<nav class="dp-nav" aria-label="<?php echo $AppUI->_('Modules'); ?>">
				<?php
				foreach ($nav as $module) {
					if (getPermission($module['mod_directory'], 'access')) {
						$on = ($module['mod_directory'] == $current_module) ? ' class="dp-on" aria-current="page"' : '';
						echo '<a href="?m=' . dPhtml($module['mod_directory']) . '"' . $on . '>' . $AppUI->_($module['mod_ui_name']) . "</a>\n";
					}
				}
				?>
			</nav>
			<div class="dp-tools">
				<?php if (getPermission('smartsearch', 'access')) { ?>
				<form name="frmHeaderSearch" action="?m=smartsearch" method="post" role="search">
					<input class="text" type="search" id="keyword1" name="keyword1" aria-label="<?php echo $AppUI->_('Search'); ?>"
						placeholder="<?php echo $AppUI->_('Search'); ?>"
						value="<?php echo dPhtml(dPgetCleanParam($_POST, 'keyword1', '')); ?>" accesskey="k" />
				</form>
				<?php } ?>
				<form name="frm_new" method="get" action="./index.php">
					<?php
					echo arraySelect($newItem, 'm', 'aria-label="' . $AppUI->_('New Item') . '"' . dPaction('dpSubmitIfValue'), '', true);
					echo '<input type="hidden" name="a" value="addedit" />';
					if (isset($company_id)) {
						echo '<input type="hidden" name="company_id" value="' . (int)$company_id . '" />';
					}
					if (isset($task_id)) {
						echo '<input type="hidden" name="task_parent" value="' . (int)$task_id . '" />';
					}
					if (isset($file_id)) {
						echo '<input type="hidden" name="file_id" value="' . (int)$file_id . '" />';
					}
					?>
				</form>
			</div>
		</div>
		<div class="dp-sub">
			<span><?php echo $AppUI->_('Welcome') . ' ' . htmlspecialchars($AppUI->user_first_name, ENT_QUOTES) . ' ' . htmlspecialchars($AppUI->user_last_name, ENT_QUOTES); ?></span>
			<span>
				<?php echo dPheaderWidgets(' · '); ?><?php echo dPcontextHelp('Help'); ?> ·
				<a href="./index.php?m=admin&amp;a=viewuser&amp;user_id=<?php echo (int)$AppUI->user_id; ?>"><?php echo $AppUI->_('My Info'); ?></a> ·
				<?php
				if (getPermission('calendar', 'access')) {
					$now = new CDate();
					?><a href="./index.php?m=tasks&amp;a=todo"><?php echo $AppUI->_('Todo'); ?></a> ·
					<a href="./index.php?m=calendar&amp;a=day_view&amp;date=<?php echo $now->format(FMT_TIMESTAMP_DATE); ?>"><?php echo $AppUI->_('Today'); ?></a> ·
				<?php } ?>
				<a href="?logout=-1"><?php echo $AppUI->_('Logout'); ?></a>
			</span>
		</div>
	</header>
<?php } // END top bar ?>
	<div class="dp-page">
		<?php echo $AppUI->getMsg(); ?>
