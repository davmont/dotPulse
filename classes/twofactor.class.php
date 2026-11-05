<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

/**
 * Two-step login with time-based one-time codes (TOTP, RFC 6238: HMAC-SHA1, 30-second
 * steps, 6 digits), as made by Google Authenticator, Aegis, FreeOTP, 1Password and others.
 *
 * A user turns it on in their account (admin/vw_usr_twofactor.php). After a correct
 * password, index.php and fileviewer.php call startPending() instead of logging the user
 * in, and includes/twofactor_login.php asks for a code (or a recovery code) before the
 * login completes. A code is accepted one step either side of now, and each step once
 * (last_counter), so a code seen by someone else cannot be replayed. Recovery codes are
 * single use and stored as SHA-256.
 */
class CTwoFactor
{
	const DIGITS = 6;
	const PERIOD = 30;
	const WINDOW = 1;
	const RECOVERY_CODES = 10;
	/** Seconds a correct password waits for its code. */
	const PENDING_TTL = 300;
	/** Wrong codes allowed per pending login; the password is asked again after that. */
	const MAX_ATTEMPTS = 5;
	const SESSION_KEY = 'dp_twofactor';

	const BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

	// ---- TOTP

	static function newSecret()
	{
		return self::base32Encode(random_bytes(20));
	}

	static function base32Encode($bin)
	{
		$bits = '';
		foreach (str_split($bin) as $c) {
			$bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
		}
		$out = '';
		foreach (str_split($bits, 5) as $chunk) {
			$out .= self::BASE32[bindec(str_pad($chunk, 5, '0'))];
		}
		return $out;
	}

	/** Decodes base32 (case, spaces and padding ignored); false on other characters. */
	static function base32Decode($text)
	{
		$text = strtoupper(preg_replace('/[\s=]/', '', (string)$text));
		$bits = '';
		foreach (str_split($text) as $c) {
			$v = strpos(self::BASE32, $c);
			if ($v === false) {
				return false;
			}
			$bits .= str_pad(decbin($v), 5, '0', STR_PAD_LEFT);
		}
		$out = '';
		foreach (str_split($bits, 8) as $byte) {
			if (strlen($byte) == 8) {
				$out .= chr(bindec($byte));
			}
		}
		return $out;
	}

	/** The code for a time step (RFC 4226 dynamic truncation). */
	static function code($secret, $counter, $digits = self::DIGITS)
	{
		$key = self::base32Decode($secret);
		$hash = hash_hmac('sha1', pack('J', $counter), (string)$key, true);
		$offset = ord($hash[19]) & 0x0f;
		$value = ((ord($hash[$offset]) & 0x7f) << 24) | (ord($hash[$offset + 1]) << 16)
			| (ord($hash[$offset + 2]) << 8) | ord($hash[$offset + 3]);
		return str_pad((string)($value % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
	}

	/**
	 * The time step a code belongs to, within WINDOW steps of $time and after $after (the
	 * last step used), or false.
	 */
	static function matchCounter($secret, $code, $time, $after = -1)
	{
		$code = preg_replace('/\s/', '', (string)$code);
		if (!preg_match('/^\d{' . self::DIGITS . '}$/', $code)) {
			return false;
		}
		$now = intdiv((int)$time, self::PERIOD);
		for ($c = $now - self::WINDOW; $c <= $now + self::WINDOW; $c++) {
			if ($c > $after && hash_equals(self::code($secret, $c), $code)) {
				return $c;
			}
		}
		return false;
	}

	static function otpauthUri($secret, $account, $issuer)
	{
		return 'otpauth://totp/' . rawurlencode($issuer . ':' . $account) . '?secret=' . $secret
			. '&issuer=' . rawurlencode($issuer) . '&algorithm=SHA1&digits=' . self::DIGITS . '&period=' . self::PERIOD;
	}

	/** The QR code of a URI as an SVG data: URI (allowed by the CSP's img-src). */
	static function qrDataUri($text)
	{
		require_once DP_BASE_DIR . '/vendor/autoload.php';
		$options = new \chillerlan\QRCode\QROptions(array(
			// outputInterface works on php-qrcode 5 and 6 (6 removed the MARKUP_SVG constant).
			'outputInterface' => \chillerlan\QRCode\Output\QRMarkupSVG::class,
			'eccLevel' => \chillerlan\QRCode\Common\EccLevel::M,
			'addQuietzone' => true,
			'outputBase64' => true,
		));
		return (new \chillerlan\QRCode\QRCode($options))->render($text);
	}

	// ---- storage

	static function row($user_id)
	{
		$q = new DBQuery;
		$q->addTable('user_twofactor');
		$q->addQuery('user_id, tf_secret, tf_last_counter, tf_enabled_on');
		$q->addWhere('user_id = ' . (int)$user_id);
		$row = $q->loadHash();
		return $row ? $row : false;
	}

	static function isEnabled($user_id)
	{
		return (bool)self::row($user_id);
	}

	/** Turns two-step login on with a secret the user has just proved with a code. */
	static function enable($user_id, $secret, $counter)
	{
		self::disable($user_id);
		$q = new DBQuery;
		$q->addTable('user_twofactor');
		$q->addInsert('user_id', (int)$user_id);
		$q->addInsert('tf_secret', $secret);
		$q->addInsert('tf_last_counter', (int)$counter);
		$q->addInsert('tf_enabled_on', date('Y-m-d H:i:s'));
		$q->exec();
		return self::newRecoveryCodes($user_id);
	}

	static function disable($user_id)
	{
		foreach (array('user_twofactor', 'user_twofactor_recovery') as $table) {
			$q = new DBQuery;
			$q->setDelete($table);
			$q->addWhere('user_id = ' . (int)$user_id);
			$q->exec();
		}
	}

	/** Replaces the user's recovery codes; returns them in clear (shown once). */
	static function newRecoveryCodes($user_id)
	{
		$q = new DBQuery;
		$q->setDelete('user_twofactor_recovery');
		$q->addWhere('user_id = ' . (int)$user_id);
		$q->exec();
		$codes = array();
		for ($i = 0; $i < self::RECOVERY_CODES; $i++) {
			$raw = strtolower(self::base32Encode(random_bytes(5)));
			$code = substr($raw, 0, 4) . '-' . substr($raw, 4, 4);
			$codes[] = $code;
			$q = new DBQuery;
			$q->addTable('user_twofactor_recovery');
			$q->addInsert('user_id', (int)$user_id);
			$q->addInsert('rc_hash', hash('sha256', $code));
			$q->exec();
		}
		return $codes;
	}

	static function recoveryCodesLeft($user_id)
	{
		$q = new DBQuery;
		$q->addTable('user_twofactor_recovery');
		$q->addQuery('COUNT(*)');
		$q->addWhere('user_id = ' . (int)$user_id . ' AND rc_used_on IS NULL');
		return (int)$q->loadResult();
	}

	/**
	 * Checks a code from the user's app, or a recovery code (xxxx-xxxx), and uses it up.
	 * Returns 'totp', 'recovery' or false.
	 */
	static function verify($user_id, $code, $time = null)
	{
		$row = self::row($user_id);
		if (!$row) {
			return false;
		}
		$code = trim((string)$code);
		$counter = self::matchCounter($row['tf_secret'], $code, $time === null ? time() : $time, (int)$row['tf_last_counter']);
		if ($counter !== false) {
			// Conditional update: of two requests with the same code, only one succeeds.
			$q = new DBQuery;
			$q->addTable('user_twofactor');
			$q->addUpdate('tf_last_counter', (int)$counter);
			$q->addWhere('user_id = ' . (int)$user_id . ' AND tf_last_counter < ' . (int)$counter);
			$q->exec();
			return db_affected_rows() == 1 ? 'totp' : false;
		}
		$recovery = strtolower(preg_replace('/\s/', '', $code));
		if (preg_match('/^[a-z2-7]{4}-?[a-z2-7]{4}$/', $recovery)) {
			if (strpos($recovery, '-') === false) {
				$recovery = substr($recovery, 0, 4) . '-' . substr($recovery, 4);
			}
			$q = new DBQuery;
			$q->addTable('user_twofactor_recovery');
			$q->addUpdate('rc_used_on', date('Y-m-d H:i:s'));
			$q->addWhere('user_id = ' . (int)$user_id . ' AND rc_used_on IS NULL AND rc_hash = '
				. $q->quote(hash('sha256', $recovery)));
			$q->exec();
			return db_affected_rows() == 1 ? 'recovery' : false;
		}
		return false;
	}

	// ---- login

	/** Holds a login whose password was right until its code is given. */
	static function startPending($user_id, $redirect)
	{
		$_SESSION[self::SESSION_KEY] = array(
			'user_id' => (int)$user_id,
			'since' => time(),
			'attempts' => 0,
			'redirect' => (string)$redirect,
			'token' => bin2hex(random_bytes(16)),
		);
	}

	/** The pending login, or null (none, or expired). */
	static function pending()
	{
		$p = $_SESSION[self::SESSION_KEY] ?? null;
		if (!is_array($p) || time() - (int)$p['since'] > self::PENDING_TTL) {
			unset($_SESSION[self::SESSION_KEY]);
			return null;
		}
		return $p;
	}

	static function clearPending()
	{
		unset($_SESSION[self::SESSION_KEY]);
	}

	/** Counts a wrong code; returns false when the pending login is dropped. */
	static function failedAttempt()
	{
		if (!isset($_SESSION[self::SESSION_KEY])) {
			return false;
		}
		if (++$_SESSION[self::SESSION_KEY]['attempts'] >= self::MAX_ATTEMPTS) {
			self::clearPending();
			return false;
		}
		return true;
	}
}
