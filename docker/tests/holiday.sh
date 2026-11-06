#!/bin/bash
# Holiday calendars (holiday module): a calendar given to a user, its days imported from an
# iCalendar file (twice: the second import adds nothing; a worker cannot import) and the
# user's timecard expecting one day less in a week with one of those days.
# Installs and activates holiday and timecard if needed.
#
#   ./reset-db.sh && tests/holiday.sh
set -u
. "$(dirname "$0")/lib.sh"
fail=0
pass() { echo "PASS  $1"; }
bad() { echo "FAIL  $1"; fail=1; }
for m in holiday timecard; do
	[ -n "$(db -e "SELECT 1 FROM dotp_modules WHERE mod_directory = '$m'")" ] || "$TESTS_DIR/modules.sh" install "$m" >/dev/null
	[ "$(db -e "SELECT mod_active FROM dotp_modules WHERE mod_directory = '$m'")" = 1 ] || "$TESTS_DIR/modules.sh" toggle "$m" >/dev/null
done
log_mark
db -e "DELETE FROM dotp_holiday_settings; INSERT INTO dotp_holiday_settings VALUES (1, 0, '');
	DELETE FROM dotp_holiday_calendar_links WHERE link_type = 'user' AND link_id = 1"

J=$OUT/holiday-admin.jar
login admin passwd "$J"
T=$(csrf_token "$J")
curl -s -b "$J" -o /dev/null "$U?m=holiday&a=calendar" -d "csrf_token=$T&action=save&calendar_name=Test+city&calendar_parent=0&calendar_country=&link_user[]=1"
C=$(db -e "SELECT MAX(calendar_id) FROM dotp_holiday_calendars WHERE calendar_name = 'Test city'")
[ -n "$C" ] && [ "$C" != NULL ] && pass "calendar saved" || bad "calendar saved"
[ "$(db -e "SELECT link_calendar FROM dotp_holiday_calendar_links WHERE link_type = 'user' AND link_id = 1")" = "$C" ] \
	&& pass "calendar given to admin" || bad "calendar given to admin"

# The Tuesday of this week (timecard weeks start on Sunday) and a day next year.
TUE=$(date -u -d "-$(date -u +%w) day +2 day" +%Y%m%d)
NEXT=$(( $(date -u +%Y) + 1 ))0515
ICS=$OUT/holiday-test.ics
printf 'BEGIN:VCALENDAR\r\nVERSION:2.0\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:%s\r\nSUMMARY:Local day\r\nEND:VEVENT\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:%s\r\nDTEND;VALUE=DATE:%s\r\nSUMMARY:Patron <b>saint</b>\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n' \
	"$TUE" "$NEXT" "$(( NEXT + 1 ))" > "$ICS"
days() { db -e "SELECT COUNT(*) FROM dotp_holiday WHERE holiday_calendar = $C"; }
import() { curl -s -b "$1" -o /dev/null "$U?m=holiday&a=calendar&calendar_id=$C" -F "csrf_token=$(csrf_token "$1")" -F action=import -F "ics=@$ICS"; }
import "$J"
[ "$(days)" = 2 ] && pass "import: 2 days" || bad "import: 2 days (found $(days))"
import "$J"
[ "$(days)" = 2 ] && pass "import again: nothing added" || bad "import again: nothing added (found $(days))"
curl -s -b "$J" "$U?m=holiday&a=calendar&calendar_id=$C" | grep -q 'Patron &lt;b&gt;saint&lt;/b&gt;' \
	&& pass "imported name escaped" || bad "imported name escaped"
W=$OUT/holiday-worker.jar
login worker worker "$W"
db -e "DELETE FROM dotp_holiday WHERE holiday_calendar = $C"
import "$W"
[ "$(days)" = 0 ] && pass "worker cannot import" || bad "worker cannot import (found $(days))"
import "$J"

# Timecard: 4 working days of 8 hours this week, whatever is logged.
page=$(curl -s -b "$J" "$U?m=timecard&tab=0&user_id=1")
total=$(echo "$page" | grep -o 'border:2px solid #999999;"> [0-9.]*' | tail -1 | grep -o '[0-9.]*$')
missing=$(echo "$page" | grep -o 'background-color:#FFF2F2;">[0-9.]*</span></b><b>' | tail -1 | grep -o '>[0-9.]*<' | tr -d '<>')
expected=$(python3 -c "print(float('${missing:-0}') + float('${total:-0}'))")
[ "$expected" = 32.0 ] && pass "timecard: 32 hours expected this week" || bad "timecard: 32 hours expected this week (found $expected)"

T=$(csrf_token "$J")
curl -s -b "$J" -o /dev/null "$U?m=holiday&a=calendar&calendar_id=$C" -d "csrf_token=$T&action=del"
[ -z "$(db -e "SELECT 1 FROM dotp_holiday_calendars WHERE calendar_id = $C")" ] && pass "calendar deleted" || bad "calendar deleted"
log_check "holiday" || fail=1
exit $fail
