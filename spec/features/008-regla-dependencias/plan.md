# 008 · Regla de dependencias en el spec — Plan

## Enfoque
Fijar la regla de análisis y reordenamiento por dependencias directamente en los documentos normativos del proceso SDD (`spec/README.md` y `spec/constitution/roadmap.md`) y propagarla a la plantilla (`spec_template/`) para que toda feature futura declare su sección `## Dependencias` y valide el orden del roadmap antes de crear rama o escribir código.

## Implementación
1. Añadir el paso `0. Verificar dependencias` y la sección `## Regla de orden por dependencias` en `spec/README.md`.
2. Actualizar `spec/constitution/roadmap.md`: agregar la nota normativa de orden por dependencias, mover `008 · Regla de dependencias en el spec` a `Hecho ✅` y renumerar el backlog pendiente (`009` a `014`) comprobando orden topológico estricto.
3. Sincronizar las referencias numéricas de features pendientes en `spec/constitution/dominio.md` (y reflejar el acuerdo de `PersonalRol` con prioridad ordenada).
4. Propagar la regla y la sección `## Dependencias` a `spec_template/README.md`, `spec_template/constitution/roadmap.md` y `spec_template/features/NNN-nombre-feature/spec.md`.

## Decisiones
- Solo se reordenan y renumeran features pendientes (`Siguiente` y `Backlog`); las features en `Hecho ✅` nunca cambian de número para preservar el histórico de ramas y commits.
- La comprobación ocurre **antes** de crear la rama y escribir código, evitando abrir ramas numeradas incorrectamente.
- Se añade la sección `## Dependencias` a la plantilla de `spec.md` para forzar la declaración explícita en cada incremento nuevo.

## Riesgos
- Ninguno en código de la aplicación; cambio estrictamente documental sobre `spec/` y `spec_template/`.
