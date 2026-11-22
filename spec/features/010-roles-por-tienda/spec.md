# 010 · Roles por tienda

**Estado:** Hecho ✅

## Dependencias
- `005 · Tiendas` (Hecho ✅)
- `007 · Roles` (Hecho ✅)
- `009 · Personal` (Hecho ✅)

## Qué hace
Vincula el catálogo compartido de `Rol` con cada `Tienda` (`ManyToMany` `tienda_rol`) y asocia las prioridades de rol de cada miembro del `Personal` **por tienda** (`PersonalRol(personal, tienda, rol, prioridad)`). En `/tiendas` se eligen qué roles existen en cada sucursal mediante chips `btn-check`; en `/personal`, al seleccionar una o varias tiendas se despliega un bloque independiente por tienda con únicamente los roles de esa sucursal y su propio orden de prioridad (`#1 Principal`, `#2`, `#3`…).

## Por qué
Cada sucursal puede tener roles distintos o compartir roles del mismo catálogo; separar la asignación y prioridad de roles por tienda evita conflictos entre sucursales al editar personal y garantiza que `013 · Propuesta semanal` trabaje solo con roles válidos para la tienda seleccionada.

## Criterios de aceptación
- [x] `Tienda` tiene relación `ManyToMany` con `Rol` (`tienda_rol`); en `/tiendas/nueva` y `/tiendas/{id}/editar` se eligen los roles de la tienda mediante chips `btn-check` (conservando roles ya asignados aunque hayan sido desactivados) y `/tiendas` muestra los roles asignados a cada sucursal.
- [x] Intentar asignar por POST un `Rol` de otro usuario a una `Tienda` es rechazado en el servidor.
- [x] `PersonalRol` incluye `tienda` (`ON DELETE CASCADE`) y restricción única `(personal_id, tienda_id, rol_id)`.
- [x] En `/personal/nuevo` y `/personal/{id}/editar`, al marcar/desmarcar tiendas con `btn-check`, Stimulus muestra/oculta dinámicamente el bloque de roles de cada tienda marcada con solo los roles de esa tienda y su propia lista priorizada `#1, #2, #3…`.
- [x] El servidor valida que cada rol asignado a una persona para una tienda exista en esa tienda y que la tienda pertenezca a las tiendas seleccionadas de la persona (rechazando roles fuera de la tienda o de otro usuario).
- [x] Al editar personal reordenando roles dentro de una tienda (o cambiando entre tiendas), se actualiza en sitio en un único `flush()` sin colisiones de clave única.
- [x] `/personal` muestra los roles agrupados por tienda con su prioridad `#1, #2…` (y el filtro `?tienda=ID` sigue funcionando).
- [x] Migración Doctrine aplicada en `dev` y `test`; `lint:container`, `lint:twig`, `lint:yaml` y `php bin/phpunit` (incluyendo pruebas nuevas en `TiendaControllerTest` y `PersonalControllerTest`) en verde.

## Fuera de alcance
- Restricción de roles por tienda en el catálogo `/roles` (el catálogo de roles sigue siendo global por usuario para poder reutilizarlos en cualquier sucursal).
- Generación de propuesta semanal (`013 · Propuesta semanal`).
