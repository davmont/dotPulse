<?php
require_once DP_BASE_DIR . '/includes/filter.php';

class FilterTest extends TestCase {
    function testCheckPlain() {
        // Happy path: no special characters
        $this->assertEquals('Hello World', check_plain('Hello World'));

        // HTML special characters
        $this->assertEquals('&lt;script&gt;', check_plain('<script>'));
        $this->assertEquals('Go to &amp; from', check_plain('Go to & from'));
        $this->assertEquals('He said &quot;Hello&quot;', check_plain('He said "Hello"'));
        $this->assertEquals('It&#039;s a test', check_plain("It's a test"));
        $this->assertEquals('&lt;a href=&quot;http://example.com&quot;&gt;Link&lt;/a&gt;', check_plain('<a href="http://example.com">Link</a>'));

        // UTF-8 characters
        $this->assertEquals('Héllo', check_plain('Héllo'));
        $this->assertEquals('こんにちは', check_plain('こんにちは'));

        // Empty string
        $this->assertEquals('', check_plain(''));

        // Multiline string
        $input = "Line 1\nLine 2 <br>";
        $expected = "Line 1\nLine 2 &lt;br&gt;";
        $this->assertEquals($expected, check_plain($input));

        // Invalid UTF-8 sequence should return empty string (as per implementation or PHP behavior)
        // \x80 is an invalid start byte in UTF-8
        $this->assertEquals('', check_plain("\x80"));
    }
}
?>
