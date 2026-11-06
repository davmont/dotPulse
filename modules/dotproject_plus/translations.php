<?php
if (!defined('DP_BASE_DIR')) {
    die('You should not access this file directly.');
}

// Try to load from both locales locations if they exist
$locales_locations = array(
    DP_BASE_DIR . '/modules/dotproject_plus/locales/'
);

$locale_to_load = isset($AppUI->user_prefs['LOCALE']) ? $AppUI->user_prefs['LOCALE'] : 'en';

// The user's language, then its first two letters (es for es_ES).
$locales_to_try = array($locale_to_load);
if (strlen($locale_to_load) > 2) {
    $locales_to_try[] = substr($locale_to_load, 0, 2);
}

foreach ($locales_locations as $loc) {
    foreach ($locales_to_try as $alias) {
        $locale_file = $loc . $alias . '.inc';
        if (file_exists($locale_file)) {
            $content = file_get_contents($locale_file);
            if ($content) {
                $content = str_replace(array('<?php', '?>', '<?'), '', $content);
                $locale = array();
                if (dPisTranslationSource($content)) {
                    @eval ("\$locale=array(" . $content . "\n'0');");
                }
                if (is_array($locale) && count($locale) > 1) {
                    $trans = array();
                    foreach ($locale as $k => $v) {
                        if ($v !== '0') {
                            $trans[$k] = $v;
                        }
                    }
                    $GLOBALS['translate'] = array_merge($GLOBALS['translate'], $trans);
                    // If we found a full match or a good fallback, we can continue to next location 
                    // or stop if we are satisfied. Let's merge all found for now.
                }
            }
        }
    }
}
?>