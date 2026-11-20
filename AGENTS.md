# AGENTS.md — Proyecto Symfony

> **Variables de proyecto (fuente de verdad):** definidas en el `.env` raíz (junto a `docker-compose.yml`).
> - `PROJECT_NAME`: nombre del proyecto Docker Compose (prefijo de contenedores, red, volumen e imagen).
> - `APP_DIR`: carpeta de la aplicación Symfony dentro del repositorio (y en `/var/www/html/<APP_DIR>` dentro del contenedor).
> - `MYSQL_*`: credenciales/base usadas por el servicio `mysql` (`docker-compose.yml` las consume como `${...}`).
>
> `docker-compose.yml` usa `name: ${PROJECT_NAME}`. Sustituye `<PROJECT_NAME>` y `<APP_DIR>` en los comandos según los valores actuales del `.env` raíz.

Contexto y reglas para agentes. Las convenciones de Symfony **no van acá**: están en
`<APP_DIR>/AGENTS.md` y se cargan solas al explorar esa carpeta. No duplicarlas.

## Qué es

## Stack

| Componente | Detalle |
|---|---|
| PHP | 8.4.27 (parchado CVE-2026-109618/109619) |
| Symfony | 8.1 · `<APP_DIR>/` · webapp-pack |
| MySQL | 8.4 · servicio `mysql` · base según `MYSQL_DATABASE` |
| Orquestación | Docker Compose · Symfony CLI **dentro** del contenedor |

## Comandos — siempre vía Docker (no hay PHP/Composer en el host)

Nombres derivados de `PROJECT_NAME` (p. ej. con `nombre_proyecto_symfony`): contenedores `<PROJECT_NAME>-php-1`, `<PROJECT_NAME>-mysql-1`.

> En una **plantilla recién clonada no existe la carpeta de la app**: los comandos
> con `-w /var/www/html/<APP_DIR>` fallan hasta crear el proyecto Symfony
> (ver README, "Puesta en marcha").

```powershell
# Dev server (no arranca con docker compose up; lanzarlo a mano)
docker exec -d -w /var/www/html/<APP_DIR> <PROJECT_NAME>-php-1 symfony server:start --allow-all-ip --port=8000 -d
docker exec -w /var/www/html/<APP_DIR> <PROJECT_NAME>-php-1 symfony server:stop
docker exec -w /var/www/html/<APP_DIR> <PROJECT_NAME>-php-1 symfony server:status

# Composer — el -w es obligatorio (sin él: "no composer.json")
docker compose run --rm -w /var/www/html/<APP_DIR> php composer require <paquete>
docker compose exec -w /var/www/html/<APP_DIR> php composer install
docker compose exec -T -w /var/www/html/<APP_DIR> php composer audit

# Consola / SQL
docker exec -w /var/www/html/<APP_DIR> <PROJECT_NAME>-php-1 php bin/console <comando>
docker exec -w /var/www/html/<APP_DIR> <PROJECT_NAME>-php-1 php bin/console dbal:run-sql "SELECT 1"

# MySQL — credenciales desde las env vars del contenedor (comillas simples: PowerShell expande $ dentro de ")
docker compose exec mysql sh -c 'MYSQL_PWD=$MYSQL_PASSWORD mysql -u$MYSQL_USER $MYSQL_DATABASE'

docker compose up -d
docker compose down          # los datos persisten en el volumen
docker compose down -v       # ⚠️ borra los datos de MySQL — solo si se pide
```

## Reglas

1. **No tocar sin confirmación:** puertos de `docker-compose.yml` (hoy `0.0.0.0` a propósito,
   para la red local), credenciales, `docker/php/php.ini`, `docker compose down -v`.
2. **Imágenes solo con `--pull`** cuando se pida actualizar seguridad: `docker compose build --pull`.
3. **`.env` trackeados = solo defaults.** Secretos reales en `.env.local` o en el `.env` raíz
   (el de Compose); ambos ignorados por git.
4. **Esquema solo por migraciones:** `make:migration` → `doctrine:migrations:migrate`.
   Nunca `doctrine:schema:update`.
5. **Credenciales = solo red local**, nunca publicar. Los valores viven en el `.env` raíz y en el
   de la app (tabla abajo), no en archivos trackeados. El acceso a esos archivos lo bloquean los
   permisos de OpenCode (`opencode.json`), no este archivo.
6. **El tech-stack se mantiene vivo:** cada vez que se agregue, cambie o retire cualquier
   herramienta, servicio, paquete, extensión o dependencia del proyecto, se actualiza **en el
   mismo cambio** `spec_template/constitution/tech-stack.md` para que informe de todas las
   herramientas implementadas. Lo que no figure ahí no se considera parte del stack.

## Gotchas frecuentes (Windows / agentes)

- **Editar archivos con PowerShell:** nunca use `Set-Content` para reescribir archivos del repo
  (grabará BOM UTF-8; Symfony lo rechaza en `.env`, por ejemplo). Use
  `[System.IO.File]::WriteAllLines($ruta, $lineas, (New-Object System.Text.UTF8Encoding($false)))`
  para escribir sin BOM, o preferido aún: a `edit`/`write`.
- **`grep` no existe en PowerShell:** use `Select-String -Pattern ... -Path ...`. Al ejecutar comandos
  en Docker/Compose, siempre poner `workdir` explícito (`d:\Programación\Calendario_Planeacion`
  o `d:\Programación\Calendario_Planeacion\app_calplan`).
- **Makers interactivos:** pásalos TODOS los argumentos junto con `--no-interaction`;
  `make:user` espera el nombre corto (`User`), no el FQCN (`App\Entity\User`).
- **`#[Route]` no va a nivel de clase del controlador:** a nivel de clase actúa como prefijo de ruta,
  no como ruta. Póngalo en el **método**.
- **Servidor de desarrollo:** no lo arranque con `docker compose up`; usar
  `docker exec -d -w /var/www/html/app_calplan calendario_planeacion-php-1 symfony server:start --allow-all-ip --port=8000 -d`.
- **Git + antivirus:** si `git add` falla con "Permission denied" en `.git/objects` (antivirus
  bloqueando temporalmente), reintentar; objetos sueltos revisar con `git fsck` / `git gc --prune=now`.

## Secretos

| Archivo | Rol | Git |
|---|---|---|
| `.env` raíz | Variables de proyecto/Compose: `PROJECT_NAME`, `APP_DIR`, `MYSQL_*` (valores consumidos por `${...}`) | ❌ |
| `docker-compose.yml` | Solo referencias `${...}` (p. ej. `name`, credenciales MySQL) | ✅ |
| `.env` de la app (`<APP_DIR>/.env`) | Defaults — los únicos que van a git | ✅ |
| `*.env.local` | Secretos reales de la app | ❌ |
| `opencode.json` | MCP/LLM vía `{file:~/.secrets/<archivo>}` | ❌ |
| `~/.secrets/*` | API keys y tokens | fuera del repo |

⚠️ **Las env vars de Compose (`MYSQL_*`) solo se aplican al crear el volumen.** Cambiar el `.env`
raíz no cambia credenciales de una base ya inicializada: hace falta `ALTER USER` en SQL (o
`down -v`, que borra datos y está prohibido sin pedirlo).

Recargar config de OpenCode: `opencode reload` · `opencode mcp list`.

## Verificación

- `http://localhost:8000` → comprobar **desde el navegador**. *Quirk:* `curl` devuelve 404
  mientras el navegador carga bien — es comportamiento de primera ejecución, no investigar.
- Logs: `symfony server:log` (contenedor), `var/log/dev.log`, profiler `/_profiler`.

## Pendiente

_Problemas y decisiones que vayan apareciendo (sin duplicar convenciones genéricas de Symfony)._
