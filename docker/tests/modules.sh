#!/bin/bash
# Run a System > Modules command on add-on modules the way an administrator does.
#
#   docker/tests/modules.sh install|toggle|menu|remove|upgrade [module ...]
#
# menu shows a module in the menu (System > Modules "visible"): activating one does not,
# and the crawl only follows links. Modules already shown are left as they are.
#
# Without module names: every add-on (modules/*/setup.php not set up by the installer).
# Logs in as admin (ADMIN_USER sets another user, password "passwd").
# Fails if the command does not take effect or logs an SQL error or PHP problem.
. "$(dirname "$0")/lib.sh"
cmd=$1; shift
case "$cmd" in install|toggle|menu|remove|upgrade) ;; *) echo "usage: $0 install|toggle|menu|remove|upgrade [module ...]"; exit 2 ;; esac
mods=${*:-$(addon_modules)}
J=$(mktemp); trap 'rm -f "$J"' EXIT
login "${ADMIN_USER:-admin}" passwd "$J"
fail=0
for m in $mods; do
	T=$(csrf_token "$J")
	id=$(db -e "SELECT mod_id FROM dotp_modules WHERE mod_directory='$m'")
	state="CONCAT(mod_version, '/', mod_active, '/', mod_ui_active)"
	before=$(db -e "SELECT $state FROM dotp_modules WHERE mod_directory='$m'")
	post=$cmd
	if [ "$cmd" = menu ]; then
		[ "${before##*/}" = 1 ] && continue
		post=toggleMenu
	fi
	log_mark
	curl -s -b "$J" -o "$OUT/modules-$cmd-$m.html" -X POST "$U?m=system&a=domodsql" \
		--data "csrf_token=$T&cmd=$post&mod_id=${id:-0}&mod_directory=$m"
	after=$(db -e "SELECT $state FROM dotp_modules WHERE mod_directory='$m'")
	case "$cmd" in
		install) ok=$([ -n "$after" ] && echo 1) ;;
		remove) ok=$([ -z "$after" ] && echo 1) ;;
		toggle) b=${before#*/}; a=${after#*/}; ok=$([ "${b%/*}" != "${a%/*}" ] && echo 1) ;;
		menu) ok=$([ "${after##*/}" = 1 ] && echo 1) ;;
		upgrade) ok=1 ;;
	esac
	if [ -z "$ok" ]; then echo "FAIL  $cmd $m: no effect (before '$before', after '$after')"; fail=1; fi
	log_check "$cmd $m" >"$OUT/.check" || { cat "$OUT/.check"; fail=1; }
done
[ $fail = 0 ] && echo "PASS  $cmd: $(echo $mods | wc -w) modules"
exit $fail
