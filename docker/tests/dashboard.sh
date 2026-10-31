#!/bin/bash
# Personal dashboard (modules/dashboard): it is the landing page, it lists the user's own
# open tasks by due date with their projects, hours and events, and it applies the task
# and project permissions (another user's private task, a project the user may not see).
#
#   ./reset-db.sh && tests/seed.sh && tests/dashboard.sh
set -u
. "$(dirname "$0")/lib.sh"
fail=0
log_mark
pass() { echo "PASS  $1"; }
bad() { echo "FAIL  $1"; fail=1; }
# has <name> <page file> <grep -E pattern> [expected count, default: at least 1]
has() {
	local n; n=$(grep -cE -- "$3" "$2")
	if [ -n "${4:-}" ]; then [ "$n" = "$4" ] && pass "$1" || bad "$1 ($n matches of '$3', expected $4)"
	else [ "$n" -gt 0 ] && pass "$1" || bad "$1 (no match for '$3')"; fi
}

P=$(db -e "SELECT MAX(project_id) FROM dotp_projects WHERE project_name = 'Seed Project'")
db -e "DELETE FROM dotp_tasks WHERE task_name LIKE 'Dash %'; DELETE FROM dotp_projects WHERE project_name = 'Dash hidden project';
	DELETE FROM dotp_task_log WHERE task_log_name = 'dash test';
	INSERT INTO dotp_tasks (task_name, task_project, task_owner, task_start_date, task_end_date, task_percent_complete, task_duration, task_duration_type, task_access, task_parent) VALUES
	 ('Dash overdue task', $P, 1, NOW() - INTERVAL 9 DAY, NOW() - INTERVAL 2 DAY, 40, 2, 24, 0, 0),
	 ('Dash this week', $P, 1, NOW(), NOW() + INTERVAL 3 DAY, 0, 1, 24, 0, 0),
	 ('Dash done task', $P, 1, NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 1 DAY, 100, 1, 24, 0, 0),
	 ('Dash private admin task', $P, 1, NOW(), NOW() + INTERVAL 1 DAY, 0, 1, 24, 3, 0);
	UPDATE dotp_tasks SET task_parent = task_id WHERE task_name LIKE 'Dash %';
	INSERT INTO dotp_user_tasks (user_id, task_id, perc_assignment) SELECT 2, task_id, 100 FROM dotp_tasks WHERE task_name IN ('Dash overdue task', 'Dash this week', 'Dash done task');
	INSERT INTO dotp_user_tasks (user_id, task_id, perc_assignment) SELECT 1, task_id, 100 FROM dotp_tasks WHERE task_name = 'Dash private admin task';
	INSERT INTO dotp_task_log (task_log_task, task_log_name, task_log_creator, task_log_hours, task_log_date) SELECT task_id, 'dash test', 2, 2.5, NOW() FROM dotp_tasks WHERE task_name = 'Dash overdue task';"
# A project in a company the worker is denied: its task assigned to the worker must not show.
db -e "INSERT INTO dotp_projects (project_name, project_short_name, project_company, project_owner, project_status) VALUES ('Dash hidden project', 'HID', (SELECT MAX(company_id) FROM dotp_companies), 1, 3);
	INSERT INTO dotp_tasks (task_name, task_project, task_owner, task_start_date, task_end_date, task_parent) SELECT 'Dash hidden task', MAX(project_id), 1, NOW(), NOW() + INTERVAL 1 DAY, 0 FROM dotp_projects WHERE project_name = 'Dash hidden project';
	UPDATE dotp_tasks SET task_parent = task_id WHERE task_name = 'Dash hidden task';
	INSERT INTO dotp_user_tasks (user_id, task_id, perc_assignment) SELECT 2, task_id, 100 FROM dotp_tasks WHERE task_name = 'Dash hidden task'"
HID=$(db -e "SELECT MAX(project_id) FROM dotp_projects WHERE project_name = 'Dash hidden project'")
# Deny the worker that project: an item deny in the permission cache, as phpGACL writes it.
db -e "DELETE FROM dotp_dotpermissions WHERE acl_id = 9998;
	INSERT INTO dotp_dotpermissions (acl_id, user_id, section, axo, permission, allow, priority, enabled) VALUES
	 (9998, 2, 'projects', '$HID', 'view', 0, 1, 1), (9998, 2, 'projects', '$HID', 'access', 0, 1, 1)"

WJ=$OUT/dash-worker.jar
login worker worker "$WJ"
curl -s -b "$WJ" "$U" > "$OUT/dash-worker.html"
has "landing page is the dashboard" "$OUT/dash-worker.html" 'class="dash"'
has "own overdue task listed" "$OUT/dash-worker.html" 'Dash overdue task'
has "overdue group" "$OUT/dash-worker.html" 'id="dash-overdue"'
has "due this week listed" "$OUT/dash-worker.html" 'Dash this week'
has "finished task not listed" "$OUT/dash-worker.html" 'Dash done task' 0
has "another user's private task not listed" "$OUT/dash-worker.html" 'Dash private admin task' 0
has "task in a denied project not listed" "$OUT/dash-worker.html" 'Dash hidden' 0
has "project shown with progress" "$OUT/dash-worker.html" 'class="dash-project"'
H=$(db -e "SELECT CAST(ROUND(SUM(task_log_hours), 2) AS DOUBLE) FROM dotp_task_log WHERE task_log_creator = 2 AND DATE(task_log_date) = CURDATE()")
has "hours logged today counted ($H h)" "$OUT/dash-worker.html" "dash-today.*title=\"[^\"]*: $H h\""
curl -s -b "$WJ" "$U?m=dashboard" > "$OUT/dash-worker-m.html"
has "menu link works" "$OUT/dash-worker-m.html" 'class="dash"'
has "menu entry" "$OUT/dash-worker.html" 'href="\?m=dashboard"'

GJ=$OUT/dash-guest.jar
login guest guest "$GJ"
curl -s -b "$GJ" "$U?m=dashboard" > "$OUT/dash-guest.html"
has "guest sees an empty dashboard" "$OUT/dash-guest.html" 'class="dash-empty"'

# Role without the dashboard: denied like any module.
db -e "INSERT INTO dotp_dotpermissions (acl_id, user_id, section, axo, permission, allow, priority, enabled) VALUES (9999, 3, 'app', 'dashboard', 'access', 0, 0, 1)"
code=$(curl -s -b "$GJ" -o /dev/null -w '%{redirect_url}' "$U?m=dashboard")
case "$code" in *access_denied*) pass "denied when the role lacks the module";; *) bad "no denial without the module ($code)";; esac
db -e "DELETE FROM dotp_dotpermissions WHERE acl_id = 9999"

db -e "DELETE FROM dotp_dotpermissions WHERE acl_id = 9998;
	DELETE FROM dotp_user_tasks WHERE task_id IN (SELECT task_id FROM dotp_tasks WHERE task_name LIKE 'Dash %');
	DELETE FROM dotp_task_log WHERE task_log_name = 'dash test'; DELETE FROM dotp_tasks WHERE task_name LIKE 'Dash %';
	DELETE FROM dotp_projects WHERE project_name = 'Dash hidden project'"
log_check "dashboard" || fail=1
exit $fail
