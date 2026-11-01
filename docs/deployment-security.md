# Deployment security notes

dotProject serves every page through `index.php` (plus `fileviewer.php` for
downloads and `install/` for the installer). Everything else in the tree is
either included by those entry points or not meant for the web at all.

## Web server rules

### Apache

The repository ships `.htaccess` files that enforce this. They need
`AllowOverride All` (or at least `AuthConfig Limit Options FileInfo`) for the
dotProject directory; without it Apache ignores them silently.

| Path | Rule |
|---|---|
| `modules/`, `lib/`, `classes/`, `functions/`, `includes/`, `style/`, `locales/`, `db/`, `misc/` | `.php`, `.phtml`, `.phar` and `.inc` refused; images, CSS and JS still served |
| `vendor/`, `tests/`, `docs/`, `docker/`, `files/` | Everything refused |
| Root | `queuescanner.php`, developer scripts, `composer.*`, `package*.json` and `*.md` refused |

Uploaded files are served by `fileviewer.php?file_id=...` and each user's PDF
report by `fileviewer.php?report=pdf`, both after the login and permission
checks.

### nginx

nginx does not read `.htaccess`. Put the equivalent rules in the `server`
block, before the PHP `location`:

```nginx
location ~ ^/(modules|lib|classes|functions|includes|style|locales|db|misc)/.*\.(php|phtml|phar|inc)$ { deny all; }
location ~ ^/(vendor|tests|docs|docker|files)/ { deny all; }
location ~ ^/(queuescanner|benchmark_\w+|test_\w+|fix_locales|install_timeplanning|gen_locale\w*|find_missing_labels)\.php$ { deny all; }
location ~ ^/(composer\.(json|lock)|package(-lock)?\.json)$ { deny all; }
location ~ \.md$ { deny all; }
```

Prefix the paths if dotProject is installed in a subdirectory.

### Checking a deployment

Each of these must answer 403 (or 404), never 200:

```sh
for p in modules/tasks/tasks.class.php vendor/autoload.php includes/config.php \
         tests/run_tests.php files/ queuescanner.php composer.json; do
  printf '%-32s %s\n' "$p" "$(curl -s -o /dev/null -w '%{http_code}' "https://your-host/$p")"
done
```

## Event queue (`queuescanner.php`)

`queuescanner.php` sends the queued events: task notification, assignment and
reminder mails, and all mail when "Queue Email for later sending" is set. Without
it those mails are never sent. It
runs on the command line only: over HTTP it answers 404 (and the web server
rules above refuse it before PHP runs). Run it from cron as the web server
user, with the site's public URL so that the links in the mails work:

```cron
# /etc/cron.d/dotproject
*/5 * * * * www-data php /var/www/dotproject/queuescanner.php --base-url=https://pm.example.com
```

- `--base-url` (or the `DP_BASE_URL` environment variable) is required; the
  script exits with status 2 without it.
- Overlapping runs are skipped through a lock on
  `files/temp/queuescanner.lock`; status 1 means that file could not be
  opened (wrong user or permissions).
- Output goes to cron's mail, or append `>> /var/log/dotproject-queue.log 2>&1`.

Sites that fetched `queuescanner.php` over HTTP (`wget`/`curl` in a cron job)
must switch to the command above.

An event that fails with a PHP error is logged (`dotPulse event queue: dropped
event ...`) and removed, so it no longer blocks the events behind it. Mail that
cannot be sent is logged as `dotPulse mail: not sent (...)` and stays queued.

## Mail

Set these under System Admin > System Configuration > Mail:

- **Sender address** (`mail_from`): the address all mail is sent from, for
  example `notifications@example.com`, on a domain whose SPF/DKIM records allow
  your SMTP server. The user who caused a message appears as the sender name and
  replies go to them. When it is empty, mail is sent from each user's own address,
  which receiving servers often reject or mark as spam (DMARC).
- **SMTP** with **Enable TLS**: port 587 uses STARTTLS, port 465 uses SMTP over
  TLS (SMTPS).
- **Send a test email** (below the settings) sends a message with the saved
  settings and shows the server's error if it fails.

Users get an email when someone else assigns them to a task; each user can turn
it off in their preferences ("Email me when I am assigned to a task").

## Two-step login

Users turn it on themselves in their account (click their name, **Two-step login**
tab): they scan a QR code with an authenticator app, confirm with a code and save ten
single-use recovery codes. After that, their password alone no longer logs them in:
the login page asks for the 6-digit code (or a recovery code) within 5 minutes.

- Recommend it at least to administrators, and to anyone who can see costs or
  personal data.
- The server's clock must be right (NTP): codes are accepted only within 30 seconds
  either side of the current time.
- A user who lost their phone and their recovery codes: an administrator who may
  edit users opens that user's **Two-step login** tab and resets it.
- Five wrong codes ask for the password again; ten wrong codes from one address in
  15 minutes block that address for the rest of the window.
- The REST API is not affected: tokens are personal access tokens, revoked separately.

## REST API (`api.php`)

The API is off until **Enable the REST API** is ticked in System Configuration; leave
it off if nothing uses it. When it is on:

- serve the site over HTTPS only, since tokens travel in every request;
- with PHP-FPM or CGI, make sure the `Authorization` header reaches PHP (the root
  `.htaccess` does it on Apache; nginx passes it by default);
- tokens act with their user's permissions and can be revoked from the user's **API
  tokens** tab, or all at once by turning the API off; deactivating a user stops
  their tokens;
- 20 failed authentications from one address in 10 minutes block that address for
  the rest of the window (HTTP 429).

Details, endpoints and nginx `PATH_INFO` configuration: [api.md](api.md).

## Content-Security-Policy

`index.php` sends an enforced `Content-Security-Policy` header (see
`docs/csp.md`): inline scripts without the per-request nonce, inline event
handlers, `javascript:` URLs, `eval()` and resources from other sites are
blocked. Browsers post violations to `csp-report.php`, which appends them to
`files/temp/csp-reports.log` (not served over HTTP; it stops growing at 5 MB,
delete it to start over). `scripts/csp-report-summary.sh` groups the log.

To roll back to report-only while a broken page is fixed, add
`$dPconfig['csp_report_only'] = true;` to `includes/config.php`.
