# 011 · Renombrar Tienda a Sucursal — Plan

## Enfoque
Refactor integral en un único incremento limpio: crear las nuevas clases/vistas/tests con nombre `Sucursal`, actualizar todas las referencias en `Personal`, `PersonalRol`, `RegistrationFormType`, `base.html.twig`, `home/index.html.twig` y `roles_ordenados_controller.js`, eliminar los archivos antiguos de `Tienda`, generar la migración Doctrine y convertir su `up()`/`down()` a `RENAME TABLE` + `CHANGE` para preservar el 100 % de los registros actuales en `dev` y `test`.

## Implementación
1. **Entidades y repositorios:** `Sucursal`, `SucursalRepository`, actualización de `Personal`, `PersonalRol`, `PersonalRepository`; eliminar `Tienda.php` y `TiendaRepository.php`.
2. **Formularios y controladores:** `SucursalType`, `SucursalController` (`/sucursales`), actualización de `PersonalType`, `PersonalController` (`?sucursal=`, `rolesPorSucursal`) y `RegistrationFormType`; eliminar `TiendaType.php` y `TiendaController.php`.
3. **Vistas y Stimulus:** `templates/sucursal/`, actualizar `base.html.twig`, `home/index.html.twig`, `templates/personal/` y `roles_ordenados_controller.js`; eliminar `templates/tienda/`.
4. **Tests:** `SucursalControllerTest`, actualizar `PersonalControllerTest`; eliminar `TiendaControllerTest.php`.
5. **Migración Doctrine preservando datos:** usar `RENAME TABLE` (`tienda` → `sucursal`, `tienda_rol` → `sucursal_rol`, `personal_tienda` → `personal_sucursal`), `CHANGE tienda_id sucursal_id` y renombrar índices/FKs para alinear exactamente con el mapping Doctrine (`doctrine:schema:validate` en verde).
6. **Validación y documentación:** ejecutar migraciones en `dev` y `test`, `doctrine:schema:validate`, `lint:*`, `php bin/phpunit`, barrido de referencias residuales y actualizar `spec/constitution/` y `spec/README.md`.

## Decisiones
- Se usa `RENAME TABLE` y `CHANGE` de columnas en la migración en lugar de `DROP/CREATE` para no perder los datos ya cargados en `dev`.
- Se conservan intactas las carpetas históricas `spec/features/001`–`010` y las migraciones previas ya ejecutadas; solo la constitución viva y la nueva migración reflejan el renombre.

## Riesgos
- Diferencias de nombres de índices o claves foráneas entre `RENAME` manual y lo que espera Doctrine: mitigado validando explícitamente con `doctrine:schema:validate` en `dev` y `test`.
