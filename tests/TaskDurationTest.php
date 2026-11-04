<?php
// The default working time: 08:00 to 17:00, 8 hours a day, Monday to Friday.
function dPgetConfig($key, $default = null) {
    $config = array('cal_day_start' => '8', 'cal_day_end' => '17', 'daily_working_hours' => '8.0',
        'cal_working_days' => '1,2,3,4,5');
    return isset($config[$key]) ? $config[$key] : $default;
}
function dPsgn($x) {
    return $x ? ($x > 0 ? 1 : -1) : 0;
}
require_once dirname(__FILE__) . '/bootstrap.php';
require_once dirname(__FILE__) . '/../includes/strftime.php';
require_once dirname(__FILE__) . '/../classes/date.class.php';

/**
 * Task end dates from a start and a duration in working hours (CDate::addDuration),
 * and back (CDate::calcDuration).
 */
class TaskDurationTest extends TestCase {
    private function end($start, $duration, $type = '1') {
        $d = new CDate($start);
        $d->addDuration($duration, $type);
        return $d->format('%a %Y-%m-%d %H:%M');
    }

    function testAddDuration() {
        // 2026-11-02 is a Monday.
        $this->assertEquals('Mon 2026-11-02 12:00', $this->end('2026-11-02 08:00', 4));
        $this->assertEquals('Mon 2026-11-02 16:00', $this->end('2026-11-02 08:00', 8));
        $this->assertEquals('Tue 2026-11-03 09:00', $this->end('2026-11-02 08:00', 9));
        // Whole days end after the last day's working hours, not at the start of the next day.
        $this->assertEquals('Tue 2026-11-03 16:00', $this->end('2026-11-02 08:00', 16));
        $this->assertEquals('Wed 2026-11-04 16:00', $this->end('2026-11-02 08:00', 24));
        $this->assertEquals('Tue 2026-11-03 12:00', $this->end('2026-11-02 13:00', 8));
        // Weekends are skipped.
        $this->assertEquals('Mon 2026-11-09 12:00', $this->end('2026-11-06 13:00', 8));
        $this->assertEquals('Mon 2026-11-09 16:00', $this->end('2026-11-06 08:00', 16));
        $this->assertEquals('Mon 2026-11-09 12:00', $this->end('2026-11-07 10:00', 4));
        // Fractions of an hour are kept.
        $this->assertEquals('Mon 2026-11-02 12:30', $this->end('2026-11-02 08:00', 4.5));
        $this->assertEquals('Tue 2026-11-03 10:15', $this->end('2026-11-02 08:00', 10.25));
        // Backwards from an end date.
        $this->assertEquals('Fri 2026-11-06 09:00', $this->end('2026-11-06 17:00', -8));
        $this->assertEquals('Thu 2026-11-05 09:00', $this->end('2026-11-06 17:00', -16));
    }

    function testCalcDuration() {
        $s = new CDate('2026-11-02 08:00:00');
        $this->assertEquals(24.0, $s->calcDuration(new CDate('2026-11-04 16:00:00')));
        $this->assertEquals(4.5, $s->calcDuration(new CDate('2026-11-02 12:30:00')));
        // Over a weekend.
        $s = new CDate('2026-11-06 13:00:00');
        $this->assertEquals(8.0, $s->calcDuration(new CDate('2026-11-09 12:00:00')));
        // An end before the start gives a negative duration (it gave 0).
        $e = new CDate('2026-11-04 16:00:00');
        $this->assertEquals(-24.0, $e->calcDuration(new CDate('2026-11-02 08:00:00')));
        $this->assertEquals('2026-11-04 16:00:00', $e->format('%Y-%m-%d %H:%M:%S'));
    }

    function testRoundTrip() {
        foreach (array('2026-11-02 08:00', '2026-11-03 13:00', '2026-11-06 15:30') as $start) {
            for ($quarters = 1; $quarters <= 4 * 60; $quarters++) {
                $hours = $quarters / 4.0;
                $s = new CDate($start);
                $e = new CDate($start);
                $e->addDuration($hours, '1');
                $this->assertEquals($hours, $s->calcDuration($e), "$start + $hours h ends " . $e->format('%Y-%m-%d %H:%M'));
            }
        }
    }
}
?>
