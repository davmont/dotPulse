/**
 * Draws the frappe-gantt charts that GanttRenderer::render() describes in
 * <script type="application/json" class="dp-gantt-data"> blocks (was an inline
 * script per chart). The task rows arrive HTML-escaped from the server, since
 * frappe-gantt inserts them with innerHTML; do not escape them again here.
 * Charts the user may change save dragged bars (see saveChange()). Esc during a drag
 * puts the bar back without saving; after a save, "Undo" saves the previous dates.
 */
(function () {
	'use strict';

	var charts = {};
	// Per chart: a mouse button is down on it (dragging), and Esc cancelled that drag.
	var drags = {};

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

	/** The line under a chart: a message, and optionally a button (label, onclick). */
	function status(id, text, label, action) {
		var el = document.querySelector('.dp-gantt-status[data-for="' + id + '"]');
		if (!el) {
			return;
		}
		el.textContent = text || '';
		if (label && action) {
			var button = document.createElement('button');
			button.type = 'button';
			button.className = 'dp-gantt-undo';
			button.textContent = label;
			button.style.marginLeft = '8px';
			button.style.padding = '0 8px';
			button.style.cursor = 'pointer';
			button.addEventListener('click', function () {
				button.disabled = true;
				action();
			});
			el.appendChild(button);
		}
	}

	/** Draw a chart again from its rows; refresh() starts from the first day, so keep the scroll. */
	function redraw(id, rows) {
		var box = charts[id].$svg && charts[id].$svg.parentElement;
		var left = box ? box.scrollLeft : 0;
		charts[id].refresh(JSON.parse(JSON.stringify(rows)));
		if (box) {
			box.scrollLeft = left;
		}
	}

	/**
	 * Charts with a data-save-url save what is dragged: a bar's dates (moved or resized)
	 * or its progress, for task bars (ids "12" or "t12"; project bars only move back).
	 * When the server refuses, the chart goes back to what it showed; when other tasks may
	 * have moved with it (dependencies, task groups), the page reloads. Then the line under
	 * the chart offers to undo the change, which saves the bar's previous values (isUndo:
	 * draw them once saved, and offer nothing more); across a reload, see noteAfterReload().
	 */
	function saveChange(block, id, rows, task, fields, isUndo) {
		var url = block.getAttribute('data-save-url');
		var m = /^t?(\d+)$/.exec(String(task.id));
		var undo = function (message) {
			redraw(id, rows);
			status(id, message);
		};
		if (!isUndo && drags[id] && drags[id].cancelled) {
			return; // Esc already put the chart back.
		}
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
				// Keep the saved state, so a later refusal goes back to it.
				var before = null;
				rows.forEach(function (row) {
					if (String(row.id) === String(task.id)) {
						before = {};
						if (fields.start) {
							before.start = row.start;
							before.end = row.end;
							row.start = fields.start;
							row.end = fields.end;
						}
						if (fields.progress !== undefined) {
							before.progress = row.progress;
							row.progress = fields.progress;
						}
					}
				});
				if (result.reload) {
					remember(id, { task: String(task.id), before: isUndo ? null : before });
					window.location.reload();
				} else if (isUndo) {
					redraw(id, rows);
					status(id, block.getAttribute('data-undone') || '');
				} else if (before && block.getAttribute('data-undo')) {
					status(id, block.getAttribute('data-saved') || '', block.getAttribute('data-undo'), function () {
						saveChange(block, id, rows, task, before, true);
					});
				} else {
					status(id, block.getAttribute('data-saved') || '');
				}
				return;
			}
			undo((result && result.message) || block.getAttribute('data-error') || 'Error');
		});
	}

	/**
	 * Esc while dragging a bar or its progress handle puts the chart back as it was. The
	 * chart still reports the change when the button is released; saveChange() ignores it.
	 */
	function watchDrags(id) {
		var svg = document.getElementById(id);
		drags[id] = { down: false, cancelled: false };
		// Capture: runs before frappe-gantt's own mousedown handler.
		svg.addEventListener('mousedown', function () {
			drags[id].down = true;
			drags[id].cancelled = false;
		}, true);
		document.addEventListener('mouseup', function () {
			drags[id].down = false;
		});
	}

	document.addEventListener('keydown', function (event) {
		if (event.key !== 'Escape' && event.key !== 'Esc') {
			return;
		}
		Object.keys(drags).forEach(function (id) {
			var d = drags[id];
			if (d.down && !d.cancelled && d.rows) {
				d.cancelled = true;
				redraw(id, d.rows);
				status(id, d.block.getAttribute('data-cancelled') || '');
				event.preventDefault();
			}
		});
	});

	/**
	 * What to say under a chart after the page reloads to show a saved change: kept for the
	 * tab in sessionStorage (which may be unavailable; the message is then lost).
	 */
	var NOTE = 'dpGanttNote';

	function remember(id, note) {
		note.chart = id;
		note.page = window.location.href;
		try {
			window.sessionStorage.setItem(NOTE, JSON.stringify(note));
		} catch (e) {
			// No storage: no message after the reload.
		}
	}

	function noteAfterReload(block, id, rows) {
		var note = null;
		try {
			note = JSON.parse(window.sessionStorage.getItem(NOTE) || 'null');
			if (note && note.chart === id) {
				window.sessionStorage.removeItem(NOTE);
			}
		} catch (e) {
			return;
		}
		if (!note || note.chart !== id || note.page !== window.location.href) {
			return;
		}
		if (!note.before) {
			status(id, block.getAttribute('data-undone') || '');
		} else if (block.getAttribute('data-undo')) {
			status(id, block.getAttribute('data-saved') || '', block.getAttribute('data-undo'), function () {
				saveChange(block, id, rows, { id: note.task }, note.before, true);
			});
		}
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
			watchDrags(id);
			drags[id].rows = rows;
			drags[id].block = block;
			noteAfterReload(block, id, rows);
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
