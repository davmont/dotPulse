<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
// Help: the documentation that comes with dotPulse (website/docs), page by page
// (classes/help.class.php). ?m=help&page=user-guide/tasks; the old help links
// (?m=help&hid=module.action) open the page for that part of the application.
require_once DP_BASE_DIR . '/classes/help.class.php';

$request = (string)dPgetCleanParam($_GET, 'page', '');
$hid = (string)dPgetCleanParam($_GET, 'hid', '');
if ($request === '' && $hid !== '' && preg_match('/^[A-Za-z0-9_-]+(\.[A-Za-z0-9_-]+)*$/', $hid)) {
	header('Location: ' . dPhelpUrl($hid));
	exit;
}

$pages = CHelp::pages();
$page = CHelp::page($request);
$html = $page !== '' ? CHelp::render($page) : null;
if ($html === null) {
	$page = '';
}

$titleBlock = new CTitleBlock('Help', 'dp.gif', $m, 'help.index');
$titleBlock->show();
?>
<link rel="stylesheet" type="text/css" href="./modules/help/help.css" />
<div class="dp-help">
	<nav class="dp-help-nav" aria-label="<?php echo $AppUI->_('Contents', UI_OUTPUT_HTML); ?>">
		<ul>
		<?php
		$section = '';
		foreach ($pages as $name => $info) {
			if ($info['section'] !== $section) {
				if ($section !== '') {
					echo '</ul></li>';
				}
				$section = $info['section'];
				if ($section !== '') {
					echo '<li class="dp-help-section"><span>' . htmlspecialchars($section) . '</span><ul>';
				}
			}
			// A section's own page ("Getting started") is its overview.
			$title = ($section !== '' && $info['title'] === $section) ? $AppUI->_('Overview') : $info['title'];
			$current = $name === $page ? ' class="dp-help-current" aria-current="page"' : '';
			echo '<li><a href="' . htmlspecialchars(CHelp::url($name)) . '"' . $current . '>'
				. htmlspecialchars($title) . '</a></li>';
		}
		if ($section !== '') {
			echo '</ul></li>';
		}
		?>
		</ul>
	</nav>
	<article class="dp-help-page">
		<?php if ($page === ''): ?>
		<p><?php echo $AppUI->_('This help page does not exist.'); ?>
			<a href="<?php echo htmlspecialchars(CHelp::url('index')); ?>"><?php echo $AppUI->_('Contents'); ?></a></p>
		<?php else: ?>
		<?php echo $html; ?>
		<p class="dp-help-foot">
			<?php echo $AppUI->_('The documentation is in English.'); ?>
			<a href="<?php echo htmlspecialchars(dPhelpOnlineUrl($page)); ?>" target="_blank" rel="noopener"><?php
				echo $AppUI->_('Read this page on the documentation website'); ?></a>.
			<?php echo $AppUI->_('Documentation under the CC BY-SA 4.0 licence.'); ?>
		</p>
		<?php endif; ?>
	</article>
</div>
