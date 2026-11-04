# dotPulse 3.1.1 Release Notes

**Release date:** 2026-11-04

dotPulse 3.1.1 is a maintenance release:
- it replaces or removes outdated libraries: JpGraph, PEAR, TinyMCE, old jQuery and jQuery UI copies, Smarty, xajax and ezPDF;
- it fixes a forum permission check and calculates task end dates correctly;
- it brings back automatic public holidays, now by country.

It upgrades 3.1.0 in place, with no database changes. Read **Changes to know about** first.

---

## Changes to know about

- **Task end dates.** End dates are calculated from a start date and a duration in working hours. A duration of whole days used to end at the start of the next working day; it now ends after the last day's working hours. For example, 24 hours from Monday 08:00 used to end Thursday 08:00 and now ends Wednesday 16:00.
  - Fractions of an hour are no longer dropped: 4.5 hours from 08:00 ends at 12:30, not 12:00.
  - Dates already saved do not change. Dates that are recalculated do (when a task moves with its dependencies, or a project's dates are shifted).
- **Holiday module 0.2.** Upgrade it in System Admin > Modules, then choose the country of the automatic holidays in its *Settings* tab.
  - The old PEAR Date_Holidays drivers stopped working on PHP 8, so automatic holidays have not marked any day since then.
  - Manual holidays (whitelist and blacklist) are unchanged.
- **Project designer Gantt chart.** It is now drawn in the browser (frappe-gantt), like every other Gantt chart in dotPulse. It used to be a JpGraph image.
- **Time planning minutes.** They are edited as plain text: the rich text editor (TinyMCE 3, unmaintained since 2010) is gone. Existing minutes are shown as text.
- **Custom code that used PEAR.** `lib/PEAR` is removed. `CDate` keeps its methods but no longer extends PEAR's `Date`. Code of your own that uses `Date`, `Date_Calc`, `Date_Span` or `Contact_Vcard_*` directly must use `CDate`, whose calendar helpers replace `Date_Calc`, or `CVcard`.
- **Adding days across a daylight saving change keeps the time of day.** Adding 14 days to 3 November at 00:00 gives 20 October at 00:00; it used to give 01:00.

## New

- **Automatic public holidays by country** (holiday module):
  - the national public holidays of Austria, Belgium, France, Germany, Ireland, Italy, the Netherlands, Norway, Portugal, Spain, Sweden, the United Kingdom (England and Wales) and the United States (federal), calculated for any year;
  - movable feasts and rules such as "last Monday of May" are included, as are the weekend substitute days of the UK, Ireland and the US;
  - the *Settings* tab lists this year's and next year's holidays;
  - regional and local holidays are not included: add them to the whitelist, which also accepts holidays that repeat every year.

## Security

- **Forum PDF export:** a user who could read one forum could export a topic of another forum. The export now checks the forum and includes only its messages.
- **vCard export:** another user's private contact could be downloaded as a vCard. It is now refused, as on the contact page.
- **Outdated JavaScript libraries replaced:**
  - jQuery 1.4.2 and 1.12.3 by one jQuery 3.7.1 (CVE-2015-9251, CVE-2019-11358, CVE-2020-11022, CVE-2020-11023);
  - jQuery UI 1.8rc3 and 1.12.1 by jQuery UI 1.14.1 (CVE-2021-41182, CVE-2021-41183, CVE-2021-41184, CVE-2022-31160).
- **Unmaintained libraries removed**, about 1,000,000 lines of code:
  - PEAR and JpGraph 3.0.7, replaced by dotPulse's own date, vCard and Gantt code;
  - TinyMCE 3.3.9;
  - Smarty 2.6.3, with the phpGACL admin pages that used it (they were not reachable);
  - xajax 0.5 beta, R&OS ezPDF, DataTables, maskMoney, and most of WireIt and YUI 2.

## Fixes

- **Project designer:** it stopped with a fatal error on PHP 8 before showing the chart (screen and print view). It works again.
- **Helpdesk list report:** it stopped with a fatal error on PHP 8, and it filtered tickets with the task permissions. It now uses the helpdesk permissions, as the helpdesk list does.
- **vCard import:**
  - it now reads the city, postcode and country of Apple and Outlook (vCard 2.1) cards, which were lost;
  - it links the contact to the company that matches its organisation name.
- **Working duration between two dates:** it gave 0 when the end was before the start, and it ignored minutes.
- **Planning and monitoring tab** (timeplanning): fixed a JavaScript error that broke the numbering.
- **Process diagram (PDM tab):** a dependency on a task outside the diagram stopped drawing the remaining arrows.
- **Sample data:** a ready-made SQL file (`db/sample/dotpulse-sample-data.sql`) can be imported with phpMyAdmin on servers without command-line PHP, and it, like the one `make sample-sql` builds, can now be imported into a fresh installation, not only into an empty database. Its dates are fixed at 2026-11-04 ([db/sample/README.md](db/sample/README.md)).

## For developers

- The PHP unit tests (`make php-test`, `tests/run.php`) run in CI. Several test files that could not run before now do.
- Dependency audit (`make deps-audit`): CI runs it on every push and every week, and Dependabot follows the Composer and npm dependencies on `devel`.
  - Five low-severity npm advisories remain in the Jest tools, from `deepmerge`, which has no fixed release. They do not affect the application.
- The libraries copied into the code base are listed in [docs/third-party.md](docs/third-party.md).

## Upgrading

1. **Back up** the database and the `files/` directory.
2. **Replace the code.** Keep `includes/config.php` and `files/`. `vendor/` is unchanged since 3.1.0. Delete the removed directories rather than copying over the old tree, or they stay behind: `lib/PEAR`, `lib/PEAR.php`, `lib/jpgraph`, `lib/fonts`, `lib/smarty`, `lib/xajax`, `lib/ezpdf` and `modules/holiday/PEAR`.
3. **Run the installer** (`install/` in a browser). There are no database changes; it records the new version. Then remove `install/` again or deny access to it.
4. **If you use the holiday module:** upgrade it in System Admin > Modules, then choose the country in its *Settings* tab.

From a version older than 3.1.0, read [RELEASE_NOTES_v3.1.0.md](RELEASE_NOTES_v3.1.0.md) and, from dotProject, [RELEASE_NOTES_v3.0.0.md](RELEASE_NOTES_v3.0.0.md) as well.

## Requirements

- PHP 8.1 or later (8.3 is tested), MySQL or MariaDB.
- Apache (recommended) or nginx with the rules from the deployment guide.
