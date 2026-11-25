/* The Automatic appearance (Preferences › Appearance): tells the server whether the computer
   is set to dark, in the dp_scheme cookie that dPappearance() (includes/main_functions.php)
   reads, and loads the page again when it was drawn in the other appearance. Loaded in the
   <head> only when the preference is Automatic, so the page doesn't show first in the wrong
   colours. A page is loaded again only when it has just arrived and was a GET
   (data-reload): never later, when the computer changes while someone is typing; the next
   page follows the change. */
(function () {
	'use strict';
	var root = document.documentElement;
	if (!window.matchMedia || root.getAttribute('data-appearance') !== 'auto') {
		return;
	}
	var query = window.matchMedia('(prefers-color-scheme: dark)');
	var path = window.location.pathname.replace(/[^\/]*$/, '');

	function stored() {
		var found = document.cookie.match(/(?:^|;\s*)dp_scheme=(dark|light)/);
		return found ? found[1] : null;
	}

	function store() {
		var wanted = query.matches ? 'dark' : 'light';
		if (stored() !== wanted) {
			document.cookie = 'dp_scheme=' + wanted + '; path=' + path + '; max-age=31536000; SameSite=Lax'
				+ (window.location.protocol === 'https:' ? '; Secure' : '');
		}
		return wanted;
	}

	var wanted = store();
	var shown = root.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
	// Only when the cookie was kept (cookies on), and once: a page that still comes back in the
	// other appearance is left as it is.
	if (shown !== wanted && stored() === wanted && root.getAttribute('data-reload') === '1') {
		var again = false;
		try {
			again = window.sessionStorage.getItem('dp_scheme_reload') === window.location.href;
			window.sessionStorage.setItem('dp_scheme_reload', window.location.href);
		} catch (e) {
			again = true;
		}
		if (!again) {
			window.location.reload();
			return;
		}
	}
	try {
		window.sessionStorage.removeItem('dp_scheme_reload');
	} catch (e) { /* storage off: nothing to clear */ }

	if (query.addEventListener) {
		query.addEventListener('change', store);
	} else if (query.addListener) {
		query.addListener(store);
	}
}());
