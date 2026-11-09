#!/bin/bash
# Security smoke test for the sandbox. Run after ./reset-db.sh.
# Exits non-zero if any check fails.
cd "$(dirname "$0")"
U=http://127.0.0.1:8089/index.php
DB="docker compose exec -T db mariadb -udotproject -pdotproject -N dotproject"
CJ=$(mktemp); FAIL=0; START=$(date +%s)
trap 'rm -f "$CJ" files/temp/ratelimit_*' EXIT
rm -f files/temp/ratelimit_*

check() { if [ "$2" = "$3" ]; then echo "PASS  $1"; else echo "FAIL  $1 (got '$2', want '$3')"; FAIL=1; fi; }
login() { rm -f "$CJ"; curl -s -c "$CJ" -b "$CJ" -o /dev/null $U
  curl -s -L -c "$CJ" -b "$CJ" $U -d "login=login&username=$1&password=$2" | grep -o 'Logout' | head -1; }
token() { curl -s -c "$CJ" -b "$CJ" "$U?m=companies" | grep -o 'name="csrf-token" content="[0-9a-f]*"' | grep -o '[0-9a-f]\{64\}'; }
code() { curl -s -c "$CJ" -b "$CJ" -o /dev/null -w '%{http_code}:%{redirect_url}' "$@"; }

# Login and password hashing (second round exercises the MD5 -> bcrypt rehash)
for r in 1 2; do
  check "admin login round $r" "$(login admin passwd)" "Logout"
  check "worker login round $r" "$(login worker worker)" "Logout"
done
check "admin hash is bcrypt" "$($DB -e "SELECT LEFT(user_password,4) FROM dotp_users WHERE user_id=1")" '$2y$'

# Session cookie flags
check "cookie HttpOnly+SameSite" "$(curl -s -D - -o /dev/null $U | grep -io 'HttpOnly; SameSite=Lax' | head -1)" "HttpOnly; SameSite=Lax"

# CSRF on dosql
login admin passwd >/dev/null; T=$(token)
check "token present" "${#T}" "64"
check "POST without token refused" "$(code -X POST "$U?m=companies" -d 'dosql=do_company_aed&company_id=0&company_name=SmokeNoTok')" "302:$U?m=public&a=access_denied"
check "POST bad token refused" "$(code -X POST "$U?m=companies" -d "dosql=do_company_aed&company_id=0&company_name=SmokeBad&csrf_token=$(printf '0%.0s' $(seq 64))")" "302:$U?m=public&a=access_denied"
check "GET dosql refused" "$(code "$U?m=companies&dosql=do_company_aed&company_id=0&company_name=SmokeGet")" "302:$U?m=public&a=access_denied"
check "any POST without token refused (not only dosql)" "$(code -X POST "$U?m=projects" -d 'project_status=1')" "302:$U?m=public&a=access_denied"
check "POST with token to a view works" "$(code -X POST "$U?m=projects" -d "project_status=1&csrf_token=$T")" "200:"
$DB -e "DELETE FROM dotp_companies WHERE company_name LIKE 'Smoke%'"
code -X POST "$U?m=companies" -d "dosql=do_company_aed&company_id=0&company_name=SmokeGood&csrf_token=$T" >/dev/null
check "POST valid token saves" "$($DB -e "SELECT COUNT(*) FROM dotp_companies WHERE company_name='SmokeGood'")" "1"
P=$(curl -s -c "$CJ" -b "$CJ" "$U?m=companies&a=addedit")
check "token injected into POST forms" "$(grep -o 'name="csrf_token" value=' <<<"$P" | wc -l)" "$(tr '\n' ' ' <<<"$P" | grep -oiE '<form[^>]*method=.?post' | wc -l)"

# Write permission on the module: guest (view only) cannot write, worker can
login guest guest >/dev/null; T=$(token)
code -X POST "$U?m=companies" -d "dosql=do_company_aed&company_id=0&company_name=SmokeGuest&csrf_token=$T" >/dev/null
check "guest cannot create company" "$($DB -e "SELECT COUNT(*) FROM dotp_companies WHERE company_name='SmokeGuest'")" "0"
SID=$($DB -e "SELECT company_id FROM dotp_companies WHERE company_name='SmokeGood'")
code -X POST "$U?m=companies" -d "dosql=do_company_aed&del=1&company_id=$SID&csrf_token=$T" >/dev/null
check "guest cannot delete company" "$($DB -e "SELECT COUNT(*) FROM dotp_companies WHERE company_id=$SID")" "1"
GID=$($DB -e "SELECT user_id FROM dotp_users WHERE user_username='guest'")
$DB -e "DELETE FROM dotp_user_preferences WHERE pref_user=$GID AND pref_name='TABVIEW'"
code -X POST "$U?m=system" -d "dosql=do_preference_aed&pref_user=$GID&pref_name[TABVIEW]=2&csrf_token=$T" >/dev/null
check "guest saves own preferences" "$($DB -e "SELECT COUNT(*) FROM dotp_user_preferences WHERE pref_user=$GID AND pref_name='TABVIEW'")" "1"
login worker worker >/dev/null; T=$(token)
code -X POST "$U?m=companies" -d "dosql=do_company_aed&del=1&company_id=$SID&csrf_token=$T" >/dev/null
check "worker deletes company" "$($DB -e "SELECT COUNT(*) FROM dotp_companies WHERE company_id=$SID")" "0"

# Password reset page reachable while logged out
rm -f "$CJ"
check "reset form reachable logged out" "$(curl -s "$U?resetpass=1&user_id=2&token=$(printf 'a%.0s' $(seq 64))" | grep -o 'name="new_password"' | head -1)" 'name="new_password"'
check "reset rejects bad token" "$(curl -s -X POST $U -d "resetpass=1&user_id=2&token=$(printf 'a%.0s' $(seq 64))&new_password=x1234567&password_confirm=x1234567" | grep -o 'Invalid or expired' | head -1)" "Invalid or expired"

# A user with no role must not log in, and must not be promoted
$DB -e "DELETE FROM dotp_gacl_groups_aro_map WHERE aro_id=(SELECT id FROM dotp_gacl_aro WHERE section_value='user' AND value='2')"
check "role-less worker refused" "$(login worker worker)" ""
check "role-less worker not promoted" "$($DB -e "SELECT COUNT(*) FROM dotp_gacl_groups_aro_map WHERE aro_id=(SELECT id FROM dotp_gacl_aro WHERE section_value='user' AND value='2')")" "0"
$DB -e "INSERT INTO dotp_gacl_groups_aro_map (group_id, aro_id) SELECT g.id, a.id FROM dotp_gacl_aro_groups g, dotp_gacl_aro a WHERE g.value='normal' AND a.section_value='user' AND a.value='2'"

# Installer on a configured system: needs the DB settings from config.php, and upgrade runs
I=http://127.0.0.1:8089/install
IC="dbtype=mysqli&dbhost=db&dbname=dotproject&dbuser=dotproject&dbprefix=dotp_"
check "installer hides DB password" "$(curl -s -X POST $I/db.php -d mode=upgrade | grep -o 'name="dbpass" value="[^"]*"')" 'name="dbpass" value=""'
check "installer hides DB user" "$(curl -s -X POST $I/db.php -d mode=upgrade | grep -o 'name="dbuser" value="[^"]*"')" 'name="dbuser" value=""'
check "installer refuses wrong password" "$(curl -s -X POST $I/do_install_db.php -d "mode=upgrade&dobackup=1&$IC&dbpass=wrong" | grep -o '^Security Check')" "Security Check"
check "installer upgrade runs" "$(curl -s -X POST $I/do_install_db.php -d "mode=upgrade&do_db=1&$IC&dbpass=dotproject" | grep -o 'Updating version information' | head -1)" "Updating version information"

# Phase 7a hardening: files that must not answer over HTTP, security headers,
# per-user PDF reports and the command-line queue scanner
B=http://127.0.0.1:8089
for p in modules/tasks/tasks.class.php lib/phpgacl/admin/acl_list.php vendor/autoload.php includes/config.php \
         tests/run_tests.php docs/security-audit/ROADMAP.md docker/config.php files/ files/temp/temp1.pdf \
         queuescanner.php composer.json install/docs/phpinfo.php; do
  check "direct request refused: $p" "$(curl -s -o /dev/null -w '%{http_code}' "$B/$p" | grep -cE '^40[34]$')" "1"
done
check "module images still served" "$(curl -s -o /dev/null -w '%{http_code}' "$B/modules/projects/images/applet3-48.png")" "200"
check "security headers sent" "$(curl -s -D - -o /dev/null $U | grep -ciE '^(x-content-type-options: nosniff|x-frame-options: sameorigin|referrer-policy: strict-origin-when-cross-origin)')" "3"
rm -f "$CJ"
check "report PDF needs login" "$(curl -s "$B/fileviewer.php?report=pdf" | grep -o 'name="password"' | head -1)" 'name="password"'
login worker worker >/dev/null
curl -s -c "$CJ" -b "$CJ" -o /dev/null "$U?m=projects&a=reports&project_id=0&report_type=tasklogs&do_report=submit&log_all=1&log_pdf=1"
check "worker gets own report PDF" "$(curl -s -b "$CJ" "$B/fileviewer.php?report=pdf" | head -c 5)" "%PDF-"
QS="docker compose exec -T -u ${DP_UID:-1000} -w /var/www/html web php queuescanner.php"
check "queue scanner needs a base URL" "$($QS >/dev/null 2>&1; echo $?)" "2"
check "queue scanner runs from the CLI" "$($QS --base-url=$B | tail -1 | grep -o '^Done')" "Done"

# Phase 7b/7h: enforced CSP with a fresh nonce, the report endpoint, dp-actions.js on every page
H1=$(curl -s -D - -o /dev/null $U | tr -d '\r' | grep -i '^content-security-policy')
H2=$(curl -s -D - -o /dev/null $U | tr -d '\r' | grep -i '^content-security-policy')
check "enforced CSP sent (not report-only)" "$(grep -ci "^content-security-policy: .*script-src 'self' 'nonce-" <<<"$H1")" "1"
check "CSP script-src has no unsafe-inline/unsafe-eval" "$(grep -oE "script-src [^;]*" <<<"$H1" | grep -c "unsafe-")" "0"
check "CSP nonce changes per request" "$([ -n "$H1" ] && [ "$H1" != "$H2" ] && echo yes)" "yes"
check "CSP report endpoint refuses GET" "$(curl -s -o /dev/null -w '%{http_code}' $B/csp-report.php)" "405"
docker compose exec -T web rm -f /var/www/html/files/temp/csp-reports.log
check "CSP report accepted" "$(curl -s -o /dev/null -w '%{http_code}' -X POST -H 'Content-Type: application/csp-report' --data '{"csp-report":{"document-uri":"http://x/index.php?m=smoke&token=SECRET","effective-directive":"script-src-attr","blocked-uri":"inline"}}' $B/csp-report.php)" "204"
check "CSP report logged without query secrets" "$(docker compose exec -T web cat /var/www/html/files/temp/csp-reports.log | grep -c $'\tm=smoke\t')" "1"
docker compose exec -T web rm -f /var/www/html/files/temp/csp-reports.log
login admin passwd >/dev/null
check "dp-actions.js loaded on pages" "$(curl -s -b "$CJ" "$U?m=companies" | grep -c 'js/dp-actions.js')" "1"
rm -f "$CJ"
check "login page has no inline JavaScript" "$(curl -s $U | grep -ciE ' on[a-z]+=|javascript:|<script>|<script type="text/javascript">')" "0"
login admin passwd >/dev/null

# No PHP fatals during the run
F=$(docker compose logs --since $(( $(date +%s) - START + 2 ))s web 2>&1 | grep -c 'PHP Fatal')
check "no PHP fatals" "$F" "0"

[ $FAIL -eq 0 ] && echo "ALL PASS" || echo "SOME CHECKS FAILED"
exit $FAIL
