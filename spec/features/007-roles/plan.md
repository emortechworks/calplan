# 007 · Roles — Plan

## Enfoque
Replica el patrón ya consolidado de `005 · Tiendas`: entidad Doctrine `Rol` ligada a `User` con únicamente `nombre` (único por usuario), `activo` (borrado lógico) y timestamps (`createdAt`, `updatedAt`); repositorio `RolRepository`; formulario `RolType`; controlador `RolController` con 4 acciones (`index`, `nuevo`, `editar`, `alternar`) bajo `/roles`; vistas Twig con Bootstrap + UX Icons locales; enlace "Roles" en la navbar; y pruebas funcionales en `RolControllerTest`.

## Implementación
1. Crear `src/Entity/Rol.php` y `src/Repository/RolRepository.php` con índice único `(usuario_id, nombre)` y ordenación activos primero → nombre ascendente.
2. Generar migración con `php bin/console make:migration --no-interaction` y aplicarla en `dev` y `test` (`doctrine:migrations:migrate --no-interaction`).
3. Crear `src/Form/RolType.php` (`nombre` con `NotBlank` y `Length(max: 120)`).
4. Crear `src/Controller/RolController.php` protegido con `#[IsGranted('ROLE_USER')]`:
   - `GET /roles` (`app_rol_index`): lista roles del usuario.
   - `GET|POST /roles/nuevo` (`app_rol_nuevo`): asigna `usuario = app.user`, valida unicidad de `nombre` por usuario.
   - `GET|POST /roles/{id}/editar` (`app_rol_editar`): verifica propiedad (`404` si es ajeno) y unicidad excluyendo el propio `id`.
   - `POST /roles/{id}/alternar` (`app_rol_alternar`): verifica propiedad + CSRF y alterna `activo`.
5. Crear vistas `templates/rol/index.html.twig`, `nuevo.html.twig`, `editar.html.twig`, `_form.html.twig` y añadir enlace "Roles" (`bi:person-badge`) en `templates/base.html.twig`. Importar `bi:person-badge` con `ux:icons:lock`.
6. Crear `tests/Controller/RolControllerTest.php` cubriendo: redirección sin sesión, listado propio (activos/desactivados) sin ajenos, alta, duplicado, edición propia, edición ajena `404` y alternar estado en BD (vía DBAL).
7. Validar `lint:container`, `lint:twig`, `lint:yaml` y `php bin/phpunit`.
8. Actualizar `spec/constitution/roadmap.md`, `spec/features/007-roles/{spec,plan,tasks}.md` y `spec/README.md`.

## Decisiones
- Campos estrictamente mínimos (`nombre` y `activo`), sin `descripcion` ni precarga de roles: el gerente construye su catálogo desde cero.
- Borrado lógico mediante `activo` para preservar integridad histórica cuando `Personal` (`008`) y `Propuesta semanal` (`011`) referencien roles.
- Unicidad `(usuario, nombre)` validada en el controlador (error visible en el campo del formulario) y respaldada por `UniqueConstraint` en MySQL.

## Riesgos
- Ninguno relevante; sigue el mismo patrón ya validado en `Tienda` y no rompe ninguna ruta o test existente.
