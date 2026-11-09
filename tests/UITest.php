<?php
if (!defined('DP_BASE_DIR')) {
    die('DP_BASE_DIR not defined');
}
require_once DP_BASE_DIR . '/classes/ui.class.php';

// Mocks for global functions
if (!function_exists('dPgetConfig')) {
    function dPgetConfig($key, $default = null) {
        return $default;
    }
}

if (!function_exists('dPfindImage')) {
    function dPfindImage($name, $module = null) {
        return $name;
    }
}

if (!function_exists('dPshowImage')) {
    function dPshowImage($src, $wid = '', $hgt = '', $alt = '', $title = '') {
        return '<img src="' . $src . '" />';
    }
}

class TestCAppUI extends CAppUI {
    var $redirected = false;
    var $redirect_params = '';

    function __construct() {
        // Bypass parent constructor
    }

    function redirect($params='', $hist='') {
        $this->redirected = true;
        $this->redirect_params = $params;
        // Do not exit
    }

    // Override translation function for tests to prevent relying on global state
    function _($str, $flags = 0) {
        return $str;
    }
}

class UITest extends TestCase {
    function testCheckFileName() {
        global $AppUI;
        // Save the original AppUI global
        $originalAppUI = $AppUI;

        // Set up our mock
        $ui = new TestCAppUI();
        $AppUI = $ui;

        // Test safe file
        $file = 'safe_file';
        $ui->redirected = false;
        $result = $ui->checkFileName($file);
        $this->assertEquals($file, $result);
        $this->assert(!$ui->redirected, "Should not redirect for safe file: $file");

        // Test bad characters
        $bad_chars = array(';', '/', '\\', '\'', '(', ')', '"', '$');
        foreach ($bad_chars as $char) {
            $ui->redirected = false;
            $file = 'bad' . $char . 'file';
            $result = $ui->checkFileName($file);
            $this->assertEquals($file, $result);
            $this->assert($ui->redirected, "Should redirect for char: $char in $file");
            $this->assertEquals('m=public&a=access_denied', $ui->redirect_params);
        }

        // Test dot in filename
        $ui->redirected = false;
        $file = 'test.php';
        $result = $ui->checkFileName($file);
        $this->assertEquals($file, $result);
        $this->assert($ui->redirected, "Should redirect for dot in filename: $file");

        // Test empty filename
        $ui->redirected = false;
        $file = '';
        $result = $ui->checkFileName($file);
        $this->assertEquals($file, $result);
        $this->assert(!$ui->redirected, "Should not redirect for empty file name");

        // Restore original AppUI
        $AppUI = $originalAppUI;
    }

    function testMakeFileNameSafe() {
        $ui = new TestCAppUI();

        // Happy path
        $this->assertEquals('file.txt', $ui->makeFileNameSafe('file.txt'));
        $this->assertEquals('folder/file.txt', $ui->makeFileNameSafe('folder/file.txt'));

        // Single traversal
        $this->assertEquals('file.txt', $ui->makeFileNameSafe('../file.txt'));
        $this->assertEquals('file.txt', $ui->makeFileNameSafe('..\\file.txt'));

        // Multiple traversal
        $this->assertEquals('file.txt', $ui->makeFileNameSafe('../../file.txt'));
        $this->assertEquals('file.txt', $ui->makeFileNameSafe('..\\..\\file.txt'));

        // Mixed traversal
        $this->assertEquals('file.txt', $ui->makeFileNameSafe('../..\\file.txt'));

        // In the middle
        $this->assertEquals('folder/file.txt', $ui->makeFileNameSafe('folder/../file.txt'));

        // Absolute path (not blocked by this function)
        $this->assertEquals('/etc/passwd', $ui->makeFileNameSafe('/etc/passwd'));
        $this->assertEquals('/file.txt', $ui->makeFileNameSafe('/../file.txt'));

        // Edge cases
        $this->assertEquals('', $ui->makeFileNameSafe(''));
        $this->assertEquals('', $ui->makeFileNameSafe('../'));

        // Nested sequences are removed until none is left
        $this->assertEquals('', $ui->makeFileNameSafe('....//'));
        $this->assertEquals('file.txt', $ui->makeFileNameSafe('....//....//file.txt'));
        $this->assertEquals('file.txt', $ui->makeFileNameSafe('....\\\\file.txt'));
    }

    function testSetMsg() {
        $ui = new TestCAppUI();
        $GLOBALS['translate']['Hello World'] = 'Hello World';
        $GLOBALS['translate']['New Message'] = 'New Message';
        $GLOBALS['translate']['Appended'] = 'Appended';
        $GLOBALS['translate']['Warning'] = 'Warning';
        $GLOBALS['translate']['Alert'] = 'Alert';

        // Test basic setMsg
        $ui->setMsg('Hello World');
        $this->assertEquals('Hello World', $ui->msg);
        $this->assertEquals(0, $ui->msgNo);

        // Test overwrite
        $ui->setMsg('New Message', UI_MSG_OK);
        $this->assertEquals('New Message', $ui->msg);
        $this->assertEquals(UI_MSG_OK, $ui->msgNo);

        // Test append
        $ui->setMsg('Appended', UI_MSG_ERROR, true);
        $this->assertEquals('New Message Appended', $ui->msg);
        $this->assertEquals(UI_MSG_ERROR, $ui->msgNo);

        // Test with different message numbers
        $ui->setMsg('Warning', UI_MSG_WARNING);
        $this->assertEquals('Warning', $ui->msg);
        $this->assertEquals(UI_MSG_WARNING, $ui->msgNo);

        $ui->setMsg('Alert', UI_MSG_ALERT);
        $this->assertEquals('Alert', $ui->msg);
        $this->assertEquals(UI_MSG_ALERT, $ui->msgNo);
    }

    function testGetMsg() {
        $ui = new TestCAppUI();
        $GLOBALS['translate']['Success'] = 'Success';
        $GLOBALS['translate']['Error Occurred'] = 'Error Occurred';
        $GLOBALS['translate']['Warning message'] = 'Warning message';
        $GLOBALS['translate']['Alert message'] = 'Alert message';
        $GLOBALS['translate']['Default message'] = 'Default message';

        // Test with UI_MSG_OK
        $ui->setMsg('Success', UI_MSG_OK);
        $msg = $ui->getMsg(false);
        $this->assertRegexp('/Success/', $msg);
        $this->assertRegexp('/class="message"/', $msg);
        $this->assertRegexp('/stock_ok-16.png/', $msg);
        $this->assertEquals('Success', $ui->msg); // Not reset yet

        // Test reset
        $msg = $ui->getMsg(true);
        $this->assertEquals('', $ui->msg);
        $this->assertEquals(0, $ui->msgNo);

        // Test with UI_MSG_ERROR
        $ui->setMsg('Error Occurred', UI_MSG_ERROR);
        $msg = $ui->getMsg(true);
        $this->assertRegexp('/Error Occurred/', $msg);
        $this->assertRegexp('/class="error"/', $msg);
        $this->assertRegexp('/stock_cancel-16.png/', $msg);

        // Test with UI_MSG_WARNING
        $ui->setMsg('Warning message', UI_MSG_WARNING);
        $msg = $ui->getMsg(true);
        $this->assertRegexp('/Warning message/', $msg);
        $this->assertRegexp('/class="warning"/', $msg);
        $this->assertRegexp('/rc-gui-status-downgr.png/', $msg);

        // Test with UI_MSG_ALERT
        $ui->setMsg('Alert message', UI_MSG_ALERT);
        $msg = $ui->getMsg(true);
        $this->assertRegexp('/Alert message/', $msg);
        $this->assertRegexp('/class="message"/', $msg);
        $this->assertRegexp('/rc-gui-status-downgr.png/', $msg);

        // Test with default/unknown message number
        $ui->msg = 'Default message';
        $ui->msgNo = 999;
        $msg = $ui->getMsg(true);
        $this->assertRegexp('/Default message/', $msg);
        $this->assertRegexp('/class="message"/', $msg);
    }
    // $AppUI lives in the session: a version cached on it by an older
    // release must not be shown after an upgrade.
    function testGetVersionIgnoresVersionCachedInTheSession() {
        include DP_BASE_DIR . '/includes/version.php';
        $current = $dp_version_major . '.' . $dp_version_minor
            . (isset($dp_version_patch) ? '.' . $dp_version_patch : '')
            . (isset($dp_version_prepatch) ? '-' . $dp_version_prepatch : '');

        $ui = new TestCAppUI();
        $ui->version_major = 3;
        $ui->version_minor = 1;
        $ui->version_patch = 0;
        $ui->version_string = '3.1.0';
        $stale = unserialize(serialize($ui));
        $this->assertEquals($current, $stale->getVersion());
        $this->assertEquals($current, $stale->version_string);
    }
}

?>
