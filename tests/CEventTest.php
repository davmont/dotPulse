<?php
if (!defined('DP_BASE_DIR')) {
    define('DP_BASE_DIR', realpath(dirname(__FILE__) . '/../'));
}

require_once DP_BASE_DIR . '/tests/phpunit.php';
// calendar.class.php needs the real DBQuery, not the mock of bootstrap.php.
require_once DP_BASE_DIR . '/classes/query.class.php';
require_once DP_BASE_DIR . '/tests/bootstrap.php';

// CEvent requires calendar.class.php
require_once DP_BASE_DIR . '/modules/calendar/calendar.class.php';

class CEventTest extends TestCase {
    public function testConstructor() {
        $event = new CEvent();

        $reflect = new ReflectionClass($event);

        $_tbl = $reflect->getProperty('_tbl');
        $_tbl->setAccessible(true);
        $this->assertEquals('events', $_tbl->getValue($event), "Table name should be 'events'");

        $_tbl_key = $reflect->getProperty('_tbl_key');
        $_tbl_key->setAccessible(true);
        $this->assertEquals('event_id', $_tbl_key->getValue($event), "Table key should be 'event_id'");

        $_permission_name = $reflect->getProperty('_permission_name');
        $_permission_name->setAccessible(true);
        $this->assertEquals('events', $_permission_name->getValue($event), "Permission name should default to 'events'");

        $_query = $reflect->getProperty('_query');
        $_query->setAccessible(true);
        $this->assert(is_object($_query->getValue($event)), "Query object should be initialized");
        // In our test environment, DBQuery is likely the mock from bootstrap.php
        $this->assert($_query->getValue($event) instanceof DBQuery, "Query object should be instance of DBQuery");
    }
}
?>
