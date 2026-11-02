# dotPulse 3.1.0 Release Notes

**Release date:** 2026-11-02

dotPulse 3.1.0 adds a REST API, a personal dashboard, a Kanban board, earned value
warnings, two-step login and a security audit log. It upgrades 3.0.0 (and every
version 3.0.0 upgrades) in place. Read **Changes to know about** and **Upgrading** first.

---

## Changes to know about

- **PHP 8.1 or later is required** (8.3 is tested). The library that draws the QR codes
  of two-step login needs it; PHP 8.0 has had no security support since November 2023.
- **The dashboard is the new landing page** on sites that still had the default one
  (the calendar's day view). Sites that had chosen another landing page keep it. It
  is set in System Configuration (`default_view_m`, `default_view_a`).
- **New menu entry and project tab:** *Dashboard* is first in the menu, and projects
  have a *Board* tab after *Gantt Chart*. Roles can be denied the dashboard like any
  other module (System Admin > User Roles).
- **The file viewer's login now has the same failed-login limit** as the main login
  page (it had none).

## New

- **Personal dashboard**:
  - your open tasks grouped as overdue, due in 7 days, later and with no finish date,
    each with a *Log hours* link;
  - the projects you work on, with progress, open and overdue tasks;
  - the hours you logged this week, day by day;
  - your events in the next 7 days.
- **Kanban board** (project tab *Board*):
  - the project's tasks in *To do*, *In progress* and *Done* columns, with a filter for
    your own tasks;
  - drag a card, or use its arrow buttons, to change its progress: *To do* is 0 %,
    *Done* is 100 %, and *In progress* keeps the progress or sets 10 % / 90 %;
  - only tasks you may edit move.
- **REST API** (`api.php`, off by default):
  - personal access tokens, read-only or read-and-write, with an expiry, created and
    revoked in each user's *API tokens* tab;
  - a token acts with its user's permissions;
  - it reads projects, tasks and work logs, logs work and updates task progress;
  - see [docs/api.md](docs/api.md).
- **Two-step login** (optional, per user):
  - codes from any authenticator app (Google or Microsoft Authenticator, Aegis,
    FreeOTP, 1Password, ...), set up from a QR code in the user's *Two-step login* tab;
  - ten single-use recovery codes;
  - administrators can reset it for a user who lost their phone.
- **Security audit log** (System Admin > *Audit log*):
  - what it records:
    - logins (also failed ones and addresses that hit the limit), logouts;
    - two-step login and password events, API tokens;
    - the changes administrators make: users, roles, permissions, configuration,
      modules;
  - filters by event, user and date;
  - kept for 365 days by default (`audit_log_days`);
  - see [docs/audit-log.md](docs/audit-log.md).
- **Earned value warnings** in the project tips bell (with monitoringandcontrol): the
  cost performance index (CPI) or the schedule performance index (SPI) below 0.9.

## Security

- Two-step login:
  - each code is accepted only once (a code that someone else sees cannot be
    replayed);
  - five wrong codes ask for the password again, ten from one address in 15 minutes
    block that address.
- API tokens:
  - only their SHA-256 is stored;
  - 20 failed authentications from one address in 10 minutes block it.
- The file viewer's login had no failed-login limit; it now has the main login's, and
  renews the session id after logging in.

## Fixes

- **dp-grey-theme:** table headers were white on light grey, nearly invisible on many
  pages.
- **Installer:** an upgrade step whose second comment line had a `;` or a quote failed
  (consecutive comment lines were not all removed).
- **API project progress:** it now comes from the tasks, as on the project pages.

## Upgrading

1. **Check the PHP version:** 8.1 or later.
2. **Back up** the database and the `files/` directory.
3. **Rehearse** on a copy if you can:
   `make rehearse DUMP=/path/to/dump.sql.gz ADMIN=<admin user>`
   ([docker/README.md](docker/README.md)).
4. **Replace the code**, including `vendor/` (it has a new library). Keep
   `includes/config.php` and `files/`.
5. **Run the installer** (`install/` in a browser): it applies the database updates
   up to 20261104 (API tokens, dashboard module, two-step login, audit log). Then
   remove `install/` again or deny access to it. No add-on module needs an upgrade.
6. **Check the server's clock** (NTP): two-step login codes are only accepted within
   30 seconds of the current time.
7. **Optional:**
   - turn on the REST API in System Configuration if you will use it (on nginx, see
     the `PATH_INFO` notes in [docs/api.md](docs/api.md));
   - ask administrators to turn on two-step login;
   - adjust `audit_log_days`.

See [docs/deployment-security.md](docs/deployment-security.md) for the server settings
of the new features.

## Requirements

- PHP 8.1 or later (8.3 is tested), MySQL or MariaDB.
- Apache (recommended) or nginx with the rules from the deployment guide.
