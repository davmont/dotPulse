# Requirements

dotPulse is a PHP application with a MySQL or MariaDB database. Any server that runs PHP 8.2 or later behind Apache or nginx can host it. Linux is where it is developed and tested.

## PHP

You need **PHP 8.2 or later**. PHP 8.3 is tested.

The libraries that ship with dotPulse need these extensions. Most PHP packages include them already:

| Extension | What needs it |
| --- | --- |
| `mysqli` | The database connection. |
| `mbstring` | Text handling throughout, PDF reports and the two-step login QR code. |
| `dom` | PDF reports. |
| `session`, `ctype`, `filter`, `hash` | Logins and sending mail. |

These extensions are optional. dotPulse works without them, but loses something:

| Extension | Without it |
| --- | --- |
| `fileinfo` | The type of uploaded files is not detected: every upload is stored as `application/octet-stream`. |
| `gd` | The installer flags it as missing. The PDF library uses it to draw images. |
| `zlib` | The Backup module has restricted operation. |
| `curl` | The Mantis module cannot reach MantisBT. |
| `ldap` | You cannot log in against an LDAP directory or import contacts from one. |

### PHP settings

The installer checks these and shows a warning for each one that is off:

- `file_uploads` on. The installer shows the largest file you can upload: the smallest of `upload_max_filesize`, `post_max_size` and `memory_limit`.
- `session.use_cookies` on, and `session.use_trans_sid` off.
- A writable `session.save_path`. You can ignore this one if you keep the default session handling, which stores sessions in the database.

Run PHP as an Apache module or through PHP-FPM. The installer warns that plain CGI mode is likely to have problems.

## Database

You need **MySQL or MariaDB**. dotPulse connects through the `mysqli` driver. The development sandbox uses MariaDB 10.11.

Create a database user for dotPulse. The installer can create the database itself if that user may create databases; otherwise create an empty database first and give the user full rights on it.

Create the database with the **utf8mb4** character set (the example in [Installing](install.md) does), so that text in every language is stored as written.

## Web server

**Apache** is recommended. dotPulse ships `.htaccess` files that stop the web server from serving code, libraries, uploaded files and other internal files directly. Apache only reads them when the dotPulse directory has `AllowOverride All` (or at least `AuthConfig Limit Options FileInfo`). Without it, Apache ignores them silently.

**nginx** works too, but it never reads `.htaccess` files. You must copy the same rules into your `server` block. The [deployment security guide](https://github.com/davmont/dotPulse/blob/master/docs/deployment-security.md#nginx) has them ready to paste.

Serve the site over **HTTPS**. You need it for the REST API, and it protects the passwords and session cookies of your users.

## Folders the web server writes to

The web server user must be able to write to:

- `includes/`, during installation only, so the installer can write `includes/config.php`. If it can't, the installer shows the file's content for you to create by hand.
- `files/`, where uploaded files are stored.
- `files/temp/`, for PDF reports and temporary files.
- `locales/` and its language folders (and `modules/*/locales/`), only if you will edit translations in **System Admin › Translations**. The installer checks `locales/en/`.

## Browsers

Your users need a current browser with JavaScript and cookies turned on. The dotPulse theme adapts to phones and laptops. The installer warns against Internet Explorer.

## Optional: cron and mail

dotPulse sends mail for password resets, task assignments, task notifications and reminders. To send it, you need either PHP's own `mail()` set up on the server, or an SMTP server you can use.

Task notifications, assignment mails and reminders go through an event queue. A cron job must run `queuescanner.php` every few minutes, or those mails are never sent. [Installing](install.md#set-up-the-cron-job) shows how.
