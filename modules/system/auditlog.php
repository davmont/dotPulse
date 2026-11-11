<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// Security audit log viewer (classes/auditlog.class.php), newest first, with filters.
// Needs view permission on System Admin (administrators by default).
if (!getPermission('system', 'view')) {
	$AppUI->redirect('m=public&a=access_denied');
}
require_once DP_BASE_DIR . '/classes/auditlog.class.php';

$events = CAuditLog::events();
$groups = array();
foreach (array_keys($events) as $e) {
	if (strpos($e, '.') !== false) {
		$groups[substr($e, 0, strpos($e, '.') + 1)] = true;
	}
}
$filter = array(
	'event' => (string)dPgetCleanParam($_GET, 'event', ''),
	'user' => trim((string)dPgetCleanParam($_GET, 'user', '')),
	'from' => (string)dPgetCleanParam($_GET, 'from', ''),
	'to' => (string)dPgetCleanParam($_GET, 'to', ''),
);
if ($filter['event'] !== '' && !isset($events[$filter['event']]) && !isset($groups[$filter['event']])) {
	$filter['event'] = '';
}
$limit = 100;
$offset = max(0, (int)dPgetParam($_GET, 'offset', 0));
$rows = CAuditLog::search($filter, $limit, $offset);
$more = count($rows) > $limit;
$rows = array_slice($rows, 0, $limit);
$df = $AppUI->getPref('SHDATEFORMAT') . ' ' . $AppUI->getPref('TIMEFORMAT');
$query = function ($offset) use ($filter) {
	return '?' . http_build_query(array('m' => 'system', 'a' => 'auditlog') + array_filter($filter) + ($offset ? array('offset' => $offset) : array()));
};
$days = (int)dPgetConfig('audit_log_days', 365);

require_once DP_BASE_DIR . '/modules/system/admin_ui.php';
dPadminOpen('auditlog');
dPadminHeading('Audit log', 'Logins, two-step login, passwords, API tokens and the changes made by administrators.');
?>
<form method="get" action="index.php" class="std" style="padding: 6px 8px; margin-bottom: 8px">
	<input type="hidden" name="m" value="system" />
	<input type="hidden" name="a" value="auditlog" />
	<label><?php echo $AppUI->_('Event'); ?>:
		<select name="event" class="text">
			<option value=""><?php echo $AppUI->_('All'); ?></option>
			<?php foreach (array_keys($groups) as $g) { ?>
				<option value="<?php echo dPhtml($g); ?>"<?php echo $filter['event'] === $g ? ' selected="selected"' : ''; ?>><?php echo dPhtml($g); ?>*</option>
			<?php } ?>
			<?php foreach ($events as $e => $label) { ?>
				<option value="<?php echo dPhtml($e); ?>"<?php echo $filter['event'] === $e ? ' selected="selected"' : ''; ?>><?php echo dPhtml($e) . ' - ' . $AppUI->_($label); ?></option>
			<?php } ?>
		</select></label>
	<label><?php echo $AppUI->_('User'); ?>: <input type="text" class="text" name="user" size="14" value="<?php echo dPhtml($filter['user']); ?>" /></label>
	<label><?php echo $AppUI->_('From'); ?>: <input type="date" class="text" name="from" value="<?php echo dPhtml($filter['from']); ?>" /></label>
	<label><?php echo $AppUI->_('To'); ?>: <input type="date" class="text" name="to" value="<?php echo dPhtml($filter['to']); ?>" /></label>
	<input type="submit" class="button" value="<?php echo $AppUI->_('filter'); ?>" />
	<a href="?m=system&amp;a=auditlog"><?php echo $AppUI->_('Reset'); ?></a>
</form>
<p><?php echo $days > 0 ? sprintf($AppUI->_('Entries are kept %d days (setting in System Admin > Security and sessions).'), $days)
	: $AppUI->_('Entries are kept forever (audit_log_days is 0).'); ?></p>
<table class="tbl" width="100%" cellpadding="3" cellspacing="1">
	<tr>
		<th><?php echo $AppUI->_('Date'); ?></th>
		<th><?php echo $AppUI->_('User'); ?></th>
		<th><?php echo $AppUI->_('IP address'); ?></th>
		<th><?php echo $AppUI->_('Event'); ?></th>
		<th><?php echo $AppUI->_('Details'); ?></th>
	</tr>
	<?php if (!$rows) { ?>
	<tr><td colspan="5"><?php echo $AppUI->_('No entries.'); ?></td></tr>
	<?php } ?>
	<?php foreach ($rows as $r) {
		$d = new CDate($r['audit_date']);
		$alert = preg_match('/\.(failure|blocked)$/', $r['audit_event']);
		?>
	<tr>
		<td nowrap="nowrap"><?php echo dPhtml($d->format($df)); ?></td>
		<td><?php if ($r['audit_user']) { ?>
			<a href="?m=admin&amp;a=viewuser&amp;user_id=<?php echo (int)$r['audit_user']; ?>"><?php echo dPhtml($r['audit_username']); ?></a>
			<?php } else { echo dPhtml($r['audit_username']); } ?></td>
		<td><?php echo dPhtml($r['audit_ip']); ?></td>
		<td title="<?php echo isset($events[$r['audit_event']]) ? $AppUI->_($events[$r['audit_event']]) : ''; ?>"<?php echo $alert ? ' style="color: #C93D27; font-weight: bold"' : ''; ?>><?php echo dPhtml($r['audit_event']); ?></td>
		<td><?php echo dPhtml($r['audit_details']); ?></td>
	</tr>
	<?php } ?>
</table>
<p>
	<?php if ($offset > 0) { ?><a href="<?php echo dPhtml($query(max(0, $offset - $limit))); ?>">&laquo; <?php echo $AppUI->_('Newer'); ?></a><?php } ?>
	<?php if ($more) { ?>&nbsp; <a href="<?php echo dPhtml($query($offset + $limit)); ?>"><?php echo $AppUI->_('Older'); ?> &raquo;</a><?php } ?>
</p>
<?php dPadminClose(); ?>
