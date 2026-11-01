/**
 * @jest-environment jsdom
 */
const fs = require('fs');
const path = require('path');

const load = (file) => window.eval(fs.readFileSync(path.resolve(__dirname, '../../../js', file), 'utf8'));

const card = (id, percent, editable = true) =>
	`<article class="dp-card${editable ? ' dp-card-editable' : ''}" data-task="${id}">` +
	`<span class="dp-card-percent">${percent}%</span>` +
	(editable ? '<button class="dp-move" data-dir="-1"></button><button class="dp-move" data-dir="1"></button>' : '') +
	'</article>';

const html = `<meta name="csrf-token" content="tok123">
<div><span class="dp-board-status"></span>
<div class="dp-board" data-dp-board-url="?m=tasks&amp;a=do_board_move&amp;suppressHeaders=1" data-dp-board-error="Could not move">
 <section class="dp-col" data-col="todo"><h3>To do <span class="dp-count">2</span></h3><div class="dp-cards">${card(1, 0)}${card(2, 0)}</div></section>
 <section class="dp-col" data-col="doing"><h3>Doing <span class="dp-count">1</span></h3><div class="dp-cards">${card(3, 40, false)}</div></section>
 <section class="dp-col" data-col="done"><h3>Done <span class="dp-count">0</span></h3><div class="dp-cards"></div></section>
</div></div>`;

const reply = (status, body) => Promise.resolve({ json: () => Promise.resolve(body), status });
const ids = (col) => Array.from(document.querySelectorAll(`.dp-col[data-col="${col}"] .dp-card`)).map((c) => c.getAttribute('data-task'));
const count = (col) => document.querySelector(`.dp-col[data-col="${col}"] .dp-count`).textContent;
const get = (id) => document.querySelector(`.dp-card[data-task="${id}"]`);

beforeAll(() => {
	document.head.innerHTML = '';
	document.body.innerHTML = html;
	load('dp-board.js');
});

beforeEach(() => {
	document.body.innerHTML = html;
	window.dpBoard.init(document.querySelector('.dp-board'));
	window.fetch = jest.fn(() => reply(200, { status: 'ok', percent: 10 }));
});

test('moving a card posts the task, the column and the CSRF token', async () => {
	const ok = await window.dpBoard.move(get(1), 'doing');
	expect(ok).toBe(true);
	expect(window.fetch).toHaveBeenCalledTimes(1);
	const [url, options] = window.fetch.mock.calls[0];
	expect(url).toBe('?m=tasks&a=do_board_move&suppressHeaders=1');
	expect(options.method).toBe('POST');
	const body = new URLSearchParams(options.body);
	expect(body.get('task_id')).toBe('1');
	expect(body.get('column')).toBe('doing');
	expect(body.get('csrf_token')).toBe('tok123');
});

test('the card moves, the counts follow and the progress comes from the server', async () => {
	await window.dpBoard.move(get(1), 'doing');
	expect(ids('todo')).toEqual(['2']);
	expect(ids('doing')).toEqual(['3', '1']);
	expect(count('todo')).toBe('1');
	expect(count('doing')).toBe('2');
	expect(get(1).querySelector('.dp-card-percent').textContent).toBe('10%');
});

test('a refused move goes back to its place and shows the message', async () => {
	window.fetch = jest.fn(() => reply(403, { status: 'error', message: 'You may not edit this task.' }));
	const ok = await window.dpBoard.move(get(1), 'done');
	expect(ok).toBe(false);
	expect(ids('todo')).toEqual(['1', '2']);
	expect(ids('done')).toEqual([]);
	expect(count('done')).toBe('0');
	expect(document.querySelector('.dp-board-status').textContent).toBe('You may not edit this task.');
});

test('a network error or a non-JSON answer also undoes the move', async () => {
	window.fetch = jest.fn(() => Promise.reject(new Error('offline')));
	expect(await window.dpBoard.move(get(2), 'done')).toBe(false);
	expect(ids('todo')).toEqual(['1', '2']);
	expect(document.querySelector('.dp-board-status').textContent).toBe('Could not move');
	window.fetch = jest.fn(() => Promise.resolve({ json: () => Promise.reject(new SyntaxError('html')) }));
	expect(await window.dpBoard.move(get(2), 'done')).toBe(false);
	expect(ids('todo')).toEqual(['1', '2']);
});

test('cards the user may not edit, unknown columns and the same column do not post', async () => {
	expect(await window.dpBoard.move(get(3), 'done')).toBe(false);
	expect(await window.dpBoard.move(get(1), 'archive')).toBe(false);
	expect(await window.dpBoard.move(get(1), 'todo')).toBe(false);
	expect(window.fetch).not.toHaveBeenCalled();
	expect(ids('doing')).toEqual(['3']);
});

test('arrow buttons step one column, and not past the ends', async () => {
	get(1).querySelector('.dp-move[data-dir="1"]').click();
	await new Promise((r) => setTimeout(r, 0));
	expect(ids('doing')).toContain('1');
	expect(window.fetch.mock.calls[0][1].body).toContain('column=doing');
	expect(await window.dpBoard.step(get(2), -1)).toBe(false);
	expect(window.fetch).toHaveBeenCalledTimes(1);
});

test('dropping a dragged card on a column moves it there', async () => {
	const fire = (el, type) => {
		const ev = new Event(type, { bubbles: true, cancelable: true });
		el.dispatchEvent(ev);
		return ev;
	};
	fire(get(2), 'dragstart');
	const over = fire(document.querySelector('.dp-col[data-col="done"] h3'), 'dragover');
	expect(over.defaultPrevented).toBe(true);
	fire(document.querySelector('.dp-col[data-col="done"] .dp-cards'), 'drop');
	await new Promise((r) => setTimeout(r, 0));
	expect(ids('done')).toEqual(['2']);
	expect(window.fetch.mock.calls[0][1].body).toContain('column=done');
	// A card that cannot move does not start a drag.
	fire(get(3), 'dragstart');
	fire(document.querySelector('.dp-col[data-col="todo"]'), 'drop');
	expect(ids('doing')).toEqual(['3']);
});
