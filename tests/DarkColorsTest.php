<?php
if (!defined('DP_BASE_DIR')) {
    die('DP_BASE_DIR not defined');
}
require_once DP_BASE_DIR . '/includes/dark_colors.php';

/**
 * includes/dark_colors.php: the colours a dark theme (style/dotpulse-dark) adapts in the markup.
 */
class DarkColorsTest extends TestCase {
    private function contrastOnSurface($hex) {
        return dPdarkContrast(dPdarkParseColor($hex), dPdarkParseColor(DP_DARK_SURFACE));
    }

    function testLightBackgroundsBecomeDarkInTheSameOrder() {
        $this->assertEquals(DP_DARK_SURFACE, dPdarkColor('#ffffff', 'bg'));
        $this->assertEquals(DP_DARK_SURFACE, dPdarkColor('white', 'bg'));
        // A lighter background stays the darker one: the shades of a table keep apart.
        $white = dPdarkLuminance(dPdarkParseColor(dPdarkColor('#ffffff', 'bg')));
        $grey = dPdarkLuminance(dPdarkParseColor(dPdarkColor('#dddddd', 'bg')));
        $this->assert($white < $grey);
        // A pastel keeps its hue: a yellow stays warm (red above blue).
        $yellow = dPdarkParseColor(dPdarkColor('#ffeebb', 'bg'));
        $this->assert($yellow[0] > $yellow[2]);
    }

    function testMidAndSaturatedBackgroundsStay() {
        $this->assertEquals(null, dPdarkColor('#3366CC', 'bg'));
        $this->assertEquals(null, dPdarkColor('#1B4F9C', 'bg'));
    }

    function testDarkTextBecomesReadable() {
        foreach (array('#000000', '#333', '#666666', '#aa0000', '#000066', '#036', 'black', '#1B4F9C') as $c) {
            $new = dPdarkColor($c, 'fg');
            $this->assert($new !== null && $this->contrastOnSurface($new) >= 4.5, "$c -> $new");
        }
        // Light enough already: unchanged.
        $this->assertEquals(null, dPdarkColor('#ffffff', 'fg'));
        $this->assertEquals(null, dPdarkColor('#7BA9F7', 'fg'));
    }

    function testFilterChangesBackgroundAndTextTogether() {
        $html = dPdarkColorFilter('<td bgcolor="#ffffff"><font color="#000000">x</font></td>');
        $this->assertEquals('<td bgcolor="' . DP_DARK_SURFACE . '"><font color="#E7E9EE">x</font></td>', $html);
        // A project colour keeps its background and the text colour chosen for it.
        $same = '<td style="background-color:#3366CC;color:#ffffff">p</td>';
        $this->assertEquals($same, dPdarkColorFilter($same));
        $kept = '<td style="background-color:#3366cc;color:#000000">p</td>';
        $this->assertEquals($kept, dPdarkColorFilter($kept));
        // A light cell becomes dark and its black text light.
        $this->assertEquals('<td style="background-color:#1F2633;color:#E7E9EE">q</td>',
            dPdarkColorFilter('<td style="background-color:#eeeeee;color:#000000">q</td>'));
    }

    function testFilterReachesStyleBlocksAndBorders() {
        $this->assertEquals('<style>.a{background:' . DP_DARK_SURFACE . ';color:#C9CED9}</style>',
            dPdarkColorFilter('<style>.a{background:#fff;color:#333}</style>'));
        $this->assertEquals('<div style="border:1px solid #394356">r</div>',
            dPdarkColorFilter('<div style="border:1px solid #ccc">r</div>'));
    }

    function testFilterLeavesTheRestAlone() {
        $same = array(
            '<a href="?color=white&bgcolor=ffffff">link</a>',
            '<p>color: #000000 in the text</p>',
            '<img src="images/white.png" alt="black">',
            '<div style="background-image:url(white.png)">x</div>',
            '<td bgcolor="#3366CC">x</td>',
        );
        foreach ($same as $html) {
            $this->assertEquals($html, dPdarkColorFilter($html));
        }
    }

    function testOnlyPagesAreFiltered() {
        $this->assert(dPdarkIsHtmlResponse(array('Content-type: text/html;charset=utf-8', 'X-Content-Type-Options: nosniff')));
        $this->assert(dPdarkIsHtmlResponse(array('Content-Type:text/html')));
        $this->assert(dPdarkIsHtmlResponse(array()));
        $this->assert(!dPdarkIsHtmlResponse(array('Content-Type: application/pdf')));
        $this->assert(!dPdarkIsHtmlResponse(array('content-type: text/csv; charset=utf-8')));
        $this->assert(!dPdarkIsHtmlResponse(array('Content-Type: application/json')));
    }
    // A kept background without a text colour of its own: dark text where the theme's
    // light text would not read (#17BECF), nothing where it reads (#1F77B4) or where the
    // tag sets its own colour.
    function testKeptLightishBackgroundsGetDarkInk() {
        $this->assertEquals('<td style="background-color:#17BECF" data-dp-ink="dark">',
            dPdarkColorFilter('<td style="background-color:#17BECF">'));
        $this->assertEquals('<td bgcolor="#FF7F0E" data-dp-ink="dark"/>',
            dPdarkColorFilter('<td bgcolor="#FF7F0E" />'));
        $this->assertEquals('<td style="background-color:#1F77B4">', dPdarkColorFilter('<td style="background-color:#1F77B4">'));
        $this->assertEquals('<td style="background-color:#17BECF; color:#000000">',
            dPdarkColorFilter('<td style="background-color:#17BECF; color:#000000">'));
    }

    // Runs last: DP_DARK_THEME stays defined for the rest of the file.
    function testBestColorInTheDarkTheme() {
        require_once DP_BASE_DIR . '/includes/main_functions.php';
        require_once DP_BASE_DIR . '/classes/csscolor.class.php';
        $this->assertEquals('#000000', bestColor('17BECF'));
        define('DP_DARK_THEME', true);
        // Kept background: dark text, in the rgb() spelling the filter leaves alone.
        $this->assertEquals('rgb(0, 0, 0)', bestColor('17BECF'));
        $html = '<span style="color:' . bestColor('17BECF') . '">';
        $this->assertEquals($html, dPdarkColorFilter($html));
        // Kept dark background: white text, as before.
        $this->assertEquals('#ffffff', strtolower(bestColor('1F77B4')));
        // A pale background the filter darkens: the theme's light text.
        $this->assertEquals(DP_DARK_INK, bestColor('EEEEEE'));
        $this->assertEquals(DP_DARK_INK, bestColor(''));
    }
}

?>
