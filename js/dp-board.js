/**
 * Project board (modules/projects/vw_board.php): moves task cards between the To do,
 * In progress and Done columns by drag and drop or with each card's arrow buttons, and
 * saves the move by posting to the board's data-dp-board-url (tasks/do_board_move.php),
 * which sets the task's progress. The card moves at once; if the server refuses, it goes
 * back and the board's status line says why.
 *
 * Markup: .dp-board[data-dp-board-url] > .dp-col[data-col] > h3 .dp-count + .dp-cards >
 * .dp-card[data-task] (.dp-card-editable cards move; .dp-card-percent shows the progress).
 */
(function () {
	'use strict';

	var COLUMNS = ['todo', 'doing', 'done'];

	function columnOf(card) {
		var col = card.closest('.dp-col');
		return col ? col.getAttribute('data-col') : null;
	}

	function column(board, name) {
		return board.querySelector('.dp-col[data-col="' + name + '"]');
	}

	function updateCounts(board) {
		Array.prototype.forEach.call(board.querySelectorAll('.dp-col'), function (col) {
			var count = col.querySelector('.dp-count');
			if (count) {
				count.textContent = String(col.querySelectorAll('.dp-card').length);
			}
		});
	}

	function setStatus(board, text) {
		var status = board.parentNode && board.parentNode.querySelector('.dp-board-status');
		if (status) {
			status.textContent = text || '';
		}
	}

	function csrfToken() {
		var meta = document.querySelector('meta[name="csrf-token"]');
		return meta ? meta.getAttribute('content') : '';
	}

	// Puts the card in the column's list, keeping where it was if the move is undone.
	function place(board, card, name, before) {
		var list = column(board, name).querySelector('.dp-cards');
		list.insertBefore(card, before && before.parentNode === list ? before : null);
		updateCounts(board);
	}

	/**
	 * Moves a card to a column and saves it. Returns a promise that resolves to true when
	 * the server accepted the move (false when it was refused and undone).
	 */
	function move(card, name) {
		var board = card.closest('.dp-board');
		var from = columnOf(card);
		if (!board || !card.classList.contains('dp-card-editable') || COLUMNS.indexOf(name) === -1 || name === from) {
			return Promise.resolve(false);
		}
		var next = card.nextElementSibling;
		place(board, card, name, null);
		card.classList.add('dp-card-saving');
		setStatus(board, '');
		var body = new URLSearchParams();
		body.append('task_id', card.getAttribute('data-task'));
		body.append('column', name);
		body.append('csrf_token', csrfToken());
		return fetch(board.getAttribute('data-dp-board-url').replace(/&amp;/g, '&'), {
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
			card.classList.remove('dp-card-saving');
			if (result && result.status === 'ok') {
				var percent = card.querySelector('.dp-card-percent');
				if (percent) {
					percent.textContent = result.percent + '%';
				}
				return true;
			}
			place(board, card, from, next);
			setStatus(board, (result && result.message) || board.getAttribute('data-dp-board-error') || 'Error');
			return false;
		});
	}

	function step(card, dir) {
		var i = COLUMNS.indexOf(columnOf(card)) + dir;
		return i >= 0 && i < COLUMNS.length ? move(card, COLUMNS[i]) : Promise.resolve(false);
	}

	var dragged = null;

	function init(board) {
		board.addEventListener('click', function (ev) {
			var button = ev.target.closest('.dp-move');
			if (button) {
				ev.preventDefault();
				var card = button.closest('.dp-card');
				step(card, parseInt(button.getAttribute('data-dir'), 10)).then(function () {
					// Keep the keyboard on the card's same arrow after it moved.
					var again = card.querySelector('.dp-move[data-dir="' + button.getAttribute('data-dir') + '"]');
					if (again) {
						again.focus();
					}
				});
			}
		});
		board.addEventListener('dragstart', function (ev) {
			var card = ev.target.closest && ev.target.closest('.dp-card-editable');
			if (!card) {
				return;
			}
			dragged = card;
			card.classList.add('dp-card-dragging');
			if (ev.dataTransfer) {
				ev.dataTransfer.effectAllowed = 'move';
				ev.dataTransfer.setData('text/plain', card.getAttribute('data-task'));
			}
		});
		board.addEventListener('dragend', function () {
			if (dragged) {
				dragged.classList.remove('dp-card-dragging');
			}
			dragged = null;
			Array.prototype.forEach.call(board.querySelectorAll('.dp-col-over'), function (c) {
				c.classList.remove('dp-col-over');
			});
		});
		board.addEventListener('dragover', function (ev) {
			var col = ev.target.closest && ev.target.closest('.dp-col');
			if (dragged && col) {
				ev.preventDefault();
				col.classList.add('dp-col-over');
			}
		});
		board.addEventListener('dragleave', function (ev) {
			var col = ev.target.closest && ev.target.closest('.dp-col');
			if (col && !col.contains(ev.relatedTarget)) {
				col.classList.remove('dp-col-over');
			}
		});
		board.addEventListener('drop', function (ev) {
			var col = ev.target.closest && ev.target.closest('.dp-col');
			if (dragged && col) {
				ev.preventDefault();
				col.classList.remove('dp-col-over');
				move(dragged, col.getAttribute('data-col'));
			}
		});
	}

	function initAll() {
		Array.prototype.forEach.call(document.querySelectorAll('.dp-board'), init);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initAll);
	} else {
		initAll();
	}

	window.dpBoard = { move: move, step: step, init: init };
})();
