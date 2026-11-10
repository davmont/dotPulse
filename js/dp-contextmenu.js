/**
 * Right-click (context) menus without inline JavaScript. Replaces the vendored
 * Milonic menu, which wrote inline handlers and javascript: URLs into its own
 * HTML and so could not run under the Content-Security-Policy.
 *
 * A page defines its menus and, if it has more than one, which one applies to
 * the right-clicked element:
 *
 *   dpContextMenu.define('contextMenu', [
 *       {image: './images/icons/stock_edit-16.png', text: 'Edit', call: 'rightClickMenuEditProject'},
 *   ]);
 *   dpContextMenu.choose = function (target) { return 'contextMenu'; };  // or null: no menu
 *
 * Items run their named function through dp-actions (data-dp-call). As with
 * Milonic, the menu only opens while contextDisabled is false (pages toggle it
 * with setContextDisabled() when the pointer enters/leaves their table), and the
 * right-clicked element is left in the global contextObject for the functions.
 */
var contextDisabled = true;
var contextObject = null;

function setContextDisabled(state) {
	contextDisabled = !!state;
}

var dpContextMenu = (function () {
	'use strict';

	var menus = {};
	var box = null;

	// The theme's version of a colour (js/dp-theme-colors.js: dark in the dark theme).
	function tc(color, role) {
		return window.dPthemeColor ? window.dPthemeColor(color, role) : color;
	}

	function close() {
		if (box && box.parentNode) {
			box.parentNode.removeChild(box);
		}
		box = null;
	}

	function item(def) {
		var row = document.createElement('div');
		row.setAttribute('role', 'menuitem');
		row.setAttribute('data-dp-call', def.call);
		row.style.cssText = 'padding:6px 12px 6px 8px;cursor:pointer;white-space:nowrap;border:1px solid transparent;';
		row.addEventListener('mouseover', function () {
			row.style.background = tc('#ECF4F9', 'bg');
			row.style.borderColor = tc('#316AC5', 'border');
		});
		row.addEventListener('mouseout', function () {
			row.style.background = '';
			row.style.borderColor = 'transparent';
		});
		if (def.image) {
			var img = document.createElement('img');
			img.src = def.image;
			img.alt = '';
			img.style.cssText = 'vertical-align:middle;margin-right:8px;max-width:16px;max-height:16px;';
			row.appendChild(img);
		}
		row.appendChild(document.createTextNode(def.text));
		return row;
	}

	function open(name, x, y) {
		close();
		box = document.createElement('div');
		box.setAttribute('role', 'menu');
		box.className = 'dp-contextmenu';
		box.style.cssText = 'position:absolute;z-index:10000;background:' + tc('#fff', 'bg')
			+ ';border:1px solid ' + tc('#999', 'border') + ';box-shadow:2px 2px 4px #777;font-family:arial,verdana,tahoma;font-size:100%;padding:2px;';
		for (var i = 0; i < menus[name].length; i++) {
			box.appendChild(item(menus[name][i]));
		}
		document.body.appendChild(box);
		// Keep it inside the visible area.
		var maxX = window.pageXOffset + document.documentElement.clientWidth - box.offsetWidth - 4;
		var maxY = window.pageYOffset + document.documentElement.clientHeight - box.offsetHeight - 4;
		box.style.left = Math.max(0, Math.min(x, maxX)) + 'px';
		box.style.top = Math.max(0, Math.min(y, maxY)) + 'px';
	}

	document.addEventListener('contextmenu', function (e) {
		if (contextDisabled) {
			close();
			return;
		}
		var name = api.choose ? api.choose(e.target) : 'contextMenu';
		if (name && menus[name]) {
			e.preventDefault();
			contextObject = e.target;
			open(name, e.pageX, e.pageY);
		}
	});
	// Registered after dp-actions, so an item's function runs before the menu closes.
	document.addEventListener('click', function (e) {
		if (box) {
			close();
		}
	});
	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') {
			close();
		}
	});
	window.addEventListener('scroll', close);

	var api = {
		/** items: [{text, call, image}]; text is set as text, never as HTML. */
		define: function (name, items) {
			menus[name] = items;
		},
		/** Optional: function (target) returning the menu name for that element, or null. */
		choose: null,
		close: close
	};
	return api;
})();
