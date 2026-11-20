# 005 · Tiendas

**Estado:** Hecho ✅

## Qué hace
CRUD de tiendas del usuario autenticado: listar (activas y desactivadas), crear, editar y activar/desactivar (borrado lógico mediante `activa`). Cada tienda pertenece a un usuario; nadie ve las tiendas de otro. Navbar con enlace "Mis tiendas".

## Por qué
Primer paso del flujo del gerente: seleccionar la tienda es el punto de partida de la planeación semanal (010).

## Criterios de aceptación
- [x] Con sesión, `/tiendas` lista las tiendas del usuario (activas primero; desactivadas atenuadas y con opción de reactivar).
- [x] Se puede crear una tienda (nombre requerido, único por usuario; código opcional, único por usuario si se usa).
- [x] Se puede editar una tienda propia; intentar editar/ver una tienda ajena da 404.
- [x] Se puede desactivar y reactivar (nunca se borra de la BD).
- [x] La navbar muestra "Mis tiendas" con sesión iniciada.
- [x] `php bin/phpunit` en verde.

## Fuera de alcance
- Borrado físico.
- Campos adicionales (dirección, teléfono, responsable, notas).
- Selección de la tienda para la planeación (viene en 010).
