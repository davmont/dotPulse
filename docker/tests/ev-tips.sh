#!/bin/bash
# Earned value tips of the project tips bell (dotproject_plus, rules 44 and 45): the cost
# tip fires below CPI 0.9, the schedule tip below SPI 0.9, neither on a project on plan.
# Installs and activates monitoringandcontrol and dotproject_plus if needed.
#
#   ./reset-db.sh && tests/ev-tips.sh
set -u
. "$(dirname "$0")/lib.sh"
fail=0
pass() { echo "PASS  $1"; }
bad() { echo "FAIL  $1"; fail=1; }
for m in monitoringandcontrol dotproject_plus; do
	[ -n "$(db -e "SELECT 1 FROM dotp_modules WHERE mod_directory = '$m'")" ] || "$TESTS_DIR/modules.sh" install "$m" >/dev/null
	[ "$(db -e "SELECT mod_active FROM dotp_modules WHERE mod_directory = '$m'")" = 1 ] || "$TESTS_DIR/modules.sh" toggle "$m" >/dev/null
done
log_mark

# The admin costs 50 an hour. Each project has one 10-day task (80 h, budget 4000) that is
# half way through its dates today, so its planned value is about 2000.
db -e "DELETE FROM dotp_monitoring_user_cost WHERE user_id = 1;
	INSERT INTO dotp_monitoring_user_cost (user_id, cost_value, cost_per_use, cost_dt_begin, cost_dt_end) VALUES (1, 50, 0, NOW() - INTERVAL 1 YEAR, NULL)"
C=$(db -e "SELECT MIN(company_id) FROM dotp_companies")
# project <name> <percent done> <hours logged>: prints the project id
project() {
	db -e "DELETE FROM dotp_task_log WHERE task_log_task IN (SELECT t.task_id FROM dotp_tasks t JOIN dotp_projects p ON p.project_id = t.task_project WHERE p.project_name = '$1');
		DELETE FROM dotp_user_tasks WHERE task_id IN (SELECT t.task_id FROM dotp_tasks t JOIN dotp_projects p ON p.project_id = t.task_project WHERE p.project_name = '$1');
		DELETE FROM dotp_tasks WHERE task_project IN (SELECT project_id FROM dotp_projects WHERE project_name = '$1');
		DELETE FROM dotp_projects WHERE project_name = '$1';
		INSERT INTO dotp_projects (project_name, project_short_name, project_company, project_owner, project_status, project_start_date, project_end_date) VALUES ('$1', 'EV', $C, 1, 3, NOW() - INTERVAL 5 DAY, NOW() + INTERVAL 5 DAY);
		SET @p = LAST_INSERT_ID();
		INSERT INTO dotp_tasks (task_name, task_project, task_owner, task_start_date, task_end_date, task_duration, task_duration_type, task_percent_complete, task_parent) VALUES ('EV work', @p, 1, NOW() - INTERVAL 5 DAY, NOW() + INTERVAL 5 DAY, 10, 24, $2, 0);
		SET @t = LAST_INSERT_ID();
		UPDATE dotp_tasks SET task_parent = @t WHERE task_id = @t;
		INSERT INTO dotp_user_tasks (user_id, task_id, perc_assignment) VALUES (1, @t, 100);
		INSERT INTO dotp_task_log (task_log_task, task_log_name, task_log_creator, task_log_hours, task_log_date) VALUES (@t, 'ev', 1, $3, NOW() - INTERVAL 1 DAY);
		SELECT @p"
}
OVER=$(project 'EV over budget' 50 60)    # EV 2000, AC 3000: CPI 0.67, SPI 1
LATE=$(project 'EV late' 20 10)           # EV 800, AC 500: CPI 1.6, SPI 0.4
GOOD=$(project 'EV on plan' 50 40)        # EV 2000, AC 2000: CPI 1, SPI 1

J=$OUT/ev-admin.jar
login admin passwd "$J"
COST='CPI below 0.9'
LATE_TIP='SPI below 0.9'
# tips <project id>: the page of the project (the bell is in its header)
tips() { curl -s -b "$J" "$U?m=projects&a=view&project_id=$1"; }
check() { # <name> <project> <text> <expected count>
	local n; n=$(tips "$2" | grep -c -- "$3")
	[ "$n" -ge 1 ] && n=1
	[ "$n" = "$4" ] && pass "$1" || bad "$1 (found $n, expected $4)"
}
check "over budget: cost tip" "$OVER" "$COST" 1
check "over budget: no schedule tip" "$OVER" "$LATE_TIP" 0
check "late: schedule tip" "$LATE" "$LATE_TIP" 1
check "late: no cost tip" "$LATE" "$COST" 0
check "on plan: no cost tip" "$GOOD" "$COST" 0
check "on plan: no schedule tip" "$GOOD" "$LATE_TIP" 0
# Nothing logged and nothing planned yet: no tips (no division by zero).
NEW=$(project 'EV not started' 0 0)
db -e "UPDATE dotp_tasks SET task_start_date = NOW() + INTERVAL 3 DAY, task_end_date = NOW() + INTERVAL 13 DAY WHERE task_project = $NEW; DELETE FROM dotp_task_log WHERE task_log_name = 'ev' AND task_log_task IN (SELECT task_id FROM dotp_tasks WHERE task_project = $NEW)"
check "not started: no cost tip" "$NEW" "$COST" 0
check "not started: no schedule tip" "$NEW" "$LATE_TIP" 0

# Spanish texts.
db -e "REPLACE INTO dotp_user_preferences VALUES (1, 'LOCALE', 'es')"
login admin passwd "$J"
check "Spanish cost tip" "$OVER" 'CPI por debajo de 0,9' 1
db -e "DELETE FROM dotp_user_preferences WHERE pref_user = 1 AND pref_name = 'LOCALE'"

for p in 'EV over budget' 'EV late' 'EV on plan' 'EV not started'; do project "$p" 0 0 >/dev/null; done
db -e "DELETE FROM dotp_task_log WHERE task_log_name = 'ev'; DELETE FROM dotp_user_tasks WHERE task_id IN (SELECT t.task_id FROM dotp_tasks t JOIN dotp_projects p ON p.project_id = t.task_project WHERE p.project_name LIKE 'EV %');
	DELETE FROM dotp_tasks WHERE task_project IN (SELECT project_id FROM dotp_projects WHERE project_name LIKE 'EV %'); DELETE FROM dotp_projects WHERE project_name LIKE 'EV %'"
log_check "ev tips" || fail=1
exit $fail
