# Roadmap

## Hecho ✅

- [001 · Controlador de inicio](features/001-controlador-inicio/) — ancla raíz `/` con el saludo de `HomeController` ([spec](features/001-controlador-inicio/spec.md))
- [002 · Hero para inicio](features/002-hero-inicio/) — página `/` con hero Bootstrap por defecto y CTA deshabilitado "Autenticación próximamente" ([spec](features/002-hero-inicio/spec.md))
- [003 · JavaScript sin CDN](features/003-javascript-sin-cdn/) — elimina el bloque FrankenPHP y fija la regla "sin recursos externos" en la constitución ([spec](features/003-javascript-sin-cdn/spec.md))
- [004 · Autenticación local](features/004-autenticacion-local/) — registro + login + logout, `/cuenta` protegida, reset de contraseña por CLI ([spec](features/004-autenticacion-local/spec.md))
- [005 · Tiendas](features/005-tiendas/) — CRUD inicial de sucursales (entonces *tiendas*) del usuario: crear/editar/alternar (borrado lógico), validación de unicidad + tests ([spec](features/005-tiendas/spec.md))
- [006 · Íconos de Bootstrap locales (UX Icons)](features/006-bootstrap-icons/) — `symfony/ux-icons` en modo estrictamente local (`iconify.on_demand: false`), `bi:calendar-week` inline en la navbar + tests ([spec](features/006-bootstrap-icons/spec.md))
- [007 · Roles](features/007-roles/) — CRUD del catálogo de roles del usuario (`nombre` único por usuario, `descripcion` opcional, `activo` borrado lógico), enlace en navbar + tests ([spec](features/007-roles/spec.md))
- [008 · Regla de dependencias en el spec](features/008-regla-dependencias/) — fija la verificación y reordenamiento automático de features por dependencias en `spec/README.md`, `roadmap.md` y `spec_template/` ([spec](features/008-regla-dependencias/spec.md))
- [009 · Personal](features/009-personal/) — CRUD de personal del usuario (`nombre`, `horasSemanales`, `fechaNacimiento`, `finContrato`, `activo`), `ManyToMany` con sucursales (`btn-check`), roles ordenados por prioridad (`PersonalRol` + Stimulus), filtro por sucursal + tests ([spec](features/009-personal/spec.md))
- [010 · Roles por tienda](features/010-roles-por-tienda/) — relación `ManyToMany` `Sucursal ↔ Rol` (`sucursal_rol`) con chips `btn-check` y prioridades de rol separadas por sucursal en `/personal` (`PersonalRol` + paneles dinámicos Stimulus) + tests ([spec](features/010-roles-por-tienda/spec.md))
- [011 · Renombrar Tienda a Sucursal](features/011-renombrar-sucursal/) — renombre integral de `Tienda` → `Sucursal` en BD (`RENAME TABLE` preservando datos), código PHP, rutas `/sucursales`, vistas Twig, Stimulus, tests y constitución ([spec](features/011-renombrar-sucursal/spec.md))
- [012 · Color del personal](features/012-color-personal/) — campo `color` (`VARCHAR(7)` `#RRGGBB`, default `#ffffff`), desplegable cerrado de 20 colores claros (`ChoiceType` + vista previa Stimulus), muestra circular en `/personal` + tests ([spec](features/012-color-personal/spec.md))
- [013 · Perfil del personal](features/013-perfil-personal/) — catálogo configurable descriptivo de atributos por usuario (`escala` 1–5, `booleano`, `lista`, `numero`, `texto`; con `orden`, borrado lógico, sin `peso` y acción idempotente **Cargar sugeridos** accesible desde `/personal`) + valores opcionales por persona en el formulario de Personal y badges en `/personal` + tests ([spec](features/013-perfil-personal/spec.md))

## Siguiente 🔜

- 014 · Incidencias y peticiones — vacaciones, descanso semanal, cumpleaños, incapacidad, cursos, repuestos/apoyos; fecha + tipo + personal. Alimentan la contabilidad de horas a la semana. Dependencias: `Personal` (009).

## Backlog / ideas 💡 (orden de dependencias)

- 015 · Propuesta semanal — formulario (sucursal + período + personal seleccionado) + generación usando prioridades de rol por sucursal y reglas por día/sucursal: días de trabajo/descanso, horario de comida cubierto por otro encargado de área, horas/semana menos incidencias y alertas de fin de contrato/cumpleaños (el perfil de `013` es solo descriptivo por el momento). Dependencias: `Sucursal` (005 / 011), `Roles` (007), `Personal` (009), `Roles por sucursal` (010 / 011), `Incidencias` (014).
- 016 · Tabla semanal coloreada — vista de la propuesta coloreada por trabajador usando `Personal.color` (`012`). Dependencias: `Color del personal` (012), `Propuesta semanal` (015).
- 017 · Exportar / compartir — PDF / CSV / Sheets / Excel (decidir formato al llegar). Dependencias: `Propuesta semanal` (015) / `Tabla` (016).

> Cada feature nueva se crea como `features/NNN-nombre-feature/` con `spec.md`, `plan.md` y `tasks.md` antes de tocar código.
> **Regla de dependencias:** toda feature solo puede depender de números menores ya en **Hecho ✅**. Si al planear un incremento se detecta una dependencia pendiente posterior, primero se reordena y renumera el backlog (nunca lo ya en **Hecho ✅**) para implementar antes el prerrequisito.
