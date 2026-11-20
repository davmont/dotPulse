// Dark theme check: no light patches and no unreadable text on the real pages.
//
//   node dark.js <user> <password> <urls-file> <report.jsonl> [known-file] [WxH]
//
// run.sh sets the theme (dotpulse-dark). For each page, after its scripts ran:
// - light patch: a visible box of 24x12 px or more whose own background is pale
//   (HSL lightness >= 0.8), as the computed style gives it: colours set by
//   JavaScript count, the page output filter cannot reach them;
// - unreadable text: text whose colour is under 3:1 against the background it is
//   drawn on (the first opaque background of it or an ancestor).
// Boxes with a background image, images, and the pop-ups that keep their own light
// style (date pickers, overlib) are skipped. Findings are grouped by element path,
// so one cause shows once with the pages it is on. Entries in known-file
// ("url-regex path-regex  # why") are reported but do not fail.
const fs = require('fs');
const { BASE, launch, loggedIn, readList } = require('./common');
const [,, user, password, urlsFile, report, knownFile, vp = '1366x768'] = process.argv;
const [width, height] = vp.split('x').map(Number);
const known = knownFile && fs.existsSync(knownFile) ? readList(knownFile).map(l => {
	const [u, p] = l.replace(/\s+#.*$/, '').split(/\s+/);
	return { u: new RegExp(u), p: new RegExp(p || '.') };
}) : [];
const isKnown = (url, path) => known.some(k => k.u.test(url) && k.p.test(path));

function scan() {
	const SKIP = '.flatpickr-calendar, #overDiv, .calendar, .ui-datepicker, img, svg, canvas, iframe, video, option';
	const rgba = s => { const m = s.match(/rgba?\(([^)]+)\)/); if (!m) return null; const [r, g, b, a = 1] = m[1].split(/[\s,\/]+/).filter(Boolean).map(Number); return [r, g, b, a]; };
	const lum = ([r, g, b]) => { const f = v => (v /= 255) <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b); };
	const ratio = (a, b) => { const x = lum(a), y = lum(b); return (Math.max(x, y) + 0.05) / (Math.min(x, y) + 0.05); };
	const lightness = ([r, g, b]) => (Math.max(r, g, b) + Math.min(r, g, b)) / 510;
	const hex = c => '#' + c.slice(0, 3).map(v => Math.round(v).toString(16).padStart(2, '0')).join('');
	const path = e => { const a = []; for (let x = e; x && x !== document.body && a.length < 4; x = x.parentElement) { let s = x.tagName.toLowerCase(); if (x.id && !/\d{2,}/.test(x.id)) s += '#' + x.id; else if (typeof x.className === 'string' && x.className.trim()) s += '.' + x.className.trim().split(/\s+/)[0]; a.unshift(s); } return a.join('>'); };
	const shown = e => { const s = getComputedStyle(e); if (s.display === 'none' || s.visibility === 'hidden' || +s.opacity === 0) return false; const b = e.getBoundingClientRect(); return b.width > 0 && b.height > 0; };
	// The background a box is drawn on: its own or the first opaque one above it.
	const backdrop = e => { for (let x = e; x; x = x.parentElement) { const s = getComputedStyle(x); if (s.backgroundImage !== 'none' && !/gradient/.test(s.backgroundImage)) return null; const c = rgba(s.backgroundColor); if (c && c[3] >= 0.5) return c; } return rgba(getComputedStyle(document.body).backgroundColor) || [255, 255, 255, 1]; };
	const light = [], text = [];
	for (const e of document.body.querySelectorAll('*')) {
		if (e.closest(SKIP) || !shown(e)) continue;
		const s = getComputedStyle(e), b = e.getBoundingClientRect();
		const c = rgba(s.backgroundColor);
		if (c && c[3] >= 0.5 && s.backgroundImage === 'none' && lightness(c) >= 0.8 && b.width >= 24 && b.height >= 12) {
			// Report the outermost box of a light region only.
			const p = e.parentElement && backdrop(e.parentElement);
			if (!p || lightness(p) < 0.8) light.push({ path: path(e), color: hex(c), size: Math.round(b.width) + 'x' + Math.round(b.height), text: (e.textContent || '').trim().slice(0, 30) });
		}
		const own = [...e.childNodes].filter(n => n.nodeType === 3 && n.textContent.trim()).map(n => n.textContent.trim()).join(' ');
		if (own) {
			const fg = rgba(s.color), bg = backdrop(e);
			if (fg && bg && fg[3] >= 0.5) {
				const r = ratio(fg, bg);
				if (r < 3) text.push({ path: path(e), color: hex(fg), on: hex(bg), ratio: Math.round(r * 100) / 100, text: own.slice(0, 30) });
			}
		}
	}
	return { light: light.slice(0, 40), text: text.slice(0, 40) };
}

(async () => {
	const browser = await launch();
	const page = await loggedIn(browser, user, password, { width, height });
	page.on('dialog', d => d.dismiss().catch(() => {}));
	const out = fs.createWriteStream(report);
	const groups = new Map(); // kind + path -> { sample, urls }
	let checked = 0, errors = 0;
	for (const url of readList(urlsFile)) {
		let r;
		try {
			await page.goto(`${BASE}/index.php?${url}`, { waitUntil: 'load', timeout: 30000 });
			await page.waitForTimeout(250);
			r = await page.evaluate(scan);
		} catch (e) {
			r = { error: String(e).split('\n')[0].slice(0, 120) };
			errors++;
		}
		checked++;
		out.write(JSON.stringify({ url, ...r }) + '\n');
		for (const kind of ['light', 'text']) {
			for (const f of r[kind] || []) {
				if (isKnown(url, f.path)) continue;
				const key = kind + ' ' + f.path;
				if (!groups.has(key)) groups.set(key, { kind, f, urls: new Set() });
				groups.get(key).urls.add(url);
			}
		}
	}
	out.end();
	await browser.close();
	const sorted = [...groups.values()].sort((a, b) => b.urls.size - a.urls.size);
	for (const g of sorted) {
		const f = g.f;
		const what = g.kind === 'light' ? `light ${f.color} ${f.size}` : `text ${f.color} on ${f.on} = ${f.ratio}:1`;
		console.log(`FAIL  ${what}  ${f.path}  "${f.text}"  ${g.urls.size} page(s), e.g. ${[...g.urls][0]}`);
	}
	const failed = sorted.length + errors;
	console.log(`${failed ? 'FAIL' : 'PASS'}  dark theme ${vp}: ${checked} pages, ${sorted.length} problems, ${errors} pages not loaded`);
	process.exit(failed ? 1 : 0);
})().catch(e => { console.log('FAIL  dark theme: ' + e); process.exit(1); });
