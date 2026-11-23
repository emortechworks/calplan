# 012 · Color del personal

**Estado:** Hecho ✅

## Dependencias
- `009 · Personal` (Hecho ✅)

## Qué hace
Añade a `Personal` el campo `color` (hexadecimal `#RRGGBB`, opcional con valor por defecto `#ffffff` — blanco), seleccionable en el formulario de alta/edición mediante `ColorType` (`form-control form-control-color` de Bootstrap 5 + `<datalist>` HTML5 con blanco y tonos pastel sugeridos, sin JS ni CDN) y visible como una muestra circular junto al nombre en `/personal`.

## Por qué
Permitir asignar desde ahora un color identificador a cada trabajador para distinguirlo en el listado y reutilizarlo directamente en `016 · Tabla semanal coloreada`.

## Criterios de aceptación
- [x] `Personal` tiene columna `color VARCHAR(7) NOT NULL DEFAULT '#ffffff'`; migración aplicada en `dev` y `test` conservando todos los registros existentes con `#ffffff`.
- [x] Al crear personal sin modificar `color`, se guarda `#ffffff` en BD.
- [x] Al crear o editar personal con un color hexadecimal válido (p. ej. `#cfe2ff`), se persiste normalizado en minúsculas y se muestra la muestra circular de color en `/personal`.
- [x] Enviar un valor de color inválido por POST (fuera del formato `#RRGGBB`) es rechazado por el servidor con error de validación.
- [x] `doctrine:schema:validate`, `lint:container`, `lint:twig templates/`, `lint:yaml config/` y `php bin/phpunit` (incluyendo pruebas de color en `PersonalControllerTest`) en verde.
- [x] `spec/constitution/dominio.md`, `roadmap.md` y `spec/README.md` actualizados en el mismo commit.

## Fuera de alcance
- Aplicación del color sobre celdas de la tabla semanal (viene en `016 · Tabla semanal coloreada`).
- Restricción de unicidad de color por sucursal (dos personas pueden compartir color si el gerente lo desea).
