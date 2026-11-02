#!/bin/bash
# REST API checks (api.php, docs/api.md): the on/off setting, token handling (created
# through the user's API tokens tab, shown once, read and write scopes, expiry,
# revocation, failed-attempt limit), every endpoint as admin, and the permission
# rules as worker and guest (private tasks, read-only roles, other users' tokens).
#
#   ./reset-db.sh && tests/seed.sh && tests/api.sh
set -u
. "$(dirname "$0")/lib.sh"
B=${DP_URL:-http://127.0.0.1:8089}
API=$B/api.php
fail=0
log_mark
clear_limits() { dc exec -T web sh -c 'rm -f /var/www/html/files/temp/ratelimit_*'; }
clear_limits

# call <token> <method> <path> [json body]: sets STATUS and BODY
call() {
	local args=(-s -o "$OUT/api-body" -w '%{http_code}' -X "$2")
	[ -n "$1" ] && args+=(-H "Authorization: Bearer $1")
	[ -n "${4:-}" ] && args+=(-H 'Content-Type: application/json' --data "$4")
	STATUS=$(curl "${args[@]}" "$API/$3")
	BODY=$(cat "$OUT/api-body")
}
# check <name> <expected status> [python expression on the decoded body "j"]
check() {
	local ok=1
	[ "$STATUS" = "$2" ] || ok=0
	if [ $ok = 1 ] && [ -n "${3:-}" ]; then
		python3 -c "import json,sys; j=json.loads(sys.argv[1]); sys.exit(0 if ($3) else 1)" "$BODY" 2>/dev/null || ok=0
	fi
	if [ $ok = 1 ]; then echo "PASS  $1"; else echo "FAIL  $1: HTTP $STATUS $(echo "$BODY" | cut -c1-300)"; fail=1; fi
}
# own_csrf <cookie jar> <user id>: the CSRF token from the user's own page (any role can open it)
own_csrf() {
	curl -s -b "$1" "$U?m=admin&a=viewuser&user_id=$2" | grep -o 'name="csrf_token" value="[0-9a-f]*"' | head -1 | sed 's/.*value="//;s/"//'
}
# new_token <user> <password> <scope> [expires days]: creates a token through the tab, prints it
new_token() {
	local jar=$OUT/api-$1.jar uid tab
	login "$1" "$2" "$jar"
	uid=$(db -e "SELECT user_id FROM dotp_users WHERE user_username = '$1'")
	curl -s -b "$jar" -o /dev/null "$U?m=admin&a=viewuser&user_id=$uid" --data \
		"dosql=do_api_token_aed&csrf_token=$(own_csrf "$jar" "$uid")&token_user=$uid&token_name=test+$3&token_scope=$3&token_expires=${4:-90}"
	tab=$(curl -s -b "$jar" "$U?m=admin&a=viewuser&user_id=$uid" | grep -o 'tab=[0-9]*">API tokens' | grep -o '[0-9]*' | head -1)
	curl -s -b "$jar" "$U?m=admin&a=viewuser&user_id=$uid&tab=$tab" | grep -o 'dpp_[0-9a-f]\{40\}' | head -1
}

API_WAS=$(db -e "SELECT config_value FROM dotp_config WHERE config_name = 'api_enabled'")
db -e "UPDATE dotp_config SET config_value = 'false' WHERE config_name = 'api_enabled'; DELETE FROM dotp_api_tokens"
call "" GET v1/me
check "API off: 404" 404 "j['error']['code'] == 'api_disabled'"
db -e "UPDATE dotp_config SET config_value = 'true' WHERE config_name = 'api_enabled'"

call "" GET v1/me
check "no token: 401" 401
call "dpp_0000000000000000000000000000000000000000" GET v1/me
check "unknown token: 401" 401

ADMIN_W=$(new_token admin passwd write)
ADMIN_R=$(new_token admin passwd read)
[ -n "$ADMIN_W" ] && [ -n "$ADMIN_R" ] && echo "PASS  tokens created in the API tokens tab" || { echo "FAIL  token creation"; fail=1; }
tab=$(curl -s -b "$OUT/api-admin.jar" "$U?m=admin&a=viewuser&user_id=1" | grep -o 'tab=[0-9]*">API tokens' | grep -o '[0-9]*' | head -1)
n=$(curl -s -b "$OUT/api-admin.jar" "$U?m=admin&a=viewuser&user_id=1&tab=$tab" | grep -c 'dpp_[0-9a-f]\{40\}')
[ "$n" = 0 ] && echo "PASS  token shown only once" || { echo "FAIL  token shown again ($n)"; fail=1; }
n=$(db -e "SELECT COUNT(*) FROM dotp_api_tokens WHERE token_hash = SHA2('$ADMIN_W', 256) AND token_hash <> '$ADMIN_W'")
[ "$n" = 1 ] && echo "PASS  only the SHA-256 is stored" || { echo "FAIL  token storage"; fail=1; }

P=$(db -e "SELECT MAX(project_id) FROM dotp_projects WHERE project_name = 'Seed Project'")
T=$(db -e "SELECT MIN(task_id) FROM dotp_tasks WHERE task_project = $P")
# Re-runnable: the seeded task as tests/seed.sh leaves it.
db -e "DELETE FROM dotp_task_log WHERE task_log_task = $T; UPDATE dotp_tasks SET task_percent_complete = 20 WHERE task_id = $T; DELETE FROM dotp_tasks WHERE task_name = 'Private admin task'"
call "$ADMIN_W" GET v1/me
check "me" 200 "j['data']['username'] == 'admin' and j['data']['token_scope'] == 'write'"
call "$ADMIN_W" GET v1/projects
check "projects" 200 "any(p['id'] == $P for p in j['data']) and j['meta']['next_offset'] is None"
call "$ADMIN_W" GET "v1/projects/$P"
check "project" 200 "j['data']['name'] == 'Seed Project' and j['data']['status_name'] == 'In Progress'"
call "$ADMIN_W" GET "v1/projects/$P/tasks?limit=1"
check "project tasks, paged" 200 "len(j['data']) == 1 and j['meta']['next_offset'] == 1"
call "$ADMIN_W" GET "v1/projects/$P/tasks?limit=1&offset=1"
check "project tasks, second page" 200 "len(j['data']) == 1 and j['meta']['next_offset'] is None"
call "$ADMIN_W" GET "v1/tasks?assigned=me&open=1"
check "my open tasks" 200 "len(j['data']) == 2 and j['data'][0]['assignees'][0]['user_id'] == 1"
call "$ADMIN_W" GET "v1/tasks/$T"
check "task" 200 "j['data']['id'] == $T and 'description' in j['data']"
call "$ADMIN_W" GET "v1/tasks?limit=500"
check "limit above 200: 400" 400
call "$ADMIN_W" GET "v1/nothing"
check "unknown endpoint: 404" 404
call "$ADMIN_W" DELETE "v1/tasks/$T"
check "wrong method: 405" 405
STATUS=$(curl -s -o "$OUT/api-body" -w '%{http_code}' -H "Authorization: Bearer $ADMIN_W" "$API?route=/v1/me"); BODY=$(cat "$OUT/api-body")
check "route parameter instead of PATH_INFO" 200 "j['data']['id'] == 1"

call "$ADMIN_W" POST "v1/tasks/$T/logs" '{"hours": 1.5, "date": "2026-10-30", "summary": "API <b>log</b>", "description": "Done via the API", "percent_complete": 60}'
check "log work" 201 "j['data']['hours'] == 1.5 and j['data']['summary'] == 'API <b>log</b>' and j['data']['date'].startswith('2026-10-30')"
call "$ADMIN_W" GET "v1/tasks/$T/logs"
check "task logs" 200 "len(j['data']) == 1 and j['data'][0]['creator_id'] == 1"
call "$ADMIN_W" GET "v1/tasks/$T"
check "logging work set the progress" 200 "j['data']['percent_complete'] == 60"
call "$ADMIN_W" PATCH "v1/tasks/$T" '{"percent_complete": 100}'
check "update progress" 200 "j['data']['percent_complete'] == 100"
call "$ADMIN_W" PATCH "v1/tasks/$T" '{"percent_complete": 101}'
check "progress above 100: 400" 400
call "$ADMIN_W" PATCH "v1/tasks/$T" '{"task_name": "x"}'
check "unknown fields only: 400" 400
STATUS=$(curl -s -o "$OUT/api-body" -w '%{http_code}' -X PATCH -H "Authorization: Bearer $ADMIN_W" -H 'Content-Type: text/plain' --data '{"percent_complete": 5}' "$API/v1/tasks/$T")
check "body not JSON: 415" 415
call "$ADMIN_W" POST "v1/tasks/$T/logs" '{"hours": "lots"}'
check "invalid hours: 400" 400
call "$ADMIN_R" PATCH "v1/tasks/$T" '{"percent_complete": 10}'
check "read token cannot write: 403" 403 "j['error']['code'] == 'read_only_token'"
n=$(db -e "SELECT task_name FROM dotp_tasks WHERE task_id = $T")
[ "$n" = "Seed task A" ] && echo "PASS  task name untouched" || { echo "FAIL  task name changed: $n"; fail=1; }

# A private task of the admin's: the worker neither sees nor reaches it.
db -e "INSERT INTO dotp_tasks (task_name, task_project, task_owner, task_start_date, task_end_date, task_access, task_parent) VALUES ('Private admin task', $P, 1, NOW(), NOW() + INTERVAL 1 DAY, 3, 0); UPDATE dotp_tasks SET task_parent = task_id WHERE task_name = 'Private admin task'"
PT=$(db -e "SELECT task_id FROM dotp_tasks WHERE task_name = 'Private admin task'")
WORKER=$(new_token worker worker write)
[ -n "$WORKER" ] && echo "PASS  worker creates their own token" || { echo "FAIL  worker token"; fail=1; }
call "$WORKER" GET "v1/projects/$P/tasks"
check "worker: private task not listed" 200 "not any(t['id'] == $PT for t in j['data']) and len(j['data']) >= 2"
call "$WORKER" GET "v1/tasks/$PT"
check "worker: private task is 404" 404
call "$WORKER" POST "v1/tasks/$PT/logs" '{"hours": 1}'
check "worker: cannot log on a private task" 404
call "$ADMIN_W" GET "v1/tasks/$PT"
check "admin: own private task" 200

# Another user's token: the worker cannot create or revoke it.
WJ=$OUT/api-worker.jar
AID=$(db -e "SELECT MIN(token_id) FROM dotp_api_tokens WHERE token_user = 1")
curl -s -b "$WJ" -o /dev/null "$U?m=admin&a=viewuser&user_id=1" --data "dosql=do_api_token_aed&csrf_token=$(own_csrf "$WJ" 2)&token_user=1&token_id=$AID&del=1"
n=$(db -e "SELECT COUNT(*) FROM dotp_api_tokens WHERE token_id = $AID")
[ "$n" = 1 ] && echo "PASS  worker cannot revoke the admin's token" || { echo "FAIL  worker revoked the admin's token"; fail=1; }
curl -s -b "$WJ" -o /dev/null "$U?m=admin&a=viewuser&user_id=1" --data "dosql=do_api_token_aed&csrf_token=$(own_csrf "$WJ" 2)&token_user=1&token_name=sneaky&token_scope=write&token_expires=0"
n=$(db -e "SELECT COUNT(*) FROM dotp_api_tokens WHERE token_name = 'sneaky'")
[ "$n" = 0 ] && echo "PASS  worker cannot create a token for the admin" || { echo "FAIL  worker created a token for the admin"; fail=1; }

# The guest role reads only: a write token does not change that.
GUEST=$(new_token guest guest write)
call "$GUEST" PATCH "v1/tasks/$T" '{"percent_complete": 5}'
check "guest: no edit permission, 403 or 404" "$( [ "$STATUS" = 404 ] && echo 404 || echo 403)"
n=$(db -e "SELECT ROUND(task_percent_complete) FROM dotp_tasks WHERE task_id = $T")
[ "$n" = 100 ] && echo "PASS  guest did not change the task" || { echo "FAIL  guest changed the task ($n)"; fail=1; }

# Expiry and revocation.
db -e "UPDATE dotp_api_tokens SET token_expires = NOW() - INTERVAL 1 MINUTE WHERE token_hash = SHA2('$GUEST', 256)"
call "$GUEST" GET v1/me
check "expired token: 401" 401
WID=$(db -e "SELECT token_id FROM dotp_api_tokens WHERE token_hash = SHA2('$WORKER', 256)")
curl -s -b "$WJ" -o /dev/null "$U?m=admin&a=viewuser&user_id=2" --data "dosql=do_api_token_aed&csrf_token=$(own_csrf "$WJ" 2)&token_user=2&token_id=$WID&del=1"
call "$WORKER" GET v1/me
check "revoked token: 401" 401
n=$(db -e "SELECT COUNT(*) FROM dotp_api_tokens WHERE token_last_used IS NOT NULL AND token_hash = SHA2('$ADMIN_W', 256)")
[ "$n" = 1 ] && echo "PASS  last use recorded" || { echo "FAIL  last use not recorded"; fail=1; }

# Failed attempts are limited per address (20 in 10 minutes), valid tokens included after that.
for i in $(seq 1 20); do call "dpp_$(printf '%040d' "$i")" GET v1/me; done
call "$ADMIN_W" GET v1/me
check "too many failed attempts: 429" 429
clear_limits

db -e "UPDATE dotp_config SET config_value = '$API_WAS' WHERE config_name = 'api_enabled'; DELETE FROM dotp_api_tokens; DELETE FROM dotp_tasks WHERE task_id = $PT"
if log_since | grep -q 'dotPulse API:'; then echo "FAIL  API errors logged:"; log_since | grep 'dotPulse API:' | sed 's/^/      /' | head; fail=1; fi
log_check "api" || fail=1
exit $fail
