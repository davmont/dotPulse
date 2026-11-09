dotPulse Holiday module 0.3
---------------------------

Non-working days for the task dates (CDate::isWorkingDay, so the end date
calculated from a start date and a duration skips them).

- Calendars: the holidays of each workplace (an office, a city). A calendar
  has the national holidays of a country, its own days (regional and local
  holidays, closures) and those of the calendar it builds on: "Madrid" can
  build on "Comunidad de Madrid", which has Spain's national holidays.
  Calendars are given to companies, departments or users. A user has their
  own calendar, else their department's (or a parent department's), else
  their company's, as set in their contact; else the default holidays.
  - A task's dates (end date from a duration, dates moved with dependencies
    or when a project is copied) skip the holidays of the task owner.
  - Reminders reach each person on their own working days, and the
    allocated hours report counts each person's own working days.
- Whitelist: days off added by hand, one-off or every year, for every
  calendar or for one.
- Blacklist: days that are working days even if they are holidays, for
  every calendar or for one.
- Settings: turn the manual and the automatic holidays on or off and choose
  the default country of the automatic ones (for the people without a
  calendar and the calendars without a country): the national public holidays of
  Austria, Belgium, France, Germany, Ireland, Italy, the Netherlands,
  Norway, Portugal, Spain, Sweden, the United Kingdom (England and Wales)
  or the United States (federal), calculated for any year
  (holiday_calendar.class.php). The Settings tab lists this year's and next
  year's.

The blacklist wins over the whitelist, and both over the automatic holidays.

Regional and local holidays are set every year by decree (in Spain, the
regional ones in the BOE each autumn and the local ones in each province's
bulletin), so they are entered in the calendars.

Upgrading to 0.3 keeps the existing whitelist and blacklist days for every
calendar.

Before dotPulse 3.1.1 the automatic holidays came from PEAR Date_Holidays,
which does not work on PHP 8. Upgrading the module to 0.2 (System Admin,
Modules) adds the country setting; choose the country again.

Originally by Sensorlink AS (c) 2006, Vegard Fiksdal.
