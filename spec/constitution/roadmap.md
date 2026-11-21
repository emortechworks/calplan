# Roadmap

## Hecho ✅

- [001 · Controlador de inicio](features/001-controlador-inicio/) — ancla raíz `/` con el saludo de `HomeController` ([spec](features/001-controlador-inicio/spec.md))
- [002 · Hero para inicio](features/002-hero-inicio/) — página `/` con hero Bootstrap por defecto y CTA deshabilitado "Autenticación próximamente" ([spec](features/002-hero-inicio/spec.md))
- [003 · JavaScript sin CDN](features/003-javascript-sin-cdn/) — elimina el bloque FrankenPHP y fija la regla "sin recursos externos" en la constitución ([spec](features/003-javascript-sin-cdn/spec.md))
- [004 · Autenticación local](features/004-autenticacion-local/) — registro + login + logout, `/cuenta` protegida, reset de contraseña por CLI ([spec](features/004-autenticacion-local/spec.md))
- [005 · Tiendas](features/005-tiendas/) — CRUD de tiendas del usuario: crear/editar/alternar (borrado lógico), validación de unicidad (usuario+nombre, usuario+código) + tests ([spec](features/005-tiendas/spec.md))
- [006 · Íconos de Bootstrap locales (UX Icons)](features/006-bootstrap-icons/) — `symfony/ux-icons` en modo estrictamente local (`iconify.on_demand: false`), `bi:calendar-week` inline en la navbar + tests ([spec](features/006-bootstrap-icons/spec.md))

## Siguiente 🔜

- 007 · Personal — CRUD de personal del usuario: `nombre` completo, `horasSemanales` (default 48), `fechaNacimiento` y `finContrato` (opcionales), `activo` (borrado lógico) y asignación `ManyToMany` a 0 o más tiendas propias (sin `rol base`). Dependencias: `Tienda` (005).

## Backlog / ideas 💡 (orden de dependencias)

- 008 · Perfil del personal — catálogo configurable de atributos de cualidades por usuario (`escala` 1–5, `booleano`, `lista`, `numero`, `texto`; con `peso` 0–5, `orden`, borrado lógico y sugeridos iniciales: Velocidad, Fuerza, Puntualidad, Confiabilidad, Sexo) + valores opcionales por persona. Dependencias: `Personal` (007).
- 009 · Roles y aptitudes — catálogo ampliable de roles y matriz "puede / no puede" por miembro del personal (único lugar donde se gestionan los roles). Dependencias: `Personal` (007).
- 010 · Incidencias y peticiones — vacaciones, descanso semanal, cumpleaños, incapacidad, cursos, repuestos/apoyos; fecha + tipo + personal. Alimentan la contabilidad de horas a la semana. Dependencias: `Personal` (007).
- 011 · Propuesta semanal — formulario (tienda + período + personal seleccionado) + generación usando pesos del perfil y reglas por día/tienda: días de trabajo/descanso, horario de comida cubierto por otro encargado de área, horas/semana menos incidencias y alertas de fin de contrato/cumpleaños. Dependencias: `Tienda` (005), `Personal` (007), `Perfil` (008), `Roles` (009), `Incidencias` (010).
- 012 · Tabla semanal coloreada — vista de la propuesta coloreada por trabajador. Dependencias: `Propuesta semanal` (011).
- 013 · Exportar / compartir — PDF / CSV / Sheets / Excel (decidir formato al llegar). Dependencias: `Propuesta semanal` (011) / `Tabla` (012).

> Cada feature nueva se crea como `features/NNN-nombre-feature/` con `spec.md`, `plan.md` y `tasks.md` antes de tocar código.
