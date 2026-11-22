# Tech stack y convenciones

## Tecnologías

- **Lenguaje:** PHP 8.4 (imagen `php:8.4-cli`, imagen construida en `docker/php/Dockerfile`).
- **Framework / runtime:** Symfony 8.1 (Flex, `8.1.*`) con Twig 3 y asset-mapper + Stimulus/Turbo (UX). Servidor de desarrollo: Symfony CLI embebido en el contenedor `php`, puerto `8000` (con `--allow-all-ip`).
- **UI:** Bootstrap 5.3.9 (+ `@popperjs/core` 2.11.8), instalado y servido con `importmap:require` (archivos en `assets/vendor/...`, **sin CDN**); íconos y spinners con `symfony/ux-icons` (`^3.7`, packs locales `bi:*` y `svg-spinners:*`, además de cualquier SVG propio) servidos inline desde `assets/icons/` con `iconify.on_demand: false` (**sin CDN ni peticiones externas en runtime**).
- **Base de datos:** MySQL 8.4 (contenedor `mysql`, servicio `mysql`), base `calplan_db` con usuario dedicado para la app. Doctrine ORM 3.7 + Doctrine Migrations. Conexión desde la app vía `mysql` (nombre del servicio en la red Docker).
- **Tests:** PHPUnit 13.4 (+ BrowserKit / css-selector) configurado en `phpunit.dist.xml` con cobertura en `tests/` (`Controller/`, `Icons/`, `Ui/`). Cada feature debe cubrir sus pruebas (WebTestCase / KernelTestCase) antes de darla por terminada.
- **Convenciones de pruebas (gotchas frecuentes):**
  - Entorno de tests: base MySQL `calplan_db_test` (creada con `doctrine:database:create --if-not-exists --env=test`) y usuario `calplan_test` (privilegios solo sobre esa base) — credenciales en `app_calplan/.env.test.local`, **git-ignorado**.
  - En `WebTestCase`: llama siempre primero a `static::createClient()`; use `static::getContainer()` después de eso, nunca al revés.
  - Para asertar estados de entidades que cambian **durante** las peticiones HTTP, aserta sobre la conexión DBAL (`connection->fetchOne('SELECT ...')`), no con `EntityManager::find()` (su identity map no refleja los cambios entre requests).
  - Para tokens CSRF de aterrizaje/cuentas: lee el `_token` oculto del HTML renderizado (o via el `KernelBrowser`) — no uses el `security.csrf.token_manager` fuera de una petición.
- **Despliegue:** Sin despliegue público — entorno local de desarrollo personal en Docker (puertos publicados en `0.0.0.0` para la red local). No hay pipeline ni hosting definido deliberadamente todavía.

## Archivos / módulos clave

- `docker-compose.yml` y `docker/php/Dockerfile` — contexto del entorno; declara servicios `php` (App) y `mysql` (datos).
- `app_calplan/` — aplicación Symfony del proyecto.
- `app_calplan/src/Kernel.php`, `src/Controller/`, `src/Entity/`, `src/Repository/` — código del dominio/controladores (PSR-4 `App\`).
- `app_calplan/config/packages/` y `config/routes*` — configuración por bundle y routing (mensajes, framework, security, doctrine...).
- `app_calplan/templates/` — vistas Twig.
- `app_calplan/assets/` — frontend con asset-mapper (JS/CSS/Stimulus; sin bundler npm externo) e íconos SVG locales versionados en `assets/icons/` (UX Icons).
- `app_calplan/migrations/` — migraciones Doctrine (esquema solo por migraciones).
- `app_calplan/public/index.php` — front controller.
- `.env` raíz (git-ignored, `PROJECT_NAME`, `APP_DIR`, `MYSQL_*`) · `app_calplan/.env` (versionado, defaults) · `app_calplan/.env.local` (git-ignored, secretos reales, p. ej. `DATABASE_URL`).

## Comandos

Todos vía Docker (no hay PHP/Composer/MySQL en el host). Sustituir `<PROJECT_NAME>` (`calendario_planeacion`) y `<APP_DIR>` (`app_calplan`):

- Levantar entorno: `docker compose up -d`
- Servidor dev: `docker exec -d -w /var/www/html/app_calplan <PROJECT_NAME>-php-1 symfony server:start --allow-all-ip --port=8000 -d` (detener: `server:stop`)
- Composer: `docker compose exec -w /var/www/html/app_calplan php composer <comando>`
- Consola / SQL: `docker exec -w /var/www/html/app_calplan <PROJECT_NAME>-php-1 php bin/console <comando>` (p. ej. `dbal:run-sql "SELECT 1"`)
- Tests: `docker compose exec -w /var/www/html/app_calplan php php bin/phpunit` (requiere la base `calplan_db_test`: `doctrine:database:create --if-not-exists --env=test`)
- Instalar paquetes JS/CSS con importmap (auto-hospedados): `php bin/console importmap:require <paquete>`
- Importar íconos/spinners locales (UX Icons): `php bin/console ux:icons:import bi:<nombre>` o `svg-spinners:<nombre>` (o `php bin/console ux:icons:lock` para bajar todos los referenciados en Twig) — commitear los `.svg` generados en `assets/icons/`.
- Lint: no hay php-cs-fixer/linting configurado aún; validación con `lint:container`, `lint:twig templates/`, `lint:yaml config/` y revisión manual según las reglas de estilo de abajo.
- Migraciones: `php bin/console make:migration` → `doctrine:migrations:migrate` (nunca `doctrine:schema:update`).

## Modelo de datos / dominio

El modelo del dominio está documentado en [dominio.md](dominio.md). Entidades actuales en `src/Entity/`: `User`, `Sucursal`, `Rol`, `Personal` y `PersonalRol` (esquema gestionado exclusivamente vía migraciones en `migrations/`).

## Convenciones

- PHP 8.4 / Symfony 8.1 (`extra.symfony.require: 8.1.*`, no instalar contribuciones fuera del canónico `8.1.*` sin revisar).
- Controladores delgados; lógica de negocio en servicios; autowiring con type-hints + `#[Autowire]`/`#[Target]` si hace falta; nada de YAML services salvo necesidad real.
- Atributos PHP como metadatos: `#[Route]`, `#[IsGranted]`, `#[AsCommand]`, `#[AsEventListener]`, `#[Assert\...]`, `#[MapRequestPayload]`/`#[MapQueryString]` para bindear y validar payloads (con Serializer+Validator).
- Twig en todo el HTML server-rendered; navegación con `path()`/`url()`; Stimulus solo para interactividad ligera.
- Validación de entrada en el borde (formularios/Constraints), nunca confiar en el cliente; errores de dominio → flash + redirect.
- Tests en `tests/` con sufijo `*Test.php`, namespace `App\Tests\`; WebTestCase para HTTP, KernelTestCase para servicios.
- Idiomas: identificadores y código en inglés; contenido visible al usuario, documentación y mensajes de dominio en español.
- Assets: asset-mapper/importmap; no añadir bundlers npm externos por ahora. Íconos y spinners vía `{{ ux_icon('bi:...') }}` / `{{ ux_icon('svg-spinners:...') }}` importados localmente en `assets/icons/` (nunca `iconify.on_demand: true`).
- Commits: mensajes breves y concretos en español; LF en los archivos (`.gitattributes`, `.editorconfig`).

## Estilo visual

- **Botones (homologados):** todos los botones de la aplicación llevan `m-2` (margen) y `p-2` (padding). En fondos claros se usa `btn btn-dark`; en fondos oscuros (navbar `bg-dark`) se usa `btn btn-light btn-sm` o `btn btn-outline-light btn-sm`. Nunca `btn-dark` sobre fondo oscuro. Los modificadores de tamaño (`btn-sm`, `btn-lg`, `w-100`) se añaden según el contexto.
- **Indicador de carga global (homologado):** overlay centrado flotante sin cuadro ni borde (`.indicador-carga-overlay` en `templates/base.html.twig`) usando el spinner SVG local `svg-spinners:blocks-shuffle-3` tras `120 ms` en navegaciones reales (`turbo:visit`) y envíos de formulario (`turbo:submit-start`, ignorando prefetches de Turbo 8), más spinner inline y bloqueo del botón pulsado en formularios vía `assets/controllers/carga_controller.js`.
- Pendiente: paleta/tokens de color por trabajador para la tabla semanal, tipografía y reglas responsive de la tabla.
- **Formularios (homologado):** cada campo o row en un div `mb-1` (o su wrapper del `form_row`); donde falte espacio, agregar `m-1` en `label_attr`/`attr`; inputs y labels con margen pequeño (`m-1`/`mb-1`).

## Límites duros

- No tocar sin confirmación explícita del dueño: puertos de `docker-compose.yml` (`0.0.0.0` a propósito), credenciales, `docker/php/php.ini`, `docker compose down -v`.
- Esquema de BD solo por migraciones; prohibido `doctrine:schema:update`.
- `app_calplan/.env` versionado = solo defaults; secretos reales en `.env.local` (git-ignored) o vault; nunca credenciales en el repo (el `.gitattributes`/renormalize mantienen LF; revisar igualmente que no se committee nada sensible).
- Sin deps nuevas sin avisar; preferir lo cubierto por Symfony/Flex o el stack ya instalado antes que paquetes terceros.
- No borrar archivos generados por recipes ni reescribir `config/packages` sin entender el recipe.
- **Nada externo en frontend:** JS/CSS/fuentes/íconos/imágenes se sirven siempre desde el proyecto (AssetMapper/importmap o `public/`); ninguna página referencia CDN ni servicios de terceros. (Los enlaces de documentación en comentarios YAML no descargan nada; no aplican.)
- Commits en español, imperativos, un commit por cambio lógico; no commitear secretos.
