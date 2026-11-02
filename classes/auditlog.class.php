<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

/**
 * Security audit log: logins, two-step login, passwords, API tokens and the changes
 * administrators make (users, roles, permissions, configuration, modules). Always on,
 * unlike the History module. Entries are kept for audit_log_days (System Configuration,
 * 0 = forever) and shown in System Admin > Audit log (modules/system/auditlog.php).
 * Write entries with dPaudit() (includes/main_functions.php). See docs/audit-log.md.
 */
class CAuditLog
{
	const DETAILS_MAX = 1000;

	/** Event names and what they mean, for the viewer's filter and docs/audit-log.md. */
	static function events()
	{
		return array(
			'login.success' => 'Logged in',
			'login.failure' => 'Wrong user name or password',
			'login.blocked' => 'Login refused: too many failed attempts',
			'logout' => 'Logged out',
			'twofactor.failure' => 'Wrong two-step login code',
			'twofactor.blocked' => 'Two-step login refused: too many wrong codes',
			'twofactor.recovery_used' => 'Logged in with a recovery code',
			'twofactor.enabled' => 'Two-step login turned on',
			'twofactor.disabled' => 'Two-step login turned off',
			'twofactor.recovery_renewed' => 'New recovery codes',
			'twofactor.reset' => 'Two-step login reset by an administrator',
			'password.changed' => 'Password changed',
			'password.reset_requested' => 'Password reset requested',
			'password.reset' => 'Password reset with an emailed link',
			'api.token_created' => 'API token created',
			'api.token_revoked' => 'API token revoked',
			'api.blocked' => 'API refused: too many failed authentications',
			'user.created' => 'User created',
			'user.updated' => 'User changed',
			'user.deleted' => 'User deleted',
			'user.transferred' => 'User\'s items transferred',
			'user.role_added' => 'Role given to a user',
			'user.role_removed' => 'Role taken from a user',
			'permission.added' => 'Permission added',
			'permission.removed' => 'Permission removed',
			'role.saved' => 'Role created or changed',
			'role.deleted' => 'Role deleted',
			'config.changed' => 'System configuration changed',
			'module.changed' => 'Module installed, removed, upgraded or switched',
		);
	}

	/**
	 * Adds an entry. $user_id and $username default to the logged-in user; for a failed
	 * login, pass 0 and the user name that was tried.
	 */
	static function add($event, $details = '', $object = '', $object_id = 0, $user_id = null, $username = null)
	{
		global $AppUI;
		if ($user_id === null) {
			$user_id = (isset($AppUI) && $AppUI->user_id > 0) ? (int)$AppUI->user_id : 0;
		}
		if ($username === null) {
			$username = $user_id ? self::username($user_id) : '';
		}
		$q = new DBQuery;
		$q->addTable('audit_log');
		$q->addInsert('audit_date', date('Y-m-d H:i:s'));
		$q->addInsert('audit_user', (int)$user_id);
		$q->addInsert('audit_username', mb_substr((string)$username, 0, 255));
		$q->addInsert('audit_ip', mb_substr((string)($_SERVER['REMOTE_ADDR'] ?? 'cli'), 0, 45));
		$q->addInsert('audit_event', mb_substr((string)$event, 0, 40));
		$q->addInsert('audit_object', mb_substr((string)$object, 0, 40));
		$q->addInsert('audit_object_id', (int)$object_id);
		$q->addInsert('audit_details', mb_substr(preg_replace('/[\x00-\x1F\x7F]+/', ' ', (string)$details), 0, self::DETAILS_MAX));
		$q->exec();
		// Old entries go now and then rather than on every write.
		if (random_int(1, 200) == 1) {
			self::purge();
		}
	}

	static function purge()
	{
		$days = (int)dPgetConfig('audit_log_days', 365);
		if ($days > 0) {
			$q = new DBQuery;
			$q->setDelete('audit_log');
			$q->addWhere('audit_date < ' . $q->quote(date('Y-m-d H:i:s', time() - $days * 86400)));
			$q->exec();
		}
	}

	static function username($user_id)
	{
		$q = new DBQuery;
		$q->addTable('users');
		$q->addQuery('user_username');
		$q->addWhere('user_id = ' . (int)$user_id);
		return (string)$q->loadResult();
	}

	/**
	 * Entries for the viewer, newest first. $filter: event (exact, or a prefix ending in
	 * '.'), user (user name, exact), from and to (YYYY-MM-DD).
	 */
	static function search($filter, $limit, $offset)
	{
		$q = new DBQuery;
		$q->addTable('audit_log');
		$q->addQuery('audit_id, audit_date, audit_user, audit_username, audit_ip, audit_event, audit_object, '
			. 'audit_object_id, audit_details');
		if (!empty($filter['event'])) {
			$event = (string)$filter['event'];
			$q->addWhere(substr($event, -1) == '.'
				? 'audit_event LIKE ' . $q->quote(str_replace(array('%', '_'), array('\\%', '\\_'), $event) . '%')
				: 'audit_event = ' . $q->quote($event));
		}
		if (!empty($filter['user'])) {
			$q->addWhere('audit_username = ' . $q->quote((string)$filter['user']));
		}
		foreach (array('from' => '>=', 'to' => '<') as $key => $op) {
			if (!empty($filter[$key]) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter[$key])) {
				$day = $key == 'to' ? date('Y-m-d', strtotime($filter[$key] . ' +1 day')) : $filter[$key];
				$q->addWhere('audit_date ' . $op . ' ' . $q->quote($day . ' 00:00:00'));
			}
		}
		$q->addOrder('audit_id DESC');
		$q->setLimit((int)$limit + 1, (int)$offset);
		return $q->loadList();
	}
}
