# 011 · Renombrar Tienda a Sucursal

**Estado:** Hecho ✅

## Dependencias
- `005 · Tiendas` (Hecho ✅)
- `009 · Personal` (Hecho ✅)
- `010 · Roles por tienda` (Hecho ✅)

## Qué hace
Sustituye de forma consistente el concepto y nombre **`Tienda`** por **`Sucursal`** en la constitución del spec (`dominio.md`, `mission.md`, `roadmap.md`), el esquema de base de datos (`sucursal`, `sucursal_rol`, `personal_sucursal`, `personal_rol.sucursal_id`), el código PHP (entidades, repositorios, formularios, controladores y rutas `/sucursales`), las vistas Twig, el controlador Stimulus y las pruebas automatizadas, **preservando todos los datos existentes**.

## Por qué
Alinear la terminología de todo el sistema con el lenguaje real del negocio (*sucursal*) antes de construir `Perfil del personal`, `Incidencias` y `Propuesta semanal`.

## Criterios de aceptación
- [x] Migración Doctrine ejecutada en `dev` y `test` renombrando tablas y columnas con `RENAME TABLE` y `CHANGE` sin perder datos existentes (`sucursal`, `sucursal_rol`, `personal_sucursal`, `personal_rol.sucursal_id`).
- [x] `doctrine:schema:validate` en verde en `dev` y `test`.
- [x] Entidad `Sucursal`, `SucursalRepository`, `SucursalType`, `SucursalController` (`/sucursales`, `nueva`, `editar`, `alternar`), vistas `templates/sucursal/` y relaciones en `Personal` / `PersonalRol` / `PersonalController` completamente renombradas (sin clases ni carpetas residuales `Tienda`).
- [x] Navbar muestra **Mis sucursales** (`/sucursales`); inicio, formularios, mensajes flash/validación y Stimulus usan *sucursal* consistentemente.
- [x] Cero referencias residuales a `tienda` en `app_calplan/src/`, `templates/`, `assets/controllers/`, `tests/` y `spec/constitution/`.
- [x] Carpetas históricas cerradas `spec/features/001`–`010` conservadas intactas; `spec/constitution/` y `spec/README.md` actualizados (`011` en Hecho, backlog `012`–`016`).
- [x] `lint:container`, `lint:twig templates/`, `lint:yaml config/` y `php bin/phpunit` (incluyendo `SucursalControllerTest` y `PersonalControllerTest`) en verde.

## Fuera de alcance
- Cambios funcionales nuevos en sucursales, roles o personal (refactor estrictamente de nomenclatura y esquema).
- Reescritura de carpetas históricas de features ya completadas (`001`–`010`).
