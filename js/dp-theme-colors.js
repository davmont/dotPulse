/*
 * Colours that scripts set (style.background = ..., cells shaded by load) for the dark
 * theme: the same mapping as the page output filter (includes/dark_colors.php), which
 * only sees the markup the server sends. In the other themes colours stay as they are.
 *
 *   dPthemeColor('#FFFFCC', 'bg')   // 'bg', 'fg' or 'border'
 *
 * The dark theme marks the page with <html data-theme="dark">.
 */
(function (global) {
	'use strict';

	var SURFACE = [0x1A, 0x20, 0x2B]; // style/dotpulse-dark/main.css --dp-surface
	var NAMES = {
		white: 'ffffff', black: '000000', silver: 'c0c0c0', gray: '808080', grey: '808080',
		lightgrey: 'd3d3d3', lightgray: 'd3d3d3', gainsboro: 'dcdcdc', whitesmoke: 'f5f5f5',
		ivory: 'fffff0', beige: 'f5f5dc', lightyellow: 'ffffe0'
	};
	var cache = {};

	function parse(value) {
		var v = String(value).trim().toLowerCase(), m;
		if (NAMES[v]) {
			v = NAMES[v];
		} else if ((m = v.match(/^#?([0-9a-f]{3}|[0-9a-f]{6})$/))) {
			v = m[1];
			if (v.length === 3) {
				v = v[0] + v[0] + v[1] + v[1] + v[2] + v[2];
			}
		} else if ((m = v.match(/^rgba?\(\s*(\d+)[\s,]+(\d+)[\s,]+(\d+)\s*(?:[,\/]\s*([\d.]+%?)\s*)?\)$/))) {
			if (m[4] !== undefined && parseFloat(m[4]) === 0) {
				return null;
			}
			return [+m[1], +m[2], +m[3]];
		} else {
			return null;
		}
		return [parseInt(v.substr(0, 2), 16), parseInt(v.substr(2, 2), 16), parseInt(v.substr(4, 2), 16)];
	}

	function hex(rgb) {
		return '#' + rgb.map(function (c) { return ('0' + c.toString(16).toUpperCase()).slice(-2); }).join('');
	}

	function luminance(rgb) {
		var c = rgb.map(function (v) {
			v /= 255;
			return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4);
		});
		return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
	}

	function contrast(a, b) {
		var la = luminance(a), lb = luminance(b);
		return (Math.max(la, lb) + 0.05) / (Math.min(la, lb) + 0.05);
	}

	function toHsl(rgb) {
		var r = rgb[0] / 255, g = rgb[1] / 255, b = rgb[2] / 255;
		var max = Math.max(r, g, b), min = Math.min(r, g, b), l = (max + min) / 2, d = max - min, h, s;
		if (max === min) {
			return [0, 0, l];
		}
		s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
		if (max === r) {
			h = (g - b) / d + (g < b ? 6 : 0);
		} else if (max === g) {
			h = (b - r) / d + 2;
		} else {
			h = (r - g) / d + 4;
		}
		return [h * 60, s, l];
	}

	function fromHsl(h, s, l) {
		var c = (1 - Math.abs(2 * l - 1)) * s, x = c * (1 - Math.abs((h / 60) % 2 - 1)), m = l - c / 2;
		var parts = [[c, x, 0], [x, c, 0], [0, c, x], [0, x, c], [x, 0, c], [c, 0, x]][Math.floor(h / 60) % 6];
		return parts.map(function (v) { return Math.round((v + m) * 255); });
	}

	// The dark version of a colour in a role, or null when it reads well as it is.
	function dark(value, role) {
		var rgb = parse(value), hsl, h, s, l, neutral, s2, l2, out = null;
		if (!rgb) {
			return null;
		}
		hsl = toHsl(rgb);
		h = hsl[0];
		s = hsl[1];
		l = hsl[2];
		neutral = s < 0.08;
		if (neutral) {
			h = 220; // the blue-grey of the theme's surfaces
		}
		if (role === 'bg' && l >= 0.6) {
			out = fromHsl(h, neutral ? 0.24 : Math.min(s, 0.5), 0.135 + (1 - l) * 0.4);
		} else if (role === 'border') {
			if (l >= 0.6) {
				out = fromHsl(h, neutral ? 0.2 : Math.min(s, 0.4), 0.22 + (1 - l) * 0.3);
			} else if (l < 0.3) {
				out = fromHsl(h, neutral ? 0.15 : Math.min(s, 0.4), 0.4);
			}
		} else if (role === 'fg' && contrast(rgb, SURFACE) < 4.5) {
			s2 = neutral ? 0.18 : Math.min(s, 0.85);
			l2 = neutral ? 0.92 - l * 0.5 : Math.max(0.62, 1 - l);
			out = fromHsl(h, s2, l2);
			while (contrast(out, SURFACE) < 4.5 && l2 < 0.97) {
				l2 += 0.02;
				out = fromHsl(h, s2, l2);
			}
		}
		return out && hex(out);
	}

	function isDark() {
		return document.documentElement.getAttribute('data-theme') === 'dark';
	}

	global.dPthemeColor = function (value, role) {
		if (!value || !isDark()) {
			return value;
		}
		var key = (role || 'bg') + '|' + String(value).toLowerCase();
		if (!(key in cache)) {
			cache[key] = dark(value, role || 'bg');
		}
		return cache[key] === null ? value : cache[key];
	};
	global.dPthemeIsDark = isDark;
}(typeof window !== 'undefined' ? window : this));
