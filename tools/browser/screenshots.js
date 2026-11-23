// Screenshots for the documentation website, taken from the sandbox with the sample data
// (`make sample-data`), so they can be retaken for every release.
//
//   node screenshots.js <list.json> <out-dir> [name ...]
//
// list.json is an array of shots:
//   { "name": "ug-dashboard",             // file name: <out-dir>/<name>.png
//     "user": "admin",                    // admin (password "passwd") or a sample user ("priya": demo1234)
//     "url": "m=dashboard",               // index.php?<url>; "" for the login page (logged out);
//                                         // "/install/index.php" for a path
//     "base": "http://127.0.0.1:8092",    // optional: another server (default BASE)
//     "viewport": "1280x800",             // optional, default 1280x800
//     "selector": "#content",             // optional: crop to this element
//     "steps": [ {"click": "text=Edit"}, {"select": ["select[name=x]", "Label"]}, {"fill": ["input[name=x]", "value"]}, {"hover": "css"}, {"press": ["input[name=password]", "Enter"]}, {"wait": 500} ],
//     "full": false }                     // optional: whole page instead of the window
// With names, only those shots are taken.
const fs = require('fs');
const path = require('path');
const { BASE, launch, loggedIn } = require('./common');
const [,, listFile, outDir, ...only] = process.argv;
const PASSWORDS = { admin: 'passwd', worker: 'worker', guest: 'guest' };

// SHOT_LANG=es: the shots of the Spanish site, with the sandbox in Spanish. The texts the steps
// click (text-is('Tasks'), text=..., has-text('...')) are looked up in that language's locale
// files, so the same list serves both sites.
const LANG = process.env.SHOT_LANG || '';
const words = {};	// file key (module name, 'common') => { English: translation }
if (LANG) {
	const root = path.join(__dirname, '..', '..');
	const files = fs.readdirSync(path.join(root, 'locales', LANG)).filter(f => f.endsWith('.inc'))
		.map(f => [f.replace(/\.inc$/, ''), path.join(root, 'locales', LANG, f)])
		.concat(fs.readdirSync(path.join(root, 'modules')).map(m => ['mod:' + m, path.join(root, 'modules', m, 'locales', LANG + '.inc')]).filter(f => fs.existsSync(f[1])));
	for (const [key, f] of files) {
		words[key] = {};
		for (const m of fs.readFileSync(f, 'utf8').matchAll(/^"((?:[^"\\]|\\.)*)"=>"((?:[^"\\]|\\.)*)"/gm)) {
			words[key][m[1]] = m[2];
		}
	}
}
// The same text can differ by module ("New Log"): the shot's module first, then common, then any.
const word = (t, mod) => {
	for (const key of [mod, 'mod:' + mod, 'common']) {
		if (words[key] && words[key][t]) return words[key][t];
	}
	for (const key in words) {
		if (words[key][t]) return words[key][t];
	}
	return t;
};
const tr = (sel, mod) => !LANG || typeof sel !== 'string' ? sel : sel
	.replace(/(text-is|has-text)\('([^']+)'\)/g, (all, fn, t) => `${fn}('${word(t, mod)}')`)
	.replace(/^text=(.+)$/, (all, t) => 'text=' + word(t, mod));

(async () => {
	const shots = JSON.parse(fs.readFileSync(listFile, 'utf8')).filter(s => !only.length || only.includes(s.name));
	fs.mkdirSync(outDir, { recursive: true });
	const browser = await launch();
	const pages = {};
	let failed = 0;
	for (const s of shots) {
		const [width, height] = (s.viewport || '1280x800').split('x').map(Number);
		try {
			let page;
			if (s.user) {
				const key = `${s.user}@${width}x${height}`;
				pages[key] = pages[key] || await loggedIn(browser, s.user, PASSWORDS[s.user] || 'demo1234', { width, height });
				page = pages[key];
			} else {
				page = await (await browser.newContext({ viewport: { width, height } })).newPage();
			}
			// A url starting with / is a path (the installer); base: another server.
			const base = s.base || BASE;
			await page.goto(s.url && s.url.startsWith('/') ? base + s.url : `${base}/index.php${s.url ? '?' + s.url : ''}`, { waitUntil: 'load' });
			const mod = ((s.url || '').match(/(?:^|&)m=([a-z_]+)/) || [])[1] || '';
			for (const step of s.steps || []) {
				// A click or a choice may load another page (a form submit); wait for it if so.
				const settle = () => page.waitForNavigation({ timeout: 2500 }).catch(() => {});
				if (step.click) await Promise.all([settle(), page.click(tr(step.click, mod))]);
				if (step.select) await Promise.all([settle(), page.selectOption(step.select[0], { label: LANG ? word(step.select[1], mod) : step.select[1] })]);
				if (step.fill) await page.fill(step.fill[0], step.fill[1]);
				if (step.hover) await page.hover(tr(step.hover, mod));
				if (step.press) await Promise.all([page.waitForNavigation().catch(() => {}), page.press(step.press[0], step.press[1])]);
				if (step.wait) await page.waitForTimeout(step.wait);
				await page.waitForLoadState('load');
			}
			// Fonts and charts settle; no blinking caret in the picture.
			await page.waitForTimeout(400);
			await page.evaluate(() => document.activeElement && document.activeElement.blur && document.activeElement.blur());
			const file = path.join(outDir, s.name + '.png');
			if (s.selector) {
				await page.locator(s.selector).first().screenshot({ path: file });
			} else {
				if (s.full) {
					// A sticky bar (System Admin's Save changes) would be drawn where the window
					// ended, over the middle of a whole-page picture: put it back in the flow.
					await page.evaluate(() => document.querySelectorAll('body *').forEach(e => {
						if (getComputedStyle(e).position === 'sticky') e.style.position = 'static';
					}));
				}
				await page.screenshot({ path: file, fullPage: !!s.full });
			}
			console.log(`ok    ${s.name}`);
		} catch (e) {
			failed++;
			console.log(`FAIL  ${s.name}: ${String(e).split('\n')[0].slice(0, 160)}`);
		}
	}
	await browser.close();
	console.log(`${failed ? 'FAIL' : 'PASS'}  screenshots: ${shots.length - failed} of ${shots.length}`);
	process.exit(failed ? 1 : 0);
})().catch(e => { console.log('FAIL  screenshots: ' + e); process.exit(1); });
