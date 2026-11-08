# Content-Security-Policy

Every application page (`index.php`) sends a Content-Security-Policy that
allows only the site's own scripts plus inline `<script>` blocks carrying the
per-request nonce. Inline event handlers (`onclick="..."`) and `javascript:`
URLs are not allowed.

The policy is **enforced** (roadmap Phase 7h): browsers block anything it does
not allow and report it to `csp-report.php`. If an upgrade breaks a page, set
`$dPconfig['csp_report_only'] = true;` in `includes/config.php` to send it as
`Content-Security-Policy-Report-Only` again (violations are then reported but
not blocked) while the page is fixed.

No file in the tree has inline JavaScript (the dormant modules were converted
too), and `tools/checks/check-inline-js.sh` fails CI on any new inline handler,
`javascript:` URL or `<script>` without the nonce. `eval()`, `new Function()`
and string timers are blocked as well: there is no `'unsafe-eval'`.

| Piece | Where |
|---|---|
| Policy and header | `dPcspPolicy()`, `dPsendCspHeader()` in `includes/main_functions.php` |
| Nonce | `dPcspNonce()` (same value for the whole request) |
| Replacement for inline handlers | `js/dp-actions.js` (loaded on every page), attributes built with `dPaction()` |
| Violation reports | `csp-report.php` writes `files/temp/csp-reports.log`; `scripts/csp-report-summary.sh` groups it |

## Converting a page

### Inline event handlers and `javascript:` URLs

Replace the handler with `data-dp-*` attributes, written by `dPaction()` so the
values are JSON-encoded and HTML-escaped. Never build these attributes by hand.

| Before | After |
|---|---|
| `<a href="javascript:popCalendar('start')">` | `<a href="#"<?php echo dPaction('popCalendar', array('start')); ?>>` |
| `<input type="button" onclick="submitIt(this.form)">` | `<input type="button"<?php echo dPaction('submitIt', array('$form')); ?>>` |
| `<select onchange="this.form.submit()">` | `<select<?php echo dPaction(null, array(), null, array('submit' => '')); ?>>` |
| `<select onchange="changeView(this.value)">` | `<select<?php echo dPaction('changeView', array('$value')); ?>>` |
| `<a href="?m=x&del=1" onclick="return confirm('Sure?')">` | `<a href="?m=x&del=1"<?php echo dPaction(null, array(), null, array('confirm' => $AppUI->_('Sure?'))); ?>>` |
| `<input type="button" onclick="window.location='?m=x'">` | `<input type="button"<?php echo dPaction(null, array(), null, array('href' => '?m=x')); ?>>` |
| `<form onsubmit="return check(this)">` | `<form<?php echo dPaction('check', array('$this'), 'submit'); ?>>` |
| `<span onmouseover="return overlib('tip')" onmouseout="nd()">` | `<span data-dp-on="mouseover mouseout" data-dp-call-mouseover="overlib" data-dp-args-mouseover='["tip"]' data-dp-call-mouseout="nd">` (escape the JSON with `htmlspecialchars()`) |

- `$this`, `$event`, `$form` and `$value` in the arguments stand for the
  element, the event, its form and its value.
- The called function runs with the element as `this`, like an inline handler;
  returning `false` cancels the default action.
- The default event is `change` for `<select>` and `click` for everything else;
  `data-dp-on` (third argument of `dPaction()`) changes it.
- Only named global functions can be called (`eval`, `Function`,
  `setTimeout` and `setInterval` are refused). Code that was more than a
  function call becomes a named function in the page's `.js` file.

### Inline `<script>` blocks

Prefer moving the code into the module's `.js` file (`modules/<m>/<a>.js` is
loaded automatically). Data the script needs from PHP goes in a JSON data
block, which is not executed and needs no nonce:

```php
<script type="application/json" id="task-data"><?php echo json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP); ?></script>
```

```js
var data = JSON.parse(document.getElementById('task-data').textContent);
```

A block that has to stay inline carries the nonce:

```php
<script nonce="<?php echo dPcspNonce(); ?>">
```

Never print the nonce into anything a user can influence, and never add it to
a block that echoes request data.

## Finding what is left

`tools/checks/check-inline-js.sh --list modules/<module>` prints every line that
still has an event-handler attribute, a `javascript:` URL or an inline
`<script>` without a nonce. The same script runs in CI (`make inline-js`) and
fails when a file gains such lines; after converting, record the lower counts
with `tools/checks/check-inline-js.sh --update`.

In `docker/`, browse the pages (or run the crawl) and summarise the reports:

```sh
docker compose -f docker/compose.yml exec -T web rm -f /var/www/html/files/temp/csp-reports.log
# ... browse ...
scripts/csp-report-summary.sh docker/files/temp/csp-reports.log
```

`script-src-attr` lines are inline handlers or `javascript:` URLs;
`script-src-elem` lines are inline `<script>` blocks without the nonce. The
browser console also shows each violation, with the element.

Browsers report an inline handler or `javascript:` URL only when it runs
(a click, a change), so a crawl that only loads pages misses most of them.
Search the code as well:

```sh
grep -rnP '\son[a-z]+\s*=|javascript:|<script(?![^>]*\b(src|nonce)=)(?![^>]*application/json)' modules/<module>
```
