# Developer checks. Each target is also a CI job (.github/workflows/ci.yml).
#
#   make check         lint + phpstan + raw-input + inline-js + js-test (no sandbox needed)
#   make audit-smoke   start docker/, reinstall the database, run the security smoke test and the
#                      REST API checks (docker/tests/api.sh)
#   make sample-data   start docker/, reinstall the database, load the sample data (db/sample/)
#   make audit-full    start docker/, then docker/tests/run.sh: every add-on installed, used and
#                      removed with no SQL error or PHP warning, layout in every theme (~1 hour)
#   make sample-sql [OUT=file] [PREFIX=dp_]
#                      the sample data as one SQL file, for servers without command-line PHP
#   make rehearse DUMP=prod.sql.gz [ADMIN=user]
#                      upgrade a copy of a production database in docker/ (docker/rehearse.sh)

PHPSTAN_IMAGE := ghcr.io/phpstan/phpstan:2
DOCKER_RUN := docker run --rm --user $(shell id -u):$(shell id -g) --security-opt label=disable -v $(CURDIR):/app -w /app
export DP_UID := $(shell id -u)
export DP_GID := $(shell id -g)

.PHONY: check lint phpstan phpstan-baseline raw-input inline-js js-test audit-smoke audit-full sample-data sample-sql rehearse

check: lint phpstan raw-input inline-js js-test

# PHP 8.3 syntax check of first-party code (vendored copies excluded, as in phpstan.neon.dist).
lint:
	git ls-files -z '*.php' \
	  | grep -zvE '^(lib|vendor)/|^modules/(holiday/PEAR)/' \
	  | $(DOCKER_RUN) -i --entrypoint sh php:8.3-cli -c 'xargs -0 -n 50 -P 4 sh -c "for f; do php -d error_reporting=E_ALL\&~E_DEPRECATED -l \"\$$f\" >/dev/null || { php -l \"\$$f\"; exit 255; }; done" _'
	@echo "OK: no syntax errors."

# includes/config.php is not tracked; PHPStan follows require statements into it.
phpstan:
	test -e includes/config.php || cp includes/config-dist.php includes/config.php
	$(DOCKER_RUN) $(PHPSTAN_IMAGE) analyse -c phpstan.neon.dist --memory-limit=3G --no-progress

# Only after fixing existing findings: records the remaining ones as accepted.
phpstan-baseline:
	test -e includes/config.php || cp includes/config-dist.php includes/config.php
	$(DOCKER_RUN) $(PHPSTAN_IMAGE) analyse -c phpstan.neon.dist --memory-limit=3G --no-progress --generate-baseline phpstan-baseline.neon

raw-input:
	scripts/check-raw-input.sh

# No new inline JavaScript (CSP); see docs/csp.md.
inline-js:
	scripts/check-inline-js.sh

# Jest tests for js/ (tools/jest/tests/*.test.js), in the node image so no local Node is needed.
js-test:
	$(DOCKER_RUN) -w /app/tools/jest -e HOME=/tmp node:22 sh -c 'npm ci --no-audit --no-fund --silent && npx jest'

audit-smoke:
	docker/up.sh
	@for i in $$(seq 30); do curl -s -o /dev/null http://127.0.0.1:8089/index.php && break; sleep 1; done
	docker/reset-db.sh
	docker/smoke.sh
	docker/tests/seed.sh
	docker/tests/api.sh

audit-full:
	docker/up.sh
	@for i in $$(seq 30); do curl -s -o /dev/null http://127.0.0.1:8089/index.php && break; sleep 1; done
	docker/tests/run.sh

sample-data:
	docker/up.sh
	@for i in $$(seq 30); do curl -s -o /dev/null http://127.0.0.1:8089/index.php && break; sleep 1; done
	docker/reset-db.sh
	docker compose -f docker/compose.yml exec -T web php db/sample/sample-data.php

sample-sql:
	docker/up.sh
	@for i in $$(seq 30); do curl -s -o /dev/null http://127.0.0.1:8089/index.php && break; sleep 1; done
	docker/sample-sql.sh "$(or $(OUT),docker/.audit/dotproject-sample-data.sql)" "$(or $(PREFIX),dotp_)"

rehearse:
	@test -n "$(DUMP)" || { echo "usage: make rehearse DUMP=/path/to/dump.sql[.gz] [ADMIN=user]"; exit 2; }
	docker/up.sh
	@for i in $$(seq 30); do curl -s -o /dev/null http://127.0.0.1:8089/index.php && break; sleep 1; done
	docker/rehearse.sh "$(DUMP)" $(ADMIN)
