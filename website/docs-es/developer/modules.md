# Escribir un módulo { #writing-a-module }

Un módulo es una carpeta dentro de `modules/`. Los módulos del núcleo (empresas,
proyectos, tareas...) y los complementos (enlaces, riesgos, los calendarios de
festivos...) se construyen de la misma manera, y un complemento que usted
escriba se instala desde **Administración del Sistema › Módulos** igual que los
que vienen con dotPulse. Esta página explica los archivos de un módulo y lo que
hace el núcleo con ellos, después construye un módulo pequeño pero completo, y
termina con las comprobaciones que debe pasar antes de publicar uno.

Lea antes [El recorrido de una petición](index.md#how-a-request-flows): explica
cómo llega `index.php` a las páginas de un módulo. Las reglas para la entrada,
la salida, los permisos y CSRF están en [Escribir código seguro](secure-code.md);
esta página solo muestra cómo las aplica un módulo.

## Los archivos de un módulo { #the-files-of-a-module }

El nombre de la carpeta es el nombre del módulo: `modules/notes/` es el módulo
`notes`, y sus páginas son `index.php?m=notes&a=<action>`. Use letras
minúsculas, dígitos y guiones bajos: `index.php` rechaza un nombre con un punto,
una barra, una comilla, un paréntesis, `;` o `$`.

| Archivo | Qué es |
| --- | --- |
| `setup.php` | La descripción del módulo (`$config`) y su clase de instalación, que crea, actualiza y elimina sus tablas. Obligatorio: sin él el módulo no se puede instalar. |
| `<name>.class.php` | Las clases del módulo, normalmente una clase de registro que extiende `CDpObject`. `index.php` lo incluye antes de cada página del módulo. |
| `index.php` | La página que se muestra para `?m=<name>`, normalmente una lista. |
| `<action>.php` | Cualquier otra página, que se muestra para `?m=<name>&a=<action>`. Por convención, `addedit.php` añade y edita un registro y `view.php` muestra uno. |
| `do_<thing>_aed.php` | Un manejador de escritura: un formulario envía a él con `dosql=do_<thing>_aed`. |
| `<action>.js`, `<name>.module.js` | JavaScript, que se carga automáticamente (vea [JavaScript](#javascript)). |
| `images/` | Los iconos del módulo. |
| `locales/<lang>.inc` | Las traducciones del módulo. |
| `<module>_tab.<name>.php` | Una pestaña que el módulo añade a las páginas de otro módulo (vea [Pestañas en las páginas de otros módulos](#tabs-on-other-modules-pages)). |

Todos los archivos PHP empiezan con la misma protección, para que no se puedan
abrir directamente por su URL dentro de `modules/`:

```php
<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
```

!!! warning "Cada archivo es una página"
    `index.php?m=<name>&a=<file>` ejecuta cualquier archivo PHP de la carpeta
    cuyo nombre no tenga un punto, para cualquiera que tenga acceso al módulo.
    Un archivo que solo está pensado para incluirse no debe hacer nada dañino
    cuando se abre de esa manera. Los archivos cuyo nombre tiene un punto
    (`notes.class.php`, `projects_tab.notes.php`) no se pueden abrir como
    páginas.

## setup.php { #setupphp }

`setup.php` define un array, `$config`, y una clase de instalación.
**Administración del Sistema › Módulos** (`modules/system/viewmods.php`)
incluye el `setup.php` de todos los módulos en la misma petición, así que el
nombre de la clase debe ser único: `CSetup<Name>` por convención. El archivo
solo debe definir estas dos cosas.

### El array $config { #the-config-array }

Cuando se instala el módulo, `modules/system/domodsql.php` vuelca `$config` en
un registro `CModule` (`modules/system/system.class.php`) y lo guarda en la
tabla `modules`. Una clave que no es una columna de esa tabla no se guarda:
`mod_config` se lee de `setup.php` cada vez que se muestra la página Módulos.

| Clave | Obligatoria | Qué hace |
| --- | --- | --- |
| `mod_name` | sí | El nombre del módulo, que se muestra en **Administración del Sistema › Módulos** y se usa como nombre de su objeto de permisos. |
| `mod_version` | sí | La versión del código. Cuando es más nueva que la versión guardada al instalar, aparece **Actualización disponible** (se compara con `version_compare()`). |
| `mod_directory` | sí | El nombre de la carpeta. |
| `mod_setup_class` | sí | El nombre de la clase de instalación definida más abajo. |
| `mod_type` | sí | `user` para un complemento. Vea los tipos más abajo. |
| `mod_ui_name` | sí | El nombre de su entrada en el menú principal, traducido con `$AppUI->_()`. |
| `mod_ui_icon` | no | El nombre de un archivo de icono. Se guarda, pero ahora mismo ningún tema lo muestra. |
| `mod_description` | no | Una descripción de una línea. |
| `mod_config` | no | `true` muestra un botón **configura** en **Administración del Sistema › Módulos**, si la clase de instalación tiene un método `configure()`. |
| `mod_ui_active` | no | `1` pone el módulo en el menú al instalarlo (Registers y Document Management lo hacen). Sin él, el módulo empieza oculto. |
| `permissions_item_table` | no | La tabla principal del módulo. Se convierte en una sección del sistema de permisos, para que se puedan dar derechos sobre sus registros, y el módulo Historia puede filtrar por ella. Ponga a la tabla el nombre del módulo (vea [Permisos](#permissions)). |
| `permissions_item_field` | no | La clave primaria de esa tabla. |
| `permissions_item_label` | no | La columna que da nombre a un registro (su título). |

Los valores de `mod_type`:

| Tipo | Significado |
| --- | --- |
| `user` | Un complemento. Al instalarse entra en el grupo **Módulos No-Admin**, así que todos los roles con derechos sobre ese grupo reciben los mismos derechos sobre él. |
| `admin` | Una herramienta de administración (el módulo de festivos). Al instalarse entra en el grupo **Módulos Admin**: solo los roles con derechos sobre los módulos de administración reciben derechos sobre él. |
| `utility` | Nunca tiene entrada en el menú, aunque se muestre en el menú: solo añade vistas a otros módulos (iGantt). |
| `core` | Parte de dotPulse: no tiene botón **eliminar**. No lo use para un complemento. |

[Permisos de un módulo nuevo](../administration/modules.md#permissions-of-a-new-module)
explica los dos grupos desde el lado del administrador.

### La clase de instalación { #the-setup-class }

`domodsql.php` llama a estos métodos sobre una instancia nueva de la clase:

| Método | Cuándo se llama | Devuelve |
| --- | --- | --- |
| `install()` | Al hacer clic en **instalar**. Cree las tablas y las filas iniciales que haga falta. | `null` (o `true`) si todo va bien: el módulo se registra y la página dice *Módulo instalado*. `false` o un mensaje de error: el módulo no se registra, y la página dice *Módulo no instalado* con el mensaje. |
| `remove()` | Al hacer clic en **eliminar**. Elimine las tablas y borre todo lo demás que haya añadido el módulo. | `null`, o un mensaje que mostrar. |
| `upgrade($old_version)` | Al hacer clic en **actualizar**. `$old_version` es la versión guardada cuando se instaló o se actualizó por última vez el módulo. Lleve las tablas de esa versión a `mod_version`. | `true` si todo va bien: se guarda el nuevo `$config` (y con él la nueva versión). `false`: la página dice *El módulo no fue actualizado* y la versión guardada se mantiene, así que se puede volver a intentar la actualización. |
| `configure()` | Al hacer clic en **configura** (solo con `mod_config` activado). Normalmente redirige a la página de configuración del propio módulo, `?m=<name>&a=configure`, que `index.php` solo abre a los usuarios que pueden editar Administración del Sistema. | `true`, o `false` para *Configuración del módulo errónea*. |

!!! warning
    Un módulo cuyo `install()` falla no se registra, así que el administrador
    no puede **eliminar**lo. Deshaga lo que hizo `install()` antes de devolver
    el error (elimine las tablas que creó), o cree las tablas con
    `CREATE TABLE IF NOT EXISTS`, para que se pueda volver a hacer clic en
    **instalar**.

La página Módulos ofrece **actualizar** solo cuando
`version_compare(mod_version in setup.php, stored version, '>')` es verdadero,
y la página de inicio de Administración del Sistema cuenta esos módulos.
Escriba `upgrade()` de forma que pueda empezar desde cualquier versión
anterior: compruebe cada paso con `version_compare()` (o con un `switch` sobre
`$old_version` cuyos casos caigan en cascada, como hace
`modules/holiday/setup.php`). Vea [Actualizar un módulo](#upgrading-a-module).

## Páginas y manejadores de escritura { #pages-and-write-handlers }

`index.php` (el controlador frontal) asigna estas variables antes de incluir la
página de un módulo:

| Variable | Valor |
| --- | --- |
| `$AppUI` | La sesión del usuario: mensajes, traducción, preferencias, redirecciones. |
| `$m`, `$a` | El módulo y la acción. |
| `$canAccess`, `$canRead`, `$canEdit`, `$canAuthor`, `$canDelete` | Los derechos del usuario sobre el módulo entero: acceso, vista, editar, añadir, borrar. |

`index.php` ya ha enviado a la página *Acceso Denegado* a los usuarios sin
acceso al módulo, o a un módulo desactivado. El resto lo comprueba la propia
página: una lista necesita `$canRead`, y una página sobre un registro comprueba
los derechos sobre ese registro (vea [Permisos](#permissions)).

Una página que cambia datos lo hace en un manejador de escritura, nunca en la
propia página. El formulario envía al módulo con un campo `dosql` que nombra el
manejador:

```html
<form method="post" action="?m=notes">
	<input type="hidden" name="dosql" value="do_note_aed" />
```

`index.php` ejecuta `modules/notes/do_note_aed.php` antes de cualquier salida,
solo para un POST que lleve el token CSRF de la sesión, y solo para los
usuarios que pueden borrar en el módulo (cuando el formulario envía `del=1`) o,
si no, añadir o editar en él. Vea
[Manejadores de escritura: dosql](index.md#write-handlers-dosql). Después, el
manejador:

1. lee su entrada con `dPgetIntParam()` y `dPgetParam()`;
2. comprueba los derechos sobre el registro con `dPrequireWritePermission()`;
3. guarda o borra;
4. pone un mensaje con `$AppUI->setMsg()` y termina con `$AppUI->redirect()`.

`$AppUI->redirect()` sin argumentos vuelve a la última página que llamó a
`$AppUI->savePlace()`, normalmente la lista.

No tiene que añadir el token CSRF a sus formularios: `index.php` añade un campo
oculto `csrf_token` a todos los `<form method="post">` de la página que envía
(`CAppUI::injectCsrfToken()`). Los formularios construidos en JavaScript lo
obtienen de `js/dp-ui.js`, que lee la etiqueta meta `csrf-token` de la página.

## La clase de registro: CDpObject { #the-record-class-cdpobject }

La tabla de un módulo la maneja normalmente una clase que extiende `CDpObject`
(`classes/dp.class.php`). La clase declara una propiedad pública por columna y
pasa la tabla y su clave al constructor padre:

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

| Miembro | Qué hace |
| --- | --- |
| `_tbl`, `_tbl_key` | La tabla (sin el prefijo) y su clave primaria, del constructor. Un tercer argumento fija el nombre del módulo que usan las comprobaciones de permisos (por defecto, el nombre de la tabla). |
| `load($oid)` | Lee en el objeto el registro con ese id (convertido a entero). Devuelve `false` si no existe. |
| `bind($hash)` | Copia en el objeto los valores de un array cuyas claves son nombres de propiedades (un formulario enviado, `$config`), y convierte la clave a entero. Solo se asignan las propiedades declaradas. |
| `check()` | Lo llama `store()` antes de escribir. Devuelva `null` si el objeto es válido, o un mensaje de error. Sobrescríbalo. |
| `store()` | Recorta los espacios de las cadenas, ejecuta `check()` e inserta el registro (clave vacía) o lo actualiza (clave asignada). Anota el cambio en el historial. Devuelve `null`, o un mensaje de error. |
| `canDelete(&$msg, $oid, $joins)` | Si el usuario puede borrar el registro (el derecho `delete` del módulo) y, con `$joins`, si otras tablas todavía hacen referencia a él. |
| `delete($oid)` | Ejecuta `canDelete()` y después borra el registro. Devuelve `null`, o un mensaje de error. |
| `getAllowedRecords($uid, $fields, $orderby)` | Los registros de la tabla que el usuario puede ver, según los permisos del módulo y de los registros. |

!!! warning "bind() acepta todos los campos que recibe"
    `bind($_POST)` asigna todas las propiedades que tienen un campo en la
    petición, incluidas las que el formulario no muestra (un propietario, una
    fecha, un indicador). Asígnelas usted después de `bind()`, o asigne los
    campos editados uno a uno, como hace el ejemplo de más abajo.

## Permisos { #permissions }

Los derechos se dan por módulo y, opcionalmente, por registro. Las funciones:

| Función | Uso |
| --- | --- |
| `getPermission($module, $op, $id = 0)` | Si el usuario actual puede hacer `access`, `view`, `add`, `edit` o `delete` en el módulo, o sobre su registro `$id` (`includes/permissions.php`). |
| `dPrequireWritePermission($module, $id, $del)` | Para los manejadores de escritura: exige `delete` cuando `$del`, `edit` cuando `$id` está asignado y `add` en los demás casos, y si no, envía al usuario a la página *Acceso Denegado*. |
| `$AppUI->acl()->checkModule($module, $op)` | Solo el derecho a nivel de módulo. |
| `$AppUI->acl()->checkModuleItem($module, $op, $id)` | El derecho sobre un registro. Cuando ninguna regla nombra el registro, se aplica el derecho del módulo. |

Para denegar el acceso, redirija a la página *Acceso Denegado*:

```php
if (!getPermission('notes', 'edit', $note_id)) {
	$AppUI->redirect('m=public&a=access_denied');
}
```

Los derechos sobre un registro se guardan con el nombre del módulo:
`checkModuleItem()` busca el registro en la sección de permisos que se llama
como el módulo, y `permissions_item_table` crea la sección que se llama como la
tabla. Ponga a la tabla principal el nombre del módulo, como hacen los módulos
del núcleo (`links`, `projects`), para que las dos coincidan. El selector de
registros de la página de permisos de los roles
(`modules/public/selector.php`) solo conoce algunas tablas del núcleo, así que
para un complemento los derechos son, en la práctica, por módulo.

## JavaScript { #javascript }

`CAppUI::loadJS()`, al que llama la cabecera de todos los temas, carga después
de los archivos de `js/`:

- `modules/<name>/<name>.module.js` en todas las páginas del módulo;
- `modules/<name>/<action>.js` en la página de esa acción.

Así, el script de una página no necesita etiqueta `<script>`: póngalo en un
archivo con el nombre de la página. Las páginas no pueden contener JavaScript
en línea (`onclick=`, URL `javascript:`, bloques `<script>` sin el nonce): la
Content-Security-Policy lo bloquea y `make inline-js` falla. Asocie el
comportamiento con `dPaction()`, que escribe los atributos `data-dp-*` que lee
`js/dp-actions.js`:

| Atributo | Lo escribe | Efecto |
| --- | --- | --- |
| `data-dp-call`, `data-dp-args` | `dPaction('fn', array('$this'))` | Llama a la función global `fn` al hacer clic (al cambiar, en un `<select>`). `'$this'`, `'$form'`, `'$value'` y `'$event'` representan el elemento, su formulario, su valor y el evento. Devolver `false` cancela la acción por defecto. |
| `data-dp-on` | tercer argumento: `dPaction('fn', array('$this'), 'submit')` | El evento que la dispara. |
| `data-dp-confirm` | `dPaction(null, array(), null, array('confirm' => $text))` | Pregunta antes; si se cancela, la acción se detiene. |
| `data-dp-href`, `data-dp-submit` | `array('href' => $url)`, `array('submit' => '')` | Va a una URL, o envía el formulario del elemento. |

[Content-Security-Policy](secure-code.md#content-security-policy) tiene la
tabla de conversión completa.

## Traducciones { #translations }

Toda cadena que muestra una página pasa por `$AppUI->_()`, que la traduce y la
escapa para HTML. `CTitleBlock`, `addCrumb()`, las etiquetas de las pestañas y
`setMsg()` también traducen sus cadenas. Para una página del módulo `<m>`,
`locales/core.php` carga:

1. `locales/<lang>/common.inc`, las cadenas comunes a todas las páginas;
2. los archivos de los módulos que añaden pestañas a las páginas de `<m>`;
3. `locales/<lang>/<m>.inc`;
4. `modules/<m>/locales/<lang>.inc`, el archivo del propio módulo, cuyas
   cadenas tienen preferencia.

Un complemento guarda sus cadenas en su propia carpeta `locales/`. Un archivo
de idioma solo contiene pares de cadenas, uno por línea:

```php
"Delete this note?"=>"¿Borrar esta nota?",
```

El archivo en inglés puede listar las cadenas solas (`'Notes',`). El archivo se
evalúa como el cuerpo de un array de PHP, tras comprobar que no contiene más
que literales de cadena, `=>`, comas y comentarios: un archivo con cualquier
otra cosa se ignora.

`make locales` (`tools/locales/check.php`) busca todas las cadenas literales
que el código pasa a `$AppUI->_()`, `CTitleBlock`, `addCrumb()`, el `add()` de
un grupo de pestañas y `setMsg()`, y falla si una usada en `modules/<m>/` no
está ni en `common.inc` ni en los archivos del módulo, en inglés y en español.
El inglés y el español se escriben a mano. Los demás idiomas tienen traducción
automática: cada entrada termina con `// MT` hasta que alguien la revisa. En un
idioma sin archivo, las páginas muestran las cadenas tal como las escribe el
código, en inglés. `php tools/locales/check.php missing es` lista lo que falta,
y `php tools/locales/check.php status` la cobertura de cada idioma.

!!! note
    El menú principal traduce `mod_ui_name` con las cadenas de la página que se
    está mostrando, así que el archivo de su módulo solo traduce su entrada de
    menú en sus propias páginas. Los complementos que vienen con dotPulse tienen
    sus nombres de menú en `locales/<lang>/common.inc`.

## Imágenes { #images }

`CTitleBlock` y `dPfindImage($name, $module)` buscan un icono en el `images/`
del tema, después en `modules/<name>/images/` y `modules/<name>/images/icons/`,
y después en `images/icons/` y `images/obj/`. En los temas de dotPulse, un SVG
con el mismo nombre tiene preferencia sobre un mapa de bits.

## Pestañas en las páginas de otros módulos { #tabs-on-other-modules-pages }

Un módulo añade una pestaña a la página de otro módulo con un archivo que lleva
el nombre de ese módulo ([Pestañas de otros módulos](index.md#tabs-from-other-modules)
explica cómo las reúne `index.php`):

| Archivo | Dónde aparece la pestaña |
| --- | --- |
| `projects_tab.<name>.php` | La página de un proyecto (`$tabBox->loadExtras('projects')`). |
| `companies_tab.<name>.php`, `companies_tab.view.<name>.php` | La página de una empresa. |
| `tasks_tab.view.<name>.php` | La página de una tarea (`$tabBox->loadExtras('tasks', 'view')`). |
| `tasks_tab.addedit.<name>.php` | El formulario de la tarea (`$tabBox->loadExtras('tasks', 'addedit')`). |
| `departments_tab.view.<name>.php`, `admin_tab.viewuser.<name>.php`, `calendar_tab.<name>.php` | La página de un departamento, la página de un usuario, la vista de día del calendario. |

La etiqueta es `<name>` con los guiones bajos como espacios y la primera letra
en mayúscula, traducida: póngala en los archivos de idioma del módulo. El
archivo de la pestaña se ejecuta dentro de la página del otro módulo: declare
como `global` las variables que use de ella (`modules/links/projects_tab.links.php`
usa `$project_id`) y compruebe el permiso de su propio módulo, ya que la página
solo comprobó el suyo. Las pestañas de un módulo desactivado, o al que el
usuario no tiene acceso, se omiten. La lista de pestañas se lee en cada
petición (`dPmoduleTabs()`).

## Otros archivos que busca el núcleo { #other-files-the-core-looks-for }

Algunas páginas del núcleo incluyen un archivo con un nombre dado de otros
módulos, si existe. Solo existen estos puntos de enganche (*hooks*); un módulo
no puede añadirse al calendario ni a Smart Search (que busca en las tablas
listadas en `modules/smartsearch/searchobjects/`).

| Archivo | Lo incluye | Propósito |
| --- | --- | --- |
| `header_widget.php` | La cabecera de todos los temas (`dPheaderWidgets()`), para cada módulo activo | Devuelve el HTML de un elemento para la barra de usuario junto a **Ayuda**, o `''`. Lo usa `modules/dotproject_plus/`. |
| `tasks_dosql.addedit.php` | `modules/tasks/do_task_aed.php`, para los módulos con una pestaña `tasks_tab.addedit.*` | Añade nombres de funciones a `$pre_save` y `$post_save`, que se llaman antes y después de guardar una tarea. |
| `tasks_dosql.updatetask.php` | `modules/tasks/do_updatetask.php`, para los módulos con una pestaña `tasks_tab.view.*` | Añade nombres de funciones a `$post_log_store` y `$post_log_delete`, que se llaman con el registro de la tarea que se guardó o se borró. |
| `tasks_log.addedit.php` | `modules/tasks/vw_log_update.php` (el formulario del registro de la tarea) | Añade filas al formulario. |
| `projects_addedit.calculated.php` | `projectCalculatedFields()` en `modules/projects/projects.class.php` | Añade nombres de campos del proyecto a `$calculated_fields`: el formulario del proyecto los muestra como solo lectura y no los guarda. |

`modules/unitcost/` usa los cuatro puntos de enganche de tareas y proyectos.

Un módulo también puede dejar trabajo en cola para más tarde con `EventQueue`
(`classes/event_queue.class.php`): `add(array('CNote', 'remind'), $args,
'notes', false, $note_id, 'reminder', $time)`. Cuando llega la hora,
`queuescanner.php` (ejecutado desde cron) incluye `modules/notes/notes.class.php`
y llama a `(new CNote)->remind($module, $type, $origin_id, $owner, $args)`.
Devuelva `true` cuando el trabajo esté hecho. `$args` se guarda serializado y
se vuelve a leer sin objetos: pase arrays y escalares.

## Ejemplo: un módulo de notas { #example-a-notes-module }

Este módulo guarda notas cortas: una lista con un cuadro de búsqueda, un
formulario para añadir y editar una nota, y el borrado. Tiene todas las partes
que necesita un complemento, escritas según las reglas actuales. Se instaló,
se usó, se actualizó y se eliminó en el entorno de pruebas (*sandbox*), y pasa
las comprobaciones de sintaxis, de entrada sin tratar, de JavaScript en línea,
de traducciones y de PHPStan, y la prueba de formularios de `make audit-full`.

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

### setup.php { #setupphp_1 }

La tabla lleva el nombre del módulo, para que `getPermission('notes', ...)`
encuentre los derechos sobre notas concretas (la sección de permisos `notes`).

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
		// Nada que cambiar todavía: 1.0 es la primera versión.
		return true;
	}
}
```

### notes.class.php { #notesclassphp }

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

### index.php { #indexphp }

La lista. El texto de búsqueda entra en la consulta como parámetro enlazado
(`?`), nunca en la cadena SQL, y todos los valores que se escriben en la página
pasan por `dPhtml()`.

```php
<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// index.php ya ha comprobado el acceso al módulo; la lista necesita vista.
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

### addedit.php { #addeditphp }

El formulario. Comprueba el derecho sobre la nota, no solo sobre el módulo. El
botón de borrar es un segundo formulario pequeño, con la confirmación en un
atributo `data-dp-confirm`; el texto se traduce con `UI_OUTPUT_RAW` porque
`dPaction()` ya lo escapa.

```php
<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

$note_id = dPgetIntParam($_GET, 'note_id', 0);

// Editar necesita edit sobre esta nota; una nota nueva necesita add sobre el módulo.
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

### addedit.js { #addeditjs }

Se carga solo en la página del formulario, porque lleva su nombre. El
formulario lo llama mediante `dPaction('notesCheck', array('$this'), 'submit')`;
devolver `false` detiene el envío. El manejador vuelve a comprobar el título:
el script es solo una comodidad.

```js
// Se carga solo en ?m=notes&a=addedit (CAppUI::getModuleJS()).
function notesCheck(form) {
	if (form.note_title.value.trim() === '') {
		alert(form.getAttribute('data-empty'));
		form.note_title.focus();
		return false;
	}
	return true;
}
```

### do_note_aed.php { #do_note_aedphp }

El manejador de escritura. Asigna solo los campos que edita el formulario, y
asigna él mismo el propietario y la fecha.

```php
<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

// index.php ha comprobado el POST, el token CSRF y los derechos sobre el módulo.
$note_id = dPgetIntParam($_POST, 'note_id', 0);
$del = dPgetIntParam($_POST, 'del', 0);
// Los derechos sobre esta nota: delete, edit, o add para una nueva.
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

// Solo los campos que edita el formulario; el propietario y la fecha se asignan aquí, no se toman de la petición.
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

### locales/en.inc y locales/es.inc { #localeseninc-and-localesesinc }

Las cadenas que `common.inc` no tiene ya (`Title`, `Date`, `Search`, `save`,
`added`...).

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

## Instalar, actualizar y eliminar { #installing-upgrading-and-removing }

[Módulos](../administration/modules.md) lo explica desde el lado del
administrador. Lo que ocurre en el código:

### Instalar un módulo { #installing-a-module }

1. Copie la carpeta en `modules/`. Aparece en **Sin instalar**, en
   **Administración del Sistema › Módulos**.
2. Haga clic en **instalar**. `domodsql.php` llama a `install()`, después
   guarda `$config` en la tabla `modules`, al final del orden del menú, y añade
   el módulo al sistema de permisos: como módulo, en el grupo de módulos de
   administración o en el de los demás, y con una sección para
   `permissions_item_table`.
3. El módulo empieza desactivado y oculto en el menú (salvo que `$config`
   asigne `mod_ui_active`). **Activar** hace que se pueda usar, **Mostrar en el
   menú** le da una entrada en el menú.

En el entorno de pruebas, `docker/tests/modules.sh install notes`, y después
`toggle notes` y `menu notes`, hacen lo mismo como administrador (vea
[Módulos en el entorno de pruebas](environment.md#modules-in-the-sandbox)).

### Actualizar un módulo { #upgrading-a-module }

Para cambiar las tablas de un módulo instalado:

1. Suba `mod_version` en `setup.php`.
2. Añada a `upgrade()` un paso para la nueva versión, y cambie `install()` para
   que una instalación nueva tenga directamente las mismas tablas.
3. El administrador hace clic en **actualizar** (después de una copia de
   seguridad). `upgrade()` recibe la versión instalada; cuando devuelve
   `true`, se guarda la nueva versión.

La versión 1.1 del ejemplo añade una columna. En `setup.php`:

```php
$config['mod_version'] = '1.1';
```

En `CSetupNotes`, el nuevo `upgrade()` (e `install()` recibe
`note_pinned tinyint(1) NOT NULL default '0',` en su `CREATE TABLE`):

```php
	function upgrade($old_version)
	{
		if (version_compare($old_version, '1.1', '<')) {
			// 1.1: las notas se pueden fijar.
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

Cada paso solo se ejecuta en las instalaciones anteriores a su versión, así
que el mismo `upgrade()` pone al día la 1.0, la 1.1 o cualquier versión
posterior. La página Módulos compara las versiones con `version_compare()`:
`1.10` es más nueva que `1.9`.

### Eliminar un módulo { #removing-a-module }

**eliminar** llama a `remove()`, que debe eliminar todas las tablas que creó el
módulo y borrar las filas que añadió a las tablas del núcleo (el módulo de
enlaces borra su lista `LinkType` de `sysvals`). Después, el módulo sale de la
tabla `modules` y del sistema de permisos. Los módulos del núcleo no se pueden
eliminar.

## Lista de comprobación antes de publicar un módulo { #checklist-before-publishing-a-module }

Un módulo que se añade al repositorio de dotPulse debe pasar las comprobaciones
de integración continua (CI) de un pull request
([Guía para desarrolladores](index.md#branches-and-contributions)). Ejecútelas
antes en su ordenador, y ejecútelas también en un módulo que publique por su
cuenta ([Objetivos de make](environment.md#make-targets)):

| Comprobación | Qué exige a un módulo |
| --- | --- |
| `make lint` | Ningún error de sintaxis de PHP (PHP 8.3). |
| `make phpstan` | Ningún hallazgo nuevo de PHPStan. Se analiza `modules/`. |
| `make raw-input` | Ningún `$_GET[...]`, `$_POST[...]`, `$_REQUEST[...]` ni `$_COOKIE[...]`: lea la entrada con `dPgetParam()`, `dPgetIntParam()` o `dPgetCleanParam()`. |
| `make inline-js` | Ningún `onclick=` ni otros atributos de manejadores de eventos, ninguna URL `javascript:` ni bloques `<script>` sin el nonce. |
| `make locales` | Todas las cadenas traducidas en `locales/en.inc` y `locales/es.inc` (o en `common.inc`). |
| `make audit-full` | Instala el módulo con todos los demás complementos, recorre sus páginas como `admin` y como `worker`, rellena y guarda sus formularios `addedit*.php`, comprueba la maquetación en todos los temas y después lo elimina: ningún error de SQL y ningún warning, notice ni deprecation de PHP en el registro. |

!!! note
    `make lint`, `make raw-input` y `make inline-js` solo comprueban los
    archivos que sigue git: haga `git add` de un módulo nuevo antes de
    ejecutarlos.

Compruebe también a mano que:

- todas las páginas que muestran un registro comprueban los derechos sobre ese
  registro, y todos los manejadores de escritura llaman a
  `dPrequireWritePermission()`;
- los ids se convierten a enteros, los demás valores entran en el SQL como
  parámetros `?` o mediante `$q->quote()`, y una columna de ordenación que
  viene de la petición se comprueba contra una lista (`dPvalidateOrder()`);
- todos los valores que se escriben en una página se escapan (`dPhtml()`,
  `$AppUI->_()`), y los enlaces que vienen de los usuarios pasan por
  `dPsafeUrl()`;
- la instalación, la actualización desde cada versión anterior y la
  eliminación funcionan, y la eliminación no deja nada atrás;
- el módulo funciona con un usuario que no es administrador, y se le niega a un
  usuario sin derechos sobre él.

[Escribir código seguro](secure-code.md) tiene las reglas que hay detrás de
estos puntos.
