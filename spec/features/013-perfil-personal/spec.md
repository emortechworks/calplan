# 013 · Perfil del personal

**Estado:** Hecho ✅

## Dependencias
- `009 · Personal` (Hecho ✅)

## Qué hace
Añade un catálogo configurable por usuario de atributos descriptivos del personal (`AtributoPerfil`: `escala` 1–5, `booleano`, `lista`, `numero`, `texto`; con `orden`, borrado lógico `activo` y acción idempotente **Cargar sugeridos**: `Velocidad`, `Fuerza`, `Puntualidad`, `Confiabilidad`, `Sexo`) accesible desde un botón en `/personal`, junto con valores opcionales por trabajador (`ValorPerfil`) editables en una sección opcional dentro del formulario de crear/editar `Personal` y visibles como badges discretos en `/personal`. El perfil es **100 % descriptivo** (sin campo `peso`) y no influye en la planeación final.

## Por qué
Permitir a cada usuario documentar cualidades flexibles de su equipo sin fijar columnas rígidas en `personal` y manteniendo los datos aislados por cuenta, manteniendo el perfil puramente informativo en esta etapa.

## Criterios de aceptación
- [x] Existen las tablas `atributo_perfil` (con unicidad `(usuario_id, nombre)`, sin columna `peso`) y `valor_perfil` (con unicidad `(personal_id, atributo_id)` y `ON DELETE CASCADE`), creadas vía migración Doctrine aplicada en `dev` y `test`.
- [x] Desde `/personal` hay un botón **Atributos de perfil** que abre `/perfil/atributos` (sin saturar la navbar), protegido con `ROLE_USER` y estricto aislamiento por usuario (`404` sobre atributos ajenos).
- [x] En `/perfil/atributos` se pueden crear, editar, ordenar, desactivar/reactivar atributos y ejecutar **Cargar sugeridos** (5 atributos iniciales, idempotente).
- [x] Cuando `tipo = lista`, `opciones` es obligatorio; cuando un atributo ya tiene valores capturados, el servidor impide cambiar su `tipo`.
- [x] El formulario de crear/editar `Personal` incluye la sección opcional **Perfil descriptivo (opcional)** con el control apropiado por `tipo`; dejar un campo vacío no guarda (o borra) el valor, y los atributos ajenos o valores inválidos son rechazados por el servidor.
- [x] En `/personal` se muestran los valores capturados como badges discretos junto a cada persona (precargados sin N+1); al editar una persona con valor en un atributo desactivado posteriormente, ese atributo se muestra marcado como desactivado para conservarlo o limpiarlo.
- [x] Íconos locales importados vía `ux:icons:lock` (cero CDN).
- [x] `doctrine:schema:validate` (`dev` y `test`), `lint:container`, `lint:twig templates/`, `lint:yaml config/` y `php bin/phpunit` (incluyendo `AtributoPerfilControllerTest` y casos de perfil en `PersonalControllerTest`) en verde.
- [x] Constitución (`dominio.md`, `roadmap.md`, `tech-stack.md`, `spec/README.md`) actualizada reflejando que `013` es solo descriptivo (sin `peso`).

## Fuera de alcance
- Campo `peso` o ponderación de atributos dentro de `015 · Propuesta semanal`.
- Reglas por día/sucursal basadas en atributos de perfil.
