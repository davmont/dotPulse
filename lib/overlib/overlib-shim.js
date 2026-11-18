/**
 * overlib-shim.js — lightweight CSS/JS replacement for the abandoned overLIB library.
 *
 * Supports the call patterns used by dotProject:
 *   overlib(html)
 *   overlib(html, CAPTION, 'Title')
 *   overlib(html, CAPTION, 'Title', CENTER)
 *   overlib(html, CAPTION, 'Title', LEFT, WIDTH, 400)
 *
 * nd() hides the popup.
 * Colours come from the theme's variables where it has them (dotpulse, dotpulse-dark), else
 * the shim's own light colours.
 * All other overlib constants are accepted but silently ignored.
 */
(function () {
  'use strict';

  // -- Constants (values chosen to not collide with normal strings) ----------
  var CAPTION       = 1;
  var STICKY        = 2;
  var ABOVE         = 3;
  var BELOW         = 4;
  var LEFT          = 5;
  var RIGHT         = 6;
  var CENTER        = 7;
  var WIDTH         = 8;
  var HEIGHT        = 9;
  var BGCOLOR       = 10;
  var FGCOLOR       = 11;
  var TEXTFONTCLASS = 12;
  var WRAP          = 13;
  var NOJSCHECK     = 14;

  // Expose constants globally so inline onmouseover strings can reference them.
  var consts = {
    CAPTION: CAPTION, STICKY: STICKY, ABOVE: ABOVE, BELOW: BELOW,
    LEFT: LEFT, RIGHT: RIGHT, CENTER: CENTER, WIDTH: WIDTH, HEIGHT: HEIGHT,
    BGCOLOR: BGCOLOR, FGCOLOR: FGCOLOR, TEXTFONTCLASS: TEXTFONTCLASS,
    WRAP: WRAP, NOJSCHECK: NOJSCHECK
  };
  for (var k in consts) {
    if (Object.prototype.hasOwnProperty.call(consts, k)) {
      window[k] = consts[k];
    }
  }

  // -- Tooltip element -------------------------------------------------------
  var _el = null;

  function _getEl() {
    if (_el) { return _el; }
    _el = document.createElement('div');
    _el.id = 'dp-overlib-popup';
    _el.style.cssText = [
      'position:fixed',
      'z-index:99999',
      'display:none',
      'max-width:420px',
      'background:var(--dp-surface, #fff)',
      'border:1px solid var(--dp-line, #aaa)',
      'border-radius:4px',
      'box-shadow:0 4px 12px var(--dp-shadow, rgba(0,0,0,.18))',
      'padding:0',
      'font-size:13px',
      'color:var(--dp-ink, #333)',
      'pointer-events:none'
    ].join(';');
    document.body.appendChild(_el);
    return _el;
  }

  function _position(el, ev, align) {
    var x = (ev ? ev.clientX : 0) + 14;
    var y = (ev ? ev.clientY : 0) + 14;
    el.style.left = x + 'px';
    el.style.top  = y + 'px';
  }

  // -- Public API ------------------------------------------------------------

  /**
   * Show a tooltip popup.
   * @param {string} content  HTML content.
   * @param {...*}   args     Optional overlib flags and their parameters.
   * @returns {boolean} false (to cancel browser default on mouseover).
   */
  window.overlib = function (content) {
    var args    = Array.prototype.slice.call(arguments, 1);
    var caption = '';
    var width   = null;

    for (var i = 0; i < args.length; i++) {
      switch (args[i]) {
        case CAPTION:
          caption = args[++i] || '';
          break;
        case WIDTH:
          width = parseInt(args[++i], 10) || null;
          break;
        // CENTER / LEFT / RIGHT / ABOVE / BELOW / STICKY etc. — ignored
        default:
          break;
      }
    }

    var el = _getEl();

    var html = '';
    if (caption) {
      html += '<div style="background:var(--dp-primary, #1976d2);color:var(--dp-on-primary, #fff);padding:5px 10px;'
            + 'border-radius:4px 4px 0 0;font-weight:600;">'
            + caption + '</div>';
    }
    html += '<div style="padding:8px 10px;">' + content + '</div>';
    el.innerHTML = html;

    if (width) { el.style.maxWidth = width + 'px'; }
    else        { el.style.maxWidth = '420px'; }

    el.style.display = 'block';

    // Follow the mouse while visible.
    document.addEventListener('mousemove', _onMove);
    return false;
  };

  function _onMove(ev) {
    var el = _getEl();
    if (el.style.display === 'none') {
      document.removeEventListener('mousemove', _onMove);
      return;
    }
    var x = ev.clientX + 16;
    var y = ev.clientY + 16;
    // Keep within viewport.
    var vw = window.innerWidth  || document.documentElement.clientWidth;
    var vh = window.innerHeight || document.documentElement.clientHeight;
    if (x + el.offsetWidth  > vw) { x = ev.clientX - el.offsetWidth  - 4; }
    if (y + el.offsetHeight > vh) { y = ev.clientY - el.offsetHeight - 4; }
    el.style.left = x + 'px';
    el.style.top  = y + 'px';
  }

  /**
   * Hide the popup (mapped from onmouseout="nd()").
   */
  window.nd = function () {
    var el = _getEl();
    el.style.display = 'none';
    document.removeEventListener('mousemove', _onMove);
  };

}());
