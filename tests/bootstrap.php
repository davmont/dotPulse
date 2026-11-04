<?php
if (!defined('DP_BASE_DIR')) {
    define('DP_BASE_DIR', realpath(dirname(__FILE__) . '/../'));
}

// Mock global functions
$GLOBALS['mock_sysvals'] = array();

if (!function_exists('dPgetSysVal')) {
    function dPgetSysVal($title) {
        global $mock_sysvals;
        return isset($mock_sysvals[$title]) ? $mock_sysvals[$title] : array();
    }
}

if (!function_exists('dPgetParam')) {
    function dPgetParam(&$arr, $name, $def = null) {
        return isset($arr[$name]) ? $arr[$name] : $def;
    }
}

if (!function_exists('dPgetConfig')) {
    function dPgetConfig($key, $default = null) {
        return $default;
    }
}

if (!function_exists('dPformSafe')) {
    function dPformSafe($txt) {
        if (is_array($txt)) {
            foreach ($txt as $k => $v) {
                $txt[$k] = dPformSafe($v);
            }
            return $txt;
        }
        // Simple mock for testing, real one does more
        return htmlspecialchars($txt);
    }
}

if (!function_exists('arraySelect')) {
    function arraySelect($arr, $name, $attribs, $selected) {
        // Basic mock implementation of arraySelect matching main_functions.php logic
        // keys are values, values are labels
        $out = "\n" . '<select name="' . $name . '" ' . $attribs . '>';
        $did_selected = 0;
        foreach ($arr as $k => $v) {
            $sel = '';
            if ($k == $selected && !$did_selected) {
                $sel = ' selected="selected"';
                $did_selected = 1;
            }
            $out .= "\n\t" . '<option value="' . htmlspecialchars($k) . '"' . $sel . '>' . htmlspecialchars($v) . '</option>';
        }
        $out .= "\n</select>\n";
        return $out;
    }
}

// Mock AppUI
if (!class_exists('CAppUI')) {
    class CAppUI {
        var $user_lang = array('en_US.UTF-8', 'en_US', 'en');
        function setMsg($msg) {}
        function _($txt) { return $txt; }
        function getLibraryClass($class) {
            return DP_BASE_DIR . '/lib/' . $class . '.php';
        }
        function getSystemClass($class) {
            return DP_BASE_DIR . '/classes/' . $class . '.class.php';
        }
        function getModuleClass($module) {
            return DP_BASE_DIR . '/modules/' . $module . '/' . $module . '.class.php';
        }
        function setBaseLocale() {}
    }
}
if (!isset($GLOBALS['AppUI'])) {
    $GLOBALS['AppUI'] = new CAppUI();
}

// Conditionally load real DBQuery or define mock
if (!class_exists('DBQuery')) {
    if (defined('LOAD_REAL_DBQUERY')) {
        require_once DP_BASE_DIR . '/classes/query.class.php';
    } else {
        class DBQuery {
            var $tables = array();
            var $query = array();
            var $where = array();

            static $mockResults = array();
            static $mockExecReturns = true;

            function addTable($table) { $this->tables[] = $table; }
            function addQuery($field) { $this->query[] = $field; }
            function addWhere($where, $params = array()) { $this->where[] = $where; }

            function exec() {
                return self::$mockExecReturns;
            }

            function fetchRow() {
                if (empty(self::$mockResults)) {
                    return false;
                }
                return array_shift(self::$mockResults);
            }

            function clear() {
                $this->tables = array();
                $this->query = array();
                $this->where = array();
            }

            function loadResult() {
                $row = $this->fetchRow();
                $this->clear();
                if ($row === false) {
                    return '';
                }
                return is_array($row) ? reset($row) : $row;
            }

            function quote($str) { return "'" . addslashes($str) . "'"; }
            function prepare() { return ''; }
            function loadHash() { return array(); }
            function loadList() { return array(); }
            function loadColumn() { return array(); }
            function addInsert($field, $value) {}
            function addUpdate($field, $value) {}
        }
    }
}
?>
