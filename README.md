<p align="center">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="images/brand/logo-dark.svg">
    <img src="images/brand/logo.svg" alt="dotPulse" height="64">
  </picture>
</p>

<p align="center"><strong>Open-source project management for PHP 8, continuing dotProject.</strong></p>

---

**dotPulse** is an actively maintained continuation of [dotProject](https://github.com/dotproject/dotProject),
the open-source, web-based project-management system. Upstream development stopped after
dotProject 2.2.0 (March 2020); this fork carries the code base forward. It runs on
PHP 8.x, ships its own dotPulse theme, adds PMBOK-oriented features and a full
Spanish translation, and has been through a complete security and code-quality audit.

The name keeps the *dot* and the **dP** initials that run through the code (`dPgetParam()`,
`$dPconfig`, `DP_BASE_DIR`): existing installations, add-on modules and their database
tables keep working unchanged.

- Repository: <https://github.com/davmont/dotPulse>
- Issues: <https://github.com/davmont/dotPulse/issues>
- Original project: see [Origins and credits](#origins-and-credits)

---

## Status

Work happens on the **`devel`** branch; **`master`** only receives stable releases.

- Current release: **dotPulse 3.1.1**, maintenance: outdated libraries replaced or removed
  (JpGraph, PEAR, TinyMCE, old jQuery), public holidays by country, task end dates
  fixed. See [release notes v3.1.1](docs/release-notes/v3.1.1.md) before upgrading.
- Previous releases: 3.1.0, with the REST API, personal dashboard, Kanban board,
  two-step login, audit log and earned value warnings
  ([release notes v3.1.0](docs/release-notes/v3.1.0.md), PHP 8.1 or later); 3.0.0, the first
  under the new name, with the breaking changes from dotProject
  ([release notes v3.0.0](docs/release-notes/v3.0.0.md)); v2.3.1, still named dotProject
  ([release notes v2.3.1](docs/release-notes/v2.3.1.md)).

## What's in it

### Security
A module-by-module audit of the whole code base (core and every bundled add-on) is
documented in [docs/security-audit/](docs/security-audit/), with its roadmap in
[ROADMAP.md](docs/security-audit/ROADMAP.md). Highlights:

- Passwords hashed with `password_hash()` (bcrypt), upgraded transparently on login;
  token-based password reset; rate-limited login and reset.
- **Audit log** of logins and administrators' changes (users, roles, permissions,
  configuration, modules, API tokens) in System Admin; see [docs/audit-log.md](docs/audit-log.md).
- Optional **two-step login** per user (TOTP codes from any authenticator app, with
  single-use recovery codes); administrators can reset it for a user who lost their phone.
- CSRF protection on every form post; actions that changed data over GET moved to POST.
- Enforced Content-Security-Policy with no inline scripts ([docs/csp.md](docs/csp.md)).
- SQL injection, XSS and missing-permission fixes across core and add-on modules.
- Hardened sessions (`HttpOnly`, `Secure`, `SameSite`, id regenerated on login) and
  file uploads (server-side MIME detection).
- Deployment checklist in [docs/deployment-security.md](docs/deployment-security.md).

### PHP 8
The full audit suite installs every add-on module, crawls every page and saves every
form as an administrator and as a regular user, and fails on any PHP error, warning,
notice or SQL error.

### Project management
- **Personal dashboard** (the landing page): your open tasks by due date (overdue, next 7
  days, later), the projects you work on with their progress and overdue tasks, the
  hours you logged this week and your coming events.
- **Board** (project tab): the project's tasks in *To do*, *In progress* and *Done*
  columns; drag a card, or use its arrows, to change its progress.
- **Project tips bell**: checks each project against PMBOK practice (charter, scope,
  schedule, cost, risks and more) and suggests the next step, including earned value
  warnings when the cost (CPI) or schedule (SPI) performance index drops below 0.9.
- **PMBOK 8th Edition** task fields (status, principles, domains) and project charters
  linked to their project (Initiating module).
- Gantt charts, calendar, Helpdesk, Mantis integration, costs and finances, timesheets,
  risks and the other dotProject modules, updated for PHP 8.

### Integration
- **REST API** (off by default): personal access tokens with read or read-and-write
  access, acting with the user's permissions; projects, tasks, work logs and task
  progress. See [docs/api.md](docs/api.md).

### Interface and languages
- The dotPulse theme by default (also on the login page), and a redesigned System Admin: a start page with the state of the installation, and the settings by section with their help in view. The classic themes (default, amp, dp-grey-theme, material, wps-redmond) are still available.
- Complete **Spanish** translation. The other 23 languages (Bulgarian, Catalan, Chinese, Czech, Danish, Dutch, French, German, Hungarian, Italian, Japanese, Korean, Latvian, Norwegian, Polish, Portuguese, Russian, Slovenian, Swedish, Turkish, Vietnamese) cover every string too, machine translated where the upstream translation had none: those entries end in `// MT` until reviewed (`php tools/locales/check.php status`).

## Installation

**Requirements:** PHP 8.2 or later (8.3 is tested), MySQL or MariaDB, Apache (recommended) or nginx.
Several access rules rely on `.htaccess`; on nginx, port the equivalent `deny` rules
(see [docs/deployment-security.md](docs/deployment-security.md)).

1. Put the source in your web root.
2. Open the `install/` directory in a browser and follow the wizard. It writes
   `includes/config.php`; to configure by hand, copy
   [includes/config-dist.php](includes/config-dist.php) to `includes/config.php`.
3. After installing, remove the `install/` directory or deny access to it.

### Upgrading from dotProject
dotPulse upgrades dotProject 2.x databases in place: back up the database, replace the
code, and run the installer, which detects the existing installation and applies the
database upgrades. Then upgrade the add-on modules from **System Admin > Modules**.
Customised page titles and e-mail prefixes are kept; the defaults are renamed to dotPulse.
The full checklist (module upgrades, cron, mail, web server rules) is in
[release notes v3.0.0](docs/release-notes/v3.0.0.md#upgrading).

## Development

- `make check` runs the PHP lint, PHPStan, the raw-input and inline-script checks, the
  PHP unit tests ([tests/](tests/), `make php-test`) and the JavaScript tests (Jest, in
  [tools/jest/](tools/jest/), `make js-test`).
- `make deps-audit` looks up the Composer and Node.js dependencies in the security
  advisory databases (`composer audit`, `npm audit`); Dependabot proposes their updates.
  Libraries copied into `lib/` and `modules/` are listed in
  [docs/third-party.md](docs/third-party.md).
- A Docker sandbox (PHP 8.3 and MariaDB) and the full audit suite are described in
  [docker/README.md](docker/README.md) (`make audit-full`).

Node.js is only needed for the development tools in [tools/](tools/); a server does not
need it.

What a server needs is the root PHP entry points (`index.php`, `api.php`, `fileviewer.php`,
`csp-report.php`, `queuescanner.php` for cron, `base.php`) and the application directories
(`classes/`, `db/`, `files/`, `functions/`, `images/`, `includes/`, `install/`, `js/`, `lib/`,
`locales/`, `misc/`, `modules/`, `style/`, `vendor/`). Next to them:
- [docs/](docs/): administration and security guides, the API, the [release notes](docs/release-notes/)
  and the audit record; [scripts/](scripts/) has the CSP report summary for administrators.
- Development only, left out of release archives (`git archive`, see `.gitattributes`):
  [tools/](tools/) (PHPStan configuration in `tools/phpstan/`, the raw-input and inline-script
  checks in `tools/checks/`, the locale check, the brand and icon builders, Jest and Playwright),
  [tests/](tests/), [docker/](docker/), `Makefile` and `.github/`.

## Brand

The logo, palette and favicons are in [images/brand/](images/brand/) and are generated
by [tools/brand/build.py](tools/brand/build.py). The mark is five dots, one per task,
drawing the pulse of a project.

| Colour | Hex | Use |
| --- | --- | --- |
| dotPulse blue | `#1B4F9C` | Logo, links, buttons |
| Deep blue | `#123A73` | Hover, pressed |
| Coral | `#F06449` | The peak in the logo, charts (not small text) |
| Coral text | `#C93D27` | Coral for text on white |
| Teal | `#0A8080` | Second data colour, text on white |
| Graphite | `#23262B` | Body text, wordmark |
| Mist | `#E8EDF5` | Section backgrounds |

The wordmark uses [Space Grotesk](https://github.com/floriankarsten/space-grotesk)
(SIL Open Font License), converted to outlines.

## Origins and credits

dotPulse is built on the work of the dotProject Development Team and its contributors.
dotProject started in 2000 and was developed by its community for nearly two decades;
its last upstream release is 2.2.0 (March 2020). This fork began in January 2013 from
dotProject's Subversion code base and was renamed dotPulse in 2026.

Original project sources:

- dotProject on GitHub: <https://github.com/dotproject/dotProject>
- dotProject website: <http://www.dotproject.net/>
- dotProject on SourceForge: <https://sourceforge.net/projects/dotproject/>
- Upstream documentation: <http://docs.dotproject.net/>
- Upstream forums: <http://forums.dotproject.net/>

The source files keep their original copyright notices and the "This file is part of
dotProject" GPL headers. Several bundled modules ("dotmods") come from third-party
authors and keep their own credits.

## License

dotPulse is free software under the **GNU General Public License**, as dotProject has
been since version 2.0 (versions 1.0.2 and earlier were BSD licensed). See
[COPYING](COPYING) and [LICENSE](LICENSE).

Bundled third-party libraries (ADOdb, phpGACL, PHPMailer, Dompdf, php-qrcode, Frappe Gantt, jQuery and
others) keep their own licences.
