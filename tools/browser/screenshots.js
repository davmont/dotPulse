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
			for (const step of s.steps || []) {
				// A click or a choice may load another page (a form submit); wait for it if so.
				const settle = () => page.waitForNavigation({ timeout: 2500 }).catch(() => {});
				if (step.click) await Promise.all([settle(), page.click(step.click)]);
				if (step.select) await Promise.all([settle(), page.selectOption(step.select[0], { label: step.select[1] })]);
				if (step.fill) await page.fill(step.fill[0], step.fill[1]);
				if (step.hover) await page.hover(step.hover);
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
