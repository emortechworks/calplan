# Proyecto Symfony (plantilla Docker)

> **Variables de proyecto (fuente de verdad):** definidas en el `.env` raíz (junto a `docker-compose.yml`).
> - `PROJECT_NAME`: nombre del proyecto Docker Compose (prefijo de contenedores, red, volumen e imagen). `docker-compose.yml` lo usa como `name: ${PROJECT_NAME}`.
> - `APP_DIR`: carpeta de la aplicación Symfony dentro del repositorio (y en `/var/www/html/<APP_DIR>` dentro del contenedor).
> - `MYSQL_*`: credenciales/base usadas por Docker Compose para el servicio `mysql`.
>
> Sustituye `<PROJECT_NAME>` y `<APP_DIR>` en los comandos según los valores actuales del `.env` raíz.

## Descripción general del proyecto

### Primera aproximación


# Plantilla Symfony + Docker

Entorno de desarrollo basado en Docker: **PHP 8.4, Composer y Symfony CLI corren dentro del contenedor** — no es necesario instalar ninguno de ellos en el host.

## Estructura del repositorio

| Ruta | Descripción |
|---|---|
| `.env` (raíz) | Variables de proyecto/Compose (`PROJECT_NAME`, `APP_DIR`, `MYSQL_*`) — no se commitea |
| `docker-compose.yml` | Servicios `php` y `mysql`; nombre del proyecto vía `name: ${PROJECT_NAME}` |
| `docker/php/Dockerfile` | Imagen PHP 8.4-cli + extensiones + Composer + Symfony CLI |
| `docker/php/php.ini` | Configuración extra de PHP (hardening de seguridad) |
| `<APP_DIR>/` | Aplicación Symfony del proyecto (no existe en una plantilla nueva) |

## Puesta en marcha de un nuevo proyecto

### 1. Construir la imagen
```powershell
docker compose build

# refrescar la imagen base PHP (parches de seguridad) antes de construir:
docker compose pull php
docker compose build --pull
```

> **Define `PROJECT_NAME` en el `.env` raíz** (normalizado: minúsculas, sin acentos ni espacios). `docker-compose.yml` lo usa como `name: ${PROJECT_NAME}` y de ahí derivan:
> - Contenedores: `<PROJECT_NAME>-php-1`, `<PROJECT_NAME>-mysql-1`
> - Red: `<PROJECT_NAME>_symfony`
> - Volumen: `<PROJECT_NAME>_data-docker-symfony`
> - Imagen: `<PROJECT_NAME>-php`

### 2. Levantar los contenedores
```powershell
docker compose up -d

# verificar
docker compose ps
```
La primera vez, MySQL tarda ~10-20 s en inicializar la base.

### 3. Crear el proyecto Symfony (si aún no existe)
```powershell
docker compose run --rm php composer create-project symfony/skeleton <APP_DIR> --no-interaction
```
Composer crea la carpeta dentro del contenedor en `/var/www/html/<APP_DIR>`,
que corresponde a `./<APP_DIR>` en el host (el bind mount `./:/var/www/html`).

### 4. Instalar las dependencias (webapp-pack)
```powershell
docker compose run --rm -w /var/www/html/<APP_DIR> php composer require symfony/webapp-pack --no-interaction
```

> **⚠️ El flag `-w` es obligatorio.** `docker compose run` arranca en `/var/www/html`
> (la raíz compartida), donde **no hay `composer.json`**, y el comando fallaría con
> *"no composer.json in /var/www/html"*. Hacer `cd <APP_DIR>` en el host **no**
> soluciona esto: el `working_dir` del contenedor no cambia.

### 5. Configurar la base de datos
Editar `<APP_DIR>/.env` y dejar:
```
DATABASE_URL="mysql://<usuario>:<password>@mysql:3306/<base>?serverVersion=8.4&charset=utf8mb4"
```

- El host es **`mysql`** (el nombre del servicio en la red de Docker), **no** `127.0.0.1`.
- Credenciales y base: definidas en el `.env` de la raíz (ignorado por git);
  `docker-compose.yml` solo referencia `${...}`. Están también en
  `<APP_DIR>/.env` (defaults de desarrollo). No se reproducen acá.

Verificación:
```powershell
docker compose exec -T -w /var/www/html/<APP_DIR> php php bin/console dbal:run-sql "SELECT 1"
```

### 6. Iniciar el servidor de desarrollo
```powershell
docker exec -d -w /var/www/html/<APP_DIR> <PROJECT_NAME>-php-1 symfony server:start --allow-all-ip --port=8000 -d
```

- `--allow-all-ip` **es necesario**: desde la v5.10.3 el servidor de Symfony solo escucha
  en `127.0.0.1` dentro del contenedor; sin ese flag el puerto publicado no sería accesible.
- Abrir `http://localhost:8000`.

### Detener el servidor
```powershell
docker exec -w /var/www/html/<APP_DIR> <PROJECT_NAME>-php-1 symfony server:stop
```

### Detener / reiniciar todo
```powershell
docker compose down      # detiene contenedores y red; MySQL conserva sus datos (volumen nombrado)
docker compose down -v   # además borra el volumen de MySQL (pierde los datos)
docker compose up -d     # vuelve a levantar todo
```

> El servidor de Symfony **no arranca solo** con `docker compose up`: es un proceso
> aparte dentro del contenedor (paso 6). Tras recrear el contenedor `php`, hay que
> volver a ejecutarlo.

## Estado actual de este repositorio (plantilla)

- ✅ Imagen Docker y servicios listos (`docker compose ps`)
- ✅ PHP **8.4.27** — parchado de CVE-2026-109618 / CVE-2026-109619 ("php2shell", RCE)
- ✅ `session.upload_progress.enabled = Off` (ver `docker/php/php.ini`)
- ✅ `.gitignore` cubre `opencode.json`, `/.env`, `.env.local`, `.env.*.local`
- ⬜ **No hay aplicación Symfony**: crear la carpeta `<APP_DIR>` con el paso 3 de "Puesta en marcha"
- ⬜ `.env` de la app apuntando a MySQL: paso 5

Arranque rápido **una vez creada la app**:
```powershell
docker compose up -d
docker exec -d -w /var/www/html/<APP_DIR> <PROJECT_NAME>-php-1 symfony server:start --allow-all-ip --port=8000 -d
```

## Comandos útiles

```powershell
# Consola de Symfony
docker exec -w /var/www/html/<APP_DIR> <PROJECT_NAME>-php-1 php bin/console about

# Composer (instalar dependencias tras cambiar composer.json)
docker compose exec -w /var/www/html/<APP_DIR> php composer install

# Auditar vulnerabilidades en dependencias
docker compose exec -T -w /var/www/html/<APP_DIR> php composer audit

# Estado del servidor de desarrollo
docker exec -w /var/www/html/<APP_DIR> <PROJECT_NAME>-php-1 symfony server:status

# Logs del servidor
docker exec -w /var/www/html/<APP_DIR> <PROJECT_NAME>-php-1 symfony server:log

# Consola Doctrine / SQL directo
docker exec -w /var/www/html/<APP_DIR> <PROJECT_NAME>-php-1 php bin/console dbal:run-sql "SELECT VERSION()"

# Shell dentro del contenedor PHP
docker exec -it <PROJECT_NAME>-php-1 bash

# MySQL — lee las credenciales de las env vars del propio contenedor
# (las recibe del .env de la raíz; no se escriben en ningún archivo)
docker compose exec mysql sh -c 'MYSQL_PWD=$MYSQL_PASSWORD mysql -u$MYSQL_USER $MYSQL_DATABASE'

# Con una query (sin abrir shell interactiva)
Write-Output 'SELECT 1;' | docker compose exec -i mysql sh -c 'MYSQL_PWD=$MYSQL_PASSWORD mysql -u$MYSQL_USER $MYSQL_DATABASE'
```

## Notas de seguridad

- **Puertos expuestos a la red local**: `8000` (servidor de desarrollo Symfony, con
  profiler/debug sin login) y `3306` (MySQL) están publicados en `0.0.0.0`, por lo que
  son accesibles desde **otros equipos de la misma red** vía `http://<IP-del-host>:8000`.
  Útil para pruebas desde otros dispositivos. Cuando no se necesite, restringir a la PC
  cambiando en `docker-compose.yml`:
  ```yaml
  ports:
    - "127.0.0.1:8000:8000"
    - "127.0.0.1:3306:3306"
  ```
- **Credenciales de desarrollo**: usar solo en redes de confianza; no exponer el
  entorno `dev` en redes abiertas (café, oficina, etc.). Los valores viven en el
  `.env` de la raíz y en `<APP_DIR>/.env` (git-ignored / defaults);
  no se documentan en archivos trackeados.
- **Mantener PHP actualizado**: reconstruir periódicamente con `docker compose build --pull`
  para incorporar los parches de seguridad de la imagen base (`php:8.4-cli`).
- La configuración de hardening vive en `docker/php/php.ini` (se copia a
  `/usr/local/etc/php/conf.d/zz-security.ini` en la imagen).
