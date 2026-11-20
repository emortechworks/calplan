# Roadmap

## Hecho ✅

- [001 · Controlador de inicio](features/001-controlador-inicio/) — ancla raíz `/` con el saludo de `HomeController` ([spec](features/001-controlador-inicio/spec.md))
- [002 · Hero para inicio](features/002-hero-inicio/) — página `/` con hero Bootstrap por defecto y CTA deshabilitado "Autenticación próximamente" ([spec](features/002-hero-inicio/spec.md))
- [003 · JavaScript sin CDN](features/003-javascript-sin-cdn/) — elimina el bloque FrankenPHP y fija la regla "sin recursos externos" en la constitución ([spec](features/003-javascript-sin-cdn/spec.md))
- [004 · Autenticación local](features/004-autenticacion-local/) — registro + login + logout, `/cuenta` protegida, reset de contraseña por CLI ([spec](features/004-autenticacion-local/spec.md))
- [005 · Tiendas](features/005-tiendas/) — CRUD de tiendas del usuario: crear/editar/alternar (borrado lógico), validación de unicidad (usuario+nombre, usuario+código) + tests ([spec](features/005-tiendas/spec.md))

## Siguiente 🔜

- 006 · Colaboradores — CRUD de colaborador ligado a tienda (nombre, rol base: gerente titular / suplente / encargado de área / otros). Dependencias: `Tienda` (005).

## Backlog / ideas 💡 (orden de dependencias)

- 007 · Perfil del colaborador — catálogo de atributos clave-valor y asignación de valores por colaborador (velocidad, fuerza, puntualidad, sexo, confiabilidad, vencimiento de contrato…). Dependencias: `Colaborador` (006).
- 008 · Roles y aptitudes — catálogo de roles y matriz "puede / no puede" por colaborador (quién puede hacer qué y quién no). Dependencias: `Colaborador` (006).
- 009 · Incidencias y peticiones — vacaciones, descanso semanal, cumpleaños, incapacidad, cursos, repuestos/apoyos; fecha + tipo + colaborador. Alimentan la contabilidad de horas a la semana. Dependencias: `Colaborador` (006).
- 010 · Propuesta semanal — formulario (tienda + período + personal seleccionado por perfil) + generación: días de trabajo/descanso, horario de comida cubierto por otro encargado de área, horas/semana menos incidencias. Dependencias: `Tienda` (005), `Colaborador` (006), `Perfil` (007), `Roles` (008), `Incidencias` (009).
- 011 · Tabla semanal coloreada — vista de la propuesta coloreada por trabajador. Dependencias: `Propuesta semanal` (010).
- 012 · Exportar / compartir — PDF / CSV / Sheets / Excel (decidir formato al llegar). Dependencias: `Propuesta semanal` (010) / `Tabla` (011).

> Cada feature nueva se crea como `features/NNN-nombre-feature/` con `spec.md`, `plan.md` y `tasks.md` antes de tocar código.
