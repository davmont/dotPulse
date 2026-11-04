dotPulse Holiday module 0.2
---------------------------

Non-working days for the task dates (CDate::isWorkingDay, so the end date
calculated from a start date and a duration skips them).

- Whitelist: days off added by hand, one-off or every year (regional and
  local holidays, company closures).
- Blacklist: days that are working days even if they are holidays.
- Settings: turn the manual and the automatic holidays on or off and choose
  the country of the automatic ones: the national public holidays of
  Austria, Belgium, France, Germany, Ireland, Italy, the Netherlands,
  Norway, Portugal, Spain, Sweden, the United Kingdom (England and Wales)
  or the United States (federal), calculated for any year
  (holiday_calendar.class.php). The Settings tab lists this year's and next
  year's.

The blacklist wins over the whitelist, and both over the automatic holidays.

Before dotPulse 3.1.1 the automatic holidays came from PEAR Date_Holidays,
which does not work on PHP 8. Upgrading the module to 0.2 (System Admin,
Modules) adds the country setting; choose the country again.

Originally by Sensorlink AS (c) 2006, Vegard Fiksdal.
