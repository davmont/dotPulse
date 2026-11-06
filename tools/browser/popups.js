// Pop-up windows at the size their window.open() asks for, and JavaScript tab
// switching on the task form.
//
//   node popups.js <user> <password> <theme> <task_id>
//
// Fails when a pop-up's buttons are outside its window, or when clicking a tab
// of the task form does not show exactly that tab (or throws a script error).
const { BASE, launch, loggedIn } = require('./common');
const [,, user, password, theme, taskId] = process.argv;

const POPUPS = [
	['m=public&a=calendar&dialog=1&callback=setCalendar&date=20261021', 280, 250],
	['m=public&a=selector&dialog=1&callback=setCompany&table=companies', 400, 250],
	['m=public&a=selector&dialog=1&callback=setPermItem&table=projects', 400, 250],
	['m=public&a=contact_selector&dialog=1&call_back=setContacts&selected_contacts_id=', 400, 600],
	['m=public&a=color_selector&dialog=1&callback=setColor', 320, 300],
	['m=public&a=chpwd&dialog=1&user_id=1', 450, 300],
	['m=contacts&a=select_contact_company&dialog=1&table_name=companies&company_id=1', 400, 250],
	['m=public&a=date_format&dialog=1', 400, 300],
];

(async () => {
	const browser = await launch();
	const page = await loggedIn(browser, user, password);
	const scriptErrors = [];
	page.on('pageerror', e => scriptErrors.push(String(e).slice(0, 160)));
	let failed = 0;
	for (const [url, w, h] of POPUPS) {
		await page.setViewportSize({ width: w, height: h });
		await page.goto(`${BASE}/index.php?${url}`, { waitUntil: 'load' });
		const hidden = await page.evaluate(() => [...document.querySelectorAll('input[type=submit],input[type=button],button')]
			.filter(e => e.offsetParent)
			.filter(e => { const b = e.getBoundingClientRect(); return b.right > innerWidth || b.bottom > innerHeight; })
			.map(e => (e.value || e.textContent).trim()));
		if (hidden.length) { failed++; console.log(`FAIL  ${theme} pop-up ${url} (${w}x${h}): buttons outside the window: ${hidden.join(', ')}`); }
	}
	await page.setViewportSize({ width: 1366, height: 768 });
	await page.goto(`${BASE}/index.php?m=tasks&a=addedit&task_id=${taskId}`, { waitUntil: 'load' });
	const tabs = await page.$$eval('[id^=toptab_]', e => e.length);
	for (let i = 0; i < tabs; i++) {
		await page.click(`#toptab_${i} a`);
		const st = await page.evaluate(i => ({
			shown: [...document.querySelectorAll('div.tab')].filter(d => d.style.display !== 'none').map(d => d.id),
			cls: document.getElementById('toptab_' + i).className,
		}), i);
		if (st.shown.length !== 1 || st.shown[0] !== 'tab_' + i || !(st.cls === 'tabon' || st.cls.split(' ').includes('dp-tab-on'))) { failed++; console.log(`FAIL  ${theme} task form tab ${i}: ${JSON.stringify(st)}`); }
	}
	if (!tabs) { failed++; console.log(`FAIL  ${theme} task form: no tabs found`); }
	if (scriptErrors.length) { failed++; console.log(`FAIL  ${theme} script errors: ${scriptErrors.join(' | ')}`); }
	await browser.close();
	console.log(`${failed ? 'FAIL' : 'PASS'}  pop-ups and tabs ${theme}: ${POPUPS.length} pop-ups, ${tabs} tabs`);
	process.exit(failed ? 1 : 0);
})().catch(e => { console.log('FAIL  pop-ups: ' + e); process.exit(1); });
