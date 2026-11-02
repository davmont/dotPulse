<?php
if (!defined('DP_BASE_DIR')) {
    die('DP_BASE_DIR not defined');
}
require_once DP_BASE_DIR . '/classes/twofactor.class.php';

/**
 * TOTP of CTwoFactor (classes/twofactor.class.php): RFC 4648 base32 and the RFC 6238
 * test vectors (SHA-1 secret "12345678901234567890").
 */
class TwoFactorTest extends TestCase {
    var $secret;

    function setUp() {
        $this->secret = CTwoFactor::base32Encode('12345678901234567890');
    }

    function testBase32RoundTrip() {
        // RFC 4648 section 10 (without padding).
        $this->assertEquals('MZXW6YTBOI', CTwoFactor::base32Encode('foobar'));
        $this->assertEquals('foobar', CTwoFactor::base32Decode('mzxw 6ytb oi======'));
        $this->assertEquals(false, CTwoFactor::base32Decode('MZXW6YTB0I'));
        $bin = random_bytes(20);
        $this->assertEquals($bin, CTwoFactor::base32Decode(CTwoFactor::base32Encode($bin)));
        $this->assertEquals(32, strlen(CTwoFactor::newSecret()));
    }

    function testRfc6238Vectors() {
        // time => 8-digit TOTP (RFC 6238 appendix B, SHA-1).
        $vectors = array(59 => '94287082', 1111111109 => '07081804', 1111111111 => '14050471',
            1234567890 => '89005924', 2000000000 => '69279037', 20000000000 => '65353130');
        foreach ($vectors as $time => $code) {
            $this->assertEquals($code, CTwoFactor::code($this->secret, intdiv($time, 30), 8), "time $time");
            $this->assertEquals(substr($code, 2), CTwoFactor::code($this->secret, intdiv($time, 30)), "6 digits at $time");
        }
    }

    function testMatchWindowAndReplay() {
        $t = 1234567890;
        $now = intdiv($t, 30);
        $code = CTwoFactor::code($this->secret, $now);
        $this->assertEquals($now, CTwoFactor::matchCounter($this->secret, $code, $t));
        $this->assertEquals($now, CTwoFactor::matchCounter($this->secret, substr($code, 0, 3) . ' ' . substr($code, 3), $t));
        // One step either side is accepted, two are not.
        $this->assertEquals($now - 1, CTwoFactor::matchCounter($this->secret, CTwoFactor::code($this->secret, $now - 1), $t));
        $this->assertEquals($now + 1, CTwoFactor::matchCounter($this->secret, CTwoFactor::code($this->secret, $now + 1), $t));
        $this->assertEquals(false, CTwoFactor::matchCounter($this->secret, CTwoFactor::code($this->secret, $now - 2), $t));
        // A step already used (or an earlier one) is refused.
        $this->assertEquals(false, CTwoFactor::matchCounter($this->secret, $code, $t, $now));
        $this->assertEquals(false, CTwoFactor::matchCounter($this->secret, '12345', $t));
        $this->assertEquals(false, CTwoFactor::matchCounter($this->secret, 'abcdef', $t));
    }

    function testOtpauthUri() {
        $uri = CTwoFactor::otpauthUri('ABC', 'ana@example.com', 'dotPulse');
        $this->assertEquals('otpauth://totp/dotPulse%3Aana%40example.com?secret=ABC&issuer=dotPulse&algorithm=SHA1&digits=6&period=30', $uri);
    }
}
