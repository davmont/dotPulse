<?php
if (!defined('DP_BASE_DIR')) {
    die('DP_BASE_DIR not defined');
}
require_once DP_BASE_DIR . '/classes/ui.class.php';
require_once DP_BASE_DIR . '/includes/main_functions.php';

// CAppUI without its constructor: only ___() is needed by dPhtml().
class EscapeTestAppUI extends CAppUI {
    function __construct() {
    }
}

/**
 * Output helpers used by the XSS fixes: dPhtml, dPjs, dPjsAttr, dPsafeUrl, dPvalidateOrder.
 */
class EscapeTest extends TestCase {
    function setUp() {
        global $AppUI, $locale_char_set;
        $AppUI = new EscapeTestAppUI();
        $locale_char_set = 'utf-8';
    }

    function testHtmlEscapesQuotesAndTags() {
        $out = dPhtml('a"b\'c<i>&');
        $this->assertEquals(false, strpbrk($out, '"\'<>'));
        $this->assertEquals('x', dPhtml('x'));
    }

    function testDataKeepsItsBackslashes() {
        global $AppUI;
        $this->assertEquals('C:\\temp\\new', dPhtml('C:\\temp\\new'));
        $this->assertEquals('a\\b', dPformSafe('a\\b'));
        $this->assertEquals('a\\b', $AppUI->___('a\\b', UI_OUTPUT_RAW));
        // Translated texts still lose the backslash of "doesn\'t" (written so in locale files).
        $GLOBALS['translate'] = array('Skip' => "doesn\\'t");
        $this->assertEquals("doesn't", $AppUI->_('Skip', UI_OUTPUT_RAW));
        $this->assertEquals('doesn&apos;t', $AppUI->_('Skip'));
        unset($GLOBALS['translate']);
    }

    function testJsLiteralHasNoBreakingCharacters() {
        $out = dPjs("a\"b'c</script>&");
        // Only the delimiting quotes remain.
        $this->assertEquals('"', $out[0]);
        $this->assertEquals('"', substr($out, -1));
        $this->assertEquals(false, strpbrk(substr($out, 1, -1), '"\'<>&'));
        $this->assertEquals('"plain"', dPjs('plain'));
    }

    function testJsAttrHasNoQuotes() {
        $this->assertEquals(false, strpbrk(dPjsAttr('x"y\'z'), '"\'<>'));
    }

    function testSafeUrl() {
        $this->assertEquals('https://example.com/a', dPsafeUrl('https://example.com/a'));
        $this->assertEquals('mailto:a@b.c', dPsafeUrl('mailto:a@b.c'));
        $this->assertEquals('/index.php?m=x', dPsafeUrl('/index.php?m=x'));
        $this->assertEquals('', dPsafeUrl('javascript:alert(1)'));
        $this->assertEquals('', dPsafeUrl('JavaScript:alert(1)'));
        $this->assertEquals('', dPsafeUrl(" java\tscript:alert(1)"));
        $this->assertEquals('', dPsafeUrl('data:text/html,x'));
    }

    function testValidateOrder() {
        $this->assertEquals('name', dPvalidateOrder('name', array('name', 'date'), 'date'));
        $this->assertEquals('date', dPvalidateOrder('name desc, (select 1)', array('name', 'date'), 'date'));
    }
}
?>
