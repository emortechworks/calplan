# Tech stack y convenciones

_Cómo está construido el proyecto y las reglas que todo el código debe respetar. Es la referencia técnica que ningún plan de feature debería contradecir._

## Tecnologías

- **Lenguaje:** PHP 8.4 (imagen `php:8.4-cli`, imagen construida en `docker/php/Dockerfile`).
- **Framework / runtime:** Symfony 8.1 (Flex, `8.1.*`) con Twig 3 y asset-mapper + Stimulus/Turbo (UX). Servidor de desarrollo: Symfony CLI embebido en el contenedor `php`, puerto `8000` (con `--allow-all-ip`).
- **Base de datos:** MySQL 8.4 (contenedor `mysql`, servicio `mysql`), base `calplan_db` con usuario dedicado para la app. Doctrine ORM 3.7 + Doctrine Migrations. Conexión desde la app vía `mysql` (nombre del servicio en la red Docker).
- **Tests:** PHPUnit 13.4 (+ BrowserKit / css-selector) configurado en `phpunit.dist.xml`; `tests/` existe pero está vacío — aún no hay casos. Cada feature debe cubrir sus pruebas (WebTestCase / KernelTestCase) antes de darla por terminada.
- **Despliegue:** Sin despliegue público — entorno local de desarrollo personal en Docker (puertos publicados en `0.0.0.0` para la red local). No hay pipeline ni hosting definido deliberadamente todavía.

## Archivos / módulos clave

- `docker-compose.yml` y `docker/php/Dockerfile` — contexto del entorno; declara servicios `php` (App) y `mysql` (datos).
- `app_calplan/` — aplicación Symfony del proyecto.
- `app_calplan/src/Kernel.php`, `src/Controller/`, `src/Entity/`, `src/Repository/` — código del dominio/controladores (PSR-4 `App\`).
- `app_calplan/config/packages/` y `config/routes*` — configuración por bundle y routing (mensajes, framework, security, doctrine...).
- `app_calplan/templates/` — vistas Twig.
- `app_calplan/assets/` — frontend con asset-mapper (JS/CSS/Stimulus; sin bundler npm externo).
- `app_calplan/migrations/` — migraciones Doctrine (esquema solo por migraciones).
- `app_calplan/public/index.php` — front controller.
- `.env` raíz (git-ignored, `PROJECT_NAME`, `APP_DIR`, `MYSQL_*`) · `app_calplan/.env` (versionado, defaults) · `app_calplan/.env.local` (git-ignored, secretos reales, p. ej. `DATABASE_URL`).

## Comandos

Todos vía Docker (no hay PHP/Composer/MySQL en el host). Sustituir `<PROJECT_NAME>` (`calendario_planeacion`) y `<APP_DIR>` (`app_calplan`):

- Levantar entorno: `docker compose up -d`
- Servidor dev: `docker exec -d -w /var/www/html/app_calplan <PROJECT_NAME>-php-1 symfony server:start --allow-all-ip --port=8000 -d` (detener: `server:stop`)
- Composer: `docker compose exec -w /var/www/html/app_calplan php composer <comando>`
- Consola / SQL: `docker exec -w /var/www/html/app_calplan <PROJECT_NAME>-php-1 php bin/console <comando>` (p. ej. `dbal:run-sql "SELECT 1"`)
- Tests: `docker compose exec -w /var/www/html/app_calplan php bin/phpunit`
- Lint: no hay php-cs-fixer/linting configurado aún; validación manual según las reglas de estilo de abajo.
- Migraciones: `php bin/console make:migration` → `doctrine:migrations:migrate` (nunca `doctrine:schema:update`).

## Modelo de datos / dominio

_Aún no hay entidades ni esquema (`src/Entity/` vacío; `migrations/` vacía)._ No documentar campos todavía; el modelo se define junto con la primera feature de persistencia, vía migraciones.

Candidatos en bruto de la misión (no confirmados, solo orientación): `Trabajador` (nombre, área/rol, contrato), `AsignacionDia` (fecha, trabajador, tipo de día: trabajo / descanso semanal / vacaciones / incapacidad / curso / cumpleaños / repuesto / etc.), `PeticionMensual` (propuestas con antelación, vinculadas al mes y trabajador). Se introducirán uno a uno vía `make:migration`.

## Convenciones

- PHP 8.4 / Symfony 8.1 (`extra.symfony.require: 8.1.*`, no instalar contribuciones fuera del canónico `8.1.*` sin revisar).
- Controladores delgados; lógica de negocio en servicios; autowiring con type-hints + `#[Autowire]`/`#[Target]` si hace falta; nada de YAML services salvo necesidad real.
- Atributos PHP como metadatos: `#[Route]`, `#[IsGranted]`, `#[AsCommand]`, `#[AsEventListener]`, `#[Assert\...]`, `#[MapRequestPayload]`/`#[MapQueryString]` para bindear y validar payloads (con Serializer+Validator).
- Twig en todo el HTML server-rendered; navegación con `path()`/`url()`; Stimulus solo para interactividad ligera.
- Validación de entrada en el borde (formularios/Constraints), nunca confiar en el cliente; errores de dominio → flash + redirect.
- Tests en `tests/` con sufijo `*Test.php`, namespace `App\Tests\`; WebTestCase para HTTP, KernelTestCase para servicios.
- Idiomas: identificadores y código en inglés; contenido visible al usuario, documentación y mensajes de dominio en español.
- Assets: asset-mapper/importmap; no añadir bundlers npm externos por ahora.
- Commits: mensajes breves y concretos en español; LF en los archivos (`.gitattributes`, `.editorconfig`).

## Estilo visual

Sin definir. El objetivo final (misión) es una tabla mensual con colores distintos por trabajador. Cuando exista la UI se fijará: paleta/tokens por trabajador, tipografía, reglas responsive de la tabla. Hasta entonces no se fija nada en este documento.

## Límites duros

- No tocar sin confirmación explícita del dueño: puertos de `docker-compose.yml` (`0.0.0.0` a propósito), credenciales, `docker/php/php.ini`, `docker compose down -v`.
- Esquema de BD solo por migraciones; prohibido `doctrine:schema:update`.
- `app_calplan/.env` versionado = solo defaults; secretos reales en `.env.local` (git-ignored) o vault; nunca credenciales en el repo (el `.gitattributes`/renormalize mantienen LF; revisar igualmente que no se committee nada sensible).
- Sin deps nuevas sin avisar; preferir lo cubierto por Symfony/Flex o el stack ya instalado antes que paquetes terceros.
- No borrar archivos generados por recipes ni reescribir `config/packages` sin entender el recipe.
- Commits en español, imperativos, un commit por cambio lógico; no commitear secretos.
