#!/usr/bin/env node
// npm audit for the development tools, failing on moderate or worse, except for the advisories
// named in IGNORE: those have no fixed release yet. Remove an entry once a fix is published
// (npm view <package> versions). Run in the package directory: node ../checks/npm-audit.js
'use strict';
const { execFileSync } = require('child_process');

const IGNORE = {
	// picomatch <= 4.0.7 ReDoS (2026-11-17), reached only through Jest; 4.0.7 is the latest release.
	'GHSA-fc5q-39xc-vjpf': 'picomatch',
};
const LEVELS = ['info', 'low', 'moderate', 'high', 'critical'];

let out;
try {
	out = execFileSync('npm', ['audit', '--omit=optional', '--json'], { encoding: 'utf8' });
} catch (e) {
	out = e.stdout; // npm audit exits 1 when it finds anything
}
const report = JSON.parse(out);
const found = new Map(); // advisory url -> {name, severity, title}
for (const [name, v] of Object.entries(report.vulnerabilities || {})) {
	for (const via of v.via) {
		if (typeof via === 'object') found.set(via.url, { name, severity: via.severity, title: via.title });
	}
}
let fail = false;
for (const [url, a] of found) {
	const id = url.split('/').pop();
	const ignored = IGNORE[id] === a.name;
	if (LEVELS.indexOf(a.severity) < LEVELS.indexOf('moderate')) continue;
	console.log(`${ignored ? 'IGNORED' : 'FAIL   '} ${a.severity} ${a.name}: ${a.title} (${url})`);
	if (!ignored) fail = true;
}
if (!found.size) console.log('no known vulnerabilities');
process.exit(fail ? 1 : 0);
