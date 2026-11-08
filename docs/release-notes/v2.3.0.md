# dotProject v2.3.0 Release Notes

Welcome to dotProject **v2.3.0**! This is a major update focused heavily on modernizing the user interface to adopt cleaner, Material-like aesthetics, while addressing several critical bugs, permissions issues, and compatibility problems with newer PHP versions.

## 🚀 Key Features & UI Modernizations
* **Material Theme Makeover**: Successfully revamped and permanently applied the `material` theme as the global default layout across the platform.
* **Modernized Login & Password Recovery**: Redesigned the `login.php` and `lostpass.php` screens into centered, responsive CSS cards with smooth dropdowns, removing the old cluttered table-based layout.
* **Calendar Overhaul**: Upgraded the month and week `Calendar` views to use crisp, clean matrix layouts with continuous light-grey borders, consistent white day-backgrounds, highlighting of the current day in soft blue, and cleanly boxed 'event chips'.
* **Contacts Interface Customization**: Restyled the `Contacts` module view to match the newly adopted layout and color palette of the application's clean design.
* **Spanish Localization (es.inc)**: Fully translated the English (`en.inc`) locales throughout the application. 

## 🛠 Bug Fixes & Code Health
* **Earnings Module Fixes**: Fixed `ENGINE=MyISAM` declaration incompatibilities upon creation, addressed missing table prefixes, fixed undefined variables, and ported in Spanish language bundles.
* **PHP 8.x Compatibility in Forums**: Fixed *"Warning: Trying to access array offset on false"* strict typing errors in the Forums module (both `addedit.php` and `post_message.php`) when loading screens without preexisting data arrays.
* **Permissions Rebuilt**: Re-enabled and validated missing permission checks throughout the `History` and `Companies` modules enabling Admins to create and view appropriately.
* **Eventum Compatibility**: Upgraded directory path evaluation and configuration code to securely map eventum trackers.
* **Cleanup of Obsolete Files**: Safely purged completely unused, dangling legacy files from the `/misc` folder (e.g. `mime.types`, `cvs2cl/`, `postnuke/`, `holidays/`).
* **Database Connection Stability**: Stripped out and refactored deprecated `mysql_pconnect` checks throughout the login process.
* **Custom Fields Improvements**: Implemented strict data-type validation and accurate ordering algorithms.

---

## ⚠️ Known Issues & Missing Module Updates
While core navigational frameworks, configuration loaders, the Calendar, Forums, Contacts, and Earnings modules have been stabilized for this release, **dotProject consists of over 60 different functional modules!**

Because we have updated the platform's core PHP requirements and introduced a unified Material styling engine, **many legacy modules have not yet been refactored or tested in this sprint**. You may encounter PHP 8+ "undefined index" or "array offset on false" errors in them, and their user interfaces may appear disjointed or continue to use archaic table `bgcolor` syntax.

**The following core modules are severely pending review and modernized updates:**
- **Projects & Tasks** (`/projects`, `/tasks`, `/tasks_template`, `/projectdesigner`)
- **Departments & Human Resources** (`/departments`, `/human_resources`)
- **Finances & Invoicing** (`/finances`, `/invoices`, `/costs`, `/unitcost`, `/payments`)
- **Helpdesk & Ticketing** (`/helpdesk`, `/ticketsmith`, `/bugspray`, `/mantis`)
- **Time & Resource Management** (`/timeplanning`, `/timecard`, `/timesheet`, `/timetrack`, `/resource_m`)
- **Monitoring, Gantt & Risks** (`/monitoringandcontrol`, `/igantt`, `/risks`)
- **File Management & System Administration** (`/files`, `/system`, `/backup`, `/dataimport`)
- **Third Party Integrations** (`/tracIntegration`, `/gallery2`)

*Contributors should prioritize auditing the above modules for PHP 8 warnings and CSS modernization in preparation for v2.4.0.*
