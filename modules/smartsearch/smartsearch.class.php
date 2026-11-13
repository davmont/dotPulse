<?php /* SMARTSEARCH$Id: smartsearch.class.php 6191 2013-01-05 04:28:23Z ajdonnison $ */
if (!defined('DP_BASE_DIR')) {
  die('You should not access this file directly.');
}

require_once $AppUI->getSystemClass('dp');

class smartsearch  {

	var $table = null;
	var $table_alias = null;
	var $table_module = null;
	var $table_key = null;	// primary key in searched table
	var $table_key2 = null; // primary key in parent table
	var $table_link = null;	// first part of link
	var $table_link2 = null; // second part of link
	var $table_title = null;
	var $table_orderby = null;
	var $table_extra = null;
	var $search_fields = array ();
	var $display_fields = array ();
	var $table_joins = array ();
	var $keyword = null;
	var $keywords = null;
	var $tmppattern = "";
	var $display_val = "";
	var $search_options = null;
//	$search_options['keywords']==array() 		array of searched keywords
//	$search_options['display_all_flds']=="on"	display all fields
//	$search_options['display_all_flds']==""		display only first 2 fields in display_fields array
//	$search_options['ignore_specchar']=="on"	ignore "special" characters
//	$search_options['ignore_specchar']==""		don't ignore "special" characters /default/
//	$search_options['ignore_case']==""			match case
//	$search_options['ignore_case']=="on"		ignore case /default/
//	$search_options['show_empty']==""			hide modules with empty results /default/
//	$search_options['show_empty']=="on"			show modules with empty results
//	$search_options['all_words']==""			match any of the words /default/
//	$search_options['all_words']=="on"			match all words

	function __construct() {
		return null;
	}
	
	function createlink($records) {
		$tmplink = "";
		if (isset($this->table_link) && isset($this->table_key)) {
			$tmplink = ($this->table_link 
			            . $records[preg_replace('/^.*\.([^\.]+)$/', '$1', $this->table_key)]);
		}
		
		if (isset($this->table_link2) && isset($this->table_key2)) {
			$tmplink = ($this->table_link . $records[preg_replace('/^.*\.([^\.]+)$/', '$1', $this->table_key)] . $this->table_link2 
			            . $records[preg_replace('/^.*\.([^\.]+)$/', '$1', $this->table_key2)]);
		}
		return $tmplink;
	}
	function fetchResults(&$record_count) {
		global $AppUI;
		
		$outstring = '';
		$module_count = 0;
		$outresults = '';
		
		if (!getPermission($this->table_module, 'access')) {
			return $outstring;
		}
		$results = $this->_searchResults();
		if ($results) {
			$shown = array();
			foreach ($results as $records) {
				// The column comes back without its table alias (projects: p.project_id); a missing
				// id would check only the module, not the record.
				$key = preg_replace('/^.*\.([^\.]+)$/', '$1', $this->table_key);
				// A record joined to several matching rows (a project's contacts) is listed once.
				$row_id = $records[$key] ?? null;
				if (isset($this->table_key2)) { // task logs: the task, then the log
					$row_id .= '/' . ($records[preg_replace('/^.*\.([^\.]+)$/', '$1', $this->table_key2)] ?? '');
				}
				if (empty($records[$key]) || isset($shown[$row_id])) {
					continue;
				}
				$shown[$row_id] = true;
				if (getPermission($this->table_module, 'view', $records[$key])) {
					$module_count++;
					$record_count++;
					$ii = 0;
					$display_val = '';
					foreach ($this->display_fields as $fld) {
						$ii++;
						if (!($this->search_options['display_all_flds'] == 'on') && ($ii > 2)) {
							break;
						}
						$display_val .= ((($display_val) ? ' ' : '') 
						                 . $records[preg_replace('/^.*\.([^\.]+)$/', '$1', $fld)]);
					}
					
					$tmplink = $this->createlink($records); 
					$outresults .= ('<tr><td>'."\n" . '<a href="' . $tmplink . '">' 
					               . highlight(dPhtml($display_val), $this->keywords) . '</a>' . "\n" 
					               . '</td></tr>' . "\n");
			    }
			}
		}
		//Search Results XHTML
		// $module_count is this group's own count; $record_count is the running total.
		if ($module_count > 0) {
			$outstring = ('<tr><th><b>' . $AppUI->_($this->table_title) 
			              . ' (' . $module_count . ')' . '</b></th></tr>' . "\n" . $outresults);
		} else if ($this->search_options['show_empty'] == 'on') {
			$outstring = ('<tr><th><b>' . $AppUI->_($this->table_title) 
			              . ' (0)' . '</b></th></tr>' . "\n" 
			              .'<tr><td>' . $AppUI->_('Empty') . '</td></tr>' . "\n");
		}
		
		return $outstring;
	}
	
	function setKeyword($keyw) {
		$this->keyword = $keyw;
	}
	function setAdvanced($search_opts) {
		$this->search_options = $search_opts;
		$this->keywords = $search_opts['keywords'];
	}
	
	function _searchResults() {
		global $AppUI, $locale_char_set;
		
		$q = new DBQuery;
		$dPObj = new CDpObject($this->table, $this->table_key);
		
		$q->addTable($this->table, $this->table_alias);
		foreach ($this->table_joins as $join) {
			$q->addJoin($join['table'],$join['alias'],$join['join']);
		}
		
		$q->addQuery($this->table_key);
		if (isset($this->table_key2)) {
			$q->addQuery($this->table_key2);
		}
		foreach ($this->display_fields as $fld) {
			$q->addQuery($fld);
		}
		
		$q->addOrder($this->table_orderby);
		$dPObj->setAllowedSQL($AppUI->user_id, $q, null, null, $this->table_module);
		if ($this->table_extra) {
			$q->addWhere($this->table_extra);
		}
        
		$keys = '';
		$keys2 = '';
		foreach (array_keys($this->keywords) as $keyword) {
			if ($keys2) {
				$keys .= (($this->search_options['all_words'] == 'on') ? ' AND ' : ' OR ');
				$keys2 = '';
			}
			foreach ($this->search_fields as $field) {
				// OR treatment to each keyword
				// Search for semi-colons, commas or spaces and allow any to be separators
				$or_keywords = preg_split('/[\s,;]+/', $keyword);
				foreach ($or_keywords as $or_keyword) {
					// The keywords arrive addslashes()ed (index.php); match them as literal
					// text: escape the regex metacharacters, then quote for SQL.
					$or_keyword = stripslashes($or_keyword);
					$ignore_case = ($this->search_options['ignore_case'] == 'on');
					$search_pattern = (($this->search_options['ignore_specchar'] == 'on') 
					                   ? recode2regexp_utf8($or_keyword, $ignore_case) 
									   : ('(' . smartsearch_regexp_quote($or_keyword) . ')'));
					$keys2 .= (($keys2) ? ' OR ' : '');
					$keys2 .= ('(' . $field . ' REGEXP ' 
					           . (($ignore_case) ? '' : ' BINARY ') 
					           . "'" . db_escape('.*' . $search_pattern . '.*') . "')");
				}
			}
			$keys .= (($keys2) ? ('(' . $keys2 . ')') : '');
		}
		
		if ($keys) {
			$q->addWhere($keys);
		}
		
		$results = $q->loadList();
		return ($results);
	}
}

function highlight($text, $keyval) {
	global $ssearch;
	
	$txt = $text;
	$hicolor = array('#FFFF66','#ADD8E6','#90EE8A','#FF99FF');
	$keys=array();
	$keys = ((!(is_array($keyval))) ? array($keyval) : $keyval);
	
	foreach ($keys as $key) {
		if (mb_strlen($key[0])>0) {
			 $key[0] = stripslashes($key[0]);
			if (isset($ssearch['ignore_specchar']) && $ssearch['ignore_specchar'] == 'on') {
				$case = (($ssearch['ignore_case']=='on') ? '/i' : '/');
				$txt = preg_replace('/'.recode2regexp_utf8(dPhtml($key[0]), ($ssearch['ignore_case']=='on')).$case, 
								 ('<span style="background:' . $hicolor[$key[1]] .'" >\0</span>'), 
								 $txt);
			} else if (!(isset($ssearch['ignore_specchar'])) || $ssearch['ignore_specchar'] == '') {
				$case = (($ssearch['ignore_case']=='on') ? '/i' : '/');
				// The text is HTML-escaped: match the escaped keyword, as a literal.
				$txt = preg_replace('/'.preg_quote(dPhtml($key[0]), '/').$case, 
								 ('<span style="background:' . $hicolor[$key[1]] .'" >\0</span>'), 
								 $txt);
			} else {
				$txt = preg_replace('/'.sql_regcase($key[0]).'/i', 
								 ('<span style="background:' . $hicolor[$key[1]] .'" >\0</span>'), 
								 $txt);
			}
		}
	}
	return $txt; 
}

/**
 * Escapes the regular expression metacharacters in $text so that it matches
 * literally, both in a MySQL/MariaDB REGEXP and in a '/'-delimited preg pattern.
 */
function smartsearch_regexp_quote($text) {
	return preg_replace('/[\\\\.^$|()\\[\\]{}*+?\\/-]/', '\\\\$0', $text);
}

/**
 * Builds a regular expression that matches $input ignoring accents ("special
 * characters"): every letter that has accented forms becomes a group of them, so
 * "cafe" matches "café" and "café" matches "cafe". Everything else is matched
 * literally. With $ignore_case the groups hold both cases; without it, only the
 * case the user typed.
 */
function recode2regexp_utf8($input, $ignore_case = true) {
	static $variants = array(
		'a' => 'áàâäãåāăą',
		'c' => 'çćčĉċ',
		'd' => 'ďđ',
		'e' => 'éèêëěēęėĕ',
		'g' => 'ğĝġģ',
		'h' => 'ĥħ',
		'i' => 'íìîïīįĩĭı',
		'j' => 'ĵ',
		'k' => 'ķ',
		'l' => 'ľĺłļŀ',
		'n' => 'ñńňņ',
		'o' => 'óòôöõøōőŏ',
		'r' => 'řŕŗ',
		's' => 'šśşșŝ',
		't' => 'ťţțŧ',
		'u' => 'úùûüůūűųũŭ',
		'w' => 'ŵ',
		'y' => 'ýÿŷ',
		'z' => 'žźż',
	);
	static $base = null;
	if ($base === null) {
		// Every letter, accented or not, in either case => its plain lower-case letter.
		$base = array();
		foreach ($variants as $plain => $accented) {
			foreach (array_merge(array($plain), mb_str_split($accented, 1, 'UTF-8')) as $letter) {
				$base[$letter] = $plain;
				$base[mb_strtoupper($letter, 'UTF-8')] = $plain;
			}
		}
	}
	
	$result = '';
	foreach (mb_str_split((string)$input, 1, 'UTF-8') as $char) {
		if (!isset($base[$char])) {
			$result .= smartsearch_regexp_quote($char);
			continue;
		}
		$plain = $base[$char];
		$lower = array_merge(array($plain), mb_str_split($variants[$plain], 1, 'UTF-8'));
		$upper = array();
		foreach ($lower as $letter) {
			$upper[] = mb_strtoupper($letter, 'UTF-8');
		}
		if ($ignore_case) {
			$group = array_merge($lower, $upper);
		} else {
			$group = (($char === mb_strtolower($char, 'UTF-8')) ? $lower : $upper);
		}
		// Alternation rather than a [...] class: with BINARY (match case) the
		// comparison is byte-wise, and a class would split the multi-byte letters.
		$result .= '(' . implode('|', array_unique($group)) . ')';
	}
	return $result;
}	
?>