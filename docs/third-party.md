# Third-party libraries

dotPulse ships its libraries with the code. Those installed with Composer (`vendor/`) are
checked by `make deps-audit` and updated by Dependabot. The others were copied in by hand:
nothing warns about their advisories, so this list is the place to check them, and to
update when one is replaced.

## Composer (`vendor/`, `composer.json`)

| Library | Used for |
| --- | --- |
| PHPMailer 7 | all mail |
| Dompdf 3 | PDF reports (through `classes/dpdf.class.php`) |
| chillerlan/php-qrcode 5 | the QR code of two-step login |

`vendor/` is committed, so a server needs no Composer. A Dependabot pull request changes
`composer.json` and `composer.lock` only: before merging it, run `composer install` and
commit `vendor/` as well.

## Copied into `lib/`

| Library | Version | Used by | Notes |
| --- | --- | --- | --- |
| ADOdb | 5.22.11 | the database layer | maintained; update from upstream releases |
| phpGACL | 3.3.x (2004) | permissions (`classes/permissions.class.php`) | unmaintained; only the two classes and the cache are kept |
| jQuery | 3.7.1 | finances, resource_m, monitoringandcontrol, dotproject_plus | `lib/jquery/` |
| jQuery UI | 1.14.1 | dotproject_plus (dialog, autocomplete) | `lib/jquery-ui/`, base theme |
| Chart.js | 4.4.4 | monitoringandcontrol charts | |
| flatpickr | 4.6.13 | date fields | |
| Frappe Gantt | | the interactive Gantt charts (core, igantt) | |
| JpGraph | 3.0.7 (2011) | Gantt images of tasks and projectdesigner | unmaintained; to be replaced by Frappe Gantt or Chart.js |
| PEAR Date, Contact_Vcard | | `CDate`, vCard import/export | unmaintained |
| overLIB | 4.21 | pop-up help texts | unmaintained |
| DHTML calendar | | date pickers of core and add-on forms | |
| DejaVu, FreeFont, Vera | | fonts for JpGraph | |

## Copied into add-on modules

| Library | Version | Module | Notes |
| --- | --- | --- | --- |
| jQuery treeTable | 2.3.0 | finances, resource_m | works with jQuery 3 |
| WireIt, YUI 2 utilities | 0.6.0, 2.8.0 | timeplanning (PDM tab) | unmaintained; only the files the tab loads are kept |
| dhtmlgoodies calendar | | timeplanning, monitoringandcontrol, dotproject_plus | |
| PEAR Date_Holidays | | holiday | |

Removed in 3.1.1: Smarty 2.6.3, xajax 0.5, R&OS ezPDF, TinyMCE 3.3.9, jQuery 1.4.2 and 1.12.3,
jQuery UI 1.8rc3 and 1.12.1, DataTables, maskMoney, and most of WireIt and YUI 2.
