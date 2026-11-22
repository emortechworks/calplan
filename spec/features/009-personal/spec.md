# 009 · Personal

**Estado:** Hecho ✅

## Dependencias
- `005 · Tiendas` (Hecho ✅)
- `007 · Roles` (Hecho ✅)

## Qué hace
CRUD del personal del usuario autenticado: listar (con filtro opcional por tienda, activos primero y desactivados atenuados), crear, editar y activar/desactivar (borrado lógico mediante `activo`). Cada persona pertenece a un usuario, puede asignarse a 0, 1 o varias tiendas propias (`ManyToMany` con chips `btn-check`) y a 0, 1 o varios roles propios ordenados por prioridad/peso (`PersonalRol`, `#1` = mayor peso, `#2`, `#3`… mediante lista interactiva con Stimulus).

## Por qué
Registrar a los trabajadores de las tiendas con sus datos operativos fijos (horas semanales, fecha de nacimiento, fin de contrato), sucursales y roles priorizados es la base de `010 · Perfil del personal`, `011 · Incidencias` y `012 · Propuesta semanal`.

## Criterios de aceptación
- [x] Sin sesión, `/personal` redirige a `/login`.
- [x] Con sesión, `/personal` lista únicamente el personal propio (activos primero, luego por nombre; desactivados atenuados con botón reactivar), mostrando horas semanales, tiendas asignadas y roles ordenados por prioridad (`#1`, `#2`…), y soporta filtro opcional por tienda (`?tienda=ID`).
- [x] Se puede crear personal con `nombre` completo, `horasSemanales` (default `48`), `fechaNacimiento` y `finContrato` opcionales, 0 o más tiendas propias (chips `btn-check`) y 0 o más roles propios en orden de prioridad.
- [x] Se puede editar personal propio reordenando roles (diff en memoria en un solo `flush()`, sin error de clave duplicada), añadiendo/quitando tiendas o roles y conservando visibles (marcados como desactivados) los roles/tiendas inactivos que ya tenía asignados; intentar editar personal ajeno devuelve `404`.
- [x] Intentar asociar por POST un `Rol` o una `Tienda` pertenecientes a otro usuario es rechazado en el servidor.
- [x] Se puede desactivar y reactivar personal mediante `POST` con token CSRF (borrado lógico, nunca `DELETE`).
- [x] La navbar autenticada muestra el enlace **Personal** con ícono local `bi:people` y todos los nuevos íconos se sirven localmente con UX Icons (cero CDN).
- [x] Migración Doctrine aplicada en `dev` y `test`; `lint:container`, `lint:twig`, `lint:yaml` y `php bin/phpunit` (incluyendo `PersonalControllerTest`) en verde.

## Fuera de alcance
- Atributos de perfil configurables (`010 · Perfil del personal`).
- Incidencias y peticiones (`011 · Incidencias y peticiones`).
- Generación de propuesta semanal (`012 · Propuesta semanal`).
- Borrado físico.
