/**
 * style/_shared/appearance.js: the Automatic appearance writes the computer's setting in the
 * dp_scheme cookie and loads the page again, once, when it was drawn in the other appearance.
 */
const fs = require('fs');
const path = require('path');

const src = fs.readFileSync(path.resolve(__dirname, '../../../style/_shared/appearance.js'), 'utf8');

// A page and a browser: attributes of <html>, the computer's setting, cookies on or off.
function run({ attrs = {}, dark = false, cookies = true, cookie = '', storage = {} } = {}) {
	const jar = { value: cookie };
	const listeners = [];
	const query = { matches: dark, addEventListener: (type, fn) => listeners.push(fn) };
	const window = {
		matchMedia: () => query,
		location: { pathname: '/dotpulse/index.php', protocol: 'http:', href: 'http://x/dotpulse/index.php?m=tasks', reload: jest.fn() },
		sessionStorage: {
			getItem: k => (k in storage ? storage[k] : null),
			setItem: (k, v) => { storage[k] = v; },
			removeItem: k => { delete storage[k]; },
		},
	};
	const document = {
		documentElement: { getAttribute: n => (n in attrs ? attrs[n] : null) },
		get cookie() { return jar.value; },
		set cookie(v) {
			if (!cookies) return;
			jar.set = v;
			jar.value = v.split(';')[0];
		},
	};
	new Function('window', 'document', src)(window, document);
	return { window, jar, storage, flip: (d) => { query.matches = d; listeners.forEach(fn => fn()); } };
}

const AUTO = { 'data-appearance': 'auto', 'data-reload': '1' };

test('does nothing unless the appearance is Automatic', () => {
	const r = run({ attrs: {}, dark: true });
	expect(r.jar.value).toBe('');
	expect(r.window.location.reload).not.toHaveBeenCalled();
});

test('a light page on a dark computer: cookie, then one reload', () => {
	const r = run({ attrs: AUTO, dark: true });
	expect(r.jar.value).toBe('dp_scheme=dark');
	expect(r.jar.set).toContain('path=/dotpulse/');
	expect(r.jar.set).toContain('SameSite=Lax');
	expect(r.window.location.reload).toHaveBeenCalledTimes(1);
});

test('a page already in the right appearance is left alone', () => {
	const r = run({ attrs: { ...AUTO, 'data-theme': 'dark' }, dark: true, cookie: 'dp_scheme=dark' });
	expect(r.window.location.reload).not.toHaveBeenCalled();
});

test('no reload of a page that was not a GET', () => {
	const r = run({ attrs: { 'data-appearance': 'auto' }, dark: true });
	expect(r.jar.value).toBe('dp_scheme=dark');
	expect(r.window.location.reload).not.toHaveBeenCalled();
});

test('no reload when cookies are off', () => {
	const r = run({ attrs: AUTO, dark: true, cookies: false });
	expect(r.window.location.reload).not.toHaveBeenCalled();
});

test('no second reload of the same page', () => {
	const href = 'http://x/dotpulse/index.php?m=tasks';
	const r = run({ attrs: AUTO, dark: true, storage: { dp_scheme_reload: href } });
	expect(r.window.location.reload).not.toHaveBeenCalled();
	expect(r.storage.dp_scheme_reload).toBeUndefined();
});

test('a change of the computer updates the cookie without reloading', () => {
	const r = run({ attrs: AUTO, dark: false, cookie: 'dp_scheme=light' });
	r.flip(true);
	expect(r.jar.value).toBe('dp_scheme=dark');
	expect(r.window.location.reload).not.toHaveBeenCalled();
});
