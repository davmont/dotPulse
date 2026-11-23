# API REST { #rest-api }

dotPulse tiene una API JSON para scripts y otras herramientas: listar proyectos y
tareas, registrar trabajo y actualizar el avance de las tareas. La sirve `api.php`
y está **desactivada de forma predeterminada**.

El código está en [`api.php`](https://github.com/davmont/dotPulse/blob/devel/api.php),
[`classes/api.class.php`](https://github.com/davmont/dotPulse/blob/devel/classes/api.class.php)
y [`classes/apitoken.class.php`](https://github.com/davmont/dotPulse/blob/devel/classes/apitoken.class.php).

## Activarla { #turning-it-on }

Un administrador del sistema marca **Activar la API REST** (`api_enabled`) en
**Administración del Sistema › Seguridad y sesiones** (consulte
[API y auditoría](../administration/settings.md#api-and-audit)). Mientras está
desactivada, `api.php` responde a todas las peticiones con `404 api_disabled` y la
pestaña **Tokens de API** no aparece. Al desactivarla se detienen todos los tokens
a la vez; los tokens se conservan y vuelven a funcionar cuando se activa de nuevo.

## Tokens { #tokens }

Cada usuario crea sus propios tokens: **Mis datos** y después la pestaña
**Tokens de API** (consulte [Tokens de API](../user-guide/preferences.md#api-tokens)
en la guía del usuario).

- **Nombre:** para qué es el token ("bot de CI", "control de horas").
- **Acceso:** *Solo lectura* (solo peticiones GET) o *Lectura y escritura*.
- **Caduca:** a los 30, 90 (el valor predeterminado) o 365 días, o nunca.

El token (`dpp_` seguido de 40 dígitos hexadecimales) se muestra **una sola vez**,
justo después de crearlo. dotPulse solo guarda su SHA-256 y sus 12 primeros
caracteres, así que un token perdido no se puede volver a mostrar: revóquelo y cree
uno nuevo. La pestaña lista de cada token el nombre, los primeros caracteres, el
acceso, cuándo se creó, cuándo caduca y cuándo se usó por última vez, con un botón
**Revocar**. Un usuario puede tener como máximo 20 tokens.

Los administradores que pueden editar usuarios ven la pestaña de todos los usuarios
y pueden revocar sus tokens, pero no pueden crear tokens para ellos (consulte
[Tokens de API](../administration/users.md#api-tokens) en la guía de administración).

Un token actúa **como su usuario**: llega exactamente a lo que el usuario puede ver
y cambiar en la interfaz web, y a nada más. Un token de lectura y escritura de un
usuario con permisos de solo lectura sigue sin poder cambiar nada.

## Peticiones y respuestas { #requests-and-responses }

```
GET /api.php/v1/projects HTTP/1.1
Host: pm.example.com
Authorization: Bearer dpp_0123456789abcdef0123456789abcdef01234567
```

```sh
curl -H "Authorization: Bearer $TOKEN" https://pm.example.com/api.php/v1/me
```

- **Dirección:** `https://<your site>/api.php/v1/...`. Cuando el servidor no pasa la
  ruta que sigue a `api.php` (algunas configuraciones de nginx), use en su lugar
  `api.php?route=/v1/...`.
- **Los cuerpos** de las peticiones POST y PATCH son objetos JSON enviados con
  `Content-Type: application/json`, de 64 KB como máximo. Los campos desconocidos se
  ignoran.
- **Las respuestas** son JSON en UTF-8. Los registros sueltos son `{"data": {...}}`.
- **Las listas** admiten `limit` (1-200, 50 de forma predeterminada) y `offset`, y devuelven
  `{"data": [...], "meta": {"limit": 50, "offset": 0, "next_offset": 50}}`.
  `next_offset` es el `offset` de la página siguiente, o `null` en la última página.
- **Los números de la cadena de consulta** (`limit`, `offset` y los filtros que
  reciben un id) son enteros no negativos; cualquier otra cosa responde
  `400 invalid_parameter`.
- **Las fechas** están en la hora local del servidor, como `YYYY-MM-DDTHH:MM:SS`; las
  fechas vacías son `null`.
- **Los errores** usan el código de estado HTTP y un cuerpo como
  `{"error": {"code": "not_found", "message": "No such task."}}`:

  | Estado | Código | Cuándo |
  | --- | --- | --- |
  | 400 | `invalid_parameter`, `invalid_body` | Un parámetro o el cuerpo no son correctos |
  | 401 | `unauthorized` | Sin token, un token desconocido, caducado o revocado, o su usuario ya no puede iniciar sesión |
  | 403 | `read_only_token` | Un token de solo lectura envió un POST o un PATCH |
  | 403 | `forbidden` | El usuario puede ver la tarea pero no cambiarla, o no puede leer sus registros |
  | 404 | `api_disabled` | La API está desactivada |
  | 404 | `not_found` | No existe ese endpoint, o no existe ese registro *para este usuario* |
  | 405 | `method_not_allowed` | La cabecera `Allow` lista los métodos |
  | 413 | `body_too_large` | El cuerpo supera los 64 KB |
  | 415 | `unsupported_media_type` | El cuerpo no se envía como `application/json` |
  | 422 | `not_saved` | El registro se rechazó al guardarlo |
  | 429 | `too_many_attempts` | 20 autenticaciones fallidas desde una misma dirección en 10 minutos |
  | 500 | `server_error` | Se anota en el registro de errores de PHP como "dotPulse API: ..." |
  | 503 | `not_installed` | dotPulse aún no está instalado |

  Una respuesta 401 lleva además `WWW-Authenticate: Bearer realm="dotPulse"`. El
  bloqueo 429 se aplica a todas las peticiones desde esa dirección, con un token
  válido o sin él, hasta que el fallo más antiguo tiene 10 minutos. Las peticiones
  sin token no cuentan como fallos.

## Endpoints { #endpoints }

| Método y ruta | Necesita | Devuelve |
| --- | --- | --- |
| `GET /v1/me` | | El usuario del token |
| `GET /v1/projects` | | Los proyectos, en una lista |
| `GET /v1/projects/{id}` | | Un proyecto |
| `GET /v1/projects/{id}/tasks` | | Las tareas del proyecto, en una lista |
| `GET /v1/tasks` | | Las tareas, en una lista |
| `GET /v1/tasks/{id}` | | Una tarea |
| `PATCH /v1/tasks/{id}` | token de escritura | La tarea |
| `GET /v1/tasks/{id}/logs` | | El registro de trabajo de la tarea, en una lista |
| `POST /v1/tasks/{id}/logs` | token de escritura | La nueva entrada del registro (201) |

### GET /v1/me { #get-v1me }

El usuario del token: `id`, `username`, `first_name`, `last_name`, `email`, `company_id`,
`locale` y `token_scope` (`read` o `write`).

### GET /v1/projects { #get-v1projects }

Los proyectos que el usuario puede ver, por nombre. Filtros: `status` (un valor de la
lista ProjectStatus, por ejemplo 3 = En curso) y `company` (id de la empresa).

```json
{
  "id": 1, "name": "Website relaunch", "short_name": "WEB",
  "company": {"id": 2, "name": "Acme"},
  "status": 3, "status_name": "In Progress", "priority": 0, "owner_id": 1,
  "start_date": "2026-11-01T09:00:00", "end_date": "2027-01-31T18:00:00",
  "percent_complete": 35.5, "color": "#1b4f9c", "description": "..."
}
```

`percent_complete` se calcula a partir de las tareas del proyecto.

### GET /v1/projects/{id} { #get-v1projectsid }

Un proyecto.

### GET /v1/projects/{id}/tasks { #get-v1projectsidtasks }

Las tareas del proyecto que el usuario puede ver, por orden de fecha de inicio. Mismo
formato que más abajo.

### GET /v1/tasks { #get-v1tasks }

Las tareas que el usuario puede ver, por proyecto y fecha de inicio. Filtros:

- `project`: un id de proyecto;
- `assigned`: `me` o un id de usuario;
- `open`: `1` para las tareas por debajo del 100 %, `0` para las terminadas.

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

`parent_id` es `null` en las tareas de primer nivel. `duration_unit` es `days` o
`hours`. `status` es 0 (activa) o -1 (inactiva); `priority` es -1 (baja), 0 (normal)
o 1 (alta).

### GET /v1/tasks/{id} { #get-v1tasksid }

Una tarea, con su `description`.

### PATCH /v1/tasks/{id} { #patch-v1tasksid }

Cambia `percent_complete` (un entero, 0-100), `priority` (-1, 0, 1) o los dos. Poner
el 100 % en una tarea sin fecha de fin pone la fecha de fin en el momento actual,
como hace el formulario web. Necesita un token de escritura y permiso de edición
sobre la tarea. Devuelve la tarea.

```sh
curl -X PATCH -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"percent_complete": 100}' https://pm.example.com/api.php/v1/tasks/12
```

### GET /v1/tasks/{id}/logs { #get-v1tasksidlogs }

El registro de trabajo de la tarea, de más reciente a más antiguo, en páginas como
las demás listas: `id`, `task_id`, `creator_id`, `date`, `hours`, `summary`,
`description`, `costcode`, `problem`. Necesita permiso de lectura sobre los registros
de tareas.

### POST /v1/tasks/{id}/logs { #post-v1tasksidlogs }

Registra trabajo en una tarea, como el usuario del token. Campos:

| Campo | Obligatorio | |
| --- | --- | --- |
| `hours` | sí | 0-999, se admiten decimales |
| `date` | no | `YYYY-MM-DD` o `YYYY-MM-DDTHH:MM:SS` (también se aceptan un espacio en lugar de `T` y la hora sin segundos); de forma predeterminada, el momento actual |
| `summary`, `description` | no | Texto. El resumen se corta a 255 caracteres. |
| `costcode` | no | Se corta a 8 caracteres |
| `problem` | no | `true` marca el registro como problema |
| `percent_complete` | no | Cambia también el avance de la tarea (un entero, 0-100) |

Necesita un token de escritura, permiso de edición sobre la tarea y permiso para
añadir registros de tareas, como la pestaña **Historial**. Devuelve 201 y el
registro. Los módulos complementarios que amplían el registro del formulario web
(Unit cost) no se actualizan desde la API.

```sh
curl -X POST -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"hours": 1.5, "summary": "Review"}' https://pm.example.com/api.php/v1/tasks/12/logs
```

## Permisos { #permissions }

Cada petición pasa las mismas comprobaciones que las páginas web, para el usuario
del token (consulte [Permisos](secure-code.md#permissions)):

- proyectos: el permiso sobre el módulo y las reglas de permitir y denegar de la
  empresa y del proyecto;
- tareas: el permiso del proyecto más el nivel de acceso de la propia tarea
  (pública, protegida, de participantes, privada, privilegiada);
- cambios: el permiso de edición sobre la tarea y, para registrar trabajo, el
  permiso para añadir registros de tareas.

Un registro que el usuario no puede ver responde 404, igual que un registro que no
existe, así que no se pueden sondear los ids. Un usuario desactivado (sin ningún rol)
ya no puede usar sus tokens.

## Notas sobre el servidor web { #web-server-notes }

- **Apache** pasa la cabecera `Authorization` a `mod_php`. Con PHP-FPM o CGI la copia
  el `.htaccess` de la raíz (`SetEnvIf Authorization`); sin `.htaccess`, añada la
  misma línea, o `CGIPassAuth On`, a la configuración del sitio.
- **nginx** pasa de forma predeterminada la cabecera a PHP-FPM como `HTTP_AUTHORIZATION`. Para
  las direcciones `api.php/v1/...`, el `location` de PHP debe aceptar una ruta
  después del script:

  ```nginx
  location ~ ^/api\.php(/|$) {
      fastcgi_split_path_info ^(/api\.php)(/.*)$;
      fastcgi_param PATH_INFO $fastcgi_path_info;
      include fastcgi_params;
      fastcgi_param SCRIPT_FILENAME $document_root/api.php;
      fastcgi_pass php-fpm;
  }
  ```

  Si no, use `api.php?route=/v1/...`.
- Use **HTTPS**: el token viaja en cada petición.
- Las respuestas llevan `Cache-Control: no-store`, `X-Content-Type-Options: nosniff` y
  una `Content-Security-Policy` de `default-src 'none'`, y no crean cookies. Los
  navegadores de otros sitios no pueden llamar a la API: no se envían cabeceras CORS.
