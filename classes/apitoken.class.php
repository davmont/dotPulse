<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

/**
 * Personal access tokens for the REST API (api.php).
 *
 * A token is "dpp_" followed by 40 hex digits (160 random bits). It is shown once, when it
 * is created; the database keeps only its SHA-256 and the first characters, so a leaked
 * database does not give access. A token acts as its user, with the user's permissions,
 * limited to reading unless its scope is 'write'.
 */
class CApiToken
{
	const PREFIX = 'dpp_';
	const MAX_PER_USER = 20;

	/**
	 * Creates a token for a user. Returns the token in clear (the only time it is known),
	 * or false when the user already has MAX_PER_USER tokens.
	 */
	static function create($user_id, $name, $scope, $expires_days)
	{
		if (count(self::listFor($user_id)) >= self::MAX_PER_USER) {
			return false;
		}
		$token = self::PREFIX . bin2hex(random_bytes(20));
		$q = new DBQuery;
		$q->addTable('api_tokens');
		$q->addInsert('token_user', (int)$user_id);
		$q->addInsert('token_name', mb_substr(trim((string)$name), 0, 100));
		$q->addInsert('token_hash', hash('sha256', $token));
		$q->addInsert('token_prefix', mb_substr($token, 0, 12));
		$q->addInsert('token_scope', $scope == 'write' ? 'write' : 'read');
		$q->addInsert('token_created', date('Y-m-d H:i:s'));
		if ((int)$expires_days > 0) {
			$q->addInsert('token_expires', date('Y-m-d H:i:s', time() + 86400 * (int)$expires_days));
		}
		$q->exec();
		return $token;
	}

	/**
	 * The token's row (with the user's id) when the token exists and has not expired,
	 * otherwise false. Records the time of use.
	 */
	static function find($token)
	{
		if (!is_string($token) || !preg_match('/^' . self::PREFIX . '[0-9a-f]{40}$/', $token)) {
			return false;
		}
		$q = new DBQuery;
		$q->addTable('api_tokens');
		$q->addQuery('token_id, token_user, token_scope, token_expires');
		$q->addWhere('token_hash = ' . $q->quote(hash('sha256', $token)));
		$row = $q->loadHash();
		if (!$row || ($row['token_expires'] && strtotime($row['token_expires']) < time())) {
			return false;
		}
		$q = new DBQuery;
		$q->addTable('api_tokens');
		$q->addUpdate('token_last_used', date('Y-m-d H:i:s'));
		$q->addWhere('token_id = ' . (int)$row['token_id']);
		$q->exec();
		return $row;
	}

	/** A user's tokens, newest first (never the token itself). */
	static function listFor($user_id)
	{
		$q = new DBQuery;
		$q->addTable('api_tokens');
		$q->addQuery('token_id, token_name, token_prefix, token_scope, token_created, token_expires, token_last_used');
		$q->addWhere('token_user = ' . (int)$user_id);
		$q->addOrder('token_id DESC');
		return $q->loadList();
	}

	/** Deletes one of a user's tokens. Returns true when it existed. */
	static function revoke($token_id, $user_id)
	{
		$q = new DBQuery;
		$q->addTable('api_tokens');
		$q->addQuery('COUNT(*)');
		$q->addWhere('token_id = ' . (int)$token_id . ' AND token_user = ' . (int)$user_id);
		if (!$q->loadResult()) {
			return false;
		}
		$q = new DBQuery;
		$q->setDelete('api_tokens');
		$q->addWhere('token_id = ' . (int)$token_id . ' AND token_user = ' . (int)$user_id);
		$q->exec();
		return true;
	}
}
