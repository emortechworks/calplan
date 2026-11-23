# 012 · Color del personal

**Estado:** Hecho ✅

## Dependencias
- `009 · Personal` (Hecho ✅)

## Qué hace
Añade a `Personal` el campo `color` (hexadecimal `#RRGGBB`, opcional con valor por defecto `#ffffff` — blanco), seleccionable exclusivamente de una paleta cerrada de **20 colores claros/pastel** (`Personal::COLORES_DISPONIBLES`, `ChoiceType` renderizado como círculos `btn-check` sin JS ni CDN) para garantizar legibilidad con texto oscuro, y visible como una muestra circular junto al nombre en `/personal`.

## Por qué
Permitir asignar desde ahora un color identificador a cada trabajador para distinguirlo en el listado y reutilizarlo directamente en `016 · Tabla semanal coloreada`, impidiendo elegir tonos oscuros que dificulten leer el texto.

## Criterios de aceptación
- [x] `Personal` tiene columna `color VARCHAR(7) NOT NULL DEFAULT '#ffffff'`; migración aplicada en `dev` y `test` conservando todos los registros existentes con `#ffffff`.
- [x] El formulario muestra una paleta cerrada de exactamente **20 colores claros** (`#ffffff` por defecto) sin permitir colores libres ni oscuros.
- [x] Al crear personal sin modificar `color`, se guarda `#ffffff` en BD.
- [x] Al crear o editar personal con un color de la paleta (p. ej. `#cfe2ff`), se persiste en BD y se muestra la muestra circular de color en `/personal`.
- [x] Enviar por POST un color oscuro o fuera de la paleta (p. ej. `#000000`) es rechazado por el servidor sin modificar el registro.
- [x] `doctrine:schema:validate`, `lint:container`, `lint:twig templates/`, `lint:yaml config/` y `php bin/phpunit` (incluyendo pruebas de color en `PersonalControllerTest`) en verde.
- [x] `spec/constitution/dominio.md`, `roadmap.md` y `spec/README.md` actualizados en el mismo commit.

## Fuera de alcance
- Aplicación del color sobre celdas de la tabla semanal (viene en `016 · Tabla semanal coloreada`).
- Restricción de unicidad de color por sucursal (dos personas pueden compartir color si el gerente lo desea).
