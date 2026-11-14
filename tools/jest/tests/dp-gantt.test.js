/**
 * @jest-environment jsdom
 */
const fs = require('fs');
const path = require('path');

const load = (file) => window.eval(fs.readFileSync(path.resolve(__dirname, '../../../js', file), 'utf8'));

describe('dp-gantt.js', () => {
	test('draws each data block once, with the server-escaped rows untouched', () => {
		const made = [];
		window.Gantt = function (el, tasks, opts) {
			made.push({ el, tasks, opts });
			this.change_view_mode = (m) => made.push({ mode: m });
		};
		document.body.innerHTML = `<svg id="g1"></svg>
			<script type="application/json" class="dp-gantt-data" data-target="g1" data-view-mode="Month">
			[{"id":"1","name":"R&amp;D &lt;b&gt;","start":"2026-01-01","end":"2026-01-02","progress":5}]</script>`;
		load('dp-gantt.js');
		expect(made).toHaveLength(1);
		expect(made[0].el).toBe(document.getElementById('g1'));
		expect(made[0].opts.view_mode).toBe('Month');
		const html = made[0].opts.custom_popup_html(made[0].tasks[0]);
		expect(html).toContain('R&amp;D &lt;b&gt;');
		expect(html).not.toContain('&amp;amp;');
		window.dpGanttViewMode('g1', 'Day');
		expect(made[1]).toEqual({ mode: 'Day' });
	});

	test('opens on today when the chart covers it', () => {
		const box = document.createElement('div');
		Object.defineProperty(box, 'clientWidth', { value: 300 });
		const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
		box.appendChild(svg);
		const day = 86400000;
		window.Gantt = function () {
			this.$svg = svg;
			this.gantt_start = new Date(Date.now() - 10 * day);
			this.gantt_end = new Date(Date.now() + 10 * day);
			this.options = { step: 24, column_width: 38 };
			this.change_view_mode = () => {};
		};
		document.body.innerHTML = `<svg id="g2"></svg>
			<script type="application/json" class="dp-gantt-data" data-target="g2">[]</script>`;
		load('dp-gantt.js');
		// 10 days of 38px columns, minus a third of the 300px box.
		expect(box.scrollLeft).toBeCloseTo(10 * 38 - 100, 0);
	});

	function editableChart(id) {
		const made = [];
		window.Gantt = function (el, tasks, opts) {
			this.opts = opts;
			this.refresh = (rows) => made.push({ refresh: rows });
			this.change_view_mode = () => {};
			made.push(this);
		};
		document.head.innerHTML = '<meta name="csrf-token" content="tok">';
		document.body.innerHTML = `<svg id="${id}"></svg><p class="dp-gantt-status" data-for="${id}"></p>
			<script type="application/json" class="dp-gantt-data" data-target="${id}" data-save-url="?m=tasks&amp;a=do_gantt_dates"
				data-saved="Saved." data-undo="Undo" data-undone="Undone." data-cancelled="Cancelled.">
			[{"id":"7","name":"A","start":"2026-03-02","end":"2026-03-06","progress":10}]</script>`;
		load('dp-gantt.js');
		return made;
	}

	const flush = () => new Promise((r) => setTimeout(r, 10));

	test('Esc during a drag puts the bar back and saves nothing', async () => {
		const made = editableChart('g3');
		const posts = [];
		window.fetch = (url, init) => { posts.push(init.body); return Promise.resolve({ json: () => Promise.resolve({ status: 'ok' }) }); };
		const svg = document.getElementById('g3');
		svg.dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
		document.dispatchEvent(new KeyboardEvent('keydown', { key: 'Escape' }));
		expect(made[1].refresh[0].start).toBe('2026-03-02');
		expect(document.querySelector('.dp-gantt-status').textContent).toBe('Cancelled.');
		// The chart still reports the drag when the button is released.
		made[0].opts.on_date_change({ id: '7' }, new Date(2026, 2, 9), new Date(2026, 2, 13, 23, 59, 59));
		document.dispatchEvent(new MouseEvent('mouseup'));
		await flush();
		expect(posts).toHaveLength(0);
	});

	test('Undo saves the previous dates and draws them', async () => {
		const made = editableChart('g4');
		const posts = [];
		window.fetch = (url, init) => { posts.push(new URLSearchParams(init.body)); return Promise.resolve({ json: () => Promise.resolve({ status: 'ok' }) }); };
		document.getElementById('g4').dispatchEvent(new MouseEvent('mousedown', { bubbles: true }));
		made[0].opts.on_date_change({ id: '7' }, new Date(2026, 2, 9), new Date(2026, 2, 13, 23, 59, 59));
		document.dispatchEvent(new MouseEvent('mouseup'));
		await flush();
		expect(posts[0].get('start')).toBe('2026-03-09');
		expect(posts[0].get('end')).toBe('2026-03-13');
		const button = document.querySelector('.dp-gantt-status button');
		expect(button.textContent).toBe('Undo');
		button.click();
		await flush();
		expect(posts[1].get('start')).toBe('2026-03-02');
		expect(posts[1].get('end')).toBe('2026-03-06');
		expect(made[made.length - 1].refresh[0].start).toBe('2026-03-02');
		expect(document.querySelector('.dp-gantt-status').textContent).toBe('Undone.');
	});
});
