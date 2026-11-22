# 009 · Personal — Plan

## Enfoque
Entidad `Personal` propiedad de `User` con relación `ManyToMany` hacia `Tienda` (`personal_tienda`) y relación `OneToMany` hacia la entidad puente `PersonalRol` (`personal_rol`) con campo `prioridad` (`1, 2, 3…`, donde `#1` representa el mayor peso / rol principal). En el formulario, `tiendas` usa `EntityType` renderizado como chips Bootstrap `btn-check`, y `roles` usa un controlador Stimulus ligero (`roles_ordenados_controller.js`, 0 dependencias externas) que permite añadir, quitar y reordenar roles con `↑` / `↓` sincronizando los IDs ordenados en un campo oculto validado en el servidor.

## Implementación
1. Crear `src/Entity/Personal.php`, `src/Entity/PersonalRol.php` y `src/Repository/PersonalRepository.php`.
   - `Personal::reemplazarRolesEnOrden(Rol[] $rolesOrdenados)` actualiza `prioridad` en sitio sobre los `PersonalRol` existentes, inserta los nuevos y elimina solo los removidos (`orphanRemoval: true`) para operar en un solo `flush()` sin colisión en `UNIQ_PERSONAL_ROL`.
2. Generar migración Doctrine (`make:migration`) creando `personal`, `personal_tienda` y `personal_rol`, y aplicarla en `dev` y `test`.
3. Crear `src/Form/PersonalType.php` con `query_builder` restringido a tiendas propias (activas o ya asignadas) y campo `rolesOrdenados` no mapeado.
4. Crear `assets/controllers/roles_ordenados_controller.js` para reordenar roles (`+`, `↑`, `↓`, `×`) actualizando badges `#1, #2, #3…` y el campo oculto.
5. Crear `src/Controller/PersonalController.php` (`/personal`, `nuevo`, `editar`, `alternar`) validando propiedad del usuario sobre `Personal`, `Tienda` y `Rol`.
6. Crear vistas `templates/personal/{index,nuevo,editar,_form}.html.twig`, añadir enlace **Personal** en `templates/base.html.twig` e importar íconos faltantes con `ux:icons:lock`.
7. Crear `tests/Controller/PersonalControllerTest.php` y validar `lint:*` + `php bin/phpunit`.
8. Actualizar `roadmap.md`, `spec/README.md` y el estado de `009-personal`.

## Decisiones
- Se guarda `prioridad` (`1, 2, 3…`) en `PersonalRol` en lugar de un peso absoluto decreciente para evitar recalcular registros existentes al añadir o desactivar roles en el catálogo; el algoritmo de `012` deriva el peso relativo directamente de la prioridad.
- No se agrega `UniqueConstraint` en `Personal.nombre` (pueden existir personas homónimas).
- Al editar, se incluyen los roles y tiendas desactivados que la persona ya tenía asignados (marcados visualmente) para no perder asignaciones previas de forma silenciosa.

## Riesgos
- Reordenamiento de roles en entidad existente: mitigado actualizando `prioridad` en sitio (sin borrar y re-insertar filas del mismo `rol_id`) y cubierto por test funcional en `PersonalControllerTest`.
