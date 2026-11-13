/**
 * Draws the frappe-gantt charts that GanttRenderer::render() describes in
 * <script type="application/json" class="dp-gantt-data"> blocks (was an inline
 * script per chart). The task rows arrive HTML-escaped from the server, since
 * frappe-gantt inserts them with innerHTML; do not escape them again here.
 * Charts the user may change save dragged bars (see saveChange()).
 */
(function () {
	'use strict';

	var charts = {};

	function popup(task) {
		return '<div style="padding:8px 10px;max-width:240px;"><strong>' + task.name + '</strong><br>'
			+ task.start + ' → ' + task.end + '<br>' + task.progress + '% complete</div>';
	}

	/**
	 * frappe-gantt scrolls to the earliest task, so a long project or a list of
	 * projects opened on bars from months ago. Show today (a third in) when the
	 * chart covers it.
	 */
	function scrollToToday(chart) {
		var box = chart.$svg && chart.$svg.parentElement;
		if (!box || !chart.gantt_start || !chart.gantt_end) {
			return;
		}
		var now = Date.now();
		if (now < chart.gantt_start.getTime() || now > chart.gantt_end.getTime()) {
			return;
		}
		var x = (now - chart.gantt_start.getTime()) / 3600000 / chart.options.step * chart.options.column_width;
		box.scrollLeft = Math.max(0, x - box.clientWidth / 3);
	}

	function csrfToken() {
		var meta = document.querySelector('meta[name="csrf-token"]');
		return meta ? meta.getAttribute('content') : '';
	}

	/**
	 * The day that starts nearest to a moment, as YYYY-MM-DD in the browser's time zone.
	 * The chart counts in hours from its first day, so after a daylight saving change its
	 * day boundaries are an hour off midnight; rounding puts them back on the day.
	 */
	function day(date) {
		var d = new Date(date.getTime());
		if (d.getHours() >= 12) {
			d.setDate(d.getDate() + 1);
		}
		return d.getFullYear() + '-' + ('0' + (d.getMonth() + 1)).slice(-2) + '-'
			+ ('0' + d.getDate()).slice(-2);
	}

	/** The last day of a bar: frappe-gantt passes the end as one second before it ends. */
	function lastDay(end) {
		var d = new Date(end.getTime() + 1000);
		d.setHours(d.getHours() - 24);
		return day(d);
	}

	function status(id, text) {
		var el = document.querySelector('.dp-gantt-status[data-for="' + id + '"]');
		if (el) {
			el.textContent = text || '';
		}
	}

	/**
	 * Charts with a data-save-url save what is dragged: a bar's dates (moved or resized)
	 * or its progress, for task bars (ids "12" or "t12"; project bars only move back).
	 * When the server refuses, the chart goes back to what it showed; when other tasks may
	 * have moved with it (dependencies, task groups), the page reloads.
	 */
	function saveChange(block, id, rows, task, fields) {
		var url = block.getAttribute('data-save-url');
		var m = /^t?(\d+)$/.exec(String(task.id));
		var undo = function (message) {
			// refresh() redraws from the first day: keep the part of the chart in view.
			var box = charts[id].$svg && charts[id].$svg.parentElement;
			var left = box ? box.scrollLeft : 0;
			charts[id].refresh(JSON.parse(JSON.stringify(rows)));
			if (box) {
				box.scrollLeft = left;
			}
			status(id, message);
		};
		if (!url || !m) {
			undo(block.getAttribute(url ? 'data-not-task' : 'data-readonly') || '');
			return;
		}
		var body = new URLSearchParams();
		body.append('task_id', m[1]);
		Object.keys(fields).forEach(function (k) {
			body.append(k, fields[k]);
		});
		body.append('csrf_token', csrfToken());
		status(id, block.getAttribute('data-saving') || '…');
		fetch(url.replace(/&amp;/g, '&'), {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		}).then(function (response) {
			return response.json().catch(function () {
				return { status: 'error' };
			});
		}).catch(function () {
			return { status: 'error' };
		}).then(function (result) {
			if (result && result.status === 'ok') {
				if (result.reload) {
					window.location.reload();
					return;
				}
				// Keep the saved state, so a later refusal goes back to it.
				rows.forEach(function (row) {
					if (row.id === task.id) {
						if (fields.start) {
							row.start = fields.start;
							row.end = fields.end;
						}
						if (fields.progress !== undefined) {
							row.progress = fields.progress;
						}
					}
				});
				status(id, block.getAttribute('data-saved') || '');
				return;
			}
			undo((result && result.message) || block.getAttribute('data-error') || 'Error');
		});
	}

	function init() {
		if (typeof window.Gantt !== 'function') {
			return;
		}
		var blocks = document.querySelectorAll('script.dp-gantt-data');
		for (var i = 0; i < blocks.length; i++) {
			var id = blocks[i].getAttribute('data-target');
			if (!id || charts[id] || !document.getElementById(id)) {
				continue;
			}
			var block = blocks[i];
			// The rows as drawn, to go back to when a change is refused.
			var rows = JSON.parse(block.textContent);
			charts[id] = new window.Gantt(document.getElementById(id), JSON.parse(block.textContent), {
				view_mode: block.getAttribute('data-view-mode') || 'Week',
				date_format: 'YYYY-MM-DD',
				custom_popup_html: popup,
				on_date_change: (function (b, cid, r) {
					return function (task, start, end) {
						saveChange(b, cid, r, task, { start: day(start), end: lastDay(end) });
					};
				}(block, id, rows)),
				on_progress_change: (function (b, cid, r) {
					return function (task, progress) {
						saveChange(b, cid, r, task, { progress: Math.round(progress) });
					};
				}(block, id, rows))
			});
			scrollToToday(charts[id]);
		}
	}

	/** View buttons: data-dp-call="dpGanttViewMode" data-dp-args='["<chart id>", "Week"]'. */
	window.dpGanttViewMode = function (id, mode) {
		if (charts[id]) {
			charts[id].change_view_mode(mode);
			scrollToToday(charts[id]);
		}
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
