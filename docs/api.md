# REST API

dotPulse has a JSON API for scripts and other tools: list projects and tasks, log
work and update task progress. It is served by `api.php` and is **off by default**.

- [Turning it on](#turning-it-on)
- [Tokens](#tokens)
- [Requests and responses](#requests-and-responses)
- [Endpoints](#endpoints)
- [Permissions](#permissions)
- [Web server notes](#web-server-notes)

## Turning it on

A system administrator ticks **Enable the REST API** (`api_enabled`) in System Admin >
System Configuration. Turning it off again stops every token at once; the tokens are
kept and work again when it is turned back on.

## Tokens

Each user creates their own tokens in their account (click your name, then the **API
tokens** tab):

- **Name:** what the token is for ("CI bot", "time tracker").
- **Access:** *Read only* (GET requests only) or *Read and write*.
- **Expires:** 30, 90 (default) or 365 days, or never.

The token (`dpp_` followed by 40 hex digits) is shown **once**, right after it is
created. dotPulse stores only its SHA-256, so a lost token cannot be shown again:
revoke it and create a new one. The tab lists each token's first characters, when it
was created and last used, and has a **Revoke** button. Administrators who may edit
users see the tab of every user and can revoke their tokens, but cannot create tokens
for them. A user can have at most 20 tokens.

A token acts **as its user**: it reaches exactly what the user can see and change in
the web interface, no more. A read-and-write token of a user with read-only
permissions still cannot change anything.

## Requests and responses

```
GET /api.php/v1/projects HTTP/1.1
Host: pm.example.com
Authorization: Bearer dpp_0123456789abcdef0123456789abcdef01234567
```

```sh
curl -H "Authorization: Bearer $TOKEN" https://pm.example.com/api.php/v1/me
```

- **Address:** `https://<your site>/api.php/v1/...`. Where the server does not pass the
  path after `api.php` (some nginx setups), use `api.php?route=/v1/...` instead.
- **Bodies** of POST and PATCH requests are JSON objects sent with
  `Content-Type: application/json`, at most 64 KB. Unknown fields are ignored.
- **Responses** are JSON in UTF-8. Single records are `{"data": {...}}`.
- **Lists** take `limit` (1-200, default 50) and `offset`, and return
  `{"data": [...], "meta": {"limit": 50, "offset": 0, "next_offset": 50}}`.
  `next_offset` is the `offset` of the next page, or `null` on the last page.
- **Dates** are in the server's local time, as `YYYY-MM-DDTHH:MM:SS`; empty dates are
  `null`.
- **Errors** use the HTTP status and a body such as
  `{"error": {"code": "not_found", "message": "No such task."}}`:

  | Status | Code | When |
  | --- | --- | --- |
  | 400 | `invalid_parameter`, `invalid_body` | A parameter or the body is wrong |
  | 401 | `unauthorized` | No token, or an unknown, expired or revoked one |
  | 403 | `read_only_token` | A read-only token sent a POST or PATCH |
  | 403 | `forbidden` | The user may see the record but not change it |
  | 404 | `api_disabled` | The API is turned off |
  | 404 | `not_found` | No such endpoint, or no such record *for this user* |
  | 405 | `method_not_allowed` | The `Allow` header lists the methods |
  | 413, 415 | `body_too_large`, `unsupported_media_type` | |
  | 422 | `not_saved` | The record was refused when saving |
  | 429 | `too_many_attempts` | 20 failed authentications from one address in 10 minutes |
  | 500 | `server_error` | Logged in the PHP error log as "dotPulse API: ..." |

## Endpoints

### `GET /v1/me`

The token's user: `id`, `username`, `first_name`, `last_name`, `email`, `company_id`,
`locale` and `token_scope` (`read` or `write`).

### `GET /v1/projects`

Projects the user may view, by name. Filters: `status` (a value of the ProjectStatus
list, for example 3 = In Progress) and `company` (company id).

```json
{
  "id": 1, "name": "Website relaunch", "short_name": "WEB",
  "company": {"id": 2, "name": "Acme"},
  "status": 3, "status_name": "In Progress", "priority": 0, "owner_id": 1,
  "start_date": "2026-11-01T09:00:00", "end_date": "2027-01-31T18:00:00",
  "percent_complete": 35.5, "color": "#1b4f9c", "description": "..."
}
```

### `GET /v1/projects/{id}`

One project.

### `GET /v1/projects/{id}/tasks`

The project's tasks the user may view, in start date order. Same format as below.

### `GET /v1/tasks`

Tasks the user may view, by project and start date. Filters:

- `project`: a project id;
- `assigned`: `me` or a user id;
- `open`: `1` for tasks below 100 %, `0` for finished ones.

```json
{
  "id": 12, "name": "Design review", "project_id": 1, "parent_id": null,
  "milestone": false, "dynamic": false, "owner_id": 1,
  "start_date": "2026-11-03T09:00:00", "end_date": "2026-11-05T18:00:00",
  "duration": 3, "duration_unit": "days", "status": 0, "priority": 0,
  "percent_complete": 40, "hours_worked": 6.5,
  "assignees": [{"user_id": 2, "percent": 100}]
}
```

`parent_id` is `null` for top-level tasks. `status` is 0 (active) or -1 (inactive);
`priority` is -1 (low), 0 (normal) or 1 (high).

### `GET /v1/tasks/{id}`

One task, with its `description`.

### `PATCH /v1/tasks/{id}` (write)

Changes `percent_complete` (0-100) and/or `priority` (-1, 0, 1). Setting 100 % on a
task without an end date sets it to now, as the web form does. Needs edit permission
on the task. Returns the task.

```sh
curl -X PATCH -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"percent_complete": 100}' https://pm.example.com/api.php/v1/tasks/12
```

### `GET /v1/tasks/{id}/logs`

The task's work log, newest first:
`id`, `task_id`, `creator_id`, `date`, `hours`, `summary`, `description`, `costcode`,
`problem`.

### `POST /v1/tasks/{id}/logs` (write)

Logs work on a task, as the token's user. Fields:

| Field | Required | |
| --- | --- | --- |
| `hours` | yes | 0-999, decimals allowed |
| `date` | no | `YYYY-MM-DD` or `YYYY-MM-DDTHH:MM:SS`; default now |
| `summary`, `description` | no | text |
| `costcode` | no | up to 8 characters |
| `problem` | no | `true` flags the log as a problem |
| `percent_complete` | no | also sets the task's progress (0-100) |

Needs edit permission on the task and add permission on task logs, like the Log tab.
Returns 201 and the log. Add-on modules that extend the web form's log (Unit cost)
are not updated by the API.

## Permissions

Every request runs the same checks as the web pages, for the token's user:

- projects: module permission, company and project allow/deny rules;
- tasks: the project's permission plus the task's own access level (public,
  protected, participant, private, privileged);
- changes: edit permission on the task, and add permission on task logs for logging.

A record the user may not see answers 404, the same as a record that does not exist,
so ids cannot be probed. A user who is deactivated (no role left) can no longer use
their tokens.

## Web server notes

- **Apache** passes the `Authorization` header to `mod_php`. With PHP-FPM or CGI the
  root `.htaccess` copies it (`SetEnvIf Authorization`); without `.htaccess` add the
  same line, or `CGIPassAuth On`, to the site configuration.
- **nginx** passes the header to PHP-FPM as `HTTP_AUTHORIZATION` by default. For
  `api.php/v1/...` addresses the PHP location must accept a path after the script:

  ```nginx
  location ~ ^/api\.php(/|$) {
      fastcgi_split_path_info ^(/api\.php)(/.*)$;
      fastcgi_param PATH_INFO $fastcgi_path_info;
      include fastcgi_params;
      fastcgi_param SCRIPT_FILENAME $document_root/api.php;
      fastcgi_pass php-fpm;
  }
  ```

  Otherwise use `api.php?route=/v1/...`.
- Use **HTTPS**: the token travels in every request.
- Responses carry `Cache-Control: no-store` and set no cookies; browsers on other
  sites cannot call the API (no CORS headers are sent).
