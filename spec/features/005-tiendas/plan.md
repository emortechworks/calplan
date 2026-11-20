# 005 · Tiendas — Plan

## Enfoque
Entidad `Tienda` (Doctrine) con relación a `User`, formulario `TiendaType` (nombre + código), controlador `TiendaController` con 4 acciones: `index` (lista), `new`, `edit`, `toggle` (activa). Propiedad por usuario en cada consulta; desactivar = `activa=false` (sin DELETE). Navbar con enlace "Mis tiendas".

## Implementación
1. `src/Entity/Tienda.php` + `src/Repository/TiendaRepository.php` (a mano; el maker de entidades es interactivo) y migración con `make:migration`.
2. `src/Form/TiendaType.php` (nombre, codigo) con restricciones NotBlank/Length.
3. `src/Controller/TiendaController.php`:
   - `index`: ordena activas primero (y por nombre); incluye desactivadas.
   - `new`: asigna `usuario = app.user`; valida unicidad por usuario (nombre y codigo) con error de formulario.
   - `edit`: carga solo si es del usuario (404 si no); misma validación de unicidad.
   - `toggle`: cambia `activa` y redirige al listado.
4. Plantillas: `tienda/index.html.twig`, `tienda/new.html.twig`, `tienda/edit.html.twig` (formulario compartido en `tienda/_form.html.twig`).
5. Navbar: enlace "Mis tiendas" (con sesión).
6. Tests: `TiendaControllerTest` — sin sesión redirige a login; listar las propias; crear; duplicado muestra error; editar ajena → 404; toggle desactiva/reactiva.

## Decisiones
- `codigo` opcional (nullable); si queda vacío se guarda `null` (la unicidad solo aplica con valor).
- `activa` por defecto `true`; el listado muestra inactivas atenuadas con botón reactivar.
- Unicidad por usuario validada en el controlador (error de formulario) + unique constraint en BD como respaldo.
- Sin `responsable` ni campos opcionales (solo los acordados).

## Riesgos
- La unicidad (usuario, nombre/codigo) con NULLs: el unique index de MySQL tolera múltiples NULLs — correcto para "código opcional".
