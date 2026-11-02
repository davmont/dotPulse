# Audit log

dotPulse keeps a security audit log of logins and of the changes administrators make.
It is always on (unlike the History module) and is shown in **System Admin > Audit
log** to users with view permission on System Admin (administrators by default),
newest first, filtered by event, user name and dates.

Each entry has the date, the user (or, for a failed login, the user name that was
tried), the IP address, the event and details. Passwords, tokens and configuration
values are never written: a configuration change lists the names of the settings that
changed.

Entries older than **audit_log_days** (System Configuration, default 365; 0 keeps them
forever) are deleted from time to time.

## Events

| Event | When |
| --- | --- |
| `login.success` | A user logged in (through two-step login when it is on) |
| `login.failure` | Wrong user name or password (the name tried is recorded) |
| `login.blocked` | An address reached the failed-login limit |
| `logout` | A user logged out |
| `twofactor.failure` | Wrong two-step login code |
| `twofactor.blocked` | Too many wrong codes: the password is asked again |
| `twofactor.recovery_used` | Login with a recovery code (how many are left) |
| `twofactor.enabled`, `twofactor.disabled` | A user turned two-step login on or off |
| `twofactor.recovery_renewed` | A user created new recovery codes |
| `twofactor.reset` | An administrator reset a user's two-step login |
| `password.changed` | Password changed (by the user, or by an administrator for them) |
| `password.reset_requested` | A reset link was emailed |
| `password.reset` | A password was reset with the emailed link |
| `api.token_created`, `api.token_revoked` | REST API tokens (name, access and expiry) |
| `api.blocked` | An address reached the API's failed-authentication limit |
| `user.created`, `user.updated`, `user.deleted` | User accounts (`password changed` in the details when it was) |
| `user.role_added`, `user.role_removed` | Roles of a user |
| `user.transferred` | A user's projects and tasks transferred to another user |
| `permission.added`, `permission.removed` | Permissions of a user or of a role |
| `role.saved`, `role.deleted` | Roles |
| `config.changed` | System Configuration saved (names of the changed settings) |
| `module.changed` | A module installed, removed, upgraded, switched on or off, or configured |

## For developers

Write entries with `dPaudit($event, $details, $object, $object_id)` (in
`includes/main_functions.php`); add new event names and their descriptions to
`CAuditLog::events()` (`classes/auditlog.class.php`) and to this page. A failure to
write the log is reported in the PHP error log and never stops the action.
