#!/bin/bash
# Full audit of the sandbox: every add-on installed, used and removed with no SQL
# error, PHP warning or broken layout. Run by `make audit-full` after docker/up.sh.
#
#   1  reinstall the database; install and activate every add-on module; load the
#      sample data (db/sample) and the seed data
#   2  crawl every page as admin and as worker
#   3  fill and save every add/edit form as admin and as worker; crawl again with that data
#   4  layout in each theme (THEMES, at 1366x768) and at LAYOUT_EXTRA (theme@WxH ...);
#      pop-ups at their window sizes and task form tab switching in each theme
#   5  remove every add-on, crawl the core without them, reinstall them all
#
# Each step also checks the PHP log (SQL errors, fatals, warnings, notices,
# deprecations). Output and reports: docker/.audit/. Exit status 1 if anything failed.
#
# Environment: STEPS (default "1 2 3 4 5"), THEMES, LAYOUT_EXTRA, CHROME, ALLOW_DEPRECATED=1.
. "$(dirname "$0")/lib.sh"
STEPS=${STEPS:-1 2 3 4 5}
THEMES=${THEMES:-dotpulse default amp dp-grey-theme material wps-redmond}
LAYOUT_EXTRA=${LAYOUT_EXTRA:-dp-grey-theme@1024x768 material@1024x768 default@1920x1080}
FAILED=()
step() { echo; echo "== $*"; }
run() { local name=$1; shift; "$@" || FAILED+=("$name"); }
logged() { local name=$1; shift; log_mark; run "$name" "$@"; run "$name (log)" log_check "$name"; }
set_theme() { db -e "UPDATE dotp_user_preferences SET pref_value='$1' WHERE pref_user=0 AND pref_name='UISTYLE'"; }
# Some forms are the preferences themselves; keep the defaults the checks expect.
reset_prefs() { db -e "DELETE FROM dotp_user_preferences WHERE pref_user<>0;
	UPDATE dotp_user_preferences SET pref_value='en' WHERE pref_user=0 AND pref_name IN ('LOCALE','CURRENCYFORM');
	UPDATE dotp_user_preferences SET pref_value='0' WHERE pref_user=0 AND pref_name='TABVIEW'"; }
has() { case " $STEPS " in *" $1 "*) return 0 ;; esac; return 1; }
BROWSER="$TESTS_DIR/../../tools/browser"
[ -d "$BROWSER/node_modules" ] || (cd "$BROWSER" && npm ci --no-audit --no-fund --silent)

# Add/edit pages: every addedit*.php and *_ae*.php of each module (also with a project).
form_pages() {
	(cd "$REPO_DIR/modules" && ls */addedit*.php */*_ae*.php 2>/dev/null) | grep -v '/do_' \
		| grep -vE '^(public|install)/|^system/addeditpref|^admin/addedituser' \
		| sed -E 's#^([^/]+)/([^/]+)\.php$#m=\1\&a=\2#' | while read -r p; do echo "$p"; echo "$p&project_id=$P"; done
}

if has 1; then
	step "1 install"
	(cd "$DOCKER_DIR" && ./reset-db.sh) >"$OUT/reset-db.txt" 2>&1 || FAILED+=("reset-db")
	run "install add-ons" "$TESTS_DIR/modules.sh" install
	run "activate add-ons" "$TESTS_DIR/modules.sh" toggle
	log_mark
	run "sample data" dc exec -T web php db/sample/sample-data.php
	run "sample data (log)" log_check "sample data"
	"$TESTS_DIR/seed.sh"
fi
P=$(db -e "SELECT MAX(project_id) FROM dotp_projects WHERE project_name='Seed Project'")
T=$(db -e "SELECT MIN(task_id) FROM dotp_tasks WHERE task_project=${P:-0}")

if has 2; then
	step "2 crawl"
	logged "crawl admin" "$TESTS_DIR/crawl.py" admin passwd "$OUT/urls-admin.txt" "$OUT/extra-urls.txt"
	logged "crawl worker" "$TESTS_DIR/crawl.py" worker worker "$OUT/urls-worker.txt" "$OUT/extra-urls.txt"
fi

if has 3; then
	step "3 forms"
	form_pages > "$OUT/form-pages.txt"
	logged "forms admin" node "$BROWSER/forms.js" admin passwd "$OUT/form-pages.txt" "$OUT/forms-admin.jsonl"
	reset_prefs
	logged "forms worker" node "$BROWSER/forms.js" worker worker "$OUT/form-pages.txt" "$OUT/forms-worker.jsonl"
	reset_prefs
	logged "crawl admin with data" "$TESTS_DIR/crawl.py" admin passwd "$OUT/urls-admin.txt" "$OUT/extra-urls.txt"
fi

if has 4; then
	step "4 layout"
	[ -s "$OUT/urls-admin.txt" ] || "$TESTS_DIR/crawl.py" admin passwd "$OUT/urls-admin.txt" "$OUT/extra-urls.txt" >/dev/null
	specs=""; for t in $THEMES; do specs="$specs $t@1366x768"; done
	for spec in $specs $LAYOUT_EXTRA; do
		theme=${spec%@*}; vp=${spec#*@}
		set_theme "$theme"
		run "layout $theme $vp" node "$BROWSER/layout.js" admin passwd "$theme" "$vp" "$OUT/urls-admin.txt" "$OUT/layout-$theme-$vp.jsonl" "$TESTS_DIR/layout-known.txt"
	done
	for theme in $THEMES; do
		set_theme "$theme"
		run "pop-ups and tabs $theme" node "$BROWSER/popups.js" admin passwd "$theme" "$T"
	done
	set_theme dp-grey-theme
fi

if has 5; then
	step "5 remove and reinstall"
	run "deactivate add-ons" "$TESTS_DIR/modules.sh" toggle
	run "remove add-ons" "$TESTS_DIR/modules.sh" remove
	logged "crawl without add-ons" "$TESTS_DIR/crawl.py" admin passwd "$OUT/urls-core.txt" "$OUT/extra-urls.txt"
	run "reinstall add-ons" "$TESTS_DIR/modules.sh" install
	run "reactivate add-ons" "$TESTS_DIR/modules.sh" toggle
	logged "crawl after reinstall" "$TESTS_DIR/crawl.py" admin passwd "$OUT/urls-reinstall.txt" "$OUT/extra-urls.txt"
fi

echo
if [ ${#FAILED[@]} = 0 ]; then echo "ALL PASS"; exit 0; fi
echo "FAILED: ${#FAILED[@]}"; printf '  %s\n' "${FAILED[@]}"
exit 1
