<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

/**
* vCard export and import of contacts (replaced PEAR Contact_Vcard_Build and
* Contact_Vcard_Parse in dotPulse 3.1.1).
*/
class CVcard {
	/** Escapes a text value (RFC 2426 section 5.8.4). */
	static function escape($text)
	{
		return str_replace(array('\\', ';', ',', "\r\n", "\n", "\r"),
		                   array('\\\\', '\;', '\,', '\n', '\n', '\n'), (string)$text);
	}

	/**
	* A vCard 3.0 of a contact row (joined with its company's company_name). The
	* telephone and e-mail lines are always written, empty or not, so that
	* import() maps them back to the same fields.
	*/
	static function fromContact(array $c, $source)
	{
		$e = array('CVcard', 'escape');
		$lines = array('BEGIN:VCARD', 'VERSION:3.0',
			'FN:' . self::escape($c['contact_first_name'] . ' ' . $c['contact_last_name']),
			'N:' . implode(';', array_map($e, array($c['contact_last_name'], $c['contact_first_name'],
			                                         '', $c['contact_title'], ''))),
			'PROFILE:VCARD',
			'SOURCE:' . self::escape($source),
			'BDAY:' . self::escape($c['contact_birthday']),
			'ADR:' . implode(';', array_map($e, array('', $c['contact_address2'], $c['contact_address1'],
			              $c['contact_city'], $c['contact_state'], $c['contact_zip'], $c['contact_country']))),
			'TEL;TYPE=PF:' . self::escape($c['contact_phone']),
			'TEL:' . self::escape($c['contact_phone2']),
			'TEL;TYPE=car:' . self::escape($c['contact_mobile']),
			'EMAIL;TYPE=PF:' . self::escape($c['contact_email']),
			'EMAIL:' . self::escape($c['contact_email2']));
		if ((string)$c['contact_type'] !== '') {
			$lines[] = 'CATEGORIES:' . self::escape($c['contact_type']);
		}
		if ((string)$c['company_name'] !== '') {
			$lines[] = 'ORG:' . self::escape($c['company_name']);
		}
		$lines[] = 'NOTE:' . self::escape(str_replace("\r", ' ', (string)$c['contact_notes']));
		$lines[] = 'UID:' . self::escape($c['contact_company']);
		$lines[] = 'END:VCARD';
		return implode("\r\n", $lines) . "\r\n";
	}

	/**
	* The cards of a vCard file (2.1 or 3.0), each as
	* $card['N'][0]['value'][component][repetition] (the PEAR parser's layout), with
	* $card['N'][0]['param'] the parameters. Values are unescaped and UTF-8.
	*/
	static function parse($text)
	{
		// Logical lines: RFC 2425 folding (a line starting with a space or tab
		// continues the previous one) and vCard 2.1 quoted-printable soft breaks
		// (a quoted-printable value ending in "=" continues on the next line).
		$lines = array();
		foreach (explode("\n", str_replace(array("\r\n", "\r"), "\n", (string)$text)) as $raw) {
			$last = count($lines) - 1;
			if ($last >= 0 && ($raw !== '' && ($raw[0] == ' ' || $raw[0] == "\t"))) {
				$lines[$last] .= substr($raw, 1);
			} else if ($last >= 0 && substr($lines[$last], -1) == '='
			           && stripos(strstr($lines[$last], ':', true), 'QUOTED-PRINTABLE') !== false) {
				$lines[$last] .= "\n" . $raw;
			} else {
				$lines[] = $raw;
			}
		}
		$cards = array();
		$card = null;
		foreach ($lines as $line) {
			if (trim($line) === '' || strpos($line, ':') === false) {
				continue;
			}
			list($head, $value) = explode(':', $line, 2);
			$params = explode(';', $head);
			$name = strtoupper(preg_replace('/^.*\./', '', array_shift($params)));
			if ($name == 'BEGIN') {
				$card = array();
				continue;
			}
			if ($name == 'END') {
				if (is_array($card)) {
					$cards[] = $card;
				}
				$card = null;
				continue;
			}
			if (!is_array($card)) {
				continue;
			}
			$param = array();
			foreach ($params as $p) {
				$pair = explode('=', $p, 2);
				$key = strtoupper(count($pair) == 2 ? $pair[0] : (in_array(strtoupper($pair[0]), array('QUOTED-PRINTABLE', 'BASE64', '8BIT')) ? 'ENCODING' : 'TYPE'));
				$param[$key][] = count($pair) == 2 ? $pair[1] : $pair[0];
			}
			if (isset($param['ENCODING']) && strtoupper($param['ENCODING'][0]) == 'QUOTED-PRINTABLE') {
				$value = quoted_printable_decode(str_replace("=\n", '', $value));
			}
			if (isset($param['CHARSET']) && strtoupper($param['CHARSET'][0]) != 'UTF-8') {
				$value = mb_convert_encoding($value, 'UTF-8', $param['CHARSET'][0]);
			}
			$components = array();
			foreach (self::split($value, ';') as $component) {
				$components[] = array_map(array('CVcard', 'unescape'), self::split($component, ','));
			}
			$card[$name][] = array('param' => $param, 'value' => $components);
		}
		return $cards;
	}

	/** Splits on a separator that is not escaped with a backslash. */
	private static function split($text, $separator)
	{
		$parts = array('');
		$length = strlen($text);
		for ($i = 0; $i < $length; $i++) {
			if ($text[$i] == '\\' && $i + 1 < $length) {
				$parts[count($parts) - 1] .= $text[$i] . $text[++$i];
			} else if ($text[$i] == $separator) {
				$parts[] = '';
			} else {
				$parts[count($parts) - 1] .= $text[$i];
			}
		}
		return $parts;
	}

	private static function unescape($text)
	{
		return preg_replace_callback('/\\\\(.)/s', function ($m) {
			return ($m[1] == 'n' || $m[1] == 'N') ? "\n" : $m[1];
		}, $text);
	}
}
?>
