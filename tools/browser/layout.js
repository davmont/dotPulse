// Layout check: pages must not scroll sideways or put controls outside the window
// (a control in a box that scrolls sideways within the window counts as reachable).
//
//   node layout.js <user> <password> <theme> <WxH> <urls-file> <report.jsonl> [known-file]
//
// The theme is only a label here (run.sh sets it). For each page: the document
// may not be wider than the window, and no button, input, select or textarea may
// sit outside it. Entries in known-file ("theme viewport url-regex  # why";
// "*" matches any theme or viewport) are reported but do not fail.
const fs = require('fs');
const { BASE, launch, loggedIn, readList } = require('./common');
const [,, user, password, theme, vp, urlsFile, report, knownFile] = process.argv;
const [width, height] = vp.split('x').map(Number);
const known = knownFile && fs.existsSync(knownFile) ? readList(knownFile).map(l => {
	const [t, v, re] = l.replace(/\s+#.*$/, '').split(/\s+/);
	return { t, v, re: new RegExp(re) };
}) : [];
const isKnown = url => known.some(k => (k.t === '*' || k.t === theme) && (k.v === '*' || k.v === vp) && k.re.test(url));

(async () => {
	const browser = await launch();
	const page = await loggedIn(browser, user, password, { width, height });
	page.on('dialog', d => d.dismiss().catch(() => {}));
	const out = fs.createWriteStream(report);
	let failed = 0, knownCount = 0, checked = 0;
	for (const url of readList(urlsFile)) {
		let r;
		try {
			await page.goto(`${BASE}/index.php?${url}`, { waitUntil: 'load', timeout: 30000 });
			await page.waitForTimeout(250);
			r = await page.evaluate(() => {
				const W = document.documentElement.clientWidth;
				const path = e => { const a = []; for (let x = e; x && x !== document.body && a.length < 5; x = x.parentElement) a.unshift(x.tagName.toLowerCase() + (x.id ? '#' + x.id : x.name ? `[name=${x.name}]` : '')); return a.join('>'); };
				const visible = e => { const s = getComputedStyle(e), b = e.getBoundingClientRect(); return s.display !== 'none' && s.visibility !== 'hidden' && b.width > 0 && b.height > 0; };
				const outside = [];
				// A control in a box that scrolls sideways and fits in the window (a wide list on
				// a phone) is reached by scrolling that box.
				const inScroller = e => { for (let x = e.parentElement; x && x !== document.body; x = x.parentElement) { const o = getComputedStyle(x).overflowX, b = x.getBoundingClientRect(); if ((o === 'auto' || o === 'scroll') && b.left >= -1 && b.right <= W + 1) return true; } return false; };
				for (const e of document.querySelectorAll('button,input:not([type=hidden]),select,textarea,a.button')) {
					if (!visible(e) || inScroller(e)) continue;
					const b = e.getBoundingClientRect();
					if (b.right + scrollX > W + 1 || b.left + scrollX < -1) outside.push(path(e) + ' ' + (e.value || e.textContent || '').trim().slice(0, 20));
				}
				// The widest elements that stick out, to point at the cause.
				const wide = [];
				if (document.documentElement.scrollWidth > W + 1) {
					for (const e of document.body.querySelectorAll('*')) {
						const b = e.getBoundingClientRect();
						if (b.width > W && ![...e.children].some(c => c.getBoundingClientRect().width > W)) wide.push(path(e) + ' ' + Math.round(b.width) + 'px');
						if (wide.length >= 5) break;
					}
				}
				return { overflow: document.documentElement.scrollWidth - W, outside: outside.slice(0, 8), wide };
			});
		} catch (e) {
			r = { error: String(e).split('\n')[0].slice(0, 120) };
		}
		checked++;
		const bad = r.error || r.overflow > 1 || r.outside.length;
		const status = !bad ? 'ok' : isKnown(url) ? 'known' : 'fail';
		if (status === 'fail') {
			failed++;
			console.log(`FAIL  ${theme} ${vp} ${url}: ` + (r.error || `${r.overflow}px too wide; outside: ${r.outside.join(', ') || '-'}; cause: ${r.wide.join(', ') || '-'}`).slice(0, 400));
		} else if (status === 'known') {
			knownCount++;
		}
		out.write(JSON.stringify({ url, theme, vp, status, ...r }) + '\n');
	}
	out.end();
	await browser.close();
	console.log(`${failed ? 'FAIL' : 'PASS'}  layout ${theme} ${vp}: ${checked} pages, ${failed} failing, ${knownCount} known`);
	process.exit(failed ? 1 : 0);
})().catch(e => { console.log('FAIL  layout: ' + e); process.exit(1); });
