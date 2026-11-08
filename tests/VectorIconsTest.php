<?php
if (!defined('DP_BASE_DIR')) {
    die('DP_BASE_DIR not defined');
}
if (!defined('DP_BASE_URL')) {
    define('DP_BASE_URL', 'https://dp.example');
}
require_once DP_BASE_DIR . '/includes/main_functions.php';

/**
 * The vector icons (tools/icons/build.py): dPvectorIconFilter() and dPfindImage(), which serve
 * the SVG next to a bitmap only in a theme that defines DP_VECTOR_ICONS, and its dark version
 * in a theme that defines DP_DARK_THEME.
 */
class VectorIconsTest extends TestCase {
    // Runs first: DP_VECTOR_ICONS cannot be undefined once the next test defines it.
    function testFindImageKeepsTheBitmapsWithoutVectorIcons() {
        $GLOBALS['uistyle'] = 'default';
        $this->assertEquals(false, dPvectorIcons());
        $this->assertEquals(DP_BASE_URL . '/modules/projects/images/applet3-48.png', dPfindImage('applet3-48.png', 'projects'));
        $this->assertEquals('./images/prev.gif', dPfindImage('prev.gif'));
    }

    function testFindImagePrefersTheSvgWithVectorIcons() {
        define('DP_VECTOR_ICONS', true);
        $GLOBALS['uistyle'] = 'dotpulse';
        $this->assertEquals(DP_BASE_URL . '/modules/projects/images/applet3-48.svg', dPfindImage('applet3-48.png', 'projects'));
        // A module's own icon, where it used a shared bitmap before.
        $this->assertEquals(DP_BASE_URL . '/modules/departments/images/users.svg', dPfindImage('users.gif', 'departments'));
        $this->assertEquals('./images/prev.svg', dPfindImage('prev.gif'));
        $this->assertEquals(DP_BASE_URL . '/images/icons/shim.gif', dPfindImage('shim.gif'));
    }

    function testFilterSwapsBitmapsThatHaveAnSvg() {
        $this->assertEquals('<img src="./images/icons/stock_edit-16.svg" width="16" />',
            dPvectorIconFilter('<img src="./images/icons/stock_edit-16.png" width="16" />'));
        $this->assertEquals("<img width='24' src='./images/calendar.svg'>",
            dPvectorIconFilter("<img width='24' src='./images/calendar.gif'>"));
        $this->assertEquals('<img src="' . DP_BASE_URL . '/modules/tasks/images/applet-48.svg">',
            dPvectorIconFilter('<img src="' . DP_BASE_URL . '/modules/tasks/images/applet-48.png">'));
        $this->assertEquals('<input type="image" src="images/obj/lock.svg">',
            dPvectorIconFilter('<input type="image" src="images/obj/lock.gif">'));
    }

    function testFilterLeavesTheRestAlone() {
        $same = array(
            '<img src="./images/shim.gif">',                       // no SVG
            '<img src="https://other.example/images/prev.gif">',   // another site
            '<img src="./images/../../prev.gif">',                 // outside the installation
            '<img src="./files/1/prev.gif">',                      // not images/, modules/ or style/
            '<img data-src="./images/prev.gif">',                  // not a src attribute
            '<img src="./images/prev.gif?v=2">',
            '<p>images/prev.gif</p>',
        );
        foreach ($same as $html) {
            $this->assertEquals($html, dPvectorIconFilter($html));
        }
    }

    // Runs last: DP_DARK_THEME stays defined for the rest of the file.
    function testDarkThemeServesTheDarkVersions() {
        define('DP_DARK_THEME', true);
        $this->assertEquals('<img src="./images/icons/stock_edit-16.dark.svg">',
            dPvectorIconFilter('<img src="./images/icons/stock_edit-16.png">'));
        $this->assertEquals('./images/prev.dark.svg', dPfindImage('prev.gif'));
        // The module tiles carry their own background: one file for both themes.
        $this->assertEquals(DP_BASE_URL . '/modules/projects/images/applet3-48.svg', dPfindImage('applet3-48.png', 'projects'));
    }
}
?>
