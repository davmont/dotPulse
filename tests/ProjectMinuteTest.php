<?php
if (!defined('DP_BASE_DIR')) {
    die('DP_BASE_DIR not defined');
}
require_once DP_BASE_DIR . '/modules/timeplanning/model/project_minute.class.php';

/**
 * Time planning minutes: edited as plain text since TinyMCE was removed (3.1.1), stored as
 * HTML like the minutes written with it (modules/timeplanning/model/project_minute.class.php).
 */
class ProjectMinuteTest extends TestCase {
    function testPlainTextRoundTrip() {
        $text = "Kick-off <b>not bold</b> & \"co\"\nSecond line\n\nAfter a blank line";
        $html = ProjectMinute::fromPlainText($text);
        $this->assertEquals(false, strpos($html, '<b>'));
        $this->assertEquals($text, ProjectMinute::plainText($html));
        $this->assertEquals("a\nb", ProjectMinute::plainText(ProjectMinute::fromPlainText("a\r\nb\r\n")));
        $this->assertEquals('', ProjectMinute::fromPlainText("  \n"));
    }

    function testLegacyHtml() {
        $this->assertEquals("Legacy & rich minute\nSecond line",
            ProjectMinute::plainText('<p>Legacy &amp; rich <b>minute</b></p><p>Second&nbsp;line<script>alert(1)</script></p>'));
        $this->assertEquals("one\ntwo", ProjectMinute::plainText('<ul><li>one</li><li>two</li></ul>'));
        // Text without tags is returned as it is.
        $this->assertEquals('a < b and 3>2', ProjectMinute::plainText('a < b and 3>2'));
    }
}
?>
