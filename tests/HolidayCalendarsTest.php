<?php
require_once dirname(__FILE__) . '/bootstrap.php';
require_once dirname(__FILE__) . '/../modules/holiday/holiday_func.php';

/**
 * The holidays of a workplace calendar (modules/holiday/holiday_func.php): its
 * own days, the days of the calendars it builds on and its country's.
 */
class HolidayCalendarsTest extends TestCase {
    private function day($white, $start, $end = null, $annual = 0, $description = '') {
        return array('holiday_white' => $white, 'holiday_annual' => $annual, 'holiday_description' => $description,
            'holiday_start_date' => $start . ' 00:00:00', 'holiday_end_date' => ($end ? $end : $start) . ' 00:00:00');
    }

    function testEntryCovers() {
        $row = $this->day(1, '2026-12-24', '2026-12-31');
        $this->assertEquals(true, holidayEntryCovers($row, '2026-12-24'));
        $this->assertEquals(true, holidayEntryCovers($row, '2026-12-31'));
        $this->assertEquals(false, holidayEntryCovers($row, '2027-12-24'));
        // Every year.
        $row = $this->day(1, '2020-05-15', null, 1, 'San Isidro');
        $this->assertEquals(true, holidayEntryCovers($row, '2026-05-15'));
        $this->assertEquals(false, holidayEntryCovers($row, '2026-05-16'));
    }

    function testLookup() {
        // Madrid 2026: San Isidro (local) on top of the national holidays.
        $madrid = array($this->day(1, '2026-05-15', null, 0, 'San Isidro'));
        $this->assertEquals('San Isidro', holidayLookup($madrid, 'ES', '2026-05-15'));
        $this->assertEquals('Good Friday', holidayLookup($madrid, 'ES', '2026-04-03'));
        $this->assertEquals(null, holidayLookup($madrid, 'ES', '2026-09-24'));
        // Barcelona works on San Isidro, and has La Mercè.
        $barcelona = array($this->day(1, '2026-09-24', null, 0, 'La Mercè'));
        $this->assertEquals(null, holidayLookup($barcelona, 'ES', '2026-05-15'));
        $this->assertEquals('La Mercè', holidayLookup($barcelona, 'ES', '2026-09-24'));
        // The blacklist wins over the whitelist and the national holidays.
        $open = array($this->day(0, '2026-12-08'), $this->day(1, '2026-12-08', null, 0, 'Closed'));
        $this->assertEquals(null, holidayLookup($open, 'ES', '2026-12-08'));
        // No country: only the days entered.
        $this->assertEquals(null, holidayLookup(array(), '', '2026-12-25'));
        $this->assertEquals('', holidayLookup(array($this->day(1, '2026-08-10')), '', '2026-08-10'));
    }

    function testChain() {
        $calendars = array(
            1 => array('calendar_id' => 1, 'calendar_parent' => 0, 'calendar_country' => 'ES'),
            2 => array('calendar_id' => 2, 'calendar_parent' => 1, 'calendar_country' => ''),
            3 => array('calendar_id' => 3, 'calendar_parent' => 4, 'calendar_country' => ''),
            4 => array('calendar_id' => 4, 'calendar_parent' => 3, 'calendar_country' => ''),
        );
        $this->assertEquals(array(2, 1), array_keys(holidayCalendarChain(2, $calendars)));
        $this->assertEquals(array(1), array_keys(holidayCalendarChain(1, $calendars)));
        $this->assertEquals(array(), array_keys(holidayCalendarChain(0, $calendars)));
        $this->assertEquals(array(), array_keys(holidayCalendarChain(9, $calendars)));
        // A loop of parents stops where it closes.
        $this->assertEquals(array(3, 4), array_keys(holidayCalendarChain(3, $calendars)));
    }
}
?>
