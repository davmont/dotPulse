# Entorno de desarrollo { #development-environment }

Esta página prepara una copia de desarrollo de dotPulse: un entorno de pruebas
(*sandbox*) que sirve su copia de trabajo del código, con una base de datos de
usar y tirar, y las comprobaciones y pruebas que debe pasar un pull request.
Todo se ejecuta en contenedores, así que no tiene que instalar PHP, MariaDB ni
Composer en su ordenador.

## Qué necesita { #what-you-need }

| Herramienta | Para qué se usa |
| --- | --- |
| git | El repositorio. |
| Docker, con el complemento Compose (`docker compose`) | El entorno de pruebas, y las imágenes de PHP, PHPStan, Composer y Node.js en las que se ejecutan las comprobaciones. El `Makefile` y los scripts llaman a `docker`. |
| make, bash, curl | El `Makefile` y los scripts de `docker/`. |
| Python 3 | `make theme-contrast`, `make docs` (la comprobación de las imágenes) y algunos scripts de prueba de `docker/tests/` (el rastreador de páginas, por ejemplo). |
| Node.js 22 y Google Chrome | Solo para las comprobaciones en el navegador de `make audit-full` y para las capturas de pantalla de la documentación (`tools/browser/`). `make js-test` y `make deps-audit` ejecutan Node.js en un contenedor. |

Obtenga el código y trabaje en la rama `devel`:

```sh
git clone https://github.com/davmont/dotPulse.git
cd dotPulse
git checkout devel
```

## El entorno de pruebas { #the-sandbox }

El entorno de pruebas, en `docker/`, ejecuta dos contenedores: `web`, PHP 8.3
con Apache, y `db`, MariaDB 10.11. Sirve el repositorio en
<http://127.0.0.1:8089>, así que un cambio que guarde en su editor se ve al
volver a cargar la página.

### Arrancarlo { #start-it }

1. Arranque los contenedores:

    ```sh
    docker/up.sh
    ```

    La primera vez se construye la imagen `web`. `up.sh` también crea un
    `includes/config.php` vacío si no existe (vea más abajo).

2. Instale la base de datos:

    ```sh
    docker/reset-db.sh
    ```

    Esto ejecuta el instalador de verdad, que crea las tablas y la cuenta
    `admin`, y después añade las cuentas `worker` y `guest` con el formulario
    de usuarios. Antes borra todas las tablas, así que ejecútelo siempre que
    quiera una base de datos limpia.

3. Cargue los datos de ejemplo, una pequeña consultora con sus clientes,
   proyectos, tareas y registros de horas:

    ```sh
    docker compose -f docker/compose.yml exec -T web php db/sample/sample-data.php
    ```

    `make sample-data` hace los pasos 1 a 3 de una vez.

4. Abra <http://127.0.0.1:8089> e inicie sesión.

### Usuarios { #logins }

| Usuario | Contraseña | Rol | Creado por |
| --- | --- | --- | --- |
| `admin` | `passwd` | Administrador | El instalador. Con los datos de ejemplo, es Alex Rivera, el socio director. |
| `worker` | `worker` | Trabajador | `reset-db.sh` |
| `guest` | `guest` | Invitado | `reset-db.sh` |
| `priya`, `marco`, `hannah`, `tomas`, `lena` | `demo1234` | Trabajador | Los datos de ejemplo. Priya es la jefa de proyecto. |

El formulario de acceso tiene un límite de intentos. Si se bloquea a sí mismo
con contraseñas erróneas, borre `docker/files/temp/ratelimit_*`.

### Cómo funciona { #how-it-works }

- El repositorio se monta en el contenedor `web` en solo lectura: dotPulse no
  puede cambiar sus archivos.
- `docker/files/` se monta como `files/` y es la única carpeta en la que se
  puede escribir: allí van los archivos subidos y el estado del limitador de
  intentos.
- `docker/config.php` se monta sobre `includes/config.php`. Dirige dotPulse al
  contenedor `db`, con el prefijo de tablas `dotp_`. Su propio
  `includes/config.php`, si lo tiene, no lo usa el entorno de pruebas; git no
  lo sigue. `docker/config.php` se monta como un archivo suelto: después de
  editarlo, ejecute
  `docker compose -f docker/compose.yml up -d --force-recreate web`.
- Apache se ejecuta con el id de usuario 1000, o con su propio id de usuario
  cuando el entorno de pruebas lo arranca `make`, para que pueda escribir en
  `docker/files/`.
- La base de datos vive en memoria (`tmpfs`). Al detener el contenedor `db` se
  pierde: después, ejecute otra vez `reset-db.sh`.

### Los registros y la base de datos { #logs-and-the-database }

PHP muestra todos los errores, advertencias, avisos y obsolescencias, en la
página y en el registro del contenedor `web`:

```sh
docker compose -f docker/compose.yml logs -f web
```

Para consultar la base de datos (usuario y contraseña `dotproject`):

```sh
docker compose -f docker/compose.yml exec -T db mariadb -udotproject -pdotproject dotproject -e "SELECT user_username FROM dotp_users"
```

Los scripts de prueba tienen la misma orden como función de shell:
`source docker/tests/lib.sh`, y después `db -e "SELECT ..."`.

Para detener el entorno de pruebas y eliminar sus contenedores:

```sh
docker compose -f docker/compose.yml down -v
```

### Módulos en el entorno de pruebas { #modules-in-the-sandbox }

El instalador solo prepara los módulos del núcleo. Para instalar módulos
complementarios como lo hace un administrador desde
**Administración del Sistema › Módulos**:

```sh
docker/tests/modules.sh install links holiday   # instalar
docker/tests/modules.sh toggle links holiday    # activar
docker/tests/modules.sh menu links holiday      # mostrar en el menú
```

Sin nombres de módulo, la orden se aplica a todos los módulos complementarios.
`remove` y `upgrade` funcionan igual. El script falla si la orden no surte
efecto o si deja en el registro un error de SQL o un problema de PHP.

### Correo y MantisBT { #mail-and-mantisbt }

Junto al entorno de pruebas se pueden ejecutar dos contenedores opcionales:

- **Mailpit** recoge el correo que envía dotPulse. Arránquelo y dirija a él la
  configuración SMTP, y después lea los mensajes en <http://127.0.0.1:8025>:

    ```sh
    cd docker
    docker compose -f compose.yml -f compose.mail.yml up -d mail
    ./mail-setup.sh
    ```

    Ejecute otra vez `mail-setup.sh` después de `reset-db.sh`. El correo en cola
    sale cuando se ejecuta el procesador de la cola:
    `docker compose exec -u 1000 web php queuescanner.php --base-url=http://127.0.0.1:8089`.

- **MantisBT**, para el módulo Mantis. `mantis-setup.sh` instala MantisBT en
  <http://127.0.0.1:8090> (`administrator` / `root`), crea un proyecto de prueba
  con incidencias, muestra un token de API y configura la dirección de Mantis
  en dotPulse:

    ```sh
    cd docker
    docker compose -f compose.yml -f compose.mantis.yml up -d mantis
    ./mantis-setup.sh
    ```

    Instale y active el módulo Mantis, y después escriba el token en su página
    **Token de API de Mantis**. `reset-db.sh` solo restablece dotPulse:
    ejecute otra vez `mantis-setup.sh` después.

## Antes de abrir un pull request { #before-you-open-a-pull-request }

Ejecute las comprobaciones que no necesitan el entorno de pruebas:

```sh
make check
```

Si ha cambiado algo que cubre un script de prueba (el inicio de sesión, los
permisos, la API, el panel, el tablero, el inicio de sesión en dos pasos, el
registro de auditoría, los festivos), ejecute también:

```sh
make audit-smoke
```

Vuelve a instalar la base de datos del entorno de pruebas, así que después ya
no están los datos de ejemplo (`make sample-data` los recupera). Si ha cambiado
las páginas o los formularios de un módulo, `make audit-full` comprueba todos
los módulos de principio a fin; tarda alrededor de una hora, y la integración
continua (CI) lo ejecuta de todos modos en cada pull request.

## Objetivos de make { #make-targets }

| Objetivo | Qué hace |
| --- | --- |
| `make check` | Ejecuta `lint`, `phpstan`, `raw-input`, `inline-js`, `locales`, `theme-contrast`, `php-test`, `js-test` y `vendor-check`. No necesita el entorno de pruebas. |
| `make lint` | Comprueba con PHP 8.3 la sintaxis de todos los archivos PHP que sigue git, salvo `lib/` y `vendor/`. |
| `make phpstan` | Análisis estático con PHPStan en el nivel 2 (`tools/phpstan/phpstan.neon.dist`). Falla con cualquier hallazgo que no esté en la línea base. Copia `includes/config-dist.php` en `includes/config.php` si no existe. |
| `make phpstan-baseline` | Reescribe la línea base de PHPStan. Consulte [Líneas base](#baselines). |
| `make raw-input` | Falla cuando un archivo lee `$_GET`, `$_POST`, `$_REQUEST` o `$_COOKIE` directamente más veces de las que permite su línea base. |
| `make inline-js` | Falla cuando un archivo tiene más JavaScript en línea (`onclick=` y otros atributos de eventos, URL `javascript:`, bloques `<script>` sin `src` ni `nonce`) del que permite su línea base. |
| `make locales` | Falla cuando un texto que traduce el código no tiene entrada en inglés o en español (`tools/locales/check.php`). `php tools/locales/check.php missing es` lista lo que falta. |
| `make theme-contrast` | Comprueba que los colores del texto y de los controles de los temas de dotPulse, claros y oscuros, alcanzan el contraste WCAG AA. |
| `make php-test` | Ejecuta las pruebas unitarias de PHP de `tests/`. |
| `make js-test` | Ejecuta las pruebas de Jest de los scripts de `js/` (`tools/jest/tests/`). |
| `make vendor-check` | Comprueba con PHP 8.2 que `vendor/` contiene las versiones de `composer.lock` y se carga con la versión de PHP más antigua admitida. |
| `make deps-audit` | `composer audit` de `composer.lock`, y `npm audit` de las herramientas de Node.js de `tools/jest` y `tools/browser`. Necesita acceso a la red. |
| `make sample-data` | Arranca el entorno de pruebas, vuelve a instalar la base de datos y carga los datos de ejemplo. |
| `make sample-sql` | Escribe los datos de ejemplo como un único archivo SQL, para un servidor sin PHP de línea de órdenes. `OUT=file` y `PREFIX=dp_` eligen el archivo y el prefijo de las tablas. |
| `make audit-smoke` | Arranca el entorno de pruebas, vuelve a instalar la base de datos y ejecuta la prueba de humo de seguridad y las pruebas de funciones (vea más abajo). |
| `make audit-full` | Arranca el entorno de pruebas y ejecuta la auditoría completa (vea más abajo). |
| `make rehearse` | Actualiza en el entorno de pruebas una copia de una base de datos de producción. Consulte [Ensayo de actualización](#upgrade-rehearsal). |
| `make docs` | Genera en `website/site/` los sitios de la documentación en inglés y en español, como hace la CI. |
| `make docs-serve` | Sirve el sitio en inglés en <http://127.0.0.1:8000>, y lo regenera cuando guarda una página. |
| `make docs-serve-es` | Lo mismo para el sitio en español, en el puerto 8001. |
| `make docs-screenshots` | Toma desde el entorno de pruebas las capturas de pantalla de la documentación. Consulte [Capturas de pantalla](#screenshots). |

Las comprobaciones se ejecutan en contenedores (`php:8.3-cli`, `php:8.2-cli`,
una imagen de PHPStan fijada por su digest, `composer:2`, `node:22`), salvo
`theme-contrast`, que ejecuta Python en su ordenador.

## Pruebas { #tests }

### Pruebas unitarias de PHP { #php-unit-tests }

Las pruebas de `tests/` usan un pequeño framework al estilo de JUnit que viene
con ellas (`tests/phpunit.php`), no PHPUnit. Un archivo de pruebas es
`tests/<Name>Test.php`, con una clase del mismo nombre que extiende `TestCase`;
cada método cuyo nombre empieza por `test` es una prueba. Las aserciones son
`assert()`, `assertEquals()` y `assertRegexp()`. Muchos archivos incluyen
`tests/bootstrap.php`, que define `DP_BASE_DIR` y sustituye algunas funciones
globales (`dPgetSysVal()`, `dPgetConfig()`...) por versiones simuladas.

`tests/run.php` ejecuta cada archivo en su propio proceso de PHP, porque los
archivos simulan funciones distintas:

```sh
make php-test                                               # todos los archivos
docker run --rm -v "$PWD":/app -w /app php:8.3-cli php tests/run.php TwoFactor   # un archivo
```

Con PHP 8.2 o posterior en su ordenador, `php tests/run.php` también funciona.
Las pruebas no necesitan base de datos.

### Pruebas de JavaScript { #javascript-tests }

`tools/jest/tests/*.test.js` prueban los scripts de `js/` con Jest en un
navegador simulado (jsdom). `make js-test` instala Jest en el contenedor
`node:22` y las ejecuta.

### Prueba de humo y pruebas de funciones { #smoke-test-and-feature-tests }

`make audit-smoke` ejecuta estos scripts contra el entorno de pruebas, después
de volver a instalar su base de datos:

| Script | Qué comprueba |
| --- | --- |
| `docker/smoke.sh` | Lo básico de la seguridad: el inicio de sesión, CSRF, el restablecimiento de la contraseña, las comprobaciones de roles. |
| `docker/tests/seed.sh` | No es una prueba: añade las empresas, el proyecto, las tareas, el foro y el evento que usan los scripts siguientes. |
| `docker/tests/api.sh` | La API REST: los tokens, todos los endpoints, los permisos de lectura y escritura del token, la caducidad, la revocación, el límite de intentos fallidos y los permisos como `worker` y `guest`. |
| `docker/tests/dashboard.sh` | El panel personal, y que las tareas privadas de otros usuarios y los proyectos denegados siguen ocultos. |
| `docker/tests/board.sh` | El tablero del proyecto: las columnas, mover una tarjeta y los rechazos. |
| `docker/tests/twofactor.sh` | El inicio de sesión en dos pasos: la configuración, los códigos, los códigos de recuperación, el límite de intentos y el restablecimiento por el administrador. |
| `docker/tests/auditlog.sh` | El registro de auditoría: los eventos que anota, que no contiene contraseñas ni tokens, y el visor. |
| `docker/tests/ev-tips.sh` | Los avisos de valor ganado de los consejos del proyecto. |
| `docker/tests/holiday.sh` | Los calendarios de festivos: la importación desde un archivo iCalendar, y que la hoja de horas tiene en cuenta un festivo. |

`docker/tests/mail.sh` no forma parte de ella: necesita Mailpit, y comprueba el
restablecimiento de la contraseña, el correo de prueba y los correos de
asignación de tareas.

Cada script se puede ejecutar por separado una vez arrancado el entorno de
pruebas, por ejemplo
`docker/reset-db.sh && docker/tests/seed.sh && docker/tests/api.sh`.

### La auditoría completa { #the-full-audit }

`make audit-full` ejecuta `docker/tests/run.sh`, que comprueba de principio a
fin todos los módulos complementarios en cinco pasos:

1. Vuelve a instalar la base de datos, instala todos los módulos
   complementarios, los activa y los muestra en el menú, y después carga los
   datos de ejemplo y los datos de `seed.sh`.
2. Recorre todas las páginas como `admin` y como `worker`.
3. Rellena y guarda todos los formularios de añadir y editar como `admin` y
   como `worker`, y después vuelve a recorrer las páginas con los datos
   guardados.
4. Comprueba la maquetación en cada tema (sin desplazamiento lateral, sin
   controles fuera de la ventana), las ventanas emergentes, el cambio de
   pestañas del formulario de tareas y el tema oscuro (sin zonas claras, sin
   texto ilegible).
5. Elimina todos los módulos complementarios, recorre el núcleo sin ellos, los
   vuelve a instalar y recorre las páginas otra vez.

Cada paso falla también con cualquier cosa que aparezca en el registro de PHP:
errores de SQL, errores fatales, advertencias, avisos y obsolescencias. Los
informes van a `docker/.audit/`, que git ignora.

| Variable | Efecto |
| --- | --- |
| `STEPS="2 3"` | Ejecuta solo esos pasos. |
| `THEMES="dotpulse material"` | Los temas que comprueba el paso 4. De forma predeterminada, los siete. |
| `CHROME=/path/to/chrome` | El Chrome que manejan las comprobaciones en el navegador, si no es `/usr/bin/google-chrome`. |
| `ALLOW_DEPRECATED=1` | Las obsolescencias del registro no hacen fallar la ejecución. |

Los pasos 3 y 4 usan `playwright-core` de `tools/browser/`; `run.sh` lo instala
con `npm ci` si hace falta. Las excepciones conocidas de maquetación y del tema
oscuro están en `docker/tests/layout-known.txt` y `docker/tests/dark-known.txt`.

## Líneas base { #baselines }

Tres comprobaciones aceptan los problemas que tenía el código cuando se
introdujeron, y fallan con los nuevos. Cada una guarda un archivo de línea base:

| Comprobación | Línea base | Se reescribe con |
| --- | --- | --- |
| `make phpstan` | `tools/phpstan/phpstan-baseline.neon` | `make phpstan-baseline` |
| `make raw-input` | `tools/checks/raw-input-baseline.txt` (número de lecturas directas por archivo) | `tools/checks/check-raw-input.sh --update` |
| `make inline-js` | `tools/checks/inline-js-baseline.txt` (número de líneas con JavaScript en línea por archivo) | `tools/checks/check-inline-js.sh --update` |

Un recuento puede bajar, nunca subir. El código nuevo no debe sumar a ninguno:
lea los datos de entrada con `dPgetParam()` y sus parientes, y ponga el
JavaScript en archivos `.js` (consulte [Escribir código seguro](secure-code.md)).
Reescriba una línea base solo después de haber eliminado problemas, para que
el recuento más bajo pase a ser el nuevo límite, y haga commit de ella junto
con el cambio.

PHPStan también falla cuando un hallazgo anotado en su línea base ya no
aparece, así que una corrección que elimina un hallazgo debe ir acompañada de
`make phpstan-baseline`. La imagen de PHPStan está fijada por su digest en el
`Makefile`: una versión nueva de PHPStan informa de hallazgos distintos, así que
actualice el digest y la línea base a la vez.

`tools/checks/check-inline-js.sh --list modules/<module>` lista las líneas con
JavaScript en línea que quedan en una carpeta.

## Integración continua { #continuous-integration }

GitHub ejecuta tres flujos de trabajo, en `.github/workflows/`:

| Flujo de trabajo | Cuándo | Trabajos |
| --- | --- | --- |
| `ci.yml` (CI) | Cada push a `devel` o `master` y cada pull request | Un trabajo por comprobación: `make lint`, `phpstan`, `raw-input`, `inline-js`, `locales`, `theme-contrast`, `php-test`, `vendor-check`, `js-test`, `audit-smoke` y `audit-full`. |
| `deps.yml` (Dependency audit) | Los push, los pull requests y cada lunes | `make deps-audit`. |
| `docs.yml` (Docs) | Los push y los pull requests que cambian `website/` | Genera los dos sitios de la documentación; en `master`, los publica en www.dotpulse.org. |

En un pull request, `audit-full` comprueba la maquetación en dos temas,
`dotpulse` y `dp-grey-theme`; en un push a `devel` o `master`, en todos. Sus
informes se guardan siete días como el artefacto `audit-reports` de la
ejecución. Cuando falla `audit-smoke` o `audit-full`, el trabajo muestra el
final del registro del servidor web.

Dependabot propone actualizaciones de los paquetes de Composer, de las
herramientas de Node.js y de las acciones de GitHub, contra `devel`. `vendor/`
está en el repositorio y Dependabot no lo actualiza: antes de fusionar uno de
sus pull requests de Composer, ejecute `composer install --no-dev` en la rama y
haga commit de `vendor/`, o fallará `vendor-check`.

## El sitio web de la documentación { #the-documentation-website }

Las páginas son Markdown en `website/docs/` (inglés) y `website/docs-es/`
(español), y se generan con Zensical a partir de `website/mkdocs.yml` y
`website/mkdocs.es.yml`. El módulo de ayuda muestra las mismas páginas dentro
de dotPulse y toma su lista de páginas del `nav` de esos archivos.

```sh
make docs            # genera los dos sitios en website/site/; falla con enlaces rotos e imágenes que faltan
make docs-serve      # vista previa del sitio en inglés en http://127.0.0.1:8000
make docs-serve-es   # vista previa del sitio en español en http://127.0.0.1:8001
```

El sitio en español tiene las mismas páginas, los mismos nombres de archivo y
los mismos nombres de imagen que el sitio en inglés, y cada título en español
conserva el ancla en inglés: `## Añadir un usuario { #add-a-user }`. Las
etiquetas de las páginas en español son las de la interfaz en español
(`locales/es/`). Cuando cambie una página en inglés, cambie también la de
español.

### Capturas de pantalla { #screenshots }

`website/screenshots.json` lista todas las imágenes: la página, el usuario, el
recorte y los clics previos. `make docs-screenshots` las toma todas desde el
entorno de pruebas en `website/docs/img/` con `tools/browser/screenshots.js`, y
las comprime con `pngquant` si está instalado. Necesita `playwright-core`
(`cd tools/browser && npm ci`) y Chrome. Para volver a tomar algunas:

```sh
node tools/browser/screenshots.js website/screenshots.json website/docs/img <name> ...
```

Antes, el entorno de pruebas necesita los datos de ejemplo y algo de
preparación (módulos complementarios instalados y visibles, algunos archivos y
enlaces); `website/README.md` lista los pasos, y una captura con una `note` en
la lista indica qué más necesita.

Para el sitio en español, ponga el entorno de pruebas en español y ejecútelo
con `SHOT_LANG=es` hacia `website/docs-es/img`: los textos en los que hacen clic
los pasos se buscan en `locales/es/`, así que la misma lista sirve para los dos
sitios.

## Ensayo de actualización { #upgrade-rehearsal }

Antes de actualizar un servidor, puede probar la actualización con una copia de
su base de datos:

```sh
mysqldump --single-transaction <database> | gzip > prod.sql.gz   # en el servidor
make rehearse DUMP=prod.sql.gz ADMIN=<an administrator's login>
```

`docker/rehearse.sh` sustituye la base de datos del entorno de pruebas por el
volcado y renombra las tablas con el prefijo `dotp_`. Hace que la copia sea
segura de usar: acceso con SQL en lugar de LDAP, el correo enviado a un
servidor que no existe y la contraseña de `ADMIN` cambiada a `passwd`. Después
ejecuta la actualización del instalador, actualiza todos los módulos cuyo
código es más reciente que la base de datos y recorre el sitio como `ADMIN`.
Falla con un error del instalador, un error de SQL o un problema de PHP en el
registro, o una página con un error, y avisa de los módulos que están en la
base de datos pero cuyo código ya no existe.

Después, el entorno de pruebas conserva la copia actualizada, así que puede
echar un vistazo en <http://127.0.0.1:8089>; `make sample-data` la sustituye.
Los archivos subidos no están en el volcado, así que las descargas fallan. El
volcado contiene datos reales: manténgalo fuera del repositorio y bórrelo
cuando termine.
