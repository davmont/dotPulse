<?php /* STYLE/DOTPULSE-DARK: dotPulse's tab boxes and title blocks, dark colours */
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly');
}
// The dark versions of the small icons and the colour filter of includes/dark_colors.php
// (index.php); dotPulse's overrides turn the vector icons on.
if (!defined('DP_DARK_THEME')) {
	define('DP_DARK_THEME', true);
}
require DP_BASE_DIR . '/style/dotpulse/overrides.php';
