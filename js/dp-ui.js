/**
 * Core page behaviour that used to be inline <script> in the themes and
 * CAppUI, moved here so it runs under the Content-Security-Policy.
 */
(function () {
	'use strict';

	// CSRF: add the token from <meta name="csrf-token"> to POST forms that
	// CAppUI::injectCsrfToken() cannot see: forms built by JavaScript, and forms
	// submitted with form.submit(), which does not fire the submit event.
	// The meta tag is printed after this file loads, so read it when needed.
	function addToken(f) {
		if (!f || String(f.getAttribute('method') || '').toLowerCase() !== 'post') {
			return;
		}
		if (f.querySelector('input[name="csrf_token"]')) {
			return;
		}
		var meta = document.querySelector('meta[name="csrf-token"]');
		if (!meta) {
			return;
		}
		var i = document.createElement('input');
		i.type = 'hidden';
		i.name = 'csrf_token';
		i.value = meta.getAttribute('content');
		f.appendChild(i);
	}
	document.addEventListener('submit', function (e) { addToken(e.target); }, true);
	var nativeSubmit = HTMLFormElement.prototype.submit;
	HTMLFormElement.prototype.submit = function () {
		addToken(this);
		return nativeSubmit.apply(this, arguments);
	};
})();

/**
 * "- New Item -" selects in the theme headers: submit the form once a real
 * choice is made. Used as data-dp-call="dpSubmitIfValue".
 */
function dpSubmitIfValue() {
	if (this.value && this.form) {
		this.form.submit();
	}
}

/**
 * Sends a POST request by submitting a hidden form, for links that change data
 * (they used to be GET links, which other sites can trigger). The CSRF token is
 * added by the submit hook above. Used as
 * data-dp-call="dpPost" data-dp-args='["?m=x&a=y", {"field": "value"}]'.
 */
function dpPost(url, fields) {
	var form = document.createElement('form');
	form.method = 'post';
	form.action = url;
	form.style.display = 'none';
	for (var name in (fields || {})) {
		if (Object.prototype.hasOwnProperty.call(fields, name)) {
			var input = document.createElement('input');
			input.type = 'hidden';
			input.name = name;
			input.value = fields[name];
			form.appendChild(input);
		}
	}
	document.body.appendChild(form);
	form.submit();
	return false;
}

/**
 * dotPulse theme on a phone: the module menu is one row that scrolls sideways;
 * bring the current module into view.
 */
document.addEventListener('DOMContentLoaded', function () {
	var nav = document.querySelector('.dp-nav');
	var on = nav && nav.querySelector('a.dp-on');
	if (on && nav.scrollWidth > nav.clientWidth) {
		nav.scrollLeft = Math.max(0, on.offsetLeft - nav.offsetLeft - (nav.clientWidth - on.offsetWidth) / 2);
	}
});
