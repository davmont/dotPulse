#!/bin/bash
# Writes the sample data (db/sample/) as one SQL file, for a server where the script
# cannot run (no command-line PHP). Run through `make sample-sql [OUT=file] [PREFIX=dp_]`.
#
# Reinstalls the sandbox database without the worker and guest test accounts, loads
# the sample data and dumps the whole database: tables and rows, no DROP TABLE. The file
# loads into an empty database or a fresh installation (existing tables are kept and
# emptied, its rows replace the installer's); a database with projects, companies or users
# besides the administrator makes it stop at the first statements and changes nothing.
# Dates are those of the day it runs; regenerate the file rather than keeping one.
set -e
OUT=${1:-docker/.audit/dotproject-sample-data.sql}
PREFIX=${2:-dotp_}
[[ "$PREFIX" =~ ^[A-Za-z0-9_]*$ ]] || { echo "PREFIX: letters, digits and _ only"; exit 2; }
cd "$(dirname "$0")/.."
mkdir -p "$(dirname "$OUT")"

NO_TEST_USERS=1 docker/reset-db.sh
docker compose -f docker/compose.yml exec -T web php db/sample/sample-data.php
# Every table is emptied before its rows load, so refuse real data first. Prepared, because
# plain SQL has no IF, and the tables may not exist yet. MySQL cannot prepare SIGNAL, so the
# refusal is a query on a table that does not exist, named after the message.
cat > "$OUT" <<SQL
SET @dp_q = IF((SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = '${PREFIX}projects') > 0, 'SET @dp_n = (SELECT COUNT(*) FROM \`${PREFIX}projects\`) + (SELECT COUNT(*) FROM \`${PREFIX}companies\`) + (SELECT COUNT(*) FROM \`${PREFIX}users\`) - 1', 'SET @dp_n = 0');
PREPARE dp_s FROM @dp_q;
EXECUTE dp_s;
SET @dp_q = IF(@dp_n > 0, 'SELECT 1 FROM \`This database already has data: sample data not loaded\`', 'DO 0');
PREPARE dp_s FROM @dp_q;
EXECUTE dp_s;
DEALLOCATE PREPARE dp_s;
SQL
# The first line of a recent mariadb-dump is a MariaDB-only marker that MySQL rejects.
docker compose -f docker/compose.yml exec -T db mariadb-dump -udotproject -pdotproject \
	--skip-add-drop-table --skip-comments --single-transaction dotproject \
	| sed -e '1{/enable the sandbox mode/d}' -e 's/^CREATE TABLE `/CREATE TABLE IF NOT EXISTS `/' \
		-e 's/^LOCK TABLES `\([^`]*\)` WRITE;$/&\nDELETE FROM `\1`;/' -e "s/\`dotp_/\`$PREFIX/g" >> "$OUT"

echo "Wrote $OUT ($(grep -ac 'CREATE TABLE' "$OUT") tables, prefix '$PREFIX')."
echo "Logins: admin / passwd, consultants / demo1234 (db/sample/README.md): change them on a server others can reach."
echo "The sandbox has no worker or guest account now; make sample-data puts them back."
