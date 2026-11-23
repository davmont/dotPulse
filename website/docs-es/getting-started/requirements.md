# Requisitos { #requirements }

dotPulse es una aplicación PHP con una base de datos MySQL o MariaDB. Cualquier servidor que ejecute PHP 8.2 o posterior detrás de Apache o nginx puede alojarlo. Se desarrolla y se prueba en Linux.

## PHP { #php }

Necesita **PHP 8.2 o posterior**. PHP 8.3 está probado.

Las bibliotecas que vienen con dotPulse necesitan estas extensiones. La mayoría de los paquetes de PHP ya las incluyen:

| Extensión | Para qué se necesita |
| --- | --- |
| `mysqli` | La conexión a la base de datos. |
| `mbstring` | El tratamiento de textos en general, los informes PDF y el código QR del inicio de sesión en dos pasos. |
| `dom` | Los informes PDF. |
| `session`, `ctype`, `filter`, `hash` | Los inicios de sesión y el envío de correo. |

Estas extensiones son opcionales. dotPulse funciona sin ellas, pero pierde algo:

| Extensión | Sin ella |
| --- | --- |
| `fileinfo` | No se detecta el tipo de los ficheros subidos: cada fichero se guarda como `application/octet-stream`. |
| `gd` | El instalador la marca como ausente. La biblioteca de PDF la usa para dibujar imágenes. |
| `zlib` | El módulo Copia de Seguridad funciona de forma limitada. |
| `curl` | El módulo Mantis no puede conectar con MantisBT. |
| `ldap` | No puede iniciar sesión contra un directorio LDAP ni importar contactos de uno. |

### Ajustes de PHP { #php-settings }

El instalador comprueba estos ajustes y muestra un aviso por cada uno que no esté bien:

- `file_uploads` activado. El instalador muestra el tamaño máximo de fichero que puede subir: el menor de `upload_max_filesize`, `post_max_size` y `memory_limit`.
- `session.use_cookies` activado, y `session.use_trans_sid` desactivado.
- Un `session.save_path` con permiso de escritura. Puede ignorar este si mantiene la gestión de sesiones por defecto, que guarda las sesiones en la base de datos.

Ejecute PHP como módulo de Apache o mediante PHP-FPM. El instalador avisa de que el modo CGI simple probablemente dará problemas.

## Base de datos { #database }

Necesita **MySQL o MariaDB**. dotPulse se conecta mediante el controlador `mysqli`. El entorno de desarrollo usa MariaDB 10.11.

Cree un usuario de base de datos para dotPulse. El instalador puede crear la base de datos él mismo si ese usuario puede crear bases de datos; si no, cree antes una base de datos vacía y dé al usuario todos los permisos sobre ella.

Cree la base de datos con el juego de caracteres **utf8mb4** (el ejemplo de [Instalación](install.md) lo hace), para que el texto de cualquier idioma se guarde tal como se escribe.

## Servidor web { #web-server }

Se recomienda **Apache**. dotPulse incluye ficheros `.htaccess` que impiden que el servidor web sirva directamente el código, las bibliotecas, los ficheros subidos y otros ficheros internos. Apache solo los lee si el directorio de dotPulse tiene `AllowOverride All` (o al menos `AuthConfig Limit Options FileInfo`). Sin eso, Apache los ignora sin avisar.

**nginx** también funciona, pero nunca lee los ficheros `.htaccess`. Debe copiar las mismas reglas en su bloque `server`. La [guía de seguridad del despliegue](https://github.com/davmont/dotPulse/blob/master/docs/deployment-security.md#nginx) las tiene listas para pegar.

Sirva el sitio por **HTTPS**. Lo necesita para la REST API, y protege las contraseñas y las cookies de sesión de sus usuarios.

## Carpetas en las que escribe el servidor web { #folders-the-web-server-writes-to }

El usuario del servidor web debe poder escribir en:

- `includes/`, solo durante la instalación, para que el instalador pueda escribir `includes/config.php`. Si no puede, el instalador muestra el contenido del fichero para que usted lo cree a mano.
- `files/`, donde se guardan los ficheros subidos.
- `files/temp/`, para los informes PDF y los ficheros temporales.
- `locales/` y sus carpetas de idioma (y `modules/*/locales/`), solo si va a editar las traducciones en **Administración del Sistema › Traducciones**. El instalador comprueba `locales/en/`.

## Navegadores { #browsers }

Sus usuarios necesitan un navegador actual con JavaScript y cookies activados. El tema de dotPulse se adapta a móviles y portátiles. El instalador desaconseja Internet Explorer.

## Opcional: cron y correo { #optional-cron-and-mail }

dotPulse envía correo para restablecer contraseñas, asignar tareas, avisar de tareas y recordatorios. Para enviarlo necesita, o bien la función `mail()` de PHP configurada en el servidor, o bien un servidor SMTP que pueda usar.

Los avisos de tareas, los correos de asignación y los recordatorios pasan por una cola de eventos. Una tarea cron debe ejecutar `queuescanner.php` cada pocos minutos; si no, esos correos no se envían nunca. [Instalación](install.md#set-up-the-cron-job) explica cómo.
