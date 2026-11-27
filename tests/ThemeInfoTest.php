<?php
require_once DP_BASE_DIR . '/classes/ui.class.php';
require_once DP_BASE_DIR . '/includes/main_functions.php';

// CAppUI without its constructor: preferences and ___() for dPhtml().
class ThemeInfoTestAppUI extends CAppUI {
    function __construct() {
    }
}

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
        $this->assertEquals(true, $info['dark_palette']);
        $this->assertEquals('#1A202B', $info['dark_surface']);
        // The old name of dotPulse with the Dark appearance, kept for one release.
        $info = dPthemeInfo('dotpulse-dark');
        $this->assertEquals(true, $info['dark']);
        $this->assertEquals('dotpulse', $info['alias_of']);
        $this->assertEquals(false, $info['dark_palette']);
        // Facet: current, vector icons, its own dark palette and dark colours for the filter.
        $info = dPthemeInfo('facet');
        $this->assertEquals(false, $info['legacy']);
        $this->assertEquals(true, $info['vector_icons']);
        $this->assertEquals(true, $info['dark_palette']);
        $this->assertEquals('#2B2B2B', $info['dark_surface']);
        // Linen: the same, with its own colours.
        $info = dPthemeInfo('linen');
        $this->assertEquals(false, $info['legacy']);
        $this->assertEquals(true, $info['dark_palette']);
        $this->assertEquals('#2C2C2E', $info['dark_surface']);
        // Material: rebuilt on the tokens, light and dark.
        $info = dPthemeInfo('material');
        $this->assertEquals(false, $info['legacy']);
        $this->assertEquals(true, $info['vector_icons']);
        $this->assertEquals(true, $info['dark_palette']);
        $this->assertEquals('#211F26', $info['dark_surface']);
        foreach (array('default', 'amp', 'dp-grey-theme', 'wps-redmond') as $legacy) {
            $info = dPthemeInfo($legacy);
            $this->assertEquals(true, $info['legacy'], $legacy);
            $this->assertEquals(false, $info['vector_icons'], $legacy);
        }
    }

    function testAppearanceFollowsThePreference() {
        global $AppUI;
        $saved = $AppUI;
        $AppUI = new ThemeInfoTestAppUI();
        unset($_COOKIE['dp_scheme']);
        // No preference, or one not known: light.
        $this->assertEquals('light', dPappearance('dotpulse'));
        $AppUI->setPref('UIAPPEARANCE', 'purple');
        $this->assertEquals('light', dPappearancePref());
        $AppUI->setPref('UIAPPEARANCE', 'dark');
        $this->assertEquals('dark', dPappearance('dotpulse'));
        // A theme without a dark palette stays light; the old dark theme stays dark.
        $this->assertEquals('light', dPappearance('wps-redmond'));
        $AppUI->setPref('UIAPPEARANCE', 'light');
        $this->assertEquals('dark', dPappearance('dotpulse-dark'));
        // Automatic: the computer's setting, from the cookie; light until it is known.
        $AppUI->setPref('UIAPPEARANCE', 'auto');
        $this->assertEquals('light', dPappearance('dotpulse'));
        $_COOKIE['dp_scheme'] = 'dark';
        $this->assertEquals('dark', dPappearance('dotpulse'));
        $this->assertEquals(' data-theme="dark" data-appearance="auto" data-reload="1"', dPappearanceAttributes('dotpulse'));
        $this->assertEquals('', dPappearanceAttributes('wps-redmond'));
        $sheets = dPthemeStylesheets('dotpulse');
        $this->assert(strpos($sheets, 'style/dotpulse/dark.css') !== false, 'dark palette linked');
        $this->assert(strpos($sheets, 'style/_shared/appearance.js') !== false, 'appearance script');
        $_COOKIE['dp_scheme'] = '<script>';
        $this->assertEquals('light', dPappearance('dotpulse'));
        unset($_COOKIE['dp_scheme']);
        $AppUI = $saved;
    }

    function testUnknownThemeGetsTheDefaults() {
        $info = dPthemeInfo('no-such-theme');
        $this->assertEquals(false, $info['legacy']);
        $this->assertEquals(false, $info['dark']);
        $info = dPthemeInfo('../../etc');
        $this->assertEquals(false, $info['dark']);
    }
}
