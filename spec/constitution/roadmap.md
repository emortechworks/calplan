# Roadmap

## Hecho ✅

- [001 · Controlador de inicio](features/001-controlador-inicio/) — ancla raíz `/` con el saludo de `HomeController` ([spec](features/001-controlador-inicio/spec.md))
- [002 · Hero para inicio](features/002-hero-inicio/) — página `/` con hero Bootstrap por defecto y CTA deshabilitado "Autenticación próximamente" ([spec](features/002-hero-inicio/spec.md))
- [003 · JavaScript sin CDN](features/003-javascript-sin-cdn/) — elimina el bloque FrankenPHP y fija la regla "sin recursos externos" en la constitución ([spec](features/003-javascript-sin-cdn/spec.md))
- [004 · Autenticación local](features/004-autenticacion-local/) — registro + login + logout, `/cuenta` protegida, reset de contraseña por CLI ([spec](features/004-autenticacion-local/spec.md))
- [005 · Tiendas](features/005-tiendas/) — CRUD de tiendas del usuario: crear/editar/alternar (borrado lógico), validación de unicidad (usuario+nombre, usuario+código) + tests ([spec](features/005-tiendas/spec.md))
- [006 · Íconos de Bootstrap locales (UX Icons)](features/006-bootstrap-icons/) — `symfony/ux-icons` en modo estrictamente local (`iconify.on_demand: false`), `bi:calendar-week` inline en la navbar + tests ([spec](features/006-bootstrap-icons/spec.md))
- [007 · Roles](features/007-roles/) — CRUD del catálogo de roles del usuario (`nombre` único por usuario, `descripcion` opcional, `activo` borrado lógico), enlace en navbar + tests ([spec](features/007-roles/spec.md))
- [008 · Regla de dependencias en el spec](features/008-regla-dependencias/) — fija la verificación y reordenamiento automático de features por dependencias en `spec/README.md`, `roadmap.md` y `spec_template/` ([spec](features/008-regla-dependencias/spec.md))
- [009 · Personal](features/009-personal/) — CRUD de personal del usuario (`nombre`, `horasSemanales`, `fechaNacimiento`, `finContrato`, `activo`), `ManyToMany` con tiendas (`btn-check`), roles ordenados por prioridad (`PersonalRol` + Stimulus), filtro por tienda + tests ([spec](features/009-personal/spec.md))

## Siguiente 🔜

- 010 · Perfil del personal — catálogo configurable de atributos de cualidades por usuario (`escala` 1–5, `booleano`, `lista`, `numero`, `texto`; con `peso` 0–5, `orden`, borrado lógico y sugeridos iniciales: Velocidad, Fuerza, Puntualidad, Confiabilidad, Sexo) + valores opcionales por persona. Dependencias: `Personal` (009).

## Backlog / ideas 💡 (orden de dependencias)

- 011 · Incidencias y peticiones — vacaciones, descanso semanal, cumpleaños, incapacidad, cursos, repuestos/apoyos; fecha + tipo + personal. Alimentan la contabilidad de horas a la semana. Dependencias: `Personal` (009).
- 012 · Propuesta semanal — formulario (tienda + período + personal seleccionado) + generación usando pesos del perfil, prioridades de rol y reglas por día/tienda: días de trabajo/descanso, horario de comida cubierto por otro encargado de área, horas/semana menos incidencias y alertas de fin de contrato/cumpleaños. Dependencias: `Tienda` (005), `Roles` (007), `Personal` (009), `Perfil` (010), `Incidencias` (011).
- 013 · Tabla semanal coloreada — vista de la propuesta coloreada por trabajador. Dependencias: `Propuesta semanal` (012).
- 014 · Exportar / compartir — PDF / CSV / Sheets / Excel (decidir formato al llegar). Dependencias: `Propuesta semanal` (012) / `Tabla` (013).

> Cada feature nueva se crea como `features/NNN-nombre-feature/` con `spec.md`, `plan.md` y `tasks.md` antes de tocar código.
> **Regla de dependencias:** toda feature solo puede depender de números menores ya en **Hecho ✅**. Si al planear un incremento se detecta una dependencia pendiente posterior, primero se reordena y renumera el backlog (nunca lo ya en **Hecho ✅**) para implementar antes el prerrequisito.
