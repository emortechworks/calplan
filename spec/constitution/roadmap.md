# Roadmap

## Hecho ✅

- [001 · Controlador de inicio](features/001-controlador-inicio/) — ancla raíz `/` con el saludo de `HomeController` ([spec](features/001-controlador-inicio/spec.md))

## Siguiente 🔜

- 002 · Autenticación local — registro + login con email/contraseña (Symfony Security), logout, rol único `ROLE_USER`. Recuperación de contraseña solo por comando CLI (`app:user:reset-password`); el reset por email queda fuera por ahora. Dependencias: ninguna.

## Backlog / ideas 💡 (orden de dependencias)

- 003 · Tiendas — CRUD de tiendas, propiedad del usuario autenticado. Dependencias: `Autenticación local` (002).
- 004 · Colaboradores — CRUD de colaborador ligado a tienda (nombre, rol base: gerente titular / suplente / encargado de área / otros). Dependencias: `Tienda` (003).
- 005 · Perfil del colaborador — catálogo de atributos clave-valor y asignación de valores por colaborador (velocidad, fuerza, puntualidad, sexo, confiabilidad, vencimiento de contrato…). Dependencias: `Colaborador` (004).
- 006 · Roles y aptitudes — catálogo de roles y matriz "puede / no puede" por colaborador (quién puede hacer qué y quién no). Dependencias: `Colaborador` (004).
- 007 · Incidencias y peticiones — vacaciones, descanso semanal, cumpleaños, incapacidad, cursos, repuestos/apoyos; fecha + tipo + colaborador. Alimentan la contabilidad de horas a la semana. Dependencias: `Colaborador` (004).
- 008 · Propuesta semanal — formulario (tienda + período + personal seleccionado por perfil) + generación: días de trabajo/descanso, horario de comida cubierto por otro encargado de área, horas/semana menos incidencias. Dependencias: `Tienda` (003), `Colaborador` (004), `Perfil` (005), `Roles` (006), `Incidencias` (007).
- 009 · Tabla semanal coloreada — vista de la propuesta coloreada por trabajador. Dependencias: `Propuesta semanal` (008).
- 010 · Exportar / compartir — PDF / CSV / Sheets / Excel (decidir formato al llegar). Dependencias: `Propuesta semanal` (008) / `Tabla` (009).

> Cada feature nueva se crea como `features/NNN-nombre-feature/` con `spec.md`, `plan.md` y `tasks.md` antes de tocar código.
