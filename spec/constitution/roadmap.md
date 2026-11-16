# Roadmap

## Hecho ✅

- [001 · Controlador de inicio](features/001-controlador-inicio/) — ancla raíz `/` con el saludo de `HomeController` ([spec](features/001-controlador-inicio/spec.md))

## Siguiente 🔜

- 002 · Tiendas — CRUD básico de sucursales (entidad `Tienda`). Primer paso del flujo del gerente (selección de tienda); sin dependencias.

## Backlog / ideas 💡 (orden de dependencias)

- 003 · Colaboradores — CRUD de colaborador ligado a tienda: nombre, rol base (gerente titular / suplente / encargado de área / otros). Dependencias: `Tienda` (002).
- 004 · Perfil del colaborador — catálogo de atributos clave-valor y asignación de valores por colaborador (velocidad, fuerza, puntualidad, sexo, confiabilidad, vencimiento de contrato…). Dependencias: `Colaborador` (003).
- 005 · Roles y aptitudes — catálogo de roles y matriz "puede / no puede" por colaborador (quién puede hacer qué y quién no). Dependencias: `Colaborador` (003).
- 006 · Incidencias y peticiones — vacaciones, descanso semanal, cumpleaños, incapacidad, cursos, repuestos/apoyos; fecha + tipo + colaborador. Alimentan la contabilidad de horas a la semana. Dependencias: `Colaborador` (003).
- 007 · Propuesta semanal — formulario (tienda + período + personal seleccionado por perfil) + generación: días de trabajo/descanso, horario de comida cubierto por otro encargado de área, horas/semana menos incidencias. Dependencias: `Tienda` (002), `Colaborador` (003), `Perfil` (004), `Roles` (005), `Incidencias` (006).
- 008 · Tabla semanal coloreada — vista de la propuesta coloreada por trabajador. Dependencias: `Propuesta semanal` (007).
- 009 · Exportar / compartir — PDF / CSV / Sheets / Excel (decidir formato al llegar). Dependencias: `Propuesta semanal` (007) / `Tabla` (008).
- 010 · Autenticación local — login básico para proteger los datos laborales. Sin dependencias; al final, mientras el proyecto corra solo en la red local.

> Cada feature nueva se crea como `features/NNN-nombre-feature/` con `spec.md`, `plan.md` y `tasks.md` antes de tocar código.
