<?php
// The mock DBQuery of bootstrap.php (mockResults, mockExecReturns).
require_once dirname(__FILE__) . '/bootstrap.php';
require_once dirname(__FILE__) . '/../classes/authenticator.class.php';

// A users.user_password column long enough for password_hash() (main_functions.php asks the database).
if (!function_exists('dPpasswordColumnFitsHash')) {
    function dPpasswordColumnFitsHash() { return true; }
}
if (!function_exists('dPhashPassword')) {
    function dPhashPassword($password) { return password_hash($password, PASSWORD_DEFAULT); }
}

class SQLAuthenticatorTest extends TestCase {

    function setUp() {
        // Reset mock state before each test
        DBQuery::$mockResults = array();
        DBQuery::$mockExecReturns = true;
    }

    function testAuthenticateSuccess() {
        $username = 'testuser';
        $password = 'password123';
        $hashedPassword = md5($password);
        $userId = 42;

        // Mock query result
        DBQuery::$mockResults = array(
            array('user_id' => $userId, 'user_password' => $hashedPassword)
        );

        $auth = new SQLAuthenticator();
        $result = $auth->authenticate($username, $password);

        $this->assert($result, "Authentication should succeed with correct credentials");
        $this->assertEquals($userId, $auth->user_id, "User ID should be correctly set on success");
    }

    function testAuthenticateWrongPassword() {
        $username = 'testuser';
        $correctPassword = 'password123';
        $wrongPassword = 'wrongpassword';
        $hashedPassword = md5($correctPassword);
        $userId = 42;

        // Mock query result with correct hash
        DBQuery::$mockResults = array(
            array('user_id' => $userId, 'user_password' => $hashedPassword)
        );

        $auth = new SQLAuthenticator();
        $result = $auth->authenticate($username, $wrongPassword);

        $this->assert(!$result, "Authentication should fail with incorrect password");
    }

    function testAuthenticateUserNotFound() {
        $username = 'nonexistent';
        $password = 'anypassword';

        // Mock empty query result
        DBQuery::$mockResults = array();

        $auth = new SQLAuthenticator();
        $result = $auth->authenticate($username, $password);

        $this->assert(!$result, "Authentication should fail if user is not found");
    }

    function testAuthenticateQueryError() {
        $username = 'testuser';
        $password = 'password123';

        // Simulate query execution failure
        DBQuery::$mockExecReturns = false;

        $auth = new SQLAuthenticator();
        $result = $auth->authenticate($username, $password);

        $this->assert(!$result, "Authentication should fail if database query fails");
    }

    function testUserId() {
        $auth = new SQLAuthenticator();
        $auth->user_id = 99;

        $this->assertEquals(99, $auth->userId(), "userId() should return the stored user_id");
    }
}
?>
