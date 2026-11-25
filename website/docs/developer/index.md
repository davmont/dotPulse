# Developer guide

This section is for people who change dotPulse itself or write add-on modules
for it. It explains how the code is laid out, how a page request reaches a
module, how to set up a development copy and run the checks, and what the code
must do to stay secure.

You need to know PHP and SQL. dotPulse runs on PHP 8.2 or later with MySQL or
MariaDB. Most of the code is dotProject's: plain PHP files included by one
front controller, a few classes, and no framework. Names starting with `dP`
(`dPgetParam()`, `$dPconfig`, `DP_BASE_DIR`) come from dotProject and are kept,
so existing add-on modules keep working.

## How the code is laid out

| Path | What it holds |
| --- | --- |
| `index.php` | The front controller: every page of the application goes through it. |
| `api.php` | The REST API. See [REST API](api.md). |
| `fileviewer.php` | Downloads of uploaded files. |
| `csp-report.php` | Receives the browser's Content-Security-Policy violation reports. |
| `queuescanner.php` | Runs the jobs waiting in the event queue, such as queued mail; run it from cron. |
| `base.php` | Defines `DP_BASE_DIR` and `DP_BASE_URL`. `index.php`, `api.php`, `fileviewer.php` and `queuescanner.php` include it first. |
| `classes/` | The core classes: `CAppUI` (the user's session, messages, translation; `ui.class.php`), `CDpObject` (a record of a table; `dp.class.php`), `DBQuery` (the query builder; `query.class.php`), permissions, dates, the audit log, two-step login, the Help module's Markdown reader. |
| `includes/` | Shared functions (`main_functions.php`: `dPgetParam()`, the escaping helpers), the database connection, sessions, `config-dist.php` (the template of `config.php`). |
| `functions/` | Helper functions of some core modules, `<module>_func.php`, loaded for that module's pages. |
| `modules/` | One folder per module, core and add-ons alike: companies, projects, tasks, system, the holiday calendars... See [Writing a module](modules.md). |
| `locales/` | The translations, one folder per language (`en`, `es`, and 23 more). Add-on modules may keep theirs in `modules/<module>/locales/`. |
| `style/` | The themes (`dotpulse`, `material` and the classic ones). Each has its own `header.php`, `footer.php`, `login.php`, stylesheet and `theme.json`; `dotpulse/dark.css` is dotPulse's dark palette, and `_shared/` holds what the dotPulse-based themes share. |
| `js/` | The JavaScript loaded on every page (`dp-ui.js`, `dp-actions.js`, the Gantt and board scripts). |
| `lib/` | Third-party libraries copied in by hand: ADOdb, phpGACL, jQuery, flatpickr, Chart.js... |
| `vendor/` | Libraries installed with Composer (PHPMailer, Dompdf, php-qrcode, Parsedown). It is committed, so a server needs no Composer. |
| `images/` | Icons and the brand files. |
| `files/` | Uploaded files and temporary data. The web server must be able to write to it. |
| `install/` | The installer and the database upgrades it runs. |
| `db/` | The database schema (`dotproject.sql`), the upgrade scripts and the sample data (`db/sample/`). |
| `misc/` | The debug helper `index.php` includes. |
| `website/` | This documentation. The Help module shows the same pages inside dotPulse, so a server keeps `website/docs` and `website/docs-es`. |
| `docs/` | Notes for administrators and maintainers: Content-Security-Policy, deployment, the API, the release notes, the security audit record. |
| `tools/` | Development tools: PHPStan configuration, the code checks, the translation check, the JavaScript tests, the browser checks. |
| `tests/` | PHP unit tests. |
| `docker/` | The development sandbox and the test scripts that run against it. |

A server needs the entry points in the root, the application folders (`classes/`
to `db/` above, and `misc/`) and `website/` for Help.
`tools/`, `tests/`, `docker/`, the `Makefile` and `.github/` are for
development only: `.gitattributes` leaves them out of release archives made
with `git archive`. Node.js and Python are only needed by the development tools.

### lib/ and vendor/

Both hold third-party code, but they are kept up to date differently.
`vendor/` follows `composer.json` and `composer.lock`: Dependabot proposes the
updates and `make deps-audit` looks its packages up in the advisory databases.
The libraries in `lib/` (and a few copied inside modules) were copied by hand:
nothing warns about their advisories. Their list, with versions, is
[docs/third-party.md](https://github.com/davmont/dotPulse/blob/devel/docs/third-party.md).

## How a request flows

Every page of the application is `index.php` with two parameters: `m`, the
module, and `a`, the action, which is the name of a PHP file in the module's
folder. `index.php?m=projects&a=view&project_id=1` runs
`modules/projects/view.php`. In order, `index.php`:

1. Includes `base.php`, `includes/config.php`, the shared functions, the
   database connection, `CAppUI` and the permissions, and starts the session
   (`dPsessionStart()`). The session holds one `CAppUI` object, available to
   every file as `$AppUI`.
2. Sends the security headers, including the Content-Security-Policy
   (`dPsendCspHeader()`).
3. Handles the requests that work without a login: a lost password, the
   password reset link and the login form itself. A login is rate limited and
   recorded in the audit log; with two-step login the session waits for the
   code before it counts as logged in.
4. Shows the login page and stops if the user is not logged in.
5. Reads `m` and `a`. Both go through `$AppUI->checkFileName()`, which rejects
   a name with a dot, a slash, a quote, a parenthesis, `;` or `$`, so they can
   only name a file in the module's folder. Without `m`, the page is the default view set in System
   Admin, or else the first active module the user may access. Without `a`, it
   is `index`. A third parameter, `u`, names a subfolder of the module.
6. Collects the tabs other modules add to this module's pages (see below).
7. Loads the translations, and `functions/<m>_func.php` if it exists.
8. Reads the user's rights on the module into `$canAccess`, `$canRead`,
   `$canEdit`, `$canAuthor` and `$canDelete`. Without access, or when the
   module is turned off, the user is sent to the *Access Denied* page.
9. Includes the module's class, `modules/<m>/<m>.class.php`, if it exists.
10. Checks the CSRF token of every POST request.
11. Runs the write handler named by `dosql`, if any (see below).
12. Includes the theme's `header.php`, then `modules/<m>/<a>.php`, then the
    theme's `footer.php`.

### Write handlers: dosql

A form that saves data posts to its module with a `dosql` field naming the
handler, a file in the module's folder. By convention its name is
`do_<thing>_aed.php` (add, edit, delete), for example
`modules/links/do_link_aed.php`. `index.php` runs it before any output, and only
when:

- the request is a POST and carries the session's CSRF token;
- the user may delete in the module, if the form posts `del=1`, or else may add
  or edit in it.

The handler checks the rights on the individual record itself, saves, sets a
message with `$AppUI->setMsg()` and redirects. A view whose name starts with
`do_`, `dosql` or `domodsql` (`index.php?m=system&a=domodsql`) is treated as a
write handler too: the same POST, token and permission rules apply.

### Pages without the theme: suppressHeaders

With `suppressHeaders=1` in the URL, `index.php` runs the module file without
the theme's header and footer, and without the `Content-Type` header. Pages
that send a download, an image or JSON use it, and set their own headers. Such
a page gets none of the JavaScript the header loads.

### Tabs from other modules

A module adds a tab to another module's pages with a file named after that
module: `<module>_tab.<name>.php` adds a tab to the module's main tabbed page
(a project's or a company's view page, for example), and
`<module>_tab.<action>.<name>.php` to that action's page. The
links module, for example, has `projects_tab.links.php` (a **Links** tab on a
project) and `tasks_tab.view.links.php` (a **Links** tab on a task's view
page). The tab's label is `<name>` with underscores turned into spaces and the
first letter in capitals. The page that shows the tabs adds them with
`$tabBox->loadExtras($m)` or `$tabBox->loadExtras($m, $a)`; the tabs of a
turned-off module are left out.

!!! note
    The list of tabs is read once per session and module. After you add a tab
    file, log out and in again to see it.

### JavaScript

Every page loads all the `.js` files in `js/`, then, if they exist,
`modules/<m>/<m>.module.js` and `modules/<m>/<a>.js` (`CAppUI::loadJS()`,
called by the theme's header). A module's script for one page therefore needs
no `<script>` tag: name it after the page. The Content-Security-Policy blocks
inline scripts and `onclick=` attributes; see
[Writing secure code](secure-code.md).

## Branches and contributions

All work happens on the `devel` branch, and every pull request goes to
`devel`. `master` only receives releases: when a version is released, `devel`
is merged into it, and the documentation website is published from it.

A pull request must pass the checks of the GitHub workflows in
`.github/workflows/`:

| Workflow | Job | What it checks |
| --- | --- | --- |
| CI | PHP 8.3 syntax | No PHP file has a syntax error. |
| CI | PHPStan (level 2, baseline) | No new static-analysis finding. |
| CI | No new raw $_GET/$_POST reads | Request input is read through `dPgetParam()` and its relatives. |
| CI | No new inline JavaScript (CSP) | No new `onclick=`, `javascript:` or inline `<script>`. |
| CI | Translations (English, Spanish) | Every string the code translates has an English and a Spanish entry. |
| CI | Theme colour contrast (WCAG AA) | The colours of the dotPulse themes are readable. |
| CI | PHP unit tests (tests/) | The PHP tests pass. |
| CI | vendor/ matches composer.lock (PHP 8.2) | `vendor/` holds what `composer.lock` says and loads on PHP 8.2. |
| CI | JavaScript tests (Jest) | The tests of the scripts in `js/` pass. |
| CI | Security smoke test (docker/) | The sandbox's security checks and feature tests pass. |
| CI | Module install/use/remove, SQL errors, PHP warnings, layout (docker/tests) | The full audit: every module installed, used and removed with no error, and the layout in the themes. |
| Dependency audit | composer audit, npm audit | No known vulnerability in the dependencies. |
| Docs | Build the documentation website | Both sites build with no broken link and no missing image. Runs only when `website/` changes. |

You can run each of them on your own computer before you push: see
[Development environment](environment.md#make-targets).

When you change something users see, change the documentation in the same pull
request: the English page in `website/docs/` and the Spanish one in
`website/docs-es/`.

## Licence

The code is free software under the GNU General Public License, version 2 or
(at your option) any later version (GPL-2.0-or-later): see `COPYING` and
`LICENSE` in the repository. The ticketsmith module is under the Voxel Public
License, and the libraries in `lib/` and `vendor/` keep their own licences.
Contributions to the code are published under the GPL.

This documentation, text and screenshots, is under
[Creative Commons Attribution-ShareAlike 4.0](https://creativecommons.org/licenses/by-sa/4.0/).
Code examples in it are under the code's licence, GPL-2.0-or-later, so they
can be copied into the program.

## Pages in this section

- [Developer guide](index.md): this page.
- [Development environment](environment.md): the sandbox, the checks and the tests.
- [Writing a module](modules.md): the files of a module, its setup, install and upgrade.
- [Writing secure code](secure-code.md): input, output, permissions, CSRF and the Content-Security-Policy.
- [REST API](api.md): the endpoints and their tokens.
