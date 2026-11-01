#!/bin/bash
# Two-step login (classes/twofactor.class.php): set-up through the user's tab, the second
# step at login (index.php and fileviewer.php), replay protection, recovery codes, the
# attempt limit, turning it off and the administrator's reset. Codes are computed here from
# the key the tab shows, as a phone app would.
#
#   ./reset-db.sh && tests/twofactor.sh
set -u
. "$(dirname "$0")/lib.sh"
fail=0
pass() { echo "PASS  $1"; }
bad() { echo "FAIL  $1"; fail=1; }
clear_limits() { dc exec -T web sh -c 'rm -f /var/www/html/files/temp/ratelimit_*'; }
clear_limits
log_mark
db -e "DELETE FROM dotp_user_twofactor; DELETE FROM dotp_user_twofactor_recovery"
WID=$(db -e "SELECT user_id FROM dotp_users WHERE user_username = 'worker'")

# totp <base32 key> [step offset]
totp() {
	python3 - "$1" "${2:-0}" <<'PY'
import base64, hmac, hashlib, struct, sys, time
key = base64.b32decode(sys.argv[1].replace(' ', '').upper())
c = int(time.time()) // 30 + int(sys.argv[2])
h = hmac.new(key, struct.pack('>Q', c), hashlib.sha1).digest()
o = h[19] & 15
print('%06d' % ((struct.unpack('>I', h[o:o + 4])[0] & 0x7fffffff) % 1000000))
PY
}
own_csrf() { curl -s -b "$1" "$U?m=admin&a=viewuser&user_id=$2" | grep -o 'name="csrf_token" value="[0-9a-f]*"' | head -1 | sed 's/.*value="//;s/"//'; }
tab_of() { curl -s -b "$1" "$U?m=admin&a=viewuser&user_id=$2" | grep -o 'tab=[0-9]*">Two-step login' | grep -o '[0-9]*' | head -1; }
# tf <jar> <user id> <action> [code]
tf() { curl -s -b "$1" -o /dev/null "$U?m=admin&a=viewuser&user_id=$2" --data "dosql=do_twofactor_aed&csrf_token=$(own_csrf "$1" "$2")&tf_user=$2&tf_action=$3&tf_code=${4:-}"; }
# password_login <user> <password> <jar>: the page after the password
password_login() {
	rm -f "$3"
	curl -s -c "$3" -b "$3" -o /dev/null "$U"
	curl -s -c "$3" -b "$3" -o /dev/null "$U" -d "login=login&username=$1&password=$2"
	curl -s -c "$3" -b "$3" "$U?m=projects"
}
pending_token() { grep -o 'name="twofactor_token" value="[0-9a-f]*"' "$1" | head -1 | sed 's/.*value="//;s/"//'; }
# second_step <jar> <page with the form> <code>: the page after the code
second_step() {
	curl -s -c "$1" -b "$1" -o /dev/null "$U" --data-urlencode "twofactor_code=$3" --data "twofactor_token=$(pending_token "$2")"
	curl -s -c "$1" -b "$1" "$U?m=projects"
}
logged_in() { grep -q 'Logout' "$1"; }
code_form() { grep -q 'name="twofactor_code"' "$1"; }

# Set-up.
J=$OUT/tf-worker.jar
login worker worker "$J"
TAB=$(tab_of "$J" "$WID")
[ -n "$TAB" ] && pass "Two-step login tab in the user's account" || bad "no Two-step login tab"
tf "$J" "$WID" start
curl -s -b "$J" "$U?m=admin&a=viewuser&user_id=$WID&tab=$TAB" > "$OUT/tf-setup.html"
KEY=$(grep -o 'class="tf-secret">[A-Z2-7 ]*<' "$OUT/tf-setup.html" | sed 's/.*">//;s/<//;s/ //g')
grep -q 'src="data:image/svg+xml;base64,' "$OUT/tf-setup.html" && pass "QR code as an inline SVG image" || bad "no QR code"
[ ${#KEY} = 32 ] && pass "160-bit key shown" || bad "key: '$KEY'"
tf "$J" "$WID" enable 000000
[ -z "$(db -e "SELECT 1 FROM dotp_user_twofactor WHERE user_id = $WID")" ] && pass "a wrong code does not turn it on" || bad "turned on with a wrong code"
tf "$J" "$WID" enable "$(totp "$KEY")"
[ -n "$(db -e "SELECT 1 FROM dotp_user_twofactor WHERE user_id = $WID")" ] && pass "turned on with a valid code" || bad "not turned on"
curl -s -b "$J" "$U?m=admin&a=viewuser&user_id=$WID&tab=$TAB" > "$OUT/tf-codes.html"
CODES=($(grep -o '<li>[a-z2-7]\{4\}-[a-z2-7]\{4\}</li>' "$OUT/tf-codes.html" | sed 's/<[^>]*>//g'))
[ ${#CODES[@]} = 10 ] && pass "10 recovery codes shown" || bad "recovery codes shown: ${#CODES[@]}"
n=$(curl -s -b "$J" "$U?m=admin&a=viewuser&user_id=$WID&tab=$TAB" | grep -c '<li>[a-z2-7]\{4\}-')
[ "$n" = 0 ] && pass "recovery codes shown only once" || bad "recovery codes shown again"
n=$(db -e "SELECT COUNT(*) FROM dotp_user_twofactor_recovery WHERE user_id = $WID AND rc_hash = SHA2('${CODES[0]}', 256)")
[ "$n" = 1 ] && pass "recovery codes stored as SHA-256" || bad "recovery code storage"

# Login: the password alone does not log in.
password_login worker worker "$J" > "$OUT/tf-after-password.html"
code_form "$OUT/tf-after-password.html" && ! logged_in "$OUT/tf-after-password.html" && pass "password alone: code form, not logged in" || bad "logged in without the code"
curl -s -b "$J" "$U?m=admin&a=viewuser&user_id=$WID" | grep -q 'Low Priv' && bad "pages reachable before the code" || pass "pages not reachable before the code"
curl -s -c "$J" -b "$J" "$U" --data "twofactor_code=123456&twofactor_token=$(pending_token "$OUT/tf-after-password.html")" > "$OUT/tf-wrong.html"
code_form "$OUT/tf-wrong.html" && grep -q 'not valid' "$OUT/tf-wrong.html" && ! logged_in "$OUT/tf-wrong.html" && pass "wrong code refused" || bad "wrong code"
CODE=$(totp "$KEY" 1)
second_step "$J" "$OUT/tf-wrong.html" "$CODE" > "$OUT/tf-ok.html"
logged_in "$OUT/tf-ok.html" && pass "valid code logs in" || bad "valid code did not log in"
password_login worker worker "$J" > "$OUT/tf-p2.html"
second_step "$J" "$OUT/tf-p2.html" "$CODE" > "$OUT/tf-replay.html"
! logged_in "$OUT/tf-replay.html" && pass "the same code cannot be used twice" || bad "code replayed"
second_step "$J" "$OUT/tf-replay.html" "$(totp "$KEY" -1)" > "$OUT/tf-older.html"
! logged_in "$OUT/tf-older.html" && pass "an older code than the last used is refused" || bad "older code accepted"

# Recovery code: once.
password_login worker worker "$J" > "$OUT/tf-p3.html"
second_step "$J" "$OUT/tf-p3.html" "${CODES[1]^^}" > "$OUT/tf-rec.html"
logged_in "$OUT/tf-rec.html" && pass "recovery code logs in (any case)" || bad "recovery code"
password_login worker worker "$J" > "$OUT/tf-p4.html"
second_step "$J" "$OUT/tf-p4.html" "${CODES[1]}" > "$OUT/tf-rec2.html"
! logged_in "$OUT/tf-rec2.html" && pass "recovery code works only once" || bad "recovery code reused"

# Five wrong codes drop the pending login.
password_login worker worker "$J" > "$OUT/tf-p5.html"
page=$OUT/tf-p5.html
for i in 1 2 3 4 5; do second_step "$J" "$page" 000000 > "$OUT/tf-w$i.html"; page=$OUT/tf-w$i.html; done
! code_form "$OUT/tf-w5.html" && grep -q 'name="password"' "$OUT/tf-w5.html" && pass "5 wrong codes: back to the password" || bad "pending login kept after 5 wrong codes"
clear_limits

# fileviewer.php has its own login: it must not skip the code.
rm -f "$J"; curl -s -c "$J" -b "$J" -o /dev/null "$BASE/fileviewer.php"
curl -s -c "$J" -b "$J" -o /dev/null "$BASE/fileviewer.php" -d "login=login&username=worker&password=worker"
curl -s -c "$J" -b "$J" "$U?m=projects" > "$OUT/tf-fv.html"
code_form "$OUT/tf-fv.html" && ! logged_in "$OUT/tf-fv.html" && pass "fileviewer.php login also asks for the code" || bad "fileviewer.php skipped the code"

# Users without two-step login are not affected.
password_login admin passwd "$OUT/tf-admin.jar" > "$OUT/tf-admin.html"
logged_in "$OUT/tf-admin.html" && pass "users without it log in with the password" || bad "admin login"

# Another user cannot change it; an administrator can reset it.
GJ=$OUT/tf-guest.jar
login guest guest "$GJ"
GID=$(db -e "SELECT user_id FROM dotp_users WHERE user_username = 'guest'")
curl -s -b "$GJ" -o /dev/null "$U?m=admin&a=viewuser&user_id=$GID" --data "dosql=do_twofactor_aed&csrf_token=$(own_csrf "$GJ" "$GID")&tf_user=$WID&tf_action=reset"
[ -n "$(db -e "SELECT 1 FROM dotp_user_twofactor WHERE user_id = $WID")" ] && pass "another user cannot reset it" || bad "guest reset the worker's two-step login"
AJ=$OUT/tf-admin.jar
tf "$AJ" "$WID" reset
[ -z "$(db -e "SELECT 1 FROM dotp_user_twofactor WHERE user_id = $WID")" ] && [ "$(db -e "SELECT COUNT(*) FROM dotp_user_twofactor_recovery WHERE user_id = $WID")" = 0 ] \
	&& pass "administrator reset removes it" || bad "admin reset"

# Turning it off needs a current code.
db -e "INSERT INTO dotp_user_twofactor (user_id, tf_secret, tf_last_counter, tf_enabled_on) VALUES ($WID, '$KEY', 0, NOW())"
login worker worker "$J" 2>/dev/null
password_login worker worker "$J" > "$OUT/tf-p6.html"
second_step "$J" "$OUT/tf-p6.html" "$(totp "$KEY")" > /dev/null
tf "$J" "$WID" disable 000000
[ -n "$(db -e "SELECT 1 FROM dotp_user_twofactor WHERE user_id = $WID")" ] && pass "turning off needs a valid code" || bad "turned off with a wrong code"
tf "$J" "$WID" disable "$(totp "$KEY" 1)"
[ -z "$(db -e "SELECT 1 FROM dotp_user_twofactor WHERE user_id = $WID")" ] && pass "turned off with a valid code" || bad "not turned off"

db -e "DELETE FROM dotp_user_twofactor; DELETE FROM dotp_user_twofactor_recovery"
clear_limits
log_check "two-step login" || fail=1
exit $fail
