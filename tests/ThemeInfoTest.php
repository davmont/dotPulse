<?php
require_once DP_BASE_DIR . '/includes/main_functions.php';

/**
 * dPthemeInfo(): what each theme declares in style/<theme>/theme.json.
 */
class ThemeInfoTest extends TestCase {
    function testEveryThemeHasItsFile() {
        foreach (glob(DP_BASE_DIR . '/style/*', GLOB_ONLYDIR) as $dir) {
            $name = basename($dir);
            if ($name[0] === '_') {
                continue;
            }
            $json = json_decode((string)@file_get_contents($dir . '/theme.json'), true);
            $this->assert(is_array($json), "style/$name/theme.json");
        }
    }

    function testCurrentDarkAndLegacyThemes() {
        $info = dPthemeInfo('dotpulse');
        $this->assertEquals(false, $info['legacy']);
        $this->assertEquals(true, $info['vector_icons']);
        $this->assertEquals(false, $info['dark']);
        $info = dPthemeInfo('dotpulse-dark');
        $this->assertEquals(true, $info['dark']);
        $this->assertEquals('dotpulse', $info['extends']);
        $this->assertEquals('#1A202B', $info['dark_surface']);
        // Material (2026) is current, with its own colours until it is rebuilt on the tokens.
        $info = dPthemeInfo('material');
        $this->assertEquals(false, $info['legacy']);
        $this->assertEquals(false, $info['vector_icons']);
        foreach (array('default', 'amp', 'dp-grey-theme', 'wps-redmond') as $legacy) {
            $info = dPthemeInfo($legacy);
            $this->assertEquals(true, $info['legacy'], $legacy);
            $this->assertEquals(false, $info['vector_icons'], $legacy);
        }
    }

    function testUnknownThemeGetsTheDefaults() {
        $info = dPthemeInfo('no-such-theme');
        $this->assertEquals(false, $info['legacy']);
        $this->assertEquals(false, $info['dark']);
        $info = dPthemeInfo('../../etc');
        $this->assertEquals(false, $info['dark']);
    }
}
