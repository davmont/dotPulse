# Guía para desarrolladores { #developer-guide }

Esta sección es para las personas que modifican el propio dotPulse o escriben
módulos complementarios para él. Explica cómo está organizado el código, cómo
llega una petición de página a un módulo, cómo preparar una copia de desarrollo
y ejecutar las comprobaciones, y qué debe hacer el código para ser seguro.

Necesita saber PHP y SQL. dotPulse funciona con PHP 8.2 o posterior y con MySQL
o MariaDB. La mayor parte del código viene de dotProject: archivos PHP sencillos
que incluye un único controlador frontal, unas pocas clases y ningún framework.
Los nombres que empiezan por `dP` (`dPgetParam()`, `$dPconfig`, `DP_BASE_DIR`)
vienen de dotProject y se conservan, para que los módulos complementarios
existentes sigan funcionando.

## Cómo está organizado el código { #how-the-code-is-laid-out }

| Ruta | Qué contiene |
| --- | --- |
| `index.php` | El controlador frontal: todas las páginas de la aplicación pasan por él. |
| `api.php` | La API REST. Consulte [API REST](api.md). |
| `fileviewer.php` | Las descargas de los archivos subidos. |
| `csp-report.php` | Recibe los informes de infracciones de la Content-Security-Policy que envía el navegador. |
| `queuescanner.php` | Ejecuta los trabajos que esperan en la cola de eventos, como el correo en cola; ejecútelo desde cron. |
| `base.php` | Define `DP_BASE_DIR` y `DP_BASE_URL`. `index.php`, `api.php`, `fileviewer.php` y `queuescanner.php` lo incluyen en primer lugar. |
| `classes/` | Las clases del núcleo: `CAppUI` (la sesión del usuario, los mensajes, la traducción; `ui.class.php`), `CDpObject` (un registro de una tabla; `dp.class.php`), `DBQuery` (el constructor de consultas; `query.class.php`), los permisos, las fechas, el registro de auditoría, el inicio de sesión en dos pasos y el lector de Markdown del módulo de ayuda. |
| `includes/` | Funciones compartidas (`main_functions.php`: `dPgetParam()`, las funciones de escapado), la conexión a la base de datos, las sesiones y `config-dist.php` (la plantilla de `config.php`). |
| `functions/` | Funciones auxiliares de algunos módulos del núcleo, `<module>_func.php`, que se cargan en las páginas de ese módulo. |
| `modules/` | Una carpeta por módulo, tanto del núcleo como complementos: empresas, proyectos, tareas, administración del sistema, los calendarios de festivos... Consulte [Escribir un módulo](modules.md). |
| `locales/` | Las traducciones, una carpeta por idioma (`en`, `es` y 23 más). Los módulos complementarios pueden guardar las suyas en `modules/<module>/locales/`. |
| `style/` | Los temas (`dotpulse`, `facet`, `linen`, `material` y los clásicos). Cada uno tiene su propio `header.php`, `footer.php`, `login.php`, su hoja de estilos y su `theme.json`; el `dark.css` de un tema es su paleta oscura, y `_shared/` contiene lo que comparten los temas basados en dotPulse. |
| `js/` | El JavaScript que se carga en todas las páginas (`dp-ui.js`, `dp-actions.js`, los scripts del Gantt y del tablero). |
| `lib/` | Bibliotecas de terceros copiadas a mano: ADOdb, phpGACL, jQuery, flatpickr, Chart.js... |
| `vendor/` | Bibliotecas instaladas con Composer (PHPMailer, Dompdf, php-qrcode, Parsedown). Está en el repositorio, así que un servidor no necesita Composer. |
| `images/` | Los iconos y los archivos de la marca. |
| `files/` | Los archivos subidos y los datos temporales. El servidor web debe poder escribir en esta carpeta. |
| `install/` | El instalador y las actualizaciones de la base de datos que ejecuta. |
| `db/` | El esquema de la base de datos (`dotproject.sql`), los scripts de actualización y los datos de ejemplo (`db/sample/`). |
| `misc/` | La herramienta de depuración que incluye `index.php`. |
| `website/` | Esta documentación. El módulo de ayuda muestra las mismas páginas dentro de dotPulse, así que un servidor conserva `website/docs` y `website/docs-es`. |
| `docs/` | Notas para administradores y mantenedores: la Content-Security-Policy, la puesta en producción, la API, las notas de versión y el registro de la auditoría de seguridad. |
| `tools/` | Herramientas de desarrollo: la configuración de PHPStan, las comprobaciones del código, la comprobación de las traducciones, las pruebas de JavaScript y las comprobaciones en el navegador. |
| `tests/` | Las pruebas unitarias de PHP. |
| `docker/` | El entorno de pruebas (*sandbox*) de desarrollo y los scripts de prueba que se ejecutan contra él. |

Un servidor necesita los puntos de entrada de la raíz, las carpetas de la
aplicación (de `classes/` a `db/` en la tabla, y `misc/`) y `website/` para la
ayuda. `tools/`, `tests/`, `docker/`, el `Makefile` y `.github/` solo sirven
para el desarrollo: `.gitattributes` los deja fuera de los archivos de versión
creados con `git archive`. Node.js y Python solo los necesitan las herramientas
de desarrollo.

### lib/ y vendor/ { #lib-and-vendor }

Las dos contienen código de terceros, pero se mantienen al día de forma
distinta. `vendor/` sigue `composer.json` y `composer.lock`: Dependabot propone
las actualizaciones y `make deps-audit` busca sus paquetes en las bases de datos
de avisos de seguridad. Las bibliotecas de `lib/` (y algunas copiadas dentro de
módulos) se copiaron a mano: nada avisa de sus vulnerabilidades. Su lista, con
las versiones, está en
[docs/third-party.md](https://github.com/davmont/dotPulse/blob/devel/docs/third-party.md).

## El recorrido de una petición { #how-a-request-flows }

Todas las páginas de la aplicación son `index.php` con dos parámetros: `m`, el
módulo, y `a`, la acción, que es el nombre de un archivo PHP de la carpeta del
módulo. `index.php?m=projects&a=view&project_id=1` ejecuta
`modules/projects/view.php`. Por orden, `index.php`:

1. Incluye `base.php`, `includes/config.php`, las funciones compartidas, la
   conexión a la base de datos, `CAppUI` y los permisos, e inicia la sesión
   (`dPsessionStart()`). La sesión guarda un objeto `CAppUI`, disponible en
   todos los archivos como `$AppUI`.
2. Envía las cabeceras de seguridad, entre ellas la Content-Security-Policy
   (`dPsendCspHeader()`).
3. Atiende las peticiones que funcionan sin iniciar sesión: la contraseña
   olvidada, el enlace para restablecer la contraseña y el propio formulario de
   acceso. El inicio de sesión tiene un límite de intentos y queda anotado en
   el registro de auditoría; con el inicio de sesión en dos pasos, la sesión
   espera el código antes de contar como iniciada.
4. Muestra la página de acceso y se detiene si el usuario no ha iniciado sesión.
5. Lee `m` y `a`. Los dos pasan por `$AppUI->checkFileName()`, que rechaza un
   nombre con un punto, una barra, una comilla, un paréntesis, `;` o `$`, así
   que solo pueden nombrar un archivo de la carpeta del módulo. Sin `m`, la
   página es la vista predeterminada elegida en Administración del Sistema o,
   si no la hay, el primer módulo activo al que el usuario puede acceder. Sin
   `a`, es `index`. Un tercer parámetro, `u`, nombra una subcarpeta del módulo.
6. Reúne las pestañas que otros módulos añaden a las páginas de este módulo
   (vea más abajo).
7. Carga las traducciones y, si existe, `functions/<m>_func.php`.
8. Lee los derechos del usuario sobre el módulo en `$canAccess`, `$canRead`,
   `$canEdit`, `$canAuthor` y `$canDelete`. Sin acceso, o cuando el módulo está
   desactivado, el usuario va a la página *Acceso Denegado*.
9. Incluye la clase del módulo, `modules/<m>/<m>.class.php`, si existe.
10. Comprueba el token CSRF de todas las peticiones POST.
11. Ejecuta el manejador de escritura que indica `dosql`, si lo hay (vea más
    abajo).
12. Incluye el `header.php` del tema, después `modules/<m>/<a>.php` y después
    el `footer.php` del tema.

### Manejadores de escritura: dosql { #write-handlers-dosql }

Un formulario que guarda datos se envía a su módulo con un campo `dosql` que
nombra el manejador, un archivo de la carpeta del módulo. Por convención se
llama `do_<thing>_aed.php` (*add, edit, delete*: añadir, editar, borrar), por
ejemplo `modules/links/do_link_aed.php`. `index.php` lo ejecuta antes de enviar
nada, y solo cuando:

- la petición es POST y lleva el token CSRF de la sesión;
- el usuario puede borrar en el módulo, si el formulario envía `del=1`, o si no,
  puede añadir o editar en él.

El propio manejador comprueba los derechos sobre el registro concreto, guarda,
deja un mensaje con `$AppUI->setMsg()` y redirige. Una vista cuyo nombre
empieza por `do_`, `dosql` o `domodsql` (`index.php?m=system&a=domodsql`) se
trata también como manejador de escritura: se aplican las mismas reglas de
POST, token y permisos.

### Páginas sin el tema: suppressHeaders { #pages-without-the-theme-suppressheaders }

Con `suppressHeaders=1` en la URL, `index.php` ejecuta el archivo del módulo sin
la cabecera ni el pie del tema, y sin la cabecera `Content-Type`. Lo usan las
páginas que envían una descarga, una imagen o JSON, y ponen sus propias
cabeceras. Una página así no recibe nada del JavaScript que carga la cabecera.

### Pestañas de otros módulos { #tabs-from-other-modules }

Un módulo añade una pestaña a las páginas de otro módulo con un archivo que
lleva el nombre de ese módulo: `<module>_tab.<name>.php` añade una pestaña a la
página principal con pestañas del módulo (la página de un proyecto o de una
empresa, por ejemplo), y `<module>_tab.<action>.<name>.php` a la página de esa
acción. El módulo de enlaces, por ejemplo, tiene `projects_tab.links.php` (una
pestaña **Enlaces** en un proyecto) y `tasks_tab.view.links.php` (una pestaña
**Enlaces** en la página de una tarea). La etiqueta de la pestaña es `<name>`,
con los guiones bajos cambiados por espacios y la primera letra en mayúscula.
La página que muestra las pestañas las añade con `$tabBox->loadExtras($m)` o
`$tabBox->loadExtras($m, $a)`; las pestañas de un módulo desactivado, o de uno
al que el usuario no puede acceder, se dejan fuera. `dPmoduleTabs($module)`
construye la lista, una vez por petición.

### JavaScript { #javascript }

Todas las páginas cargan todos los archivos `.js` de `js/` y después, si
existen, `modules/<m>/<m>.module.js` y `modules/<m>/<a>.js` (`CAppUI::loadJS()`,
que llama la cabecera del tema). Por eso el script de un módulo para una página
no necesita etiqueta `<script>`: basta con darle el nombre de la página. La
Content-Security-Policy bloquea los scripts en línea y los atributos
`onclick=`; consulte [Escribir código seguro](secure-code.md).

## Ramas y contribuciones { #branches-and-contributions }

Todo el trabajo se hace en la rama `devel`, y todos los pull requests van a
`devel`. `master` solo recibe las versiones publicadas: cuando sale una versión,
`devel` se fusiona en `master`, y el sitio web de la documentación se publica
desde ella.

Un pull request debe pasar las comprobaciones de los flujos de trabajo de GitHub
de `.github/workflows/`:

| Flujo de trabajo | Trabajo | Qué comprueba |
| --- | --- | --- |
| CI | PHP 8.3 syntax | Ningún archivo PHP tiene un error de sintaxis. |
| CI | PHPStan (level 2, baseline) | Ningún hallazgo nuevo del análisis estático. |
| CI | No new raw $_GET/$_POST reads | Los datos de la petición se leen con `dPgetParam()` y sus parientes. |
| CI | No new inline JavaScript (CSP) | Ningún `onclick=`, `javascript:` ni `<script>` en línea nuevo. |
| CI | Translations (English, Spanish) | Cada texto que traduce el código tiene una entrada en inglés y otra en español. |
| CI | Theme colour contrast (WCAG AA) | Los colores de los temas de dotPulse se leen bien. |
| CI | PHP unit tests (tests/) | Pasan las pruebas de PHP. |
| CI | vendor/ matches composer.lock (PHP 8.2) | `vendor/` contiene lo que dice `composer.lock` y se carga con PHP 8.2. |
| CI | JavaScript tests (Jest) | Pasan las pruebas de los scripts de `js/`. |
| CI | Security smoke test (docker/) | Pasan las comprobaciones de seguridad y las pruebas de funciones del entorno de pruebas. |
| CI | Module install/use/remove, SQL errors, PHP warnings, layout (docker/tests) | La auditoría completa: todos los módulos se instalan, se usan y se eliminan sin errores, y la maquetación en los temas. |
| Dependency audit | composer audit, npm audit | Ninguna vulnerabilidad conocida en las dependencias. |
| Docs | Build the documentation website | Los dos sitios se generan sin enlaces rotos ni imágenes que falten. Solo se ejecuta cuando cambia `website/`. |

Puede ejecutar cada una en su propio ordenador antes de hacer push: consulte
[Entorno de desarrollo](environment.md#make-targets).

Cuando cambie algo que ven los usuarios, cambie la documentación en el mismo
pull request: la página en inglés de `website/docs/` y la página en español de
`website/docs-es/`.

## Licencia { #licence }

El código es software libre con la GNU General Public License, versión 2 o (a
su elección) cualquier versión posterior (GPL-2.0-or-later): consulte `COPYING`
y `LICENSE` en el repositorio. El módulo ticketsmith tiene la Voxel Public
License, y las bibliotecas de `lib/` y `vendor/` conservan sus propias
licencias. Las contribuciones al código se publican con la GPL.

Esta documentación, texto y capturas de pantalla, tiene la licencia
[Creative Commons Reconocimiento-CompartirIgual 4.0](https://creativecommons.org/licenses/by-sa/4.0/).
Los ejemplos de código que contiene tienen la licencia del código,
GPL-2.0-or-later, para que se puedan copiar en el programa.

## Páginas de esta sección { #pages-in-this-section }

- [Guía para desarrolladores](index.md): esta página.
- [Entorno de desarrollo](environment.md): el entorno de pruebas, las comprobaciones y las pruebas.
- [Escribir un módulo](modules.md): los archivos de un módulo, su configuración, su instalación y su actualización.
- [Escribir código seguro](secure-code.md): los datos de entrada, la salida, los permisos, CSRF y la Content-Security-Policy.
- [API REST](api.md): los endpoints y sus tokens.
