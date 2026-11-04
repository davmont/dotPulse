<?php
require_once dirname(__FILE__) . '/bootstrap.php';
require_once dirname(__FILE__) . '/../modules/holiday/holiday_calendar.class.php';

/**
 * The automatic holidays of the holiday module, by country
 * (modules/holiday/holiday_calendar.class.php), against the published calendars.
 */
class HolidayCalendarTest extends TestCase {
    function testEaster() {
        $known = array(2000 => '2000-04-23', 2019 => '2019-04-21', 2024 => '2024-03-31', 2025 => '2025-04-20',
            2026 => '2026-04-05', 2027 => '2027-03-28', 2038 => '2038-04-25', 2285 => '2285-03-22');
        foreach ($known as $year => $date) {
            $this->assertEquals($date, gmdate('Y-m-d', CHolidayCalendar::easter($year)), "Easter $year");
        }
    }

    function testSpain() {
        $this->assertEquals(array('2026-01-01', '2026-01-06', '2026-04-03', '2026-05-01', '2026-08-15',
            '2026-10-12', '2026-11-01', '2026-12-06', '2026-12-08', '2026-12-25'),
            array_keys(CHolidayCalendar::forYear('ES', 2026)));
        $this->assertEquals('Good Friday', CHolidayCalendar::holidayName('ES', '2026-04-03'));
        $this->assertEquals(null, CHolidayCalendar::holidayName('ES', '2026-04-02'));
    }

    function testUnitedKingdomSubstituteDays() {
        // gov.uk: 2026 Boxing Day is a Saturday, the 28th is off; in 2027 both fall on the weekend.
        $days = CHolidayCalendar::forYear('GB', 2026);
        foreach (array('2026-01-01', '2026-04-03', '2026-04-06', '2026-05-04', '2026-05-25', '2026-08-31',
                '2026-12-25', '2026-12-28') as $date) {
            $this->assertEquals(true, isset($days[$date]), "GB $date");
        }
        $this->assertEquals('Boxing Day (substitute day)', $days['2026-12-28']);
        $days = CHolidayCalendar::forYear('GB', 2027);
        $this->assertEquals('Christmas Day (substitute day)', $days['2027-12-27']);
        $this->assertEquals('Boxing Day (substitute day)', $days['2027-12-28']);
        // 2022: Christmas on a Sunday, Boxing Day on the Monday, Christmas moves to Tuesday.
        $this->assertEquals('Christmas Day (substitute day)', CHolidayCalendar::holidayName('GB', '2022-12-27'));
        $this->assertEquals('Spring bank holiday', CHolidayCalendar::holidayName('GB', '2022-06-02'));
        $this->assertEquals('Early May bank holiday', CHolidayCalendar::holidayName('GB', '2020-05-08'));
    }

    function testUnitedStatesObserved() {
        $days = CHolidayCalendar::forYear('US', 2026);
        $this->assertEquals('Martin Luther King Jr. Day', $days['2026-01-19']);
        $this->assertEquals('Memorial Day', $days['2026-05-25']);
        $this->assertEquals('Independence Day (observed)', $days['2026-07-03']);
        $this->assertEquals('Labor Day', $days['2026-09-07']);
        $this->assertEquals('Thanksgiving Day', $days['2026-11-26']);
        // 1 January 2022 was a Saturday: observed on Friday 31 December 2021.
        $this->assertEquals("New Year's Day (observed)", CHolidayCalendar::holidayName('US', '2021-12-31'));
        $this->assertEquals(false, isset($days['2025-12-31']));
    }

    function testMovableDays() {
        $this->assertEquals('Ascension Day', CHolidayCalendar::holidayName('DE', '2026-05-14'));
        $this->assertEquals('Whit Monday', CHolidayCalendar::holidayName('DE', '2026-05-25'));
        $this->assertEquals('Midsummer Eve', CHolidayCalendar::holidayName('SE', '2026-06-19'));
        $this->assertEquals("All Saints' Day", CHolidayCalendar::holidayName('SE', '2026-10-31'));
        $this->assertEquals("St Brigid's Day", CHolidayCalendar::holidayName('IE', '2026-02-02'));
        $this->assertEquals("St Brigid's Day", CHolidayCalendar::holidayName('IE', '2030-02-01'));
        $this->assertEquals("King's Day", CHolidayCalendar::holidayName('NL', '2025-04-26'));
        $this->assertEquals('Corpus Christi', CHolidayCalendar::holidayName('PT', '2026-06-04'));
        $this->assertEquals(null, CHolidayCalendar::holidayName('PT', '2014-06-19'));
    }

    function testEveryCountry() {
        foreach (array_keys(CHolidayCalendar::countries()) as $country) {
            for ($year = 2000; $year <= 2040; $year++) {
                $days = CHolidayCalendar::forYear($country, $year);
                $this->assertEquals(true, count($days) >= 7, "$country $year has " . count($days) . ' holidays');
                $this->assertEquals("$year-01-01", key($days), "$country $year starts on New Year's Day");
            }
        }
        $this->assertEquals(array(), CHolidayCalendar::forYear('XX', 2026));
    }
}
?>
