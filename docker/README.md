# Security-audit sandbox

Ephemeral PHP 8.3 + MariaDB stack that serves the repository read-only on http://127.0.0.1:8089.

    cd docker
    ./up.sh                          # start (creates the includes/config.php mount point if missing)
    ./reset-db.sh                    # run the real installer (admin/passwd), then create worker/worker through the user form
    docker compose logs -f web       # PHP errors go to stderr
    ./smoke.sh                       # security smoke checks: login, CSRF, reset flow, role check
    docker compose down -v           # tear down

Notes
- `config.php` here overrides `includes/config.php` inside the container (DB host `db`). `includes/config.php` is not tracked by git; `up.sh` creates an empty one as the mount point. The repo tree is mounted read-only; only `docker/files` is writable (uploads, rate-limiter state).
- `security_opt: label:disable` is needed on SELinux hosts; `APACHE_RUN_USER=#1000` makes Apache run as the host uid.
- Browser checks (forms sent with `form.submit()`, all five themes) were run with Playwright and headless Chrome; `smoke.sh` covers the server-side behaviour with curl.
- `config.php` is bind-mounted as a single file: after editing it, run `docker compose up -d --force-recreate web`, otherwise the container keeps serving the old copy.
- `tests/api.sh` checks the REST API (`api.php`): tokens created through the user's API tokens tab, every endpoint, read and write scopes, expiry, revocation, the failed-attempt limit, and permissions as worker and guest (needs `tests/seed.sh` after `reset-db.sh`; part of `make audit-smoke`).
- `tests/dashboard.sh` checks the personal dashboard: landing page, task grouping, hours, and that another user's private task, a denied project and a role without the module stay hidden (after `tests/seed.sh`; part of `make audit-smoke`).
- `tests/board.sh` checks the project board: columns by progress, moves through `do_board_move` (progress rules per column, end date on done), and refusals (CSRF, GET, private task, task group, guest) (after `tests/seed.sh`; part of `make audit-smoke`).
- `tests/ev-tips.sh` checks the earned value tips of the project tips bell (CPI and SPI below 0.9, neither on plan or before any work), installing monitoringandcontrol and dotproject_plus if needed (part of `make audit-smoke`).
- `tests/twofactor.sh` checks two-step login: set-up through the tab (QR code, key, recovery codes shown once), the code step after the password on `index.php` and `fileviewer.php`, wrong, replayed and older codes, recovery codes used once, the 5-attempt limit, turning it off and the administrator's reset; codes are computed from the shown key as an app would (part of `make audit-smoke`).
- `tests/auditlog.sh` checks the security audit log: entries for logins, logouts, the failed-login limit, API tokens, users, configuration (names only) and modules, no passwords or tokens in it, and the viewer (filters, escaping, access) (after `tests/seed.sh`; part of `make audit-smoke`).
- Optional Mailpit to see the mail the application sends: `docker compose -f compose.yml -f compose.mail.yml up -d mail && ./mail-setup.sh` points the SMTP settings at it (run `mail-setup.sh` again after `reset-db.sh`); messages are on http://127.0.0.1:8025. `tests/mail.sh` checks the password reset, the test email and task assignment emails against it. Queued mail goes out when you run `docker compose exec -u 1000 web php queuescanner.php --base-url=http://127.0.0.1:8089`.
- Optional MantisBT for the Mantis module: `docker compose -f compose.yml -f compose.mantis.yml up -d mantis && ./mantis-setup.sh` installs MantisBT 2.28 on http://127.0.0.1:8090 (administrator / root, database `bugtracker` in the same MariaDB), creates a test project with issues, prints an API token and sets dotProject's Mantis address (`http://mantis`, HTTP allowed for the sandbox only). Install the Mantis module first, then enter the token on its "Mantis API token" page. `reset-db.sh` resets dotProject only; run the script again afterwards.

## Full audit (`make audit-full`, docker/tests/)

`docker/tests/run.sh` checks every add-on module end to end in the sandbox (about an hour locally; CI job `audit-full`):

1. reinstall the database, install and activate every add-on through System > Modules and show it in the menu (the crawl only follows links), seed data (`seed.sh`);
2. crawl every page as admin and as worker (`crawl.py`);
3. fill and save every add/edit form as admin and as worker (`tools/browser/forms.js`), then crawl again with the saved data;
4. layout in each theme at 1366x768 plus `LAYOUT_EXTRA` (`tools/browser/layout.js`: no sideways scrolling, no control outside the window; `layout-known.txt` lists accepted exceptions), pop-ups at their window sizes and the task form's tab switching (`tools/browser/popups.js`); the dark theme as admin and as worker (`tools/browser/dark.js`: no light patches, no text under 3:1 on the background it is drawn on, also where scripts set the colour; `dark-known.txt` lists accepted exceptions);
5. remove every add-on, crawl the core without them, reinstall and crawl again.

Every step also fails on anything in the PHP log: SQL errors, fatals, warnings, notices and deprecations (`ALLOW_DEPRECATED=1` relaxes the last). Reports go to `docker/.audit/` (ignored by git). Run parts with `STEPS="2 3"`; choose themes with `THEMES="default material"`. The browser checks use `playwright-core` (`cd tools/browser && npm ci`) with an installed Chrome (`CHROME=/path/to/chrome` if it is not `/usr/bin/google-chrome`). `modules.sh install|toggle|remove|upgrade [module ...]` is also useful on its own.

## Upgrade rehearsal (`make rehearse`, docker/rehearse.sh)

Before upgrading a server, try the upgrade on a copy of its database:

    mysqldump --single-transaction <database> | gzip > prod.sql.gz   # on the server
    make rehearse DUMP=prod.sql.gz ADMIN=<an administrator's user name>

The script replaces the sandbox database with the dump (any table prefix is renamed to `dotp_`), switches the copy to SQL logins and a mail server that does not exist (so nothing is sent), and sets ADMIN's password to `passwd`. Then it runs the installer's upgrade mode, the System > Modules upgrade of every module whose code is newer than the database, and a crawl of the site as ADMIN. It fails on an installer error, an SQL error or PHP problem in the log, or a page with an error. It warns about modules in the database whose code is gone (bugspray, smartsearchns, groups, tracIntegration, eventum): uninstall those on the server before uploading the new code. Afterwards the sandbox keeps the upgraded copy for a look around; `make sample-data` replaces it. The dump holds real data: keep it out of the repository (`docker/.audit/` and the dump's own folder are good places) and delete it when done.
