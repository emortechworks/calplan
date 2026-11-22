# 007 · Roles

**Estado:** Hecho ✅

## Qué hace
CRUD del catálogo de roles del usuario autenticado: listar (activos y desactivados), crear, editar y activar/desactivar (borrado lógico mediante `activo`). Cada rol pertenece a un usuario; nadie ve ni modifica los roles de otro. Navbar con enlace "Roles".

## Por qué
El catálogo de roles es la base configurable de la que se sirve `Personal` (`008`) para definir qué roles puede y cuáles no desempeñar cada miembro del personal.

## Criterios de aceptación
- [x] Con sesión, `/roles` lista únicamente los roles del usuario (activos primero y ordenados por nombre; desactivados atenuados con opción de reactivar). Sin sesión redirige a `/login`.
- [x] Se puede crear un rol desde cero (`nombre` requerido, máximo 120 caracteres, único por usuario; `descripcion` opcional hasta 255 caracteres).
- [x] Se puede editar un rol propio (nombre y descripción); intentar editar un rol ajeno devuelve `404`.
- [x] Se puede desactivar y reactivar mediante `POST` con token CSRF (nunca se borra físicamente de la BD).
- [x] La navbar muestra el enlace "Roles" con sesión iniciada y vistas con íconos locales UX Icons (`bi:*`, sin CDN).
- [x] Migración Doctrine aplicada en `dev` y `test`.
- [x] `lint:container`, `lint:twig templates/`, `lint:yaml config/` y `php bin/phpunit` en verde (incluyendo `RolControllerTest`).

## Fuera de alcance
- Precarga de roles sugeridos (el usuario crea sus roles desde cero).
- Campos adicionales (`cubreComida`, color, permisos).
- Borrado físico.
- Asignación de roles a personal (viene en `008 · Personal`).
