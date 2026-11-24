# Writing secure code

dotPulse went through a full security audit, and the code now follows a small
set of rules. This page lists them: for each one, the helper to use, a short
example and the pattern it replaces. New code and changes to old code must
follow them; two of the rules are checked in CI.

Most helpers are in
[`includes/main_functions.php`](https://github.com/davmont/dotPulse/blob/devel/includes/main_functions.php),
`CAppUI` in [`classes/ui.class.php`](https://github.com/davmont/dotPulse/blob/devel/classes/ui.class.php)
and `DBQuery` in [`classes/query.class.php`](https://github.com/davmont/dotPulse/blob/devel/classes/query.class.php).
How a request reaches a module file is described in
[How a request flows](index.md#how-a-request-flows).

## Every file starts with a guard

A module file runs only when `index.php` (or another entry point) includes it.
The first lines stop a direct request:

```php
<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
```

The web server rules refuse `.php` files under `modules/`, `classes/` and the
other code folders too (see
[deployment-security.md](https://github.com/davmont/dotPulse/blob/devel/docs/deployment-security.md)),
but the guard protects a site where those rules are missing.

## Request input

Never index `$_GET`, `$_POST`, `$_REQUEST` or `$_COOKIE` directly. Read each
value with a helper and give it a type before you use it.

| Helper | Returns |
|---|---|
| `dPgetParam($arr, $name, $def = null)` | The value as sent, or `$def` when it is missing. No cleaning: it can be any string, or an array when the request sends `name[]=...`. |
| `dPgetIntParam($arr, $name, $def = 0)` | The value cast to `int`, or `(int)$def`. Use it for ids and every other number. |
| `dPgetCleanParam($arr, $name, $def = null)` | The value passed through `dPsanitiseHTML()`: htmLawed in `safe` mode, which removes scripts, event attributes and dangerous URL schemes, and also the `link`, `meta`, `style` and `template` elements; arrays are cleaned item by item. For text fields that may hold HTML. It is not escaping: the result still needs the SQL and output rules below. |
| `dPdepartmentParam($arr, $name = 'department')` | The department filter of the project lists: `company_<id>` or a department id as `int`. |
| `dPintList($ids)` | Not a reader: turns a comma-separated list of ids into integers (`'3,x,5'` becomes `'3,0,5'`). |
| `dPvalidateOrder($value, $allowed, $default)` | Not a reader: `$value` if it is in `$allowed`, otherwise `$default`. For sort columns (see [SQL](#sql)). |

There is no array reader. Cast each item yourself, as
`modules/mantis/do_mantis_aed.php` does:

```php
$issue_ids = array_values(array_unique(array_filter(array_map('intval',
	(array)dPgetParam($_POST, 'issues', array())))));
```

An example from `modules/links/addedit.php`:

```php
$link_id = intval(dPgetParam($_GET, 'link_id', 0));
```

It replaces the old pattern:

```php
$link_id = $_GET['link_id'];   // wrong: raw, untyped, and fails the raw-input check
```

### The raw-input check

`tools/checks/check-raw-input.sh` (`make raw-input`, run in CI) counts the
lines in each first-party `.php` and `.inc` file that index a superglobal
(`$_GET[`, `$_POST[`, `$_REQUEST[`, `$_COOKIE[`). `lib/`, `vendor/`, `tests/` and
`docker/` are not checked. The counts allowed are in
`tools/checks/raw-input-baseline.txt`: the old add-on modules still hold a
few hundred such reads. The check fails when a file has more than its
baseline, and a file that is not listed is allowed none.

When you remove raw reads from a file, record the lower count:

```sh
tools/checks/check-raw-input.sh --update
```

Passing the whole array, as in `dPgetParam($_POST, 'name')` or
`$obj->bind($_POST)`, is not a raw read.

### bind() and the fields a user must not set

`CDpObject::bind($_POST)` copies every posted field whose name matches a
property of the object. It casts the primary key to `int`, nothing else. After
a bind, set again every field the user must not choose. From
`modules/files/do_file_aed.php`:

```php
if (!$obj->bind($_POST)) {
	$AppUI->setMsg($obj->getError(), UI_MSG_ERROR);
	$AppUI->redirect($redirect);
}
// Where the file lives on disk, its size and type come from the stored record or the
// upload below, never from the request.
$obj->file_real_filename = $file_id ? $oldObj->file_real_filename : null;
$obj->file_size = $file_id ? $oldObj->file_size : null;
$obj->file_type = $file_id ? $oldObj->file_type : null;
```

### File names from the request

A name that selects a file to include goes through `$AppUI->checkFileName()`,
which sends the user to *Access Denied* when the name contains a dot, a slash,
a backslash, a quote, a parenthesis, `;` or `$`. `index.php` already does this
for `m`, `a`, `u` and `dosql`.

## SQL

Build queries with `DBQuery`. Never put a request value into SQL as it came.
You have three safe ways:

| Value | Do this |
|---|---|
| Any value | Pass it as a parameter: `$q->addWhere('user_username = ?', $name)`. `addInsert()` and `addUpdate()` always send their values as parameters. MySQL receives a prepared statement, so a `?` inside a quoted string in the same query is not a placeholder. |
| A number | Cast it: `$q->addWhere('link_project = ' . (int)$project_id)`. |
| A string you must put in the SQL text | Quote it: `$q->quote($value)` returns the value escaped and in quotes. |

`addWhere()` takes the parameters as its second argument, a single value or an
array, in the order of the `?` marks. From `modules/admin/do_user_aed.php`:

```php
$q->addWhere('user_username = ?', $obj->user_username);
```

A search with `LIKE`, from `modules/links/index_table.php`:

```php
if (!empty($search)) {
	$like = $q->quote('%' . $search . '%');
	$q->addWhere("(link_name like $like OR link_description like $like)");
}
if ($project_id) {
	$q->addWhere('link_project = ' . (int)$project_id);
}
```

Column names, `ORDER BY` and `GROUP BY` can't be parameters. Check them
against a list with `dPvalidateOrder()`, as `modules/companies/vw_active.php`
does:

```php
$sort = dPvalidateOrder(dPgetCleanParam($_GET, 'sort', 'project_name'),
	array('project_name', 'project_priority'), 'project_name');
```

A list of ids for `IN (...)`: clean it with `dPintList()`, or build one `?` per
item and pass the array.

The wrong pattern:

```php
$q->addWhere("project_name = '" . $_POST['name'] . "'");   // wrong: SQL injection
$q->addOrder($_GET['orderby']);                              // wrong: injection in ORDER BY
```

`DBQuery::sanitise()` and `quote_sanitised()` only strip some characters. Don't
use them for new code. The same goes for the old `db_loadList()`-style
functions of `includes/db_connect.php` with SQL built by hand.

## Output

Escape every value that comes from the database or the request where you write
it into the page. Pick the escaping for the place:

| Where the value goes | Use |
|---|---|
| HTML text or a quoted attribute | `dPhtml($value)` |
| An `href` or `src` attribute | `dPhtml(dPsafeUrl($value))`: `dPsafeUrl()` returns `''` for any scheme other than `http`, `https`, `mailto` and `ftp` (no `javascript:`, `data:`) |
| A JavaScript value in a `<script>` block | `dPjs($value)`: a string literal with its quotes, with `<`, `>`, `&` and quotes as `\u` escapes |
| An event attribute (`data-dp-*`) | `dPaction()`, which escapes for you (see [Content-Security-Policy](#content-security-policy)) |
| A JSON data block | `json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP)` |
| The fields of a form | `dPformSafe($value)`, or `dPformSafe($obj)` to escape every property of an object or array |

`dPhtml()` and `dPformSafe()` use `htmlentities()` with `ENT_QUOTES` in the
page's character set.

From `modules/admin/vw_usr_api_tokens.php` and `modules/contacts/view.php`:

```php
<td><?php echo dPhtml($t['token_name']); ?></td>
<a href="<?php echo dPhtml(dPsafeUrl(@$row->contact_url)); ?>">
```

It replaces:

```php
<td><?php echo $t['token_name']; ?></td>   <!-- wrong: stored XSS -->
```

### Translated text: `$AppUI->_()`

`$AppUI->_($text, $flags)` translates a fixed string of the interface and
escapes the result. Don't pass data through it: it looks the value up in the
translations. The second argument chooses the escaping:

| Flag | What it does | Use it |
|---|---|---|
| `UI_OUTPUT_HTML` (the default, `0`) | `htmlentities()` with `ENT_QUOTES` | In HTML text and attributes. |
| `UI_OUTPUT_JS` | `addslashes()` | Inside a quoted JavaScript string. It does not escape `<` or `</script>`: use `dPjs()` for anything that is not a fixed string. |
| `UI_OUTPUT_RAW` | No escaping | When something else escapes the text later, or it is not HTML: arguments of `dPaction()`, `json_encode()`, mail, comparisons. Without it the text would be escaped twice, and a confirm box would show `&apos;`. |
| `UI_OUTPUT_URI` | Spaces become `%20` | Rarely. |

A case flag can be added with `|`: `UI_CASE_UPPER`, `UI_CASE_LOWER`,
`UI_CASE_UPPERFIRST`. `$AppUI->setMsg($msg)` translates and escapes its message
the same way.

```php
echo dPaction('history.back', array(), null,
	array('confirm' => $AppUI->_('Are you sure you want to cancel?', UI_OUTPUT_RAW)));
```

## Forms and CSRF

Every POST from a logged-in session must carry the session's CSRF token, a
field named `csrf_token`. `index.php` checks it with
`$AppUI->verifyCsrfToken()` on every POST, before the module runs. A missing or
wrong token sends the user to *Access Denied* with *Invalid or missing security
token.*

You rarely add the token yourself:

| How the request is sent | How it gets the token |
|---|---|
| A `<form method="post">` in the page | `index.php` adds the hidden field to every form tag with `method="post"` while the page is sent (`CAppUI::injectCsrfToken()`, an output buffer). |
| A form built by JavaScript, or sent with `form.submit()` | `js/dp-ui.js` adds the field from the `<meta name="csrf-token">` tag that each theme's header prints (`$AppUI->getCsrfMeta()`). |
| `fetch()` or `XMLHttpRequest` | Your script reads the meta tag and sends `csrf_token` in the body, as `js/dp-board.js` does. |
| Output that skips the output buffer | Print `$AppUI->getCsrfInput()` inside the form. |

From `js/dp-board.js`:

```js
var meta = document.querySelector('meta[name="csrf-token"]');
body.append('csrf_token', meta ? meta.getAttribute('content') : '');
```

### What must be a POST

A request that changes data must be a POST. `index.php` enforces it for
write handlers: a request with `dosql`, and a view whose name starts with
`do_`, `dosql` or `domodsql`, is refused unless it is a POST with the token and
the user may add, edit or (with `del=1`) delete in the module. Module
configuration pages (`a=configure`) need edit rights on System Admin.

A GET link that deletes or changes something can be triggered by another site.
Use a form, or a link that posts with `dpPost()` from `js/dp-ui.js`, as
`modules/macroprojects/addproject.php` does:

```php
<a href="#"<?php echo dPaction('dpPost', array('?m=macroprojects&a=addproject&macroproject_id=' . (int)$macroproject_id,
	array('project' => (int)$row['project_id'], 'add' => 'true'))); ?>>
```

It replaces `<a href="?m=macroprojects&a=addproject&project=5&add=true">`.

## Permissions

`index.php` checks only the module: it sets `$canAccess`, `$canRead`,
`$canEdit`, `$canAuthor` and `$canDelete` for `m`, and refuses a module the user
can't access. Each page must check the **record** it shows or changes.

| Helper | What it does |
|---|---|
| `getPermission($module, $op, $item_id = 0)` | `true` when the user may do `$op` (`access`, `view`, `add`, `edit`, `delete`) in the module, on the item if you give its id. For `view`, a task also needs its project, a project its company. |
| `$AppUI->redirect('m=public&a=access_denied')` | Sends the user to *Access Denied* and stops the script. |
| `dPrequireWritePermission($module, $id, $del = false)` | For write handlers: stops unless the user may delete (`$del`), edit (`$id` > 0) or add (`$id` = 0). |
| `dPrequireProjectEdit($project_id, $records)` | Stops unless the user may edit the project and every record listed belongs to it (`dPrecordList()` builds the list). |
| `CTask::canAccess($user_id)` | A task's own access level: public, protected, participant, private, privileged. Check it after `getPermission()` for tasks. |

A view page, from `modules/links/addedit.php`:

```php
$link_id = intval(dPgetParam($_GET, 'link_id', 0));
$canEdit = getPermission($m, 'edit', $link_id);
if (!(($canEdit && $link_id) || ($canAuthor && !($link_id)))) {
	$AppUI->redirect('m=public&a=access_denied');
}
```

Its write handler, `modules/links/do_link_aed.php`:

```php
$del = intval(dPgetParam($_POST, 'del', 0));
dPrequireWritePermission('links', dPgetParam($_POST, 'link_id', 0), $del);
```

The write handler must check again: `index.php` checks the module, and anyone
can post any id.

### Lists

A list must leave out the records the user may not see. The roles can allow
or deny single records, so don't filter on the module permission alone. The
`CDpObject` classes (`classes/dp.class.php`) build the filter from the user's
allowed and denied items (`getAllowedItems()`, `getDeniedItems()` in
`classes/permissions.class.php`):

| Method | Gives |
|---|---|
| `setAllowedSQL($uid, $q, $index = null, $key = null)` | Adds the filter to a `DBQuery`. `$index` is the column of your query that holds the object's id; the method joins the object's table on it. |
| `getAllowedSQL($uid, $index = null)` | The `WHERE` conditions as an array of strings, to add yourself. |
| `getAllowedRecords($uid, $fields, $orderby, $index)` | The allowed records, as a hash list. |
| `getDeniedRecords($uid)` | The ids denied to the user. |

From `modules/links/index_table.php`:

```php
$project->setAllowedSQL($AppUI->user_id, $q, 'link_project');
$task->setAllowedSQL($AppUI->user_id, $q, 'link_task and task_project = link_project');
```

The REST API applies the same filters; see `CApi::taskQuery()` in
[`classes/api.class.php`](https://github.com/davmont/dotPulse/blob/devel/classes/api.class.php).

## Files and uploads

Uploaded files live under `files/`, which the web server must refuse to serve
(`files/.htaccess` does it on Apache; nginx needs the rule in
[deployment-security.md](https://github.com/davmont/dotPulse/blob/devel/docs/deployment-security.md)).
`fileviewer.php` sends them after the login and permission checks.

If your module stores files:

- keep them under `files/` (temporary files under `files/temp/`), never in a
  folder the web server serves;
- store each upload under a name you generate (the files module uses
  `uniqid(rand())`), never the name or path the user sent, and move it with
  `move_uploaded_file()`;
- build the path from the stored record with `basename()`, so an old record
  can't point outside the folder:

  ```php
  $fname = DP_BASE_DIR . '/files/' . (int)$file['file_project'] . '/' . basename($file['file_real_filename']);
  ```

- serve the file through PHP, after `getPermission()` on the record, with
  `Content-Disposition: attachment`.

A file a module includes from another application's installation goes through
`dPexternalAppFile($dir, $name)`, which refuses anything under `files/`.

## Passwords and secrets

- Hash passwords with `dPhashPassword($password)` (`password_hash()` with
  `PASSWORD_DEFAULT`) and check them with `password_verify()`. Never compare
  hashes yourself and never store a password in clear.
- Tokens and codes the user has to send back come from `random_bytes()`. Store
  only their SHA-256 (`hash('sha256', $token)`), as `classes/apitoken.class.php`
  does for API tokens.
- Never write a password, a token or a two-step code into a log, the history, a
  message or the audit log.
- Anything that checks a secret sent by the user is rate limited by address with
  `RateLimiter` (`classes/ratelimiter.class.php`):
  `new RateLimiter('name', $max_attempts, $seconds)`, then `isAllowed()` and
  `recordAttempt()` on each failure. See `CApi::authenticate()`.

### The audit log

Security events go to the audit log, which administrators read in
[System Admin › Audit log](../administration/audit-and-maintenance.md#audit-log).
Add an entry with `dPaudit()`:

```php
dPaudit($event, $details = '', $object = '', $object_id = 0, $user_id = null, $username = null);
```

- `$event` is a name from `CAuditLog::events()` in
  [`classes/auditlog.class.php`](https://github.com/davmont/dotPulse/blob/devel/classes/auditlog.class.php).
  A new event needs a new entry there, with its description, and a line in
  [docs/audit-log.md](https://github.com/davmont/dotPulse/blob/devel/docs/audit-log.md)
  and in the [audit log page](../administration/audit-and-maintenance.md#audit-log).
- `$details` is text for people, at most 1000 characters. No passwords or tokens.
- `$object` and `$object_id` name the record, for example `'users', $user_id`.
- `$user_id` and `$username` default to the logged-in user. For a failed login,
  pass `0` and the name that was tried.

A failure to write the entry goes to the PHP error log and never stops the
action. From `modules/admin/do_userrole_aed.php`:

```php
dPaudit('user.role_added', 'role ' . $user_role . ' to ' . CAuditLog::username($user_id), 'users', $user_id);
```

## Content-Security-Policy

Every application page (`index.php`) sends a Content-Security-Policy that
allows only the site's own scripts plus inline `<script>` blocks that carry the
per-request nonce. Inline event handlers (`onclick="..."`) and `javascript:`
URLs are blocked, and so are `eval()`, `new Function()` and string timers: there
is no `'unsafe-eval'`.

The policy is enforced. Browsers block anything it does not allow and report it
to `csp-report.php`. If an upgrade breaks a page, an administrator can add
`$dPconfig['csp_report_only'] = true;` to `includes/config.php`: the policy is
then sent as `Content-Security-Policy-Report-Only`, and violations are reported
but not blocked, while the page is fixed.

No file in the tree has inline JavaScript, and
`tools/checks/check-inline-js.sh` (`make inline-js`, run in CI) fails on any new
event-handler attribute, `javascript:` URL or `<script>` without a nonce.

| Piece | Where |
|---|---|
| Policy and header | `dPcspPolicy()`, `dPsendCspHeader()` in `includes/main_functions.php` |
| Nonce | `dPcspNonce()`, the same value for the whole request |
| Replacement for inline handlers | `js/dp-actions.js` (loaded on every page), with attributes built by `dPaction()` |
| Violation reports | `csp-report.php` adds a line per violation to `files/temp/csp-reports.log` (it stops growing at 5 MB); `scripts/csp-report-summary.sh` groups it |

The policy:

| Directive | Value |
|---|---|
| `default-src` | `'self'` |
| `script-src` | `'self'`, the nonce, `'report-sample'` |
| `style-src` | `'self' 'unsafe-inline'`: inline styles are allowed |
| `img-src`, `font-src` | `'self' data:` |
| `connect-src`, `frame-src`, `frame-ancestors`, `form-action`, `base-uri` | `'self'` |
| `object-src` | `'none'` |
| `report-uri` | `csp-report.php` |

### Inline event handlers and javascript: URLs

Replace the handler with `data-dp-*` attributes, written by `dPaction()`, which
JSON-encodes and HTML-escapes the values. Never build these attributes by hand.

```php
dPaction($call, $args = array(), $on = null, $extra = array())
```

`$call` is the function, `$args` its arguments, `$on` the events and `$extra`
more `data-dp-<key>` attributes: `confirm`, `href`, `submit`.

| Before | After |
|---|---|
| `<a href="javascript:popCalendar('start')">` | `<a href="#"<?php echo dPaction('popCalendar', array('start')); ?>>` |
| `<input type="button" onclick="submitIt(this.form)">` | `<input type="button"<?php echo dPaction('submitIt', array('$form')); ?>>` |
| `<select onchange="this.form.submit()">` | `<select<?php echo dPaction(null, array(), null, array('submit' => '')); ?>>` |
| `<select onchange="changeView(this.value)">` | `<select<?php echo dPaction('changeView', array('$value')); ?>>` |
| `<a href="?m=x&del=1" onclick="return confirm('Sure?')">` | `<a href="?m=x&del=1"<?php echo dPaction(null, array(), null, array('confirm' => $AppUI->_('Sure?', UI_OUTPUT_RAW))); ?>>` |
| `<input type="button" onclick="window.location='?m=x'">` | `<input type="button"<?php echo dPaction(null, array(), null, array('href' => '?m=x')); ?>>` |
| `<form onsubmit="return check(this)">` | `<form<?php echo dPaction('check', array('$this'), 'submit'); ?>>` |
| `<span onmouseover="return overlib('tip')" onmouseout="nd()">` | `<span data-dp-on="mouseover mouseout" data-dp-call-mouseover="overlib" data-dp-args-mouseover='["tip"]' data-dp-call-mouseout="nd">` (escape the JSON with `htmlspecialchars()`) |

In the confirm example, `UI_OUTPUT_RAW` keeps the text from being escaped
twice: `dPaction()` escapes it. A link that deletes should also be a POST (see
[What must be a POST](#what-must-be-a-post)).

How `js/dp-actions.js` runs them:

- `$this`, `$event`, `$form` and `$value` in the arguments stand for the
  element, the event, its form and its value.
- The function runs with the element as `this`, like an inline handler;
  returning `false` cancels the default action. A dotted name
  (`window.open`) runs on its owner.
- The default event is `change` for `<select>` and `click` for everything else;
  `data-dp-on` (the third argument of `dPaction()`) changes it, and can list
  several events separated by spaces. `load` and `unload` are not supported:
  run that code on `DOMContentLoaded`.
- The order is confirm, call, then `href` or `submit` (skipped when the call
  returned `false`). A link with `href="#"` never follows the `#`.
- Only named functions can be called. `eval`, `Function`, `setTimeout`,
  `setInterval`, `execScript` and `document` are refused. Code that was more
  than a function call becomes a named function in the page's `.js` file.

### Inline script blocks

Move the code into the module's `.js` file: `modules/<m>/<a>.js` and
`modules/<m>/<m>.module.js` are loaded automatically (see
[JavaScript](index.md#javascript)). Data the script needs from PHP goes in a
JSON data block, which the browser does not run, so it needs no nonce:

```php
<script type="application/json" id="task-data"><?php echo json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP); ?></script>
```

```js
var data = JSON.parse(document.getElementById('task-data').textContent);
```

`classes/gantt_renderer.class.php` passes the Gantt chart's data to
`js/dp-gantt.js` this way.

A block that has to stay inline carries the nonce:

```php
<script nonce="<?php echo dPcspNonce(); ?>">
```

Never print the nonce into anything a user can influence, and never add it to
a block that echoes request data.

### Finding what is left

`tools/checks/check-inline-js.sh --list modules/<module>` prints every line that
still has an event-handler attribute, a `javascript:` URL or an inline
`<script>` without a nonce. The per-file counts allowed are in
`tools/checks/inline-js-baseline.txt`, which is empty: no file may have any.
`tools/checks/check-inline-js.sh --update` rewrites the baseline.

In the sandbox, empty the report log, browse the pages, then summarise the
reports:

```sh
docker compose -f docker/compose.yml exec -T web rm -f /var/www/html/files/temp/csp-reports.log
# ... browse ...
scripts/csp-report-summary.sh docker/files/temp/csp-reports.log
```

`script-src-attr` lines are inline handlers or `javascript:` URLs;
`script-src-elem` lines are inline `<script>` blocks without the nonce. The
browser console also shows each violation, with the element.

Browsers report an inline handler or `javascript:` URL only when it runs (a
click, a change), so a crawl that only loads pages misses most of them. Search
the code as well:

```sh
grep -rnP '\son[a-z]+\s*=|javascript:|<script(?![^>]*\b(src|nonce)=)(?![^>]*application/json)' modules/<module>
```
