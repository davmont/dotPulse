# Developer checks. Each target is also a CI job (.github/workflows/ci.yml).
#
#   make check         lint + phpstan + raw-input + inline-js + locales + theme-contrast +
#                      php-test + js-test + vendor-check
#                      (no sandbox needed)
#   make deps-audit    composer audit and npm audit of the dependencies (needs network access)
#   make audit-smoke   start docker/, reinstall the database, run the security smoke test and the
#                      REST API, dashboard, board, two-step login, audit log, earned value tip and
#                      holiday calendar checks
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

.PHONY: check lint phpstan phpstan-baseline raw-input inline-js locales theme-contrast php-test js-test vendor-check deps-audit audit-smoke audit-full sample-data sample-sql rehearse docs docs-serve docs-screenshots

check: lint phpstan raw-input inline-js locales theme-contrast php-test js-test vendor-check

# PHP 8.3 syntax check of first-party code (vendored copies excluded, as in tools/phpstan/phpstan.neon.dist).
lint:
	git ls-files -z '*.php' \
	  | grep -zvE '^(lib|vendor)/' \
	  | $(DOCKER_RUN) -i --entrypoint sh php:8.3-cli -c 'xargs -0 -n 50 -P 4 sh -c "for f; do php -d error_reporting=E_ALL\&~E_DEPRECATED -l \"\$$f\" >/dev/null || { php -l \"\$$f\"; exit 255; }; done" _'
	@echo "OK: no syntax errors."

# includes/config.php is not tracked; PHPStan follows require statements into it.
phpstan:
	test -e includes/config.php || cp includes/config-dist.php includes/config.php
	$(DOCKER_RUN) $(PHPSTAN_IMAGE) analyse -c tools/phpstan/phpstan.neon.dist --memory-limit=3G --no-progress

# Only after fixing existing findings: records the remaining ones as accepted.
phpstan-baseline:
	test -e includes/config.php || cp includes/config-dist.php includes/config.php
	$(DOCKER_RUN) $(PHPSTAN_IMAGE) analyse -c tools/phpstan/phpstan.neon.dist --memory-limit=3G --no-progress --generate-baseline tools/phpstan/phpstan-baseline.neon

raw-input:
	tools/checks/check-raw-input.sh

# No new inline JavaScript (CSP); see docs/csp.md.
inline-js:
	tools/checks/check-inline-js.sh

# Every string the code translates has an English and a Spanish entry (tools/locales/check.php;
# `php tools/locales/check.php missing es` lists what is missing).
locales:
	$(DOCKER_RUN) php:8.3-cli php tools/locales/check.php

# Text and control colours of the dotPulse themes reach WCAG AA (tools/checks/theme-contrast.py;
# the icons are checked when tools/icons/build.py writes them).
theme-contrast:
	python3 tools/checks/theme-contrast.py

# PHP unit tests (tests/*Test.php, tests/run.php).
php-test:
	$(DOCKER_RUN) php:8.3-cli php tests/run.php

# vendor/ holds what composer.lock says and loads on the oldest supported PHP (8.2).
vendor-check:
	$(DOCKER_RUN) php:8.2-cli php tools/composer/vendor-check.php

# Known vulnerabilities: any in the Composer packages (vendor/, deployed with the code); moderate
# or worse in the Node.js packages of the development tools (never deployed; Jest's deepmerge
# has a low advisory with no fixed release, and tools/jest/package.json overrides glob and
# minimatch to fixed versions).
# Libraries copied into lib/ and modules/ are not covered: see docs/third-party.md.
deps-audit:
	$(DOCKER_RUN) -e COMPOSER_HOME=/tmp composer:2 audit --locked
	for d in tools/jest tools/browser; do \
	  $(DOCKER_RUN) -w /app/$$d -e HOME=/tmp node:22 npm audit --omit=optional --audit-level=moderate || exit 1; \
	done

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
	docker/tests/dashboard.sh
	docker/tests/board.sh
	docker/tests/twofactor.sh
	docker/tests/auditlog.sh
	docker/tests/ev-tips.sh
	docker/tests/holiday.sh

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

# Documentation website (website/, MkDocs Material). docs fails on broken links like CI.
MKDOCS := docker run --rm --user $(shell id -u):$(shell id -g) --security-opt label=disable -v $(CURDIR)/website:/docs squidfunk/mkdocs-material:9.6.14
docs:
	$(MKDOCS) build --strict

docs-serve:
	docker run --rm -it --user $(shell id -u):$(shell id -g) --security-opt label=disable -p 8000:8000 -v $(CURDIR)/website:/docs squidfunk/mkdocs-material:9.6.14

# Screenshots for the website from the sandbox with the sample data (docker/up.sh first).
docs-screenshots:
	node tools/browser/screenshots.js website/screenshots.json website/docs/img
	@command -v pngquant >/dev/null && pngquant --quality=80-95 --skip-if-larger --strip --force --ext .png website/docs/img/*.png || echo 'pngquant not found: images left uncompressed'
