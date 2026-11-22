# 008 · Regla de dependencias en el spec

**Estado:** Hecho ✅

## Dependencias
- Ninguna (regla transversal del proceso SDD; beneficia a todas las features pendientes a partir de `009`).

## Qué hace
Incorpora en el proceso SDD (`spec/README.md`, `spec/constitution/roadmap.md` y la plantilla `spec_template/`) una regla obligatoria para identificar dependencias entre incrementos antes de iniciar cualquier feature y reordenar/renumerar el roadmap cuando una feature dependa de otra que aún no está completada.

## Por qué
Evitar empezar incrementos sobre catálogos o entidades inexistentes (como ocurrió cuando `Personal` requería primero el catálogo `Roles`), manteniendo el roadmap en orden topológico estricto sin retrabajo ni dependencias rotas.

## Criterios de aceptación
- [x] `spec/README.md` incluye el paso obligatorio de verificación de dependencias y la sección **Regla de orden por dependencias** (prerrequisitos primero, reordenar solo pendientes, nunca renumerar lo ya en `Hecho ✅`, romper ciclos extrayendo la base común).
- [x] `spec/constitution/roadmap.md` documenta la regla, incorpora `008 · Regla de dependencias en el spec` en `Hecho ✅` y verifica que toda feature pendiente (`009`–`014`) solo dependa de números estrictamente menores.
- [x] `spec/constitution/dominio.md` mantiene sincronizadas las referencias numéricas de las features pendientes (`009`, `010`, `012`).
- [x] La plantilla `spec_template/` (`README.md`, `constitution/roadmap.md` y `features/NNN-nombre-feature/spec.md`) incorpora la sección `## Dependencias` y la regla para futuros proyectos y features.

## Fuera de alcance
- Scripts o validadores automáticos de grafos de dependencias (la verificación se realiza en la fase de especificación/planificación antes de crear la rama).
