# Development environment

This page sets up a development copy of dotPulse: a sandbox that serves your
working copy of the code, with a database you can throw away, and the checks
and tests a pull request must pass. Everything runs in containers, so you don't
install PHP, MariaDB or Composer on your computer.

## What you need

| Tool | Used for |
| --- | --- |
| git | The repository. |
| Docker, with the Compose plugin (`docker compose`) | The sandbox, and the PHP, PHPStan, Composer and Node.js images the checks run in. The `Makefile` and the scripts call `docker`. |
| make, bash, curl | The `Makefile` and the scripts in `docker/`. |
| Python 3 | `make theme-contrast`, `make docs` (the image check) and some test scripts in `docker/tests/` (the page crawler, for example). |
| Node.js 22 and Google Chrome | Only for the browser checks of `make audit-full` and for the documentation screenshots (`tools/browser/`). `make js-test` and `make deps-audit` run Node.js in a container. |

Get the code and work on the `devel` branch:

```sh
git clone https://github.com/davmont/dotPulse.git
cd dotPulse
git checkout devel
```

## The sandbox

The sandbox, in `docker/`, runs two containers: `web`, PHP 8.3 with Apache,
and `db`, MariaDB 10.11. It serves the repository on
<http://127.0.0.1:8089>, so a change you save in your editor shows on the next
page load.

### Start it

1. Start the containers:

    ```sh
    docker/up.sh
    ```

    The first run builds the `web` image. `up.sh` also creates an empty
    `includes/config.php` if there is none (see below).

2. Install the database:

    ```sh
    docker/reset-db.sh
    ```

    This runs the real installer, which creates the tables and the `admin`
    account, then adds the `worker` and `guest` accounts through the user form.
    It drops every table first, so run it whenever you want a clean database.

3. Load the sample data, a small consultancy with its clients, projects, tasks
   and time logs:

    ```sh
    docker compose -f docker/compose.yml exec -T web php db/sample/sample-data.php
    ```

    `make sample-data` does steps 1 to 3 in one go.

4. Open <http://127.0.0.1:8089> and log in.

### Logins

| User | Password | Role | Created by |
| --- | --- | --- | --- |
| `admin` | `passwd` | Administrator | The installer. With the sample data, this is Alex Rivera, the managing partner. |
| `worker` | `worker` | Project worker | `reset-db.sh` |
| `guest` | `guest` | Guest | `reset-db.sh` |
| `priya`, `marco`, `hannah`, `tomas`, `lena` | `demo1234` | Project worker | The sample data. Priya is the project manager. |

The login form is rate limited. If you lock yourself out with wrong passwords,
delete `docker/files/temp/ratelimit_*`.

### How it works

- The repository is mounted read-only into the `web` container: dotPulse can't
  change files in it.
- `docker/files/` is mounted as `files/` and is the only writable folder:
  uploaded files and the rate limiter's state go there.
- `docker/config.php` is mounted over `includes/config.php`. It points
  dotPulse at the `db` container, with the table prefix `dotp_`. Your own
  `includes/config.php`, if you have one, is not used by the sandbox; it is not
  tracked by git. `docker/config.php` is mounted as a single file: after you
  edit it, run `docker compose -f docker/compose.yml up -d --force-recreate web`.
- Apache runs as user id 1000, or as your own user id when the sandbox is
  started by `make`, so that it can write to `docker/files/`.
- The database lives in memory (`tmpfs`). Stopping the `db` container loses it:
  run `reset-db.sh` again afterwards.

### Logs and the database

PHP shows every error, warning, notice and deprecation, on the page and in the
`web` container's log:

```sh
docker compose -f docker/compose.yml logs -f web
```

To query the database (user and password `dotproject`):

```sh
docker compose -f docker/compose.yml exec -T db mariadb -udotproject -pdotproject dotproject -e "SELECT user_username FROM dotp_users"
```

The test scripts have the same command as a shell function:
`source docker/tests/lib.sh`, then `db -e "SELECT ..."`.

To stop the sandbox and remove its containers:

```sh
docker compose -f docker/compose.yml down -v
```

### Modules in the sandbox

The installer sets up the core modules only. To install add-on modules the way
an administrator does from **System Admin › Modules**:

```sh
docker/tests/modules.sh install links holiday   # install
docker/tests/modules.sh toggle links holiday    # turn on
docker/tests/modules.sh menu links holiday      # show in the menu
```

Without module names, the command applies to every add-on module. `remove` and
`upgrade` work the same way. The script fails if the command doesn't take effect
or logs an SQL error or a PHP problem.

### Mail and MantisBT

Two optional containers run next to the sandbox:

- **Mailpit** catches the mail dotPulse sends. Start it and point the SMTP
  settings at it, then read the messages on <http://127.0.0.1:8025>:

    ```sh
    cd docker
    docker compose -f compose.yml -f compose.mail.yml up -d mail
    ./mail-setup.sh
    ```

    Run `mail-setup.sh` again after `reset-db.sh`. Queued mail goes out when
    the queue scanner runs:
    `docker compose exec -u 1000 web php queuescanner.php --base-url=http://127.0.0.1:8089`.

- **MantisBT**, for the Mantis module. `mantis-setup.sh` installs MantisBT on
  <http://127.0.0.1:8090> (`administrator` / `root`), creates a test project
  with issues, prints an API token and sets dotPulse's Mantis address:

    ```sh
    cd docker
    docker compose -f compose.yml -f compose.mantis.yml up -d mantis
    ./mantis-setup.sh
    ```

    Install and turn on the Mantis module, then enter the token on its
    **Mantis API token** page. `reset-db.sh` resets dotPulse only: run
    `mantis-setup.sh` again after it.

## Before you open a pull request

Run the checks that need no sandbox:

```sh
make check
```

If you changed something a test script covers (logins, permissions, the API,
the dashboard, the board, two-step login, the audit log, holidays), also run:

```sh
make audit-smoke
```

It reinstalls the sandbox database, so the sample data is gone afterwards
(`make sample-data` puts it back). If you changed a module's pages or forms,
`make audit-full` checks every module end to end; it takes about an hour, and CI
runs it on every pull request anyway.

## Make targets

| Target | What it does |
| --- | --- |
| `make check` | Runs `lint`, `phpstan`, `raw-input`, `inline-js`, `locales`, `theme-contrast`, `php-test`, `js-test` and `vendor-check`. Needs no sandbox. |
| `make lint` | Checks the syntax of every PHP file tracked by git with PHP 8.3, except `lib/` and `vendor/`. |
| `make phpstan` | Static analysis with PHPStan at level 2 (`tools/phpstan/phpstan.neon.dist`). Fails on any finding that is not in the baseline. Copies `includes/config-dist.php` to `includes/config.php` if there is none. |
| `make phpstan-baseline` | Rewrites the PHPStan baseline. See [Baselines](#baselines). |
| `make raw-input` | Fails when a file reads `$_GET`, `$_POST`, `$_REQUEST` or `$_COOKIE` directly more often than its baseline allows. |
| `make inline-js` | Fails when a file has more inline JavaScript (`onclick=` and other event attributes, `javascript:` URLs, `<script>` blocks without `src` or `nonce`) than its baseline allows. |
| `make locales` | Fails when a string the code translates has no English or no Spanish entry (`tools/locales/check.php`). `php tools/locales/check.php missing es` lists what is missing. |
| `make theme-contrast` | Checks that the text and control colours of the dotPulse themes, light and dark, reach WCAG AA contrast. |
| `make php-test` | Runs the PHP unit tests in `tests/`. |
| `make js-test` | Runs the Jest tests of the scripts in `js/` (`tools/jest/tests/`). |
| `make vendor-check` | Checks with PHP 8.2 that `vendor/` holds the versions in `composer.lock` and loads on the oldest supported PHP. |
| `make deps-audit` | `composer audit` of `composer.lock`, and `npm audit` of the Node.js tools in `tools/jest` and `tools/browser`. Needs network access. |
| `make sample-data` | Starts the sandbox, reinstalls the database and loads the sample data. |
| `make sample-sql` | Writes the sample data as one SQL file, for a server with no command-line PHP. `OUT=file` and `PREFIX=dp_` choose the file and the table prefix. |
| `make audit-smoke` | Starts the sandbox, reinstalls the database and runs the security smoke test and the feature tests (see below). |
| `make audit-full` | Starts the sandbox and runs the full audit (see below). |
| `make rehearse` | Upgrades a copy of a production database in the sandbox. See [Upgrade rehearsal](#upgrade-rehearsal). |
| `make docs` | Builds the English and the Spanish documentation sites into `website/site/`, as CI does. |
| `make docs-serve` | Serves the English site on <http://127.0.0.1:8000>, rebuilt when you save a page. |
| `make docs-serve-es` | The same for the Spanish site, on port 8001. |
| `make docs-screenshots` | Takes the documentation screenshots from the sandbox. See [Screenshots](#screenshots). |

The checks run in containers (`php:8.3-cli`, `php:8.2-cli`, a PHPStan image
pinned by digest, `composer:2`, `node:22`), except `theme-contrast`, which runs
Python on your computer.

## Tests

### PHP unit tests

The tests in `tests/` use a small JUnit-style framework that ships with them
(`tests/phpunit.php`), not PHPUnit. A test file is `tests/<Name>Test.php` with a
class of the same name that extends `TestCase`; every method whose name starts
with `test` is a test. The assertions are `assert()`, `assertEquals()` and
`assertRegexp()`. Many files include `tests/bootstrap.php`, which defines
`DP_BASE_DIR` and replaces a few global functions (`dPgetSysVal()`,
`dPgetConfig()`...) with stubs.

`tests/run.php` runs each file in its own PHP process, because the files stub
different functions:

```sh
make php-test                                               # every file
docker run --rm -v "$PWD":/app -w /app php:8.3-cli php tests/run.php TwoFactor   # one file
```

With PHP 8.2 or later on your computer, `php tests/run.php` works as well. The
tests need no database.

### JavaScript tests

`tools/jest/tests/*.test.js` test the scripts in `js/` with Jest in a simulated
browser (jsdom). `make js-test` installs Jest in the `node:22` container and runs
them.

### Smoke test and feature tests

`make audit-smoke` runs these scripts against the sandbox, after reinstalling
its database:

| Script | What it checks |
| --- | --- |
| `docker/smoke.sh` | Security basics: login, CSRF, the password reset flow, role checks. |
| `docker/tests/seed.sh` | Not a test: adds the companies, project, tasks, forum and event the next scripts use. |
| `docker/tests/api.sh` | The REST API: tokens, every endpoint, read and write scopes, expiry, revocation, the failed-attempt limit, permissions as `worker` and `guest`. |
| `docker/tests/dashboard.sh` | The personal dashboard, and that other users' private tasks and denied projects stay hidden. |
| `docker/tests/board.sh` | The project board: columns, moving a card, and the refusals. |
| `docker/tests/twofactor.sh` | Two-step login: set-up, codes, recovery codes, the attempt limit, the administrator's reset. |
| `docker/tests/auditlog.sh` | The audit log: the events recorded, no passwords or tokens in it, the viewer. |
| `docker/tests/ev-tips.sh` | The earned value warnings of the project tips. |
| `docker/tests/holiday.sh` | Holiday calendars: import from an iCalendar file, and the timecard taking a holiday into account. |

`docker/tests/mail.sh` is not part of it: it needs Mailpit, and checks the
password reset, the test email and the task assignment emails.

Each script can run on its own once the sandbox is up, for example
`docker/reset-db.sh && docker/tests/seed.sh && docker/tests/api.sh`.

### The full audit

`make audit-full` runs `docker/tests/run.sh`, which checks every add-on module
end to end in five steps:

1. Reinstalls the database, installs every add-on module, turns it on and shows
   it in the menu, then loads the sample data and the seed data.
2. Crawls every page as `admin` and as `worker`.
3. Fills in and saves every add and edit form as `admin` and as `worker`, then
   crawls again with the saved data.
4. Checks the layout in each theme (no sideways scrolling, no control outside
   the window), the pop-up windows, the tab switching of the task form, and the
   dark theme (no light patches, no unreadable text).
5. Removes every add-on module, crawls the core without them, reinstalls them
   and crawls again.

Every step also fails on anything in the PHP log: SQL errors, fatal errors,
warnings, notices and deprecations. Reports go to `docker/.audit/`, which git
ignores.

| Variable | Effect |
| --- | --- |
| `STEPS="2 3"` | Runs only those steps. |
| `THEMES="dotpulse material"` | The themes step 4 checks. All seven by default. |
| `CHROME=/path/to/chrome` | The Chrome the browser checks drive, if it is not `/usr/bin/google-chrome`. |
| `ALLOW_DEPRECATED=1` | Deprecations in the log don't fail the run. |

Steps 3 and 4 use `playwright-core` in `tools/browser/`; `run.sh` installs it
with `npm ci` if needed. Known layout and dark theme exceptions are listed in
`docker/tests/layout-known.txt` and `docker/tests/dark-known.txt`.

## Baselines

Three checks accept the problems the code had when they were introduced, and
fail on new ones. Each keeps a baseline file:

| Check | Baseline | Rewrite it with |
| --- | --- | --- |
| `make phpstan` | `tools/phpstan/phpstan-baseline.neon` | `make phpstan-baseline` |
| `make raw-input` | `tools/checks/raw-input-baseline.txt` (number of raw reads per file) | `tools/checks/check-raw-input.sh --update` |
| `make inline-js` | `tools/checks/inline-js-baseline.txt` (number of lines with inline JavaScript per file) | `tools/checks/check-inline-js.sh --update` |

A count may go down, never up. New code must not add to any of them: read
input with `dPgetParam()` and its relatives, and put JavaScript in `.js` files
(see [Writing secure code](secure-code.md)). Rewrite a baseline only after you
have removed problems, so that the lower count becomes the new limit, and
commit it with the change.

PHPStan also fails when a finding recorded in its baseline no longer occurs, so
a fix that removes a finding must come with `make phpstan-baseline`. The PHPStan
image is pinned by digest in the `Makefile`: a new PHPStan release reports
different findings, so update the digest and the baseline together.

`tools/checks/check-inline-js.sh --list modules/<module>` lists the lines with
inline JavaScript left in a folder.

## Continuous integration

GitHub runs three workflows, in `.github/workflows/`:

| Workflow | When | Jobs |
| --- | --- | --- |
| `ci.yml` (CI) | Every push to `devel` or `master`, every pull request | One job per check: `make lint`, `phpstan`, `raw-input`, `inline-js`, `locales`, `theme-contrast`, `php-test`, `vendor-check`, `js-test`, `audit-smoke` and `audit-full`. |
| `deps.yml` (Dependency audit) | Pushes, pull requests, and every Monday | `make deps-audit`. |
| `docs.yml` (Docs) | Pushes and pull requests that change `website/` | Builds both documentation sites; on `master`, publishes them to www.dotpulse.org. |

On a pull request, `audit-full` checks the layout in two themes, `dotpulse` and
`dp-grey-theme`; on a push to `devel` or `master`, in all of them. Its reports
are kept as the `audit-reports` artifact of the run for seven days. When
`audit-smoke` or `audit-full` fails, the job prints the end of the web server
log.

Dependabot proposes updates of the Composer packages, the Node.js tools and the
GitHub actions, against `devel`. `vendor/` is committed and Dependabot doesn't
update it: before merging one of its Composer pull requests, run
`composer install --no-dev` on the branch and commit `vendor/`, or
`vendor-check` fails.

## The documentation website

The pages are Markdown in `website/docs/` (English) and `website/docs-es/`
(Spanish), built with Zensical from `website/mkdocs.yml` and
`website/mkdocs.es.yml`. The Help module shows the same pages inside dotPulse
and takes its page list from the `nav` of those files.

```sh
make docs            # build both sites into website/site/, failing on broken links and missing images
make docs-serve      # preview the English site on http://127.0.0.1:8000
make docs-serve-es   # preview the Spanish site on http://127.0.0.1:8001
```

The Spanish site has the same pages, file names and image names as the
English one, and each Spanish heading keeps the English anchor:
`## Añadir un usuario { #add-a-user }`. The labels in the Spanish pages are
those of the Spanish interface (`locales/es/`). When you change an English
page, change the Spanish one too.

### Screenshots

`website/screenshots.json` lists every picture: the page, the user, the crop
and the clicks before it. `make docs-screenshots` takes them all from the
sandbox into `website/docs/img/` with `tools/browser/screenshots.js`, and
compresses them with `pngquant` if it is installed. It needs `playwright-core`
(`cd tools/browser && npm ci`) and Chrome. To take some again:

```sh
node tools/browser/screenshots.js website/screenshots.json website/docs/img <name> ...
```

The sandbox needs the sample data and some preparation first (add-on modules
installed and shown, a few files and links); `website/README.md` lists the
steps, and a shot with a `note` in the list says what else it needs.

For the Spanish site, switch the sandbox to Spanish and run with
`SHOT_LANG=es` into `website/docs-es/img`: the texts the steps click are looked
up in `locales/es/`, so the same list serves both sites.

## Upgrade rehearsal

Before upgrading a server, you can try the upgrade on a copy of its database:

```sh
mysqldump --single-transaction <database> | gzip > prod.sql.gz   # on the server
make rehearse DUMP=prod.sql.gz ADMIN=<an administrator's login>
```

`docker/rehearse.sh` replaces the sandbox database with the dump, renaming the
tables to the `dotp_` prefix. It makes the copy safe to use: SQL logins instead
of LDAP, mail sent to a server that doesn't exist, and the password of `ADMIN`
set to `passwd`. Then it runs the installer's upgrade, upgrades every module
whose code is newer than the database, and crawls the site as `ADMIN`. It fails
on an installer error, an SQL error or PHP problem in the log, or a page with an
error, and it warns about modules in the database whose code no longer exists.

Afterwards the sandbox keeps the upgraded copy, so you can look around at
<http://127.0.0.1:8089>; `make sample-data` replaces it. Uploaded files are not
in the dump, so downloads fail. The dump holds real data: keep it out of the
repository and delete it when you are done.
