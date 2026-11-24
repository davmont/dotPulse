# Escribir código seguro { #writing-secure-code }

dotPulse pasó por una auditoría de seguridad completa, y el código sigue ahora
un pequeño conjunto de reglas. Esta página las lista: para cada una, la función
auxiliar que hay que usar, un ejemplo corto y el patrón al que sustituye. El
código nuevo y los cambios en el código antiguo deben seguirlas; dos de las
reglas se comprueban en la integración continua (CI).

La mayoría de las funciones auxiliares están en
[`includes/main_functions.php`](https://github.com/davmont/dotPulse/blob/devel/includes/main_functions.php),
`CAppUI` en [`classes/ui.class.php`](https://github.com/davmont/dotPulse/blob/devel/classes/ui.class.php)
y `DBQuery` en [`classes/query.class.php`](https://github.com/davmont/dotPulse/blob/devel/classes/query.class.php).
Cómo llega una petición a un archivo de un módulo se describe en
[El recorrido de una petición](index.md#how-a-request-flows).

## Todos los archivos empiezan con una protección { #every-file-starts-with-a-guard }

Un archivo de un módulo solo se ejecuta cuando lo incluye `index.php` (u otro
punto de entrada). Las primeras líneas detienen una petición directa:

```php
<?php
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}
```

Las reglas del servidor web también rechazan los archivos `.php` de `modules/`,
`classes/` y las demás carpetas de código (vea
[deployment-security.md](https://github.com/davmont/dotPulse/blob/devel/docs/deployment-security.md)),
pero la protección cubre un sitio en el que faltan esas reglas.

## Entrada de la petición { #request-input }

No acceda nunca directamente a `$_GET`, `$_POST`, `$_REQUEST` o `$_COOKIE` por
índice. Lea cada valor con una función auxiliar y dele un tipo antes de usarlo.

| Función auxiliar | Devuelve |
|---|---|
| `dPgetParam($arr, $name, $def = null)` | El valor tal como se envió, o `$def` cuando falta. Sin limpieza: puede ser cualquier cadena, o un array cuando la petición envía `name[]=...`. |
| `dPgetIntParam($arr, $name, $def = 0)` | El valor convertido a `int`, o `(int)$def`. Úsela para los ids y para cualquier otro número. |
| `dPgetCleanParam($arr, $name, $def = null)` | El valor pasado por `dPsanitiseHTML()`: htmLawed en modo `safe`, que quita los scripts, los atributos de eventos y los esquemas de URL peligrosos, y también los elementos `link`, `meta`, `style` y `template`; los arrays se limpian elemento a elemento. Para campos de texto que pueden contener HTML. No es escapado: el resultado sigue necesitando las reglas de SQL y de salida de más abajo. |
| `dPdepartmentParam($arr, $name = 'department')` | El filtro de departamento de las listas de proyectos: `company_<id>` o un id de departamento como `int`. |
| `dPintList($ids)` | No es un lector: convierte en enteros una lista de ids separados por comas (`'3,x,5'` pasa a ser `'3,0,5'`). |
| `dPvalidateOrder($value, $allowed, $default)` | No es un lector: `$value` si está en `$allowed`, y si no, `$default`. Para columnas de ordenación (vea [SQL](#sql)). |

No hay lector de arrays. Convierta usted cada elemento, como hace
`modules/mantis/do_mantis_aed.php`:

```php
$issue_ids = array_values(array_unique(array_filter(array_map('intval',
	(array)dPgetParam($_POST, 'issues', array())))));
```

Un ejemplo de `modules/links/addedit.php`:

```php
$link_id = intval(dPgetParam($_GET, 'link_id', 0));
```

Sustituye al patrón antiguo:

```php
$link_id = $_GET['link_id'];   // mal: sin tratar, sin tipo, y no pasa la comprobación raw-input
```

### La comprobación raw-input { #the-raw-input-check }

`tools/checks/check-raw-input.sh` (`make raw-input`, que se ejecuta en CI)
cuenta, en cada archivo `.php` e `.inc` propio, las líneas que acceden por
índice a una superglobal (`$_GET[`, `$_POST[`, `$_REQUEST[`, `$_COOKIE[`). No se
comprueban `lib/`, `vendor/`, `tests/` ni `docker/`. Los recuentos permitidos
están en `tools/checks/raw-input-baseline.txt`: los complementos antiguos aún
tienen unos cientos de lecturas así. La comprobación falla cuando un archivo
tiene más que su línea base, y un archivo que no está en la lista no puede
tener ninguna.

Cuando quite lecturas sin tratar de un archivo, anote el recuento más bajo:

```sh
tools/checks/check-raw-input.sh --update
```

Pasar el array entero, como en `dPgetParam($_POST, 'name')` o
`$obj->bind($_POST)`, no es una lectura sin tratar.

### bind() y los campos que un usuario no debe fijar { #bind-and-the-fields-a-user-must-not-set }

`CDpObject::bind($_POST)` copia todos los campos enviados cuyo nombre coincide
con una propiedad del objeto. Convierte la clave primaria a `int`, y nada más.
Después de `bind()`, vuelva a asignar todos los campos que el usuario no debe
elegir. De `modules/files/do_file_aed.php`:

```php
if (!$obj->bind($_POST)) {
	$AppUI->setMsg($obj->getError(), UI_MSG_ERROR);
	$AppUI->redirect($redirect);
}
// Where the file lives on disk, its size and type come from the stored record or the
// upload below, never from the request.
$obj->file_real_filename = $file_id ? $oldObj->file_real_filename : null;
$obj->file_size = $file_id ? $oldObj->file_size : null;
$obj->file_type = $file_id ? $oldObj->file_type : null;
```

### Nombres de archivo de la petición { #file-names-from-the-request }

Un nombre que elige un archivo que se va a incluir pasa por
`$AppUI->checkFileName()`, que envía al usuario a *Acceso Denegado* cuando el
nombre contiene un punto, una barra, una barra invertida, una comilla, un
paréntesis, `;` o `$`. `index.php` ya lo hace para `m`, `a`, `u` y `dosql`.

## SQL { #sql }

Construya las consultas con `DBQuery`. No ponga nunca en el SQL un valor de la
petición tal como llegó. Tiene tres formas seguras:

| Valor | Haga esto |
|---|---|
| Cualquier valor | Páselo como parámetro: `$q->addWhere('user_username = ?', $name)`. `addInsert()` y `addUpdate()` siempre envían sus valores como parámetros. MySQL recibe una sentencia preparada, así que un `?` dentro de una cadena entre comillas de la misma consulta no es un marcador. |
| Un número | Conviértalo: `$q->addWhere('link_project = ' . (int)$project_id)`. |
| Una cadena que tiene que ir en el texto SQL | Póngala entre comillas: `$q->quote($value)` devuelve el valor escapado y entre comillas. |

`addWhere()` recibe los parámetros como segundo argumento, un solo valor o un
array, en el orden de las marcas `?`. De `modules/admin/do_user_aed.php`:

```php
$q->addWhere('user_username = ?', $obj->user_username);
```

Una búsqueda con `LIKE`, de `modules/links/index_table.php`:

```php
if (!empty($search)) {
	$like = $q->quote('%' . $search . '%');
	$q->addWhere("(link_name like $like OR link_description like $like)");
}
if ($project_id) {
	$q->addWhere('link_project = ' . (int)$project_id);
}
```

Los nombres de columna, `ORDER BY` y `GROUP BY` no pueden ser parámetros.
Compruébelos contra una lista con `dPvalidateOrder()`, como hace
`modules/companies/vw_active.php`:

```php
$sort = dPvalidateOrder(dPgetCleanParam($_GET, 'sort', 'project_name'),
	array('project_name', 'project_priority'), 'project_name');
```

Una lista de ids para `IN (...)`: límpiela con `dPintList()`, o construya un
`?` por elemento y pase el array.

El patrón incorrecto:

```php
$q->addWhere("project_name = '" . $_POST['name'] . "'");   // mal: inyección SQL
$q->addOrder($_GET['orderby']);                              // mal: inyección en ORDER BY
```

`DBQuery::sanitise()` y `quote_sanitised()` solo quitan algunos caracteres. No
los use en código nuevo. Lo mismo vale para las antiguas funciones al estilo de
`db_loadList()` de `includes/db_connect.php` con SQL construido a mano.

## Salida { #output }

Escape todos los valores que vienen de la base de datos o de la petición en el
lugar donde los escribe en la página. Elija el escapado según el lugar:

| Dónde va el valor | Use |
|---|---|
| Texto HTML o un atributo entre comillas | `dPhtml($value)` |
| Un atributo `href` o `src` | `dPhtml(dPsafeUrl($value))`: `dPsafeUrl()` devuelve `''` para cualquier esquema que no sea `http`, `https`, `mailto` y `ftp` (nada de `javascript:` ni `data:`) |
| Un valor JavaScript en un bloque `<script>` | `dPjs($value)`: un literal de cadena con sus comillas, con `<`, `>`, `&` y las comillas como escapes `\u` |
| Un atributo de evento (`data-dp-*`) | `dPaction()`, que escapa por usted (vea [Content-Security-Policy](#content-security-policy)) |
| Un bloque de datos JSON | `json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP)` |
| Los campos de un formulario | `dPformSafe($value)`, o `dPformSafe($obj)` para escapar todas las propiedades de un objeto o array |

`dPhtml()` y `dPformSafe()` usan `htmlentities()` con `ENT_QUOTES` en el juego
de caracteres de la página. Las dos quitan además las barras invertidas (un
resto de las *magic quotes* de PHP), así que `a\b` se muestra como `ab`; escriba
`htmlspecialchars($value, ENT_QUOTES)` donde las barras invertidas importen.

De `modules/admin/vw_usr_api_tokens.php` y `modules/contacts/view.php`:

```php
<td><?php echo dPhtml($t['token_name']); ?></td>
<a href="<?php echo dPhtml(dPsafeUrl(@$row->contact_url)); ?>">
```

Sustituye a:

```php
<td><?php echo $t['token_name']; ?></td>   <!-- mal: XSS almacenado -->
```

### Texto traducido: `$AppUI->_()` { #translated-text-appui-_ }

`$AppUI->_($text, $flags)` traduce una cadena fija de la interfaz y escapa el
resultado. No pase datos por ella: busca el valor en las traducciones. El
segundo argumento elige el escapado:

| Indicador | Qué hace | Úselo |
|---|---|---|
| `UI_OUTPUT_HTML` (el predeterminado, `0`) | `htmlentities()` con `ENT_QUOTES` | En texto y atributos HTML. |
| `UI_OUTPUT_JS` | `addslashes()` | Dentro de una cadena JavaScript entre comillas. No escapa `<` ni `</script>`: use `dPjs()` para todo lo que no sea una cadena fija. |
| `UI_OUTPUT_RAW` | Sin escapado | Cuando otra cosa escapa el texto después, o no es HTML: argumentos de `dPaction()`, `json_encode()`, correo, comparaciones. Sin él, el texto se escaparía dos veces, y un cuadro de confirmación mostraría `&apos;`. |
| `UI_OUTPUT_URI` | Los espacios pasan a ser `%20` | Rara vez. |

Se puede añadir un indicador de mayúsculas con `|`: `UI_CASE_UPPER`,
`UI_CASE_LOWER`, `UI_CASE_UPPERFIRST`. `$AppUI->setMsg($msg)` traduce y escapa
su mensaje de la misma manera.

```php
echo dPaction('history.back', array(), null,
	array('confirm' => $AppUI->_('Are you sure you want to cancel?', UI_OUTPUT_RAW)));
```

## Formularios y CSRF { #forms-and-csrf }

Todo POST de una sesión iniciada debe llevar el token CSRF de la sesión, un
campo llamado `csrf_token`. `index.php` lo comprueba con
`$AppUI->verifyCsrfToken()` en cada POST, antes de que se ejecute el módulo. Un
token ausente o incorrecto envía al usuario a *Acceso Denegado* con *Token de
seguridad no válido o ausente.*

Rara vez tendrá que añadir el token usted mismo:

| Cómo se envía la petición | Cómo obtiene el token |
|---|---|
| Un `<form method="post">` en la página | `index.php` añade el campo oculto a todas las etiquetas form con `method="post"` mientras se envía la página (`CAppUI::injectCsrfToken()`, un búfer de salida). |
| Un formulario construido por JavaScript, o enviado con `form.submit()` | `js/dp-ui.js` añade el campo a partir de la etiqueta `<meta name="csrf-token">` que imprime la cabecera de cada tema (`$AppUI->getCsrfMeta()`). |
| `fetch()` o `XMLHttpRequest` | Su script lee la etiqueta meta y envía `csrf_token` en el cuerpo, como hace `js/dp-board.js`. |
| Salida que no pasa por el búfer de salida | Imprima `$AppUI->getCsrfInput()` dentro del formulario. |

De `js/dp-board.js`:

```js
var meta = document.querySelector('meta[name="csrf-token"]');
body.append('csrf_token', meta ? meta.getAttribute('content') : '');
```

### Qué tiene que ser un POST { #what-must-be-a-post }

Una petición que cambia datos tiene que ser un POST. `index.php` lo impone para
los manejadores de escritura: una petición con `dosql`, y una vista cuyo nombre
empieza por `do_`, `dosql` o `domodsql`, se rechaza salvo que sea un POST con el
token y que el usuario pueda añadir, editar o (con `del=1`) borrar en el
módulo. Las páginas de configuración de los módulos (`a=configure`) necesitan
derechos de edición sobre Administración del Sistema.

Otro sitio puede disparar un enlace GET que borra o cambia algo. Use un
formulario, o un enlace que envíe por POST con `dpPost()` de `js/dp-ui.js`,
como hace `modules/macroprojects/addproject.php`:

```php
<a href="#"<?php echo dPaction('dpPost', array('?m=macroprojects&a=addproject&macroproject_id=' . (int)$macroproject_id,
	array('project' => (int)$row['project_id'], 'add' => 'true'))); ?>>
```

Sustituye a `<a href="?m=macroprojects&a=addproject&project=5&add=true">`.

## Permisos { #permissions }

`index.php` solo comprueba el módulo: asigna `$canAccess`, `$canRead`,
`$canEdit`, `$canAuthor` y `$canDelete` para `m`, y rechaza un módulo al que el
usuario no tiene acceso. Cada página debe comprobar el **registro** que muestra
o cambia.

| Función auxiliar | Qué hace |
|---|---|
| `getPermission($module, $op, $item_id = 0)` | `true` cuando el usuario puede hacer `$op` (`access`, `view`, `add`, `edit`, `delete`) en el módulo, sobre el elemento si da su id. Para `view`, una tarea también necesita su proyecto, y un proyecto su empresa. |
| `$AppUI->redirect('m=public&a=access_denied')` | Envía al usuario a *Acceso Denegado* y detiene el script. |
| `dPrequireWritePermission($module, $id, $del = false)` | Para los manejadores de escritura: se detiene salvo que el usuario pueda borrar (`$del`), editar (`$id` > 0) o añadir (`$id` = 0). |
| `dPrequireProjectEdit($project_id, $records)` | Se detiene salvo que el usuario pueda editar el proyecto y todos los registros de la lista pertenezcan a él (`dPrecordList()` construye la lista). |
| `CTask::canAccess($user_id)` | El nivel de acceso propio de una tarea: público, protegido, participante, privado, privilegiado. Compruébelo después de `getPermission()` en las tareas. |

Una página de vista, de `modules/links/addedit.php`:

```php
$link_id = intval(dPgetParam($_GET, 'link_id', 0));
$canEdit = getPermission($m, 'edit', $link_id);
if (!(($canEdit && $link_id) || ($canAuthor && !($link_id)))) {
	$AppUI->redirect('m=public&a=access_denied');
}
```

Su manejador de escritura, `modules/links/do_link_aed.php`:

```php
$del = intval(dPgetParam($_POST, 'del', 0));
dPrequireWritePermission('links', dPgetParam($_POST, 'link_id', 0), $del);
```

El manejador de escritura debe volver a comprobarlo: `index.php` comprueba el
módulo, y cualquiera puede enviar cualquier id.

### Listas { #lists }

Una lista debe omitir los registros que el usuario no puede ver. Los roles
pueden permitir o denegar registros concretos, así que no filtre solo por el
permiso del módulo. Las clases `CDpObject` (`classes/dp.class.php`) construyen
el filtro a partir de los elementos permitidos y denegados del usuario
(`getAllowedItems()`, `getDeniedItems()` en `classes/permissions.class.php`):

| Método | Da |
|---|---|
| `setAllowedSQL($uid, $q, $index = null, $key = null)` | Añade el filtro a un `DBQuery`. `$index` es la columna de su consulta que contiene el id del objeto; el método une la tabla del objeto por ella. |
| `getAllowedSQL($uid, $index = null)` | Las condiciones `WHERE` como un array de cadenas, para que las añada usted. |
| `getAllowedRecords($uid, $fields, $orderby, $index)` | Los registros permitidos, como una lista de hashes. |
| `getDeniedRecords($uid)` | Los ids denegados al usuario. |

De `modules/links/index_table.php`:

```php
$project->setAllowedSQL($AppUI->user_id, $q, 'link_project');
$task->setAllowedSQL($AppUI->user_id, $q, 'link_task and task_project = link_project');
```

La API REST aplica los mismos filtros; vea `CApi::taskQuery()` en
[`classes/api.class.php`](https://github.com/davmont/dotPulse/blob/devel/classes/api.class.php).

## Archivos y subidas { #files-and-uploads }

Los archivos subidos se guardan en `files/`, que el servidor web debe negarse a
servir (`files/.htaccess` lo hace en Apache; nginx necesita la regla de
[deployment-security.md](https://github.com/davmont/dotPulse/blob/devel/docs/deployment-security.md)).
`fileviewer.php` los envía después de las comprobaciones de inicio de sesión y
de permisos.

Si su módulo guarda archivos:

- guárdelos en `files/` (los archivos temporales en `files/temp/`), nunca en
  una carpeta que sirva el servidor web;
- guarde cada subida con un nombre que genere usted (el módulo de archivos usa
  `uniqid(rand())`), nunca con el nombre o la ruta que envió el usuario, y
  muévala con `move_uploaded_file()`;
- construya la ruta a partir del registro guardado con `basename()`, para que
  un registro antiguo no pueda apuntar fuera de la carpeta:

  ```php
  $fname = DP_BASE_DIR . '/files/' . (int)$file['file_project'] . '/' . basename($file['file_real_filename']);
  ```

- sirva el archivo a través de PHP, después de `getPermission()` sobre el
  registro, con `Content-Disposition: attachment`.

Un archivo que un módulo incluye desde la instalación de otra aplicación pasa
por `dPexternalAppFile($dir, $name)`, que rechaza todo lo que esté en `files/`.

## Contraseñas y secretos { #passwords-and-secrets }

- Calcule el hash de las contraseñas con `dPhashPassword($password)`
  (`password_hash()` con `PASSWORD_DEFAULT`) y compruébelas con
  `password_verify()`. No compare nunca hashes usted mismo y no guarde nunca
  una contraseña en claro.
- Los tokens y códigos que el usuario tiene que devolver salen de
  `random_bytes()`. Guarde solo su SHA-256 (`hash('sha256', $token)`), como hace
  `classes/apitoken.class.php` con los tokens de la API.
- No escriba nunca una contraseña, un token o un código de verificación en dos
  pasos en un registro, en el historial, en un mensaje ni en el registro de
  auditoría.
- Todo lo que comprueba un secreto enviado por el usuario tiene un límite de
  intentos por dirección con `RateLimiter` (`classes/ratelimiter.class.php`):
  `new RateLimiter('name', $max_attempts, $seconds)`, y después `isAllowed()` y
  `recordAttempt()` en cada fallo. Vea `CApi::authenticate()`.

### El registro de auditoría { #the-audit-log }

Los eventos de seguridad van al registro de auditoría, que los administradores
consultan en
[Administración del Sistema › Registro de auditoría](../administration/audit-and-maintenance.md#audit-log).
Añada una entrada con `dPaudit()`:

```php
dPaudit($event, $details = '', $object = '', $object_id = 0, $user_id = null, $username = null);
```

- `$event` es un nombre de `CAuditLog::events()` en
  [`classes/auditlog.class.php`](https://github.com/davmont/dotPulse/blob/devel/classes/auditlog.class.php).
  Un evento nuevo necesita una entrada nueva allí, con su descripción, y una
  línea en
  [docs/audit-log.md](https://github.com/davmont/dotPulse/blob/devel/docs/audit-log.md)
  y en la [página del registro de auditoría](../administration/audit-and-maintenance.md#audit-log).
- `$details` es texto para personas, de 1000 caracteres como máximo. Sin
  contraseñas ni tokens.
- `$object` y `$object_id` nombran el registro, por ejemplo `'users', $user_id`.
- `$user_id` y `$username` toman por defecto el usuario que ha iniciado sesión.
  Para un inicio de sesión fallido, pase `0` y el nombre que se intentó.

Un fallo al escribir la entrada va al registro de errores de PHP y nunca
detiene la acción. De `modules/admin/do_userrole_aed.php`:

```php
dPaudit('user.role_added', 'role ' . $user_role . ' to ' . CAuditLog::username($user_id), 'users', $user_id);
```

## Content-Security-Policy { #content-security-policy }

Todas las páginas de la aplicación (`index.php`) envían una
Content-Security-Policy que solo permite los scripts del propio sitio más los
bloques `<script>` en línea que llevan el nonce de la petición. Los manejadores
de eventos en línea (`onclick="..."`) y las URL `javascript:` se bloquean, y
también `eval()`, `new Function()` y los temporizadores con cadenas: no hay
`'unsafe-eval'`.

La política se aplica. Los navegadores bloquean todo lo que no permite y lo
notifican a `csp-report.php`. Si una actualización rompe una página, un
administrador puede añadir `$dPconfig['csp_report_only'] = true;` a
`includes/config.php`: la política se envía entonces como
`Content-Security-Policy-Report-Only`, y las infracciones se notifican pero no
se bloquean, mientras se corrige la página.

Ningún archivo del árbol tiene JavaScript en línea, y
`tools/checks/check-inline-js.sh` (`make inline-js`, que se ejecuta en CI)
falla con cualquier atributo de manejador de eventos nuevo, URL `javascript:` o
`<script>` sin nonce.

| Pieza | Dónde |
|---|---|
| Política y cabecera | `dPcspPolicy()`, `dPsendCspHeader()` en `includes/main_functions.php` |
| Nonce | `dPcspNonce()`, el mismo valor para toda la petición |
| Sustituto de los manejadores en línea | `js/dp-actions.js` (se carga en todas las páginas), con atributos construidos por `dPaction()` |
| Informes de infracciones | `csp-report.php` añade una línea por infracción a `files/temp/csp-reports.log` (deja de crecer a los 5 MB); `scripts/csp-report-summary.sh` los agrupa |

La política:

| Directiva | Valor |
|---|---|
| `default-src` | `'self'` |
| `script-src` | `'self'`, el nonce, `'report-sample'` |
| `style-src` | `'self' 'unsafe-inline'`: se permiten los estilos en línea |
| `img-src`, `font-src` | `'self' data:` |
| `connect-src`, `frame-src`, `frame-ancestors`, `form-action`, `base-uri` | `'self'` |
| `object-src` | `'none'` |
| `report-uri` | `csp-report.php` |

### Manejadores de eventos en línea y URL javascript: { #inline-event-handlers-and-javascript-urls }

Sustituya el manejador por atributos `data-dp-*`, escritos por `dPaction()`, que
codifica los valores en JSON y los escapa para HTML. No construya nunca estos
atributos a mano.

```php
dPaction($call, $args = array(), $on = null, $extra = array())
```

`$call` es la función, `$args` sus argumentos, `$on` los eventos y `$extra`
más atributos `data-dp-<key>`: `confirm`, `href`, `submit`.

| Antes | Después |
|---|---|
| `<a href="javascript:popCalendar('start')">` | `<a href="#"<?php echo dPaction('popCalendar', array('start')); ?>>` |
| `<input type="button" onclick="submitIt(this.form)">` | `<input type="button"<?php echo dPaction('submitIt', array('$form')); ?>>` |
| `<select onchange="this.form.submit()">` | `<select<?php echo dPaction(null, array(), null, array('submit' => '')); ?>>` |
| `<select onchange="changeView(this.value)">` | `<select<?php echo dPaction('changeView', array('$value')); ?>>` |
| `<a href="?m=x&del=1" onclick="return confirm('Sure?')">` | `<a href="?m=x&del=1"<?php echo dPaction(null, array(), null, array('confirm' => $AppUI->_('Sure?', UI_OUTPUT_RAW))); ?>>` |
| `<input type="button" onclick="window.location='?m=x'">` | `<input type="button"<?php echo dPaction(null, array(), null, array('href' => '?m=x')); ?>>` |
| `<form onsubmit="return check(this)">` | `<form<?php echo dPaction('check', array('$this'), 'submit'); ?>>` |
| `<span onmouseover="return overlib('tip')" onmouseout="nd()">` | `<span data-dp-on="mouseover mouseout" data-dp-call-mouseover="overlib" data-dp-args-mouseover='["tip"]' data-dp-call-mouseout="nd">` (escape el JSON con `htmlspecialchars()`) |

En el ejemplo de la confirmación, `UI_OUTPUT_RAW` evita que el texto se escape
dos veces: lo escapa `dPaction()`. Un enlace que borra también debería ser un
POST (vea [Qué tiene que ser un POST](#what-must-be-a-post)).

Cómo los ejecuta `js/dp-actions.js`:

- `$this`, `$event`, `$form` y `$value` en los argumentos representan el
  elemento, el evento, su formulario y su valor.
- La función se ejecuta con el elemento como `this`, igual que un manejador en
  línea; devolver `false` cancela la acción por defecto. Un nombre con puntos
  (`window.open`) se ejecuta sobre su propietario.
- El evento por defecto es `change` para `<select>` y `click` para todo lo
  demás; `data-dp-on` (el tercer argumento de `dPaction()`) lo cambia, y puede
  listar varios eventos separados por espacios. `load` y `unload` no se
  admiten: ejecute ese código en `DOMContentLoaded`.
- El orden es: confirmación, llamada, y después `href` o `submit` (que se
  omiten cuando la llamada devolvió `false`). Un enlace con `href="#"` nunca
  sigue el `#`.
- Solo se pueden llamar funciones con nombre. Se rechazan `eval`, `Function`,
  `setTimeout`, `setInterval`, `execScript` y `document`. El código que era más
  que una llamada a una función pasa a ser una función con nombre en el archivo
  `.js` de la página.

### Bloques de script en línea { #inline-script-blocks }

Lleve el código al archivo `.js` del módulo: `modules/<m>/<a>.js` y
`modules/<m>/<m>.module.js` se cargan automáticamente (vea
[JavaScript](index.md#javascript)). Los datos que el script necesita de PHP van
en un bloque de datos JSON, que el navegador no ejecuta, así que no necesita
nonce:

```php
<script type="application/json" id="task-data"><?php echo json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP); ?></script>
```

```js
var data = JSON.parse(document.getElementById('task-data').textContent);
```

`classes/gantt_renderer.class.php` pasa así los datos del diagrama de Gantt a
`js/dp-gantt.js`.

Un bloque que tiene que seguir en línea lleva el nonce:

```php
<script nonce="<?php echo dPcspNonce(); ?>">
```

No imprima nunca el nonce en nada en lo que pueda influir un usuario, y no lo
añada nunca a un bloque que reproduce datos de la petición.

### Encontrar lo que queda { #finding-what-is-left }

`tools/checks/check-inline-js.sh --list modules/<module>` imprime todas las
líneas que aún tienen un atributo de manejador de eventos, una URL
`javascript:` o un `<script>` en línea sin nonce. Los recuentos permitidos por
archivo están en `tools/checks/inline-js-baseline.txt`, que está vacío: ningún
archivo puede tener ninguno. `tools/checks/check-inline-js.sh --update`
reescribe la línea base.

En el entorno de pruebas (*sandbox*), vacíe el registro de informes, recorra
las páginas y después resuma los informes:

```sh
docker compose -f docker/compose.yml exec -T web rm -f /var/www/html/files/temp/csp-reports.log
# ... recorra las páginas ...
scripts/csp-report-summary.sh docker/files/temp/csp-reports.log
```

Las líneas `script-src-attr` son manejadores en línea o URL `javascript:`; las
líneas `script-src-elem` son bloques `<script>` en línea sin el nonce. La
consola del navegador también muestra cada infracción, con el elemento.

Los navegadores solo notifican un manejador en línea o una URL `javascript:`
cuando se ejecuta (un clic, un cambio), así que un rastreo que solo carga
páginas se pierde la mayoría. Busque también en el código:

```sh
grep -rnP '\son[a-z]+\s*=|javascript:|<script(?![^>]*\b(src|nonce)=)(?![^>]*application/json)' modules/<module>
```
