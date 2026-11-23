<?php
require_once DP_BASE_DIR . '/includes/main_functions.php';

/**
 * dPsanitiseHTML() and dPgetCleanParam(): htmLawed's safe mode plus the elements dotPulse
 * refuses. Plain text must come through unchanged; markup that runs code or changes the
 * page must not.
 */
class SanitiseTest extends TestCase {
    function testPlainTextAndFormattingStay() {
        $this->assertEquals('Plain text', dPsanitiseHTML('Plain text'));
        $this->assertEquals("Two\nlines", dPsanitiseHTML("Two\nlines"));
        $this->assertEquals('Ñandú café 50% €', dPsanitiseHTML('Ñandú café 50% €'));
        $this->assertEquals('Tom &amp; Jerry', dPsanitiseHTML('Tom & Jerry'));
        $this->assertEquals('a &lt; b &gt; c', dPsanitiseHTML('a < b > c'));
        $this->assertEquals('<b>bold</b> <i>it</i> <u>u</u>', dPsanitiseHTML('<b>bold</b> <i>it</i> <u>u</u>'));
        $this->assertEquals('<a href="http://x.com" title="t">l</a>', dPsanitiseHTML('<a href="http://x.com" title="t">l</a>'));
        $this->assertEquals('<b>unclosed</b>', dPsanitiseHTML('<b>unclosed'));
    }

    function testScriptsAndEventsAreRemoved() {
        $this->assertEquals('alert(1)', dPsanitiseHTML('<script>alert(1)</script>'));
        $this->assertEquals('<img src="x" alt="image" />', dPsanitiseHTML('<img src=x onerror=alert(1)>'));
        $this->assertEquals('', dPsanitiseHTML('<iframe src="//evil"></iframe>'));
        $this->assertEquals('<x-foo>c</x-foo>', dPsanitiseHTML('<x-foo onclick="a">c</x-foo>'));
        foreach (array('javascript:alert(1)', 'java&#x09;script:alert(1)', '&#106;avascript:alert(1)', 'data:text/html,x', 'vbscript:x') as $url) {
            $this->assertRegexp('/href="denied:/', dPsanitiseHTML('<a href="' . $url . '">x</a>'), $url);
        }
    }

    function testElementsThatChangeThePageAreRemoved() {
        $this->assertEquals('', dPsanitiseHTML('<meta http-equiv="refresh" content="0;url=http://evil">'));
        $this->assertEquals('', dPsanitiseHTML('<link rel="stylesheet" href="//evil/x.css">'));
        $this->assertEquals('', dPsanitiseHTML('<base href="//evil/">'));
        $this->assertEquals('*{display:none}', dPsanitiseHTML('<style>*{display:none}</style>'));
        $this->assertEquals('<img src="x" alt="image" />', dPsanitiseHTML('<template><img src=x onerror=a></template>'));
    }

    function testCleanParamCleansEachItem() {
        $in = array('t' => '<b>x</b><script>y</script>', 'list' => array('<i>a</i>', '<style>b</style>'), 'empty' => '');
        $this->assertEquals('<b>x</b>y', dPgetCleanParam($in, 't'));
        $this->assertEquals(array('<i>a</i>', 'b'), dPgetCleanParam($in, 'list'));
        $this->assertEquals('', dPgetCleanParam($in, 'empty'));
        $this->assertEquals('def', dPgetCleanParam($in, 'missing', 'def'));
    }
}
