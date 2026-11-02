<?php
require_once dirname(__FILE__) . '/bootstrap.php';
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

}

?>