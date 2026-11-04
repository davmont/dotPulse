<?php
require_once dirname(__FILE__) . '/bootstrap.php';
require_once dirname(__FILE__) . '/../includes/strftime.php';
require_once dirname(__FILE__) . '/../classes/date.class.php';

class CDateTest extends TestCase {

    function setUp() {
        // Any setup if needed
    }

    function testDateDiff_SameDate() {
        $date1 = new CDate('2023-01-01 12:00:00');
        $date2 = new CDate('2023-01-01 15:30:00'); // Time difference shouldn't matter

        // CDate::dateDiff returns absolute difference in days
        $diff = $date1->dateDiff($date2);

        $this->assertEquals(0, $diff, "Difference between same dates should be 0");
    }

    function testDateDiff_FutureDate() {
        $date1 = new CDate('2023-01-01 00:00:00');
        $date2 = new CDate('2023-01-10 00:00:00');

        $diff = $date1->dateDiff($date2);

        $this->assertEquals(9, $diff, "Difference between 2023-01-01 and 2023-01-10 should be 9 days");
    }

    function testDateDiff_PastDate() {
        $date1 = new CDate('2023-01-10 00:00:00');
        $date2 = new CDate('2023-01-01 00:00:00');

        // Pear Date_Calc::dateDiff uses abs() so difference is always positive
        $diff = $date1->dateDiff($date2);

        $this->assertEquals(9, $diff, "Difference between 2023-01-10 and 2023-01-01 should be 9 days");
    }

    function testDateDiff_LeapYear() {
        $date1 = new CDate('2024-02-28 00:00:00');
        $date2 = new CDate('2024-03-01 00:00:00');

        $diff = $date1->dateDiff($date2);

        // 2024 is a leap year, so Feb has 29 days. Diff between Feb 28 and Mar 1 is 2 days.
        $this->assertEquals(2, $diff, "Difference between Feb 28 and Mar 1 in leap year should be 2 days");

        $date3 = new CDate('2023-02-28 00:00:00');
        $date4 = new CDate('2023-03-01 00:00:00');

        $diff_non_leap = $date3->dateDiff($date4);

        // 2023 is not a leap year. Diff between Feb 28 and Mar 1 is 1 day.
        $this->assertEquals(1, $diff_non_leap, "Difference between Feb 28 and Mar 1 in non-leap year should be 1 day");
    }

    function testDateDiff_CrossYear() {
        $date1 = new CDate('2022-12-25 00:00:00');
        $date2 = new CDate('2023-01-05 00:00:00');

        $diff = $date1->dateDiff($date2);

        // 6 days in Dec (26, 27, 28, 29, 30, 31) + 5 days in Jan (1, 2, 3, 4, 5) = 11
        $this->assertEquals(11, $diff, "Difference crossing year should be 11 days");
    }

    function testDateDiff_InvalidDate() {
        $date1 = new CDate('2023-01-01');
        // Let's create an invalid date to trigger the -1 return in Date_Calc::dateDiff
        // N.B.: We might need to manually set the day/month/year since CDate might try to fix it.
        $date2 = new CDate('2023-01-01');
        $date2->setDay(32); // Invalid day for Jan

        $diff = $date1->dateDiff($date2);

        // Date_Calc returns -1 for invalid dates
        $this->assertEquals(-1, $diff, "Difference with invalid date should be -1");
    }

    function testGetAMPM() {
        $date = new CDate('2023-01-01 00:00:00');
        $this->assertEquals('am', $date->getAMPM(), "00:00 should be am");

        $date = new CDate('2023-01-01 11:59:59');
        $this->assertEquals('am', $date->getAMPM(), "11:59 should be am");

        $date = new CDate('2023-01-01 12:00:00');
        $this->assertEquals('pm', $date->getAMPM(), "12:00 should be pm");

        $date = new CDate('2023-01-01 23:59:59');
        $this->assertEquals('pm', $date->getAMPM(), "23:59 should be pm");
    }


    // CDate replaced PEAR Date in dotPulse 3.1.1; these pin the behaviour it kept.

    function testParse() {
        $date = new CDate('20261103153045');
        $this->assertEquals('2026-11-03 15:30:45', $date->getDate());
        $date = new CDate('202611031530'); // YYYYMMDDHHMM
        $this->assertEquals('2026-11-03 15:30:00', $date->getDate());
        $date = new CDate('2026-11-03');
        $this->assertEquals('2026-11-03 00:00:00', $date->getDate());
        foreach (array('0000-00-00 00:00:00', '2026-02-30', 'garbage') as $invalid) {
            $date = new CDate($invalid);
            $this->assert($date->isNull(), "'$invalid' is not a date");
        }
        $date = new CDate();
        $date->setDate(mktime(10, 20, 30, 7, 15, 2026), DATE_FORMAT_UNIXTIME);
        $this->assertEquals('2026-07-15 10:20:30', $date->getDate());
        $this->assertEquals((string)mktime(10, 20, 30, 7, 15, 2026), $date->getDate(DATE_FORMAT_UNIXTIME));
    }

    function testFormat() {
        $date = new CDate('2026-11-03 15:05:09');
        $this->assertEquals('03/11/2026 15:05', $date->format('%d/%m/%Y %H:%M'));
        $this->assertEquals('03:05 pm|3|307|2|20261103', $date->format('%I:%M %p|%e|%j|%w|%Y%m%d'));
        $this->assertEquals('Tuesday Tue November Nov', $date->format('%A %a %B %b'));
    }

    function testAddMonthsClampsTheDay() {
        $date = new CDate('2026-01-31 09:00:00');
        $date->addMonths(1);
        $this->assertEquals('2026-02-28 09:00:00', $date->getDate());
        $date = new CDate('2024-02-29');
        $date->addYears(1);
        $this->assertEquals('2025-02-28 00:00:00', $date->getDate());
        $date = new CDate('2026-03-31');
        $date->addMonths(-13);
        $this->assertEquals('2025-02-28 00:00:00', $date->getDate());
    }

    function testAddSecondsKeepsTheWallClock() {
        $zone = date_default_timezone_get();
        date_default_timezone_set('Europe/Madrid'); // summer time starts on 2026-03-29
        $date = new CDate('2026-03-28 09:00:00');
        $date->addSeconds(86400);
        $this->assertEquals('2026-03-29 09:00:00', $date->getDate());
        $date->addSeconds(-90061);
        $this->assertEquals('2026-03-28 07:58:59', $date->getDate());
        date_default_timezone_set($zone);
    }

    function testSettersChangeOneFieldAtATime() {
        $date = new CDate('2026-01-31');
        $date->setMonth(2);
        $date->setDay(1);
        $this->assertEquals('2026-02-01 00:00:00', $date->getDate());
        $date->setTime(25, 0, 0); // an invalid hour is refused
        $this->assertEquals(0, $date->getHour());
    }

    function testCompare() {
        $a = new CDate('2026-11-03 10:00:00');
        $b = new CDate('2026-11-03 10:00:01');
        $this->assertEquals(-1, CDate::compare($a, $b));
        $this->assertEquals(1, $b->compare($b, '2026-11-03 10:00:00'));
        $this->assert($a->before($b) && $b->after($a) && $a->equals(new CDate('2026-11-03 10:00:00')));
        $next = $a->getNextDay();
        $this->assertEquals('2026-11-04 10:00:00', $next->getDate());
        $this->assertEquals(2, $a->getDayOfWeek());
    }

    function testCalendarHelpers() {
        $this->assertEquals('20261102', CDate::beginOfWeek(4, 11, 2026));
        $this->assertEquals('20261108', CDate::endOfWeek(4, 11, 2026));
        $this->assertEquals('20240229', CDate::endOfMonth(2, 2024));
        $this->assertEquals('20251201', CDate::beginOfMonthOffset(-1, 1, 2026));
        $this->assertEquals('20260201', CDate::beginOfMonthOffset(1, 1, 2026));
        $weeks = CDate::calendarMonth(2, 2026, '%d');
        $this->assertEquals(5, count($weeks));
        $this->assertEquals('26 27 28 29 30 31 01', implode(' ', $weeks[0]));
        $this->assertEquals('Mon Tue Wed Thu Fri Sat Sun', implode(' ', CDate::weekdayNames()));
    }
}

?>