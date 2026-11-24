# Writing a module

A module is a folder under `modules/`. The core modules (companies, projects,
tasks...) and the add-ons (links, risks, the holiday calendars...) are built the
same way, and an add-on you write is installed from **System Admin › Modules**
like the ones that come with dotPulse. This page explains the files of a module
and what the core does with them, then builds a small but complete module, and
ends with the checks to run before you publish one.

Read [How a request flows](index.md#how-a-request-flows) first: it explains how
`index.php` reaches a module's pages. The rules for input, output, permissions
and CSRF are in [Writing secure code](secure-code.md); this page only shows
how a module applies them.

## The files of a module

The folder's name is the module's name: `modules/notes/` is the module `notes`,
and its pages are `index.php?m=notes&a=<action>`. Use lowercase letters, digits
and underscores: `index.php` refuses a name with a dot, a slash, a quote, a
parenthesis, `;` or `$`.

| File | What it is |
| --- | --- |
| `setup.php` | The module's description (`$config`) and its setup class, which creates, upgrades and drops its tables. Required: without it the module can't be installed. |
| `<name>.class.php` | The module's classes, usually one record class extending `CDpObject`. `index.php` includes it before every page of the module. |
| `index.php` | The page shown for `?m=<name>`, usually a list. |
| `<action>.php` | Any other page, shown for `?m=<name>&a=<action>`. By convention `addedit.php` adds and edits a record and `view.php` shows one. |
| `do_<thing>_aed.php` | A write handler: a form posts to it with `dosql=do_<thing>_aed`. |
| `<action>.js`, `<name>.module.js` | JavaScript, loaded automatically (see [JavaScript](#javascript)). |
| `images/` | The module's icons. |
| `locales/<lang>.inc` | The module's translations. |
| `<module>_tab.<name>.php` | A tab the module adds to another module's pages (see [Tabs on other modules' pages](#tabs-on-other-modules-pages)). |

Every PHP file starts with the same guard, so that it can't be opened directly
by its URL under `modules/`:

```php
<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
```

!!! warning "Every file is a page"
    `index.php?m=<name>&a=<file>` runs any PHP file of the folder whose name
    has no dot, for anyone with access to the module. A file that is only
    meant to be included must not do anything harmful when it is opened that
    way. Files whose names have a dot (`notes.class.php`,
    `projects_tab.notes.php`) can't be opened as pages.

## setup.php

`setup.php` defines an array, `$config`, and a setup class. **System Admin ›
Modules** (`modules/system/viewmods.php`) includes the `setup.php` of every
module in the same request, so the class name must be unique: `CSetup<Name>`
by convention. The file should only define these two things.

### The $config array

When the module is installed, `modules/system/domodsql.php` binds `$config` to
a `CModule` record (`modules/system/system.class.php`) and stores it in the
`modules` table. A key that is not a column of that table is not stored:
`mod_config` is read from `setup.php` each time the Modules page is shown.

| Key | Required | What it does |
| --- | --- | --- |
| `mod_name` | yes | The module's name, shown in **System Admin › Modules** and used as the name of its permission object. |
| `mod_version` | yes | The version of the code. When it is newer than the version stored at install, **Upgrade available** appears (compared with `version_compare()`). |
| `mod_directory` | yes | The folder name. |
| `mod_setup_class` | yes | The name of the setup class defined below. |
| `mod_type` | yes | `user` for an add-on. See the types below. |
| `mod_ui_name` | yes | The name of its entry in the main menu, translated with `$AppUI->_()`. |
| `mod_ui_icon` | no | An icon file name. It is stored, but no theme shows it at present. |
| `mod_description` | no | A one-line description. |
| `mod_config` | no | `true` shows a **configure** button in **System Admin › Modules**, if the setup class has a `configure()` method. |
| `mod_ui_active` | no | `1` puts the module in the menu at install (Registers and Document Management do). Without it the module starts hidden. |
| `permissions_item_table` | no | The module's main table. It becomes a section of the permission system, so rights can be given on its records, and the History module can filter by it. Name the table after the module (see [Permissions](#permissions)). |
| `permissions_item_field` | no | That table's primary key. |
| `permissions_item_label` | no | The column that names a record (its title). |

The `mod_type` values:

| Type | Meaning |
| --- | --- |
| `user` | An add-on. On install it joins the **non-admin modules** group, so every role with rights on that group gets the same rights on it. |
| `admin` | An administration tool (the holiday module). On install it joins the **admin modules** group instead: only roles with rights on the admin modules get rights on it. |
| `utility` | Never gets a menu entry, even when shown in the menu: it only adds views to other modules (iGantt). |
| `core` | Part of dotPulse: it has no **remove** button. Don't use it for an add-on. |

[Permissions of a new module](../administration/modules.md#permissions-of-a-new-module)
explains the two groups from the administrator's side.

### The setup class

`domodsql.php` calls these methods on a new instance of the class:

| Method | Called when | Returns |
| --- | --- | --- |
| `install()` | **install** is clicked. Create the tables and any initial rows. | `null` (or `true`) on success: the module is registered and the page says *Module installed*. `false` or an error message: the module is not registered, and the page says *Module not installed* with the message. |
| `remove()` | **remove** is clicked. Drop the tables and delete anything else the module added. | `null`, or a message to show. |
| `upgrade($old_version)` | **upgrade** is clicked. `$old_version` is the version stored when the module was installed or last upgraded. Bring the tables from that version to `mod_version`. | `true` on success: the new `$config` (and so the new version) is stored. `false`: the page says *Module not upgraded* and the stored version stays, so the upgrade can be tried again. |
| `configure()` | **configure** is clicked (only with `mod_config` set). Usually redirects to the module's own settings page, `?m=<name>&a=configure`, which `index.php` opens only to users who may edit System Admin. | `true`, or `false` for *Module configuration failed*. |

!!! warning
    A module whose `install()` fails is not registered, so the administrator
    can't **remove** it. Undo what `install()` did before it returns the error
    (drop the tables it created), or create the tables with
    `CREATE TABLE IF NOT EXISTS`, so that **install** can be clicked again.

The Modules page offers **upgrade** only when
`version_compare(mod_version in setup.php, stored version, '>')` is true, and
the System Admin start page counts those modules. Write
`upgrade()` so that it can start from any earlier version: test each step with
`version_compare()` (or a `switch` on `$old_version` whose cases fall through,
as `modules/holiday/setup.php` does). See [Upgrading a module](#upgrading-a-module).

## Pages and write handlers

`index.php` (the front controller) sets these variables before it includes a
module's page:

| Variable | Value |
| --- | --- |
| `$AppUI` | The user's session: messages, translation, preferences, redirects. |
| `$m`, `$a` | The module and the action. |
| `$canAccess`, `$canRead`, `$canEdit`, `$canAuthor`, `$canDelete` | The user's rights on the whole module: access, view, edit, add, delete. |

`index.php` has already sent users without access to the module, or to a
turned-off module, to the *Access Denied* page. A page checks the rest itself:
a list needs `$canRead`, and a page about one record checks the rights on that
record (see [Permissions](#permissions)).

A page that changes data does it in a write handler, never in the page itself.
The form posts to the module with a `dosql` field naming the handler:

```html
<form method="post" action="?m=notes">
	<input type="hidden" name="dosql" value="do_note_aed" />
```

`index.php` runs `modules/notes/do_note_aed.php` before any output, only for a
POST that carries the session's CSRF token, and only for users who may delete
in the module (when the form posts `del=1`) or else add or edit in it. See
[Write handlers: dosql](index.md#write-handlers-dosql). The handler then:

1. reads its input with `dPgetIntParam()` and `dPgetParam()`;
2. checks the rights on the record with `dPrequireWritePermission()`;
3. saves or deletes;
4. sets a message with `$AppUI->setMsg()` and ends with `$AppUI->redirect()`.

`$AppUI->redirect()` with no argument goes back to the page that last called
`$AppUI->savePlace()`, usually the list.

You don't add the CSRF token to your forms: `index.php` adds a hidden
`csrf_token` field to every `<form method="post">` of the page it sends
(`CAppUI::injectCsrfToken()`). Forms built in JavaScript get it from
`js/dp-ui.js`, which reads the page's `csrf-token` meta tag.

## The record class: CDpObject

A module's table is usually handled by a class that extends `CDpObject`
(`classes/dp.class.php`). The class declares one public property per column and
passes the table and its key to the parent constructor:

```php
class CNote extends CDpObject
{
	var $note_id = null;
	var $note_title = null;

	function __construct()
	{
		parent::__construct('notes', 'note_id');
	}
}
```

| Member | What it does |
| --- | --- |
| `_tbl`, `_tbl_key` | The table (without the prefix) and its primary key, from the constructor. A third argument sets the module name the permission checks use (by default the table name). |
| `load($oid)` | Reads the record with that id (cast to an integer) into the object. Returns `false` if there is none. |
| `bind($hash)` | Copies the values of an array whose keys are property names (a posted form, `$config`) into the object, and casts the key to an integer. Only declared properties are set. |
| `check()` | Called by `store()` before writing. Return `null` if the object is valid, or an error message. Override it. |
| `store()` | Trims the strings, runs `check()`, and inserts the record (key empty) or updates it (key set). Records the change in the history. Returns `null`, or an error message. |
| `canDelete(&$msg, $oid, $joins)` | Whether the user may delete the record (the module's `delete` right), and, with `$joins`, whether other tables still refer to it. |
| `delete($oid)` | Runs `canDelete()`, then deletes the record. Returns `null`, or an error message. |
| `getAllowedRecords($uid, $fields, $orderby)` | The records of the table the user may see, by the module's and the records' permissions. |

!!! warning "bind() takes every field it is given"
    `bind($_POST)` sets every property that has a field in the request,
    including ones the form doesn't show (an owner, a date, a flag). Set those
    yourself after binding, or assign the edited fields one by one as the
    example below does.

## Permissions

Rights are given per module, and optionally per record. The functions:

| Function | Use |
| --- | --- |
| `getPermission($module, $op, $id = 0)` | Whether the current user may `access`, `view`, `add`, `edit` or `delete` in the module, or on record `$id` of it (`includes/permissions.php`). |
| `dPrequireWritePermission($module, $id, $del)` | For write handlers: requires `delete` when `$del`, `edit` when `$id` is set, `add` otherwise, and sends the user to the *Access Denied* page if not. |
| `$AppUI->acl()->checkModule($module, $op)` | The module-level right alone. |
| `$AppUI->acl()->checkModuleItem($module, $op, $id)` | The right on one record. When no rule names the record, the module's right applies. |

To deny access, redirect to the *Access Denied* page:

```php
if (!getPermission('notes', 'edit', $note_id)) {
	$AppUI->redirect('m=public&a=access_denied');
}
```

Rights on a record are stored under the module's name: `checkModuleItem()`
looks for the record in the permission section named like the module, and
`permissions_item_table` creates the section named like the table. Name the
main table after the module, as the core modules do (`links`, `projects`), so
the two match. The record picker of the roles' permission page
(`modules/public/selector.php`) only knows some core tables, so for an add-on
the rights are in practice per module.

## JavaScript

`CAppUI::loadJS()`, called by every theme's header, loads after the files in
`js/`:

- `modules/<name>/<name>.module.js` on every page of the module;
- `modules/<name>/<action>.js` on the page of that action.

So a page's script needs no `<script>` tag: put it in a file named after the
page. Pages may not contain inline JavaScript (`onclick=`, `javascript:` URLs,
`<script>` blocks without the nonce): the Content-Security-Policy blocks it and
`make inline-js` fails. Attach behaviour with `dPaction()`, which writes the
`data-dp-*` attributes `js/dp-actions.js` reads:

| Attribute | Written by | Effect |
| --- | --- | --- |
| `data-dp-call`, `data-dp-args` | `dPaction('fn', array('$this'))` | Calls the global function `fn` on click (on change for a `<select>`). `'$this'`, `'$form'`, `'$value'` and `'$event'` stand for the element, its form, its value and the event. Returning `false` cancels the default action. |
| `data-dp-on` | third argument: `dPaction('fn', array('$this'), 'submit')` | The event that triggers it. |
| `data-dp-confirm` | `dPaction(null, array(), null, array('confirm' => $text))` | Asks first; cancelling stops the action. |
| `data-dp-href`, `data-dp-submit` | `array('href' => $url)`, `array('submit' => '')` | Goes to a URL, or submits the element's form. |

[Content-Security-Policy](secure-code.md#content-security-policy) has the full
conversion table.

## Translations

Every string a page shows goes through `$AppUI->_()`, which translates it and
escapes it for HTML. `CTitleBlock`, `addCrumb()`, tab labels and `setMsg()`
translate their strings too. `locales/core.php` loads, for a page of module
`<m>`:

1. `locales/<lang>/common.inc`, the strings shared by every page;
2. the files of the modules that add tabs to `<m>`'s pages;
3. `locales/<lang>/<m>.inc`;
4. `modules/<m>/locales/<lang>.inc`, the module's own file, whose strings win.

An add-on keeps its strings in its own `locales/` folder. A locale file holds
only string pairs, one per line:

```php
"Delete this note?"=>"¿Borrar esta nota?",
```

The English file may list the bare strings instead (`'Notes',`). The file is
evaluated as the body of a PHP array, after a check that it contains nothing
but string literals, `=>`, commas and comments: a file with anything else is
skipped.

`make locales` (`tools/locales/check.php`) finds every literal string the code
passes to `$AppUI->_()`, `CTitleBlock`, `addCrumb()`, a tab box's `add()` and
`setMsg()`, and fails if one used in `modules/<m>/` is in neither
`common.inc` nor the module's files, in English and in Spanish. English and
Spanish are written by hand. The other languages are machine translated: each
entry ends with `// MT` until someone reviews it. In a language with no file,
the pages show the strings as the code writes them, in English. `php tools/locales/check.php missing es` lists what is
missing, and `php tools/locales/check.php status` the coverage of every
language.

!!! note
    The main menu translates `mod_ui_name` with the strings of the page being
    shown, so your module's file translates its menu entry only on its own
    pages. The add-ons that come with dotPulse have their menu names in
    `locales/<lang>/common.inc`.

## Images

`CTitleBlock` and `dPfindImage($name, $module)` look for an icon in the
theme's `images/`, then in `modules/<name>/images/` and
`modules/<name>/images/icons/`, then in `images/icons/` and `images/obj/`.
In the dotPulse themes an SVG with the same name wins over a bitmap.

## Tabs on other modules' pages

A module adds a tab to another module's page with a file named after that
module ([Tabs from other modules](index.md#tabs-from-other-modules) explains
how `index.php` collects them):

| File | Where the tab appears |
| --- | --- |
| `projects_tab.<name>.php` | A project's view page (`$tabBox->loadExtras('projects')`). |
| `companies_tab.<name>.php`, `companies_tab.view.<name>.php` | A company's view page. |
| `tasks_tab.view.<name>.php` | A task's view page (`$tabBox->loadExtras('tasks', 'view')`). |
| `tasks_tab.addedit.<name>.php` | The task form (`$tabBox->loadExtras('tasks', 'addedit')`). |
| `departments_tab.view.<name>.php`, `admin_tab.viewuser.<name>.php`, `calendar_tab.<name>.php` | A department's page, a user's page, the calendar's day view. |

The label is `<name>` with underscores as spaces and a capital first letter,
translated: put it in the module's locale files. The tab file runs inside the
other module's page: declare the variables you use from it as `global`
(`modules/links/projects_tab.links.php` uses `$project_id`) and check your own
module's permission, since the page checked only its own. Tabs of a module
that is turned off, or that the user has no access to, are left out. The list
of tabs is read on each request (`dPmoduleTabs()`).

## Other files the core looks for

Some core pages include a file of a given name from other modules, if it
exists. Only these hooks exist; a module can't add itself to the calendar or
to Smart Search (which searches the tables listed in
`modules/smartsearch/searchobjects/`).

| File | Included by | Purpose |
| --- | --- | --- |
| `header_widget.php` | Every theme's header (`dPheaderWidgets()`), for each active module | Returns the HTML of an item for the user bar next to **Help**, or `''`. Used by `modules/dotproject_plus/`. |
| `tasks_dosql.addedit.php` | `modules/tasks/do_task_aed.php`, for modules with a `tasks_tab.addedit.*` tab | Adds function names to `$pre_save` and `$post_save`, called before and after a task is saved. |
| `tasks_dosql.updatetask.php` | `modules/tasks/do_updatetask.php`, for modules with a `tasks_tab.view.*` tab | Adds function names to `$post_log_store` and `$post_log_delete`, called with the task log that was saved or deleted. |
| `tasks_log.addedit.php` | `modules/tasks/vw_log_update.php` (the task log form) | Adds rows to the form. |
| `projects_addedit.calculated.php` | `projectCalculatedFields()` in `modules/projects/projects.class.php` | Adds project field names to `$calculated_fields`: the project form shows them read-only and doesn't save them. |

`modules/unitcost/` uses the four task and project hooks.

A module can also queue work for later with `EventQueue`
(`classes/event_queue.class.php`): `add(array('CNote', 'remind'), $args,
'notes', false, $note_id, 'reminder', $time)`. When the time comes,
`queuescanner.php` (run from cron) includes `modules/notes/notes.class.php`
and calls `(new CNote)->remind($module, $type, $origin_id, $owner, $args)`.
Return `true` when the job is done. `$args` is stored serialized and read back
without objects: pass arrays and scalars.

## Example: a notes module

This module keeps short notes: a list with a search box, a form to add and edit
a note, and delete. It has every part an add-on needs, written to the current
rules. It was installed, used, upgraded and removed in the sandbox, and passes
the syntax, raw-input, inline-JavaScript, translation and PHPStan checks and
the form test of `make audit-full`.

```text
modules/notes/
├── setup.php
├── notes.class.php
├── index.php
├── addedit.php
├── addedit.js
├── do_note_aed.php
└── locales/
    ├── en.inc
    └── es.inc
```

### setup.php

The table is named after the module, so that the rights on single notes (the
`notes` permission section) are found by `getPermission('notes', ...)`.

```php
<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

$config = array();
$config['mod_name'] = 'Notes';
$config['mod_version'] = '1.0';
$config['mod_directory'] = 'notes';
$config['mod_setup_class'] = 'CSetupNotes';
$config['mod_type'] = 'user';
$config['mod_ui_name'] = 'Notes';
$config['mod_ui_icon'] = 'notepad.gif';
$config['mod_description'] = 'Short notes';
$config['mod_config'] = false;
$config['permissions_item_table'] = 'notes';
$config['permissions_item_field'] = 'note_id';
$config['permissions_item_label'] = 'note_title';

class CSetupNotes
{
	function install()
	{
		$q = new DBQuery();
		$q->createTable('notes');
		$q->createDefinition('(
			note_id int(11) NOT NULL auto_increment,
			note_title varchar(255) NOT NULL default \'\',
			note_body text,
			note_owner int(11) NOT NULL default 0,
			note_date datetime default NULL,
			PRIMARY KEY (note_id)
		) DEFAULT CHARSET=utf8mb4');
		if (!$q->exec()) {
			return db_error();
		}
		return null;
	}

	function remove()
	{
		$q = new DBQuery();
		$q->dropTable('notes');
		$q->exec();
		return null;
	}

	function upgrade($old_version)
	{
		// Nothing to change yet: 1.0 is the first version.
		return true;
	}
}
```

### notes.class.php

```php
<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

require_once $AppUI->getSystemClass('dp');

class CNote extends CDpObject
{
	var $note_id = null;
	var $note_title = null;
	var $note_body = null;
	var $note_owner = null;
	var $note_date = null;

	function __construct()
	{
		parent::__construct('notes', 'note_id');
	}

	function check()
	{
		if (trim((string)$this->note_title) === '') {
			return 'Note title is required';
		}
		$this->note_owner = (int)$this->note_owner;
		return null;
	}
}
```

### index.php

The list. The search text goes into the query as a bound parameter (`?`), never
into the SQL string, and every value written into the page goes through
`dPhtml()`.

```php
<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// index.php has already checked access to the module; listing needs view.
if (!$canRead) {
	$AppUI->redirect('m=public&a=access_denied');
}
$AppUI->savePlace();

$search = trim((string)dPgetParam($_GET, 'search', ''));

$q = new DBQuery();
$q->addTable('notes');
$q->addQuery('note_id, note_title, note_date');
if ($search !== '') {
	$q->addWhere('note_title LIKE ?', array('%' . $search . '%'));
}
$q->addOrder('note_date DESC');
$notes = $q->loadList();

$titleBlock = new CTitleBlock('Notes', 'notepad.gif', $m, "$m.$a");
if ($canAuthor) {
	$titleBlock->addCrumb('?m=notes&amp;a=addedit', 'New note');
}
$titleBlock->show();

$df = $AppUI->getPref('SHDATEFORMAT');
?>
<form method="get" action="index.php">
	<input type="hidden" name="m" value="notes" />
	<input type="text" class="text" name="search" value="<?php echo dPhtml($search); ?>" />
	<input type="submit" class="button" value="<?php echo $AppUI->_('Search'); ?>" />
</form>

<table width="100%" class="tbl">
<tr>
	<th><?php echo $AppUI->_('Title'); ?></th>
	<th><?php echo $AppUI->_('Date'); ?></th>
</tr>
<?php foreach ($notes as $row) { ?>
<tr>
	<td><a href="?m=notes&amp;a=addedit&amp;note_id=<?php echo (int)$row['note_id']; ?>"><?php
		echo dPhtml($row['note_title']); ?></a></td>
	<td><?php echo $row['note_date'] ? dPhtml((new CDate($row['note_date']))->format($df)) : ''; ?></td>
</tr>
<?php } ?>
<?php if (!$notes) { ?>
<tr><td colspan="2"><?php echo $AppUI->_('No notes'); ?></td></tr>
<?php } ?>
</table>
```

### addedit.php

The form. It checks the right on the note, not only on the module. The delete
button is a second small form, with the confirmation in a `data-dp-confirm`
attribute; the text is translated with `UI_OUTPUT_RAW` because `dPaction()`
escapes it itself.

```php
<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

$note_id = dPgetIntParam($_GET, 'note_id', 0);

// Editing needs edit on this note; a new note needs add on the module.
if (!getPermission('notes', $note_id ? 'edit' : 'add', $note_id)) {
	$AppUI->redirect('m=public&a=access_denied');
}

$obj = new CNote();
if ($note_id && !$obj->load($note_id)) {
	$AppUI->setMsg('Note');
	$AppUI->setMsg('invalidID', UI_MSG_ERROR, true);
	$AppUI->redirect();
}
$canDeleteNote = $note_id && getPermission('notes', 'delete', $note_id);

$titleBlock = new CTitleBlock($note_id ? 'Edit note' : 'New note', 'notepad.gif', $m, "$m.$a");
$titleBlock->addCrumb('?m=notes', 'Notes');
$titleBlock->show();
?>
<form name="editNote" method="post" action="?m=notes"
	data-empty="<?php echo $AppUI->_('Note title is required'); ?>"<?php
	echo dPaction('notesCheck', array('$this'), 'submit'); ?>>
	<input type="hidden" name="dosql" value="do_note_aed" />
	<input type="hidden" name="note_id" value="<?php echo (int)$note_id; ?>" />
	<table class="std" width="100%">
	<tr>
		<td align="right"><?php echo $AppUI->_('Title'); ?>:</td>
		<td><input type="text" class="text" name="note_title" size="60" maxlength="255"
			value="<?php echo dPhtml($obj->note_title); ?>" /></td>
	</tr>
	<tr>
		<td align="right"><?php echo $AppUI->_('Text'); ?>:</td>
		<td><textarea name="note_body" cols="60" rows="8"><?php echo dPhtml($obj->note_body); ?></textarea></td>
	</tr>
	<tr>
		<td></td>
		<td><input type="submit" class="button" value="<?php echo $AppUI->_('save'); ?>" /></td>
	</tr>
	</table>
</form>
<?php if ($canDeleteNote) { ?>
<form name="deleteNote" method="post" action="?m=notes">
	<input type="hidden" name="dosql" value="do_note_aed" />
	<input type="hidden" name="del" value="1" />
	<input type="hidden" name="note_id" value="<?php echo (int)$note_id; ?>" />
	<input type="submit" class="button" value="<?php echo $AppUI->_('Delete note'); ?>"<?php
		echo dPaction(null, array(), null, array('confirm' => $AppUI->_('Delete this note?', UI_OUTPUT_RAW))); ?> />
</form>
<?php } ?>
```

### addedit.js

Loaded on the form's page only, because it is named after it. The form calls
it through `dPaction('notesCheck', array('$this'), 'submit')`; returning
`false` stops the submit. The handler checks the title again: the script is
only a convenience.

```js
// Loaded on ?m=notes&a=addedit only (CAppUI::getModuleJS()).
function notesCheck(form) {
	if (form.note_title.value.trim() === '') {
		alert(form.getAttribute('data-empty'));
		form.note_title.focus();
		return false;
	}
	return true;
}
```

### do_note_aed.php

The write handler. It assigns only the fields the form edits, and sets the
owner and the date itself.

```php
<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// index.php has checked the POST, the CSRF token and the rights on the module.
$note_id = dPgetIntParam($_POST, 'note_id', 0);
$del = dPgetIntParam($_POST, 'del', 0);
// The rights on this note: delete, edit, or add for a new one.
dPrequireWritePermission('notes', $note_id, $del);

$obj = new CNote();
if ($note_id && !$obj->load($note_id)) {
	$AppUI->setMsg('Note');
	$AppUI->setMsg('invalidID', UI_MSG_ERROR, true);
	$AppUI->redirect('m=notes');
}

$AppUI->setMsg('Note');
if ($del) {
	if (($msg = $obj->delete())) {
		$AppUI->setMsg($msg, UI_MSG_ERROR);
		$AppUI->redirect();
	}
	$AppUI->setMsg('deleted', UI_MSG_ALERT, true);
	$AppUI->redirect('m=notes');
}

// Only the fields the form edits; owner and date are set here, not taken from the request.
$obj->note_title = trim((string)dPgetParam($_POST, 'note_title', ''));
$obj->note_body = (string)dPgetParam($_POST, 'note_body', '');
if ($obj->note_title === '') {
	$AppUI->setMsg('Note title is required', UI_MSG_ERROR);
	$AppUI->redirect('m=notes&a=addedit&note_id=' . $note_id);
}
if (!$note_id) {
	$obj->note_owner = $AppUI->user_id;
}
$obj->note_date = date('Y-m-d H:i:s');

if (($msg = $obj->store())) {
	$AppUI->setMsg($msg, UI_MSG_ERROR);
	$AppUI->redirect('m=notes&a=addedit&note_id=' . $note_id);
}
$AppUI->setMsg($note_id ? 'updated' : 'added', UI_MSG_OK, true);
$AppUI->redirect('m=notes');
```

### locales/en.inc and locales/es.inc

The strings that `common.inc` doesn't have already (`Title`, `Date`, `Search`,
`save`, `added`...).

```php
"Notes"=>"Notes",
"New note"=>"New note",
"Edit note"=>"Edit note",
"Text"=>"Text",
"No notes"=>"No notes",
"Delete note"=>"Delete note",
"Delete this note?"=>"Delete this note?",
"Note title is required"=>"Note title is required",
```

```php
"Notes"=>"Notas",
"New note"=>"Nueva nota",
"Edit note"=>"Editar nota",
"Text"=>"Texto",
"No notes"=>"No hay notas",
"Delete note"=>"Borrar nota",
"Delete this note?"=>"¿Borrar esta nota?",
"Note title is required"=>"La nota necesita un título",
```

## Installing, upgrading and removing

[Modules](../administration/modules.md) explains these from the
administrator's side. What happens in the code:

### Installing a module

1. Copy the folder to `modules/`. It appears under **Not installed** in
   **System Admin › Modules**.
2. Click **install**. `domodsql.php` calls `install()`, then stores `$config`
   in the `modules` table, at the end of the menu order, and adds the module to
   the permission system: as a module, in the admin or non-admin group, and
   with a section for `permissions_item_table`.
3. The module starts turned off and hidden from the menu (unless `$config` sets
   `mod_ui_active`). **Turn on** makes it usable, **Show in menu** gives it a
   menu entry.

In the sandbox, `docker/tests/modules.sh install notes`, then `toggle notes`
and `menu notes`, do the same as an administrator (see
[Modules in the sandbox](environment.md#modules-in-the-sandbox)).

### Upgrading a module

To change the tables of an installed module:

1. Raise `mod_version` in `setup.php`.
2. Add a step to `upgrade()` for the new version, and change `install()` so
   that a new installation gets the same tables directly.
3. The administrator clicks **upgrade** (after a backup). `upgrade()` gets the
   installed version; when it returns `true`, the new version is stored.

Version 1.1 of the example adds a column. In `setup.php`:

```php
$config['mod_version'] = '1.1';
```

In `CSetupNotes`, the new `upgrade()` (and `install()` gets
`note_pinned tinyint(1) NOT NULL default '0',` in its `CREATE TABLE`):

```php
	function upgrade($old_version)
	{
		if (version_compare($old_version, '1.1', '<')) {
			// 1.1: notes can be pinned.
			$q = new DBQuery();
			$q->alterTable('notes');
			$q->addField('note_pinned', "tinyint(1) NOT NULL default '0'");
			if (!$q->exec()) {
				return false;
			}
		}
		return true;
	}
```

Each step runs only for installations older than its version, so the same
`upgrade()` brings 1.0, 1.1 or any later version up to date. The Modules page
compares the versions with `version_compare()`: `1.10` is newer than `1.9`.

### Removing a module

**remove** calls `remove()`, which must drop every table the module created
and delete the rows it added to core tables (the links module deletes its
`LinkType` list from `sysvals`). Then the module leaves the `modules` table and
the permission system. Core modules can't be removed.

## Checklist before publishing a module

A module added to the dotPulse repository must pass the CI checks of a pull
request ([Developer guide](index.md#branches-and-contributions)). Run them on
your computer first, and run them on a module you publish on your own too
([Make targets](environment.md#make-targets)):

| Check | What it wants from a module |
| --- | --- |
| `make lint` | No PHP syntax error (PHP 8.3). |
| `make phpstan` | No new PHPStan finding. `modules/` is analysed. |
| `make raw-input` | No `$_GET[...]`, `$_POST[...]`, `$_REQUEST[...]` or `$_COOKIE[...]`: read input with `dPgetParam()`, `dPgetIntParam()` or `dPgetCleanParam()`. |
| `make inline-js` | No `onclick=` and other handler attributes, `javascript:` URLs or `<script>` blocks without the nonce. |
| `make locales` | Every translated string in `locales/en.inc` and `locales/es.inc` (or `common.inc`). |
| `make audit-full` | Installs the module with every other add-on, crawls its pages as `admin` and as `worker`, fills in and saves its `addedit*.php` forms, checks the layout in every theme, then removes it: no SQL error and no PHP warning, notice or deprecation in the log. |

!!! note
    `make lint`, `make raw-input` and `make inline-js` only check the files git
    tracks: `git add` a new module before you run them.

Also check by hand:

- every page that shows a record checks the rights on that record, and every
  write handler calls `dPrequireWritePermission()`;
- ids are cast to integers, other values go into SQL as `?` parameters or
  through `$q->quote()`, and a sort column from the request is checked against
  a list (`dPvalidateOrder()`);
- every value written into a page is escaped (`dPhtml()`, `$AppUI->_()`),
  links from users go through `dPsafeUrl()`;
- install, upgrade from each earlier version, and remove work, and remove
  leaves nothing behind;
- the module works as a user who is not an administrator, and is refused to a
  user without rights on it.

[Writing secure code](secure-code.md) has the rules behind these points.
