# Phase 1 & 2 Library Modernization — Testing Plan

**Branch:** `devel`  
**Date:** 2026-06-15  
**Environment:** http://localhost/~david/proyectos/ (or equivalent local URL)

---

## 1. Email (PHPMailer replaces raw SMTP)

### Setup
Admin → Configuration → Mail. Configure SMTP or leave as `php` transport.

### Test cases

| # | Action | Expected |
|---|---|---|
| E1 | Create a new user (Admin → Users → Add) | Welcome email sent; **no plaintext password** in message body; user gets directed to Forgot Password link |
| E2 | Use Forgot Password on the login screen | Email arrives with a new temporary password |
| E3 | Assign a user to a calendar event with notification enabled | Notification email arrives |
| E4 | Post to a forum that has email notifications on | Subscribers receive the post email |
| E5 | With SMTP configured: send any of E1–E4 | Email delivered via SMTP; no PHP warnings in error log |
| E6 | Set `mail_defer = 1` in config | Email queued (no immediate send); queue manager drains it correctly |

**Regression check:** After each email action, confirm no `Fatal error` or `Call to undefined method` in the Apache/PHP error log.

---

## 2. Tooltip (overlib-shim replaces overLIB)

### Test cases

| # | Page | Action | Expected |
|---|---|---|---|
| T1 | Annotations module (index) | Hover over a project row | Card-style tooltip appears near cursor with description text |
| T2 | Annotations / addedit | Hover over a strategy/risks/sizing cell | Tooltip with caption "Description" appears; disappears on mouseout |
| T3 | Opportunities → project list | Hover over a project with a description | Tooltip with HTML content renders correctly |
| T4 | Move mouse away from tooltip element | `nd()` fires; tooltip disappears | |
| T5 | Hover over several items in a row | Tooltip correctly updates content each time (no stale content) |
| T6 | Open DevTools console | No `overlib is not defined` or `nd is not defined` JS errors |

---

## 3. Date picker (Flatpickr replaces jscalendar)

### Test cases

| # | Page | Action | Expected |
|---|---|---|---|
| D1 | Inventory → Add/Edit item | Click the calendar icon next to a date field | Flatpickr popup appears |
| D2 | Select any date in the picker | Input field shows `YYYY-MM-DD` format (e.g. `2026-06-20`) | |
| D3 | Invoices → Add/Edit | Click calendar icon for `date1` and `date2` | Both pickers work independently |
| D4 | Annotations → addedit date fields | Calendar triggers Flatpickr | |
| D5 | Type a date directly into an input (allowInput=true) | Field accepts manually typed `YYYY-MM-DD` |
| D6 | Open DevTools console | No `showCalendar is not defined` errors |
| D7 | Open two different date fields on the same page | Each picker works independently; clicking one doesn't break the other |

---

## 4. PDF reports (Dompdf replaces ezpdf)

### Test cases

| # | Report | Action | Expected |
|---|---|---|---|
| P1 | Projects → Reports → Completed Tasks | Generate PDF | PDF downloads; landscape A4; company name, date, table with columns |
| P2 | Projects → Reports → Overdue Tasks | Generate PDF | Same layout, correct data |
| P3 | Projects → Reports → Upcoming Tasks | Generate PDF | Same layout, correct data |
| P4 | Projects → Reports → Task List | Generate PDF | Table with task names, owners, dates |
| P5 | Projects → Reports → Task Logs | Generate PDF | Two-section layout (header + log rows) |
| P6 | Forums → View forum thread → Print / PDF | Generate PDF | Forum posts render in portrait A4 |
| P7 | Helpdesk → Reports → Helpdesk List | Generate PDF | Helpdesk items in table |
| P8 | Helpdesk → Reports → Task Logs | Generate PDF | |
| P9 | Timecard → Calendar by User | Generate PDF | |
| P10 | Macroprojects equivalents of P1–P5 | Generate PDFs | Identical quality to projects reports |

**Check for each PDF:**
- [ ] File actually downloads (no PHP fatal error)
- [ ] Content is readable (UTF-8 accented characters in Spanish show correctly — e.g. "Descripción", "Módulo")
- [ ] Table columns align to expected widths
- [ ] No garbled boxes where accented characters were (ISO-8859-1 re-encoding working)
- [ ] `tasks/gantt_pdf.php` (the Gantt PDF from Tasks module) still works — it stays on legacy ezpdf

**Negative test:**
- P11: Tasks → Gantt → Export PDF — should still work (uses old ezpdf, untouched)

---

## 5. Regression: Core navigation

Quick smoke-test that the library loading changes didn't break anything foundational.

| # | Action | Expected |
|---|---|---|
| R1 | Login and logout | Works; session cookie has `HttpOnly`, `Secure` (if HTTPS), `SameSite=Lax` |
| R2 | Navigate to Projects, Tasks, Calendar, Contacts | Pages load without PHP errors |
| R3 | Open browser DevTools console on any page | No JS errors in console from overlib-shim or calendar.js loading |
| R4 | Open a form with a date field and a tooltip element on the same page | Both features work together without conflict |
| R5 | View page source of any page | `flatpickr.min.css`, `flatpickr.min.js`, `overlib-shim.js` appear in `<head>` |

---

## 6. What's NOT tested here (deferred to Phase 2b/3)

- `tasks/gantt_pdf.php` modernization (left on ezpdf — complex drawing)
- `modules/igantt/IEversion/` xajax code (dead code — IE is gone; scheduled for deletion)
- jpgraph Gantt charts in `macroprojects/gantt.php` (scheduled for Chart.js migration)
- Monitoring & control line/pie charts (jpgraph — scheduled for Chart.js)

---

## Logging

If any test fails, check:
```
tail -f /var/log/apache2/error.log
# or
tail -f /var/log/php8.x/error.log
```

Key patterns to look for:
- `Class "DotPdf" not found` → dpdf.class.php not loading
- `Class "PHPMailer\PHPMailer\PHPMailer" not found` → vendor/autoload.php not included
- `flatpickr is not defined` → Flatpickr JS not loaded before calendar.js
- `overlib is not defined` → overlib-shim.js not loading
