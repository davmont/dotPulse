#!/bin/bash
# Project board (projects/vw_board.php, tasks/do_board_move.php): columns by progress,
# moves through the real handler (POST, CSRF token, permission on the task), the progress
# rules per column, and what worker and guest may not do.
#
#   ./reset-db.sh && tests/seed.sh && tests/board.sh
set -u
. "$(dirname "$0")/lib.sh"
fail=0
log_mark
pass() { echo "PASS  $1"; }
bad() { echo "FAIL  $1"; fail=1; }
eq() { [ "$2" = "$3" ] && pass "$1" || bad "$1: got '$2', expected '$3'"; }

P=$(db -e "SELECT MAX(project_id) FROM dotp_projects WHERE project_name = 'Seed Project'")
db -e "DELETE FROM dotp_user_tasks WHERE task_id IN (SELECT task_id FROM dotp_tasks WHERE task_name LIKE 'Board %');
	DELETE FROM dotp_tasks WHERE task_name LIKE 'Board %';
	INSERT INTO dotp_tasks (task_name, task_project, task_owner, task_start_date, task_end_date, task_percent_complete, task_duration, task_duration_type, task_access, task_dynamic, task_parent) VALUES
	 ('Board todo', $P, 1, NOW(), NOW() + INTERVAL 2 DAY, 0, 1, 24, 0, 0, 0),
	 ('Board doing', $P, 1, NOW(), NOW() + INTERVAL 2 DAY, 40, 1, 24, 0, 0, 0),
	 ('Board done', $P, 1, NOW(), NULL, 100, 1, 24, 0, 0, 0),
	 ('Board private', $P, 1, NOW(), NOW() + INTERVAL 2 DAY, 0, 1, 24, 3, 0, 0),
	 ('Board group', $P, 1, NOW(), NOW() + INTERVAL 2 DAY, 0, 1, 24, 0, 1, 0);
	UPDATE dotp_tasks SET task_parent = task_id WHERE task_name LIKE 'Board %';
	INSERT INTO dotp_user_tasks (user_id, task_id, perc_assignment) SELECT 2, task_id, 100 FROM dotp_tasks WHERE task_name = 'Board doing'"
id() { db -e "SELECT task_id FROM dotp_tasks WHERE task_name = '$1'"; }
pc() { db -e "SELECT ROUND(task_percent_complete) FROM dotp_tasks WHERE task_id = $1"; }
TODO=$(id 'Board todo'); DOING=$(id 'Board doing'); DONE=$(id 'Board done'); PRIV=$(id 'Board private'); GROUP=$(id 'Board group')

WJ=$OUT/board-worker.jar
login worker worker "$WJ"
WT=$(csrf_token "$WJ")
[ -n "$WT" ] || WT=$(curl -s -b "$WJ" "$U?m=projects&a=view&project_id=$P" | grep -o 'name="csrf-token" content="[0-9a-f]*"' | head -1 | sed 's/.*content="//;s/"//')
tab=$(curl -s -b "$WJ" "$U?m=projects&a=view&project_id=$P" | grep -o 'tab=[0-9]*">Board<' | grep -o '[0-9]*' | head -1)
[ -n "$tab" ] && pass "Board tab in the project view" || bad "no Board tab"
curl -s -b "$WJ" "$U?m=projects&a=view&project_id=$P&tab=$tab" > "$OUT/board.html"
col_of() { python3 - "$OUT/board.html" "$1" <<'PY'
import re, sys
html = open(sys.argv[1], encoding='utf-8', errors='replace').read()
for m in re.finditer(r'<section class="dp-col" data-col="(\w+)".*?</section>', html, re.S):
	if 'data-task="%s"' % sys.argv[2] in m.group(0):
		print(m.group(1))
PY
}
eq "0% task in To do" "$(col_of "$TODO")" todo
eq "40% task in In progress" "$(col_of "$DOING")" doing
eq "100% task in Done" "$(col_of "$DONE")" done
eq "another user's private task not on the board" "$(col_of "$PRIV")" ""
eq "task groups not on the board" "$(col_of "$GROUP")" ""
n=$(grep -c 'dp-card dp-card-editable" data-task="'"$DOING"'"' "$OUT/board.html"); eq "worker may move their task" "$n" 1
curl -s -b "$WJ" "$U?m=projects&a=view&project_id=$P&tab=$tab&board_mine=1" > "$OUT/board-mine.html"
n=$(grep -o 'data-task="[0-9]*"' "$OUT/board-mine.html" | sort -u | grep -c "\"$DOING\""); eq "only my tasks: mine shown" "$n" 1
n=$(grep -c "data-task=\"$TODO\"" "$OUT/board-mine.html"); eq "only my tasks: others hidden" "$n" 0

# move <jar> <token> <task> <column>: prints "HTTP-status body"
move() {
	curl -s -b "$1" -o "$OUT/board-move" -w '%{http_code}' "$U?m=tasks&a=do_board_move&suppressHeaders=1" \
		--data "csrf_token=$2&task_id=$3&column=$4"
	echo " $(cat "$OUT/board-move")"
}
eq "todo -> doing answers ok" "$(move "$WJ" "$WT" "$TODO" doing)" '200 {"status":"ok","percent":10}'
eq "todo -> doing sets 10%" "$(pc "$TODO")" 10
eq "doing keeps a progress between 1 and 99" "$(move "$WJ" "$WT" "$DOING" doing | cut -c1-40)" '200 {"status":"ok","percent":40}'
eq "-> done answers 100" "$(move "$WJ" "$WT" "$TODO" done)" '200 {"status":"ok","percent":100}'
n=$(db -e "SELECT task_end_date IS NOT NULL AND task_end_date <> '0000-00-00 00:00:00' FROM dotp_tasks WHERE task_id = $DONE")
move "$WJ" "$WT" "$DONE" doing >/dev/null
eq "done -> doing sets 90%" "$(pc "$DONE")" 90
move "$WJ" "$WT" "$DONE" done >/dev/null
n=$(db -e "SELECT task_end_date IS NOT NULL AND task_end_date <> '0000-00-00 00:00:00' FROM dotp_tasks WHERE task_id = $DONE")
eq "-> done fills an empty end date" "$n" 1
eq "-> todo sets 0%" "$(move "$WJ" "$WT" "$TODO" todo)" '200 {"status":"ok","percent":0}'
eq "unknown column: 400" "$(move "$WJ" "$WT" "$TODO" archive | cut -c1-3)" 400
eq "private task of another user: 404" "$(move "$WJ" "$WT" "$PRIV" done | cut -c1-3)" 404
eq "private task untouched" "$(pc "$PRIV")" 0
eq "task group: 400" "$(move "$WJ" "$WT" "$GROUP" done | cut -c1-3)" 400
r=$(move "$WJ" "wrong" "$TODO" done)
eq "wrong CSRF token: not moved" "$(pc "$TODO")" 0
case "$r" in *'"status":"ok"'*) bad "wrong CSRF token answered ok";; *) pass "wrong CSRF token refused";; esac
curl -s -b "$WJ" -o /dev/null "$U?m=tasks&a=do_board_move&suppressHeaders=1&task_id=$TODO&column=done"
eq "GET does not move" "$(pc "$TODO")" 0

GJ=$OUT/board-guest.jar
login guest guest "$GJ"
GT=$(curl -s -b "$GJ" "$U?m=projects&a=view&project_id=$P" | grep -o 'name="csrf-token" content="[0-9a-f]*"' | head -1 | sed 's/.*content="//;s/"//')
curl -s -b "$GJ" "$U?m=projects&a=view&project_id=$P&tab=$tab" > "$OUT/board-guest.html"
n=$(grep -c 'dp-card-editable' "$OUT/board-guest.html"); eq "guest sees no movable cards" "$n" 0
r=$(move "$GJ" "$GT" "$TODO" done)
eq "guest cannot move (read-only role)" "$(pc "$TODO")" 0

db -e "DELETE FROM dotp_user_tasks WHERE task_id IN (SELECT task_id FROM dotp_tasks WHERE task_name LIKE 'Board %');
	DELETE FROM dotp_tasks WHERE task_name LIKE 'Board %'"
log_check "board" || fail=1
exit $fail
