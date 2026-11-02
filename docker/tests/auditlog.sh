#!/bin/bash
# Security audit log (classes/auditlog.class.php, System Admin > Audit log): the events
# written by logins, logouts, the failed-login limit, API tokens, user, role and module
# changes and the system configuration (names only), and the viewer (filters, escaping,
# access).
#
#   ./reset-db.sh && tests/seed.sh && tests/auditlog.sh
set -u
. "$(dirname "$0")/lib.sh"
fail=0
pass() { echo "PASS  $1"; }
bad() { echo "FAIL  $1"; fail=1; }
clear_limits() { dc exec -T web sh -c 'rm -f /var/www/html/files/temp/ratelimit_*'; }
clear_limits
log_mark
db -e "DELETE FROM dotp_audit_log"
# has <name> <event> <SQL condition> [expected count, default 1]
has() {
	local n; n=$(db -e "SELECT COUNT(*) FROM dotp_audit_log WHERE audit_event = '$2' AND ($3)")
	[ "$n" = "${4:-1}" ] && pass "$1" || bad "$1: $n entries of $2 where $3, expected ${4:-1}"
}
try_login() { rm -f "$3"; curl -s -c "$3" -b "$3" -o /dev/null "$U"; curl -s -c "$3" -b "$3" -o /dev/null "$U" -d "login=login&username=$1&password=$2"; }
WID=$(db -e "SELECT user_id FROM dotp_users WHERE user_username = 'worker'")

# Logins.
try_login worker wrong "$OUT/al.jar"
has "failed login: user name tried, no user id" login.failure "audit_username = 'worker' AND audit_user = 0 AND audit_ip <> ''"
try_login '<b>nobody</b>' x "$OUT/al.jar"
J=$OUT/al-worker.jar
login worker worker "$J"
has "login" login.success "audit_user = $WID AND audit_username = 'worker'"
curl -s -b "$J" -o /dev/null "$U?logout=-1"
has "logout" logout "audit_user = $WID"

# The failed-login limit (10): one blocked entry when it is reached, not one per attempt.
for i in $(seq 1 12); do try_login guest "bad$i" "$OUT/al.jar"; done
has "limit reached: one blocked entry" login.blocked "audit_username = 'guest'"
clear_limits

# API tokens.
db -e "UPDATE dotp_config SET config_value = 'true' WHERE config_name = 'api_enabled'"
login worker worker "$J"
T=$(curl -s -b "$J" "$U?m=admin&a=viewuser&user_id=$WID" | grep -o 'name="csrf_token" value="[0-9a-f]*"' | head -1 | sed 's/.*value="//;s/"//')
curl -s -b "$J" -o /dev/null "$U?m=admin&a=viewuser&user_id=$WID" --data "dosql=do_api_token_aed&csrf_token=$T&token_user=$WID&token_name=Audit+bot&token_scope=write&token_expires=30"
has "API token created (name and scope, not the token)" api.token_created "audit_details = 'Audit bot (read and write, 30 days)' AND audit_details NOT LIKE '%dpp_%'"
TID=$(db -e "SELECT MAX(token_id) FROM dotp_api_tokens WHERE token_user = $WID")
curl -s -b "$J" -o /dev/null "$U?m=admin&a=viewuser&user_id=$WID" --data "dosql=do_api_token_aed&csrf_token=$T&token_user=$WID&token_id=$TID&del=1"
has "API token revoked" api.token_revoked "audit_user = $WID"

# Administrators' changes.
AJ=$OUT/al-admin.jar
login admin passwd "$AJ"
AT=$(csrf_token "$AJ")
ROLE=$(db -e "SELECT id FROM dotp_gacl_aro_groups WHERE value = 'normal'")
curl -s -b "$AJ" -o /dev/null "$U?m=admin" --data "csrf_token=$AT&dosql=do_user_aed&user_id=0&contact_id=0&user_username=audituser&user_password=secret1&password_check=secret1&user_type=0&user_role=$ROLE&contact_first_name=Au&contact_last_name=Dit&contact_email=au@example.com&contact_company=0&contact_department=0"
NID=$(db -e "SELECT user_id FROM dotp_users WHERE user_username = 'audituser'")
has "user created (by admin, with role)" user.created "audit_username = 'admin' AND audit_object_id = $NID AND audit_details = 'audituser, role $ROLE'"
n=$(db -e "SELECT COUNT(*) FROM dotp_audit_log WHERE audit_details LIKE '%secret1%'")
[ "$n" = 0 ] && pass "no password in the log" || bad "password written to the log"
curl -s -b "$AJ" -o /dev/null "$U?m=admin" --data "csrf_token=$AT&dosql=do_user_aed&del=1&user_id=$NID"
has "user deleted" user.deleted "audit_object_id = $NID AND audit_details = 'audituser'"
OLD=$(db -e "SELECT config_value FROM dotp_config WHERE config_name = 'page_title'")
CFG=$(curl -s -b "$AJ" "$U?m=system&a=systemconfig")
# Re-post the configuration form with one value changed (checkboxes as they are).
DATA=$(python3 - "$CFG" <<'PY'
import re, sys, urllib.parse
html = sys.argv[1]
html = html[html.index('name="cfgFrm"'):]
html = html[:html.index('</form>')]
fields = []
for m in re.finditer(r'<input[^>]*>', html):
	tag = m.group(0)
	name = re.search(r'name="([^"]+)"', tag)
	if not name or not (name.group(1).startswith('dPcfg') or name.group(1) in ('csrf_token', 'dosql')):
		continue
	value = re.search(r'value="([^"]*)"', tag)
	value = value.group(1) if value else ''
	if 'type="checkbox"' in tag and 'checked' not in tag:
		continue
	if name.group(1) == 'dPcfg[page_title]':
		value = 'Audit title'
	fields.append((name.group(1), value))
for m in re.finditer(r'<select[^>]*name="(dPcfg\[[^"]+\])"[^>]*>(.*?)</select>', html, re.S):
	sel = re.search(r'<option[^>]*value="([^"]*)"[^>]*selected', m.group(2))
	fields.append((m.group(1), sel.group(1) if sel else ''))
print(urllib.parse.urlencode(fields))
PY
)
curl -s -b "$AJ" -o /dev/null "$U?m=system&a=systemconfig" --data "$DATA"
has "configuration change lists the setting, not its value" config.changed "audit_details LIKE '%page_title%' AND audit_details NOT LIKE '%Audit title%'"
db -e "UPDATE dotp_config SET config_value = '$OLD' WHERE config_name = 'page_title'"
MID=$(db -e "SELECT mod_id FROM dotp_modules WHERE mod_directory = 'forums'")
curl -s -b "$AJ" -o /dev/null "$U?m=system&a=domodsql" --data "csrf_token=$AT&cmd=toggle&mod_id=$MID&mod_directory=forums"
curl -s -b "$AJ" -o /dev/null "$U?m=system&a=domodsql" --data "csrf_token=$AT&cmd=toggle&mod_id=$MID&mod_directory=forums"
has "module switched off and on" module.changed "audit_details IN ('toggle forums (now inactive)', 'toggle forums (now active)')" 2

# Viewer.
curl -s -b "$AJ" "$U?m=system&a=auditlog" > "$OUT/al-view.html"
grep -q 'login.failure' "$OUT/al-view.html" && pass "viewer lists entries" || bad "viewer empty"
grep -q '&lt;b&gt;nobody&lt;/b&gt;' "$OUT/al-view.html" && ! grep -q '<b>nobody</b>' "$OUT/al-view.html" && pass "tried user names are escaped" || bad "user name not escaped"
curl -s -b "$AJ" "$U?m=system&a=auditlog&event=api." > "$OUT/al-api.html"
n=$(grep -c '<td title=' "$OUT/al-api.html"); [ "$n" = 2 ] && pass "filter by event group (api.*)" || bad "event group filter: $n rows"
curl -s -b "$AJ" "$U?m=system&a=auditlog&user=worker&event=login.success" > "$OUT/al-u.html"
n=$(grep -c '<td title=' "$OUT/al-u.html"); [ "$n" -ge 1 ] && ! grep -q '>admin</a>' "$OUT/al-u.html" && pass "filter by user and event" || bad "user filter: $n rows"
code=$(curl -s -b "$J" -o /dev/null -w '%{redirect_url}' "$U?m=system&a=auditlog")
case "$code" in *access_denied*) pass "worker cannot open the audit log";; *) bad "worker reached the audit log ($code)";; esac

db -e "UPDATE dotp_config SET config_value = 'false' WHERE config_name = 'api_enabled'; DELETE FROM dotp_api_tokens"
clear_limits
log_check "audit log" || fail=1
exit $fail
