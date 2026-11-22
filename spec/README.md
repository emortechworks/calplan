# spec/ — Spec Driven Development

## Estructura

```
spec/
├── constitution/            ← reglas estables del proyecto (cambian poco)
│   ├── mission.md           ← qué construimos y para quién
│   ├── tech-stack.md        ← tecnologías, convenciones y límites
│   ├── dominio.md           ← modelo de datos y reglas del negocio
│   └── roadmap.md           ← orden de las features
└── features/                ← una carpeta por feature
    └── NNN-nombre-feature/
        ├── spec.md          ← qué hace + criterios de aceptación
        ├── plan.md          ← cómo se implementa
        └── tasks.md         ← checklist de tareas
```
## Regla de orden por dependencias

Antes de iniciar cualquier feature (o al incorporar una nueva al backlog), se identifican las entidades, catálogos y capacidades que consume:

1. **Declaración obligatoria:** cada feature indica sus **Dependencias** en `constitution/roadmap.md` y en la sección `## Dependencias` de su `spec.md`.
2. **Prerrequisitos primero:** ninguna feature se implementa mientras dependa de otra que aún no esté en **Hecho ✅**. Si se descubre que un incremento depende de otro que está después en el backlog (o aún no existe), **primero se reordena y renumera `constitution/roadmap.md`** (y sus referencias en `constitution/dominio.md` y este archivo) para mover la dependencia antes del incremento dependiente, y se aborda primero la dependencia.
3. **Histórico inmutable:** nunca se renumeran features ya marcadas en **Hecho ✅**; únicamente se reordenan/renumeran las pendientes (**Siguiente** y **Backlog**).
4. **Sin ciclos:** si dos features se necesitan mutuamente, se extrae el catálogo o entidad base común a una feature previa independiente.

## Flujo para una feature nueva

0. **Verificar dependencias:** revisar contra `constitution/roadmap.md` y `constitution/dominio.md` que todas las dependencias de la feature estén en **Hecho ✅**. Si falta un prerrequisito, aplicar primero la **Regla de orden por dependencias** y reordenar el roadmap antes de crear la rama.
1. Crear `features/NNN-nombre-feature/` con el siguiente número libre (`001`, `002`, …).
2. Escribir `spec.md`: qué hace, por qué, dependencias y criterios de aceptación medibles.
3. Escribir `plan.md`: enfoque técnico y decisiones, respetando `constitution/tech-stack.md`.
4. Desglosar en `tasks.md` y marcar el progreso.
5. Implementar y validar (build/tests/lint o lo que defina la constitución),
   con comentarios mínimos: solo para lo que el código no se explica por sí
   mismo —el "por qué", no el "qué"—, en español, en tercera persona y directos
   ("asigna", "calcula", "valida"). Comentar solo las secciones importantes; nada
   redundante.
6. Actualizar la documentación de la feature en el mismo commit: `spec.md` (Estado → Hecho ✅, criterios aceptados ✅), `plan.md` (Decisiones y Riesgos), `tasks.md` (checkmarks). Mover la feature a "Hecho" en `constitution/roadmap.md`.

## Flujo de entrega por feature

Cada vez que se empiece un nuevo feature, se sigue este flujo:

1. **Crear rama nueva** antes de tocar código: `git switch -c feature-NNN-nombre`
   (`NNN` es el número de la feature según `constitution/roadmap.md`, ej. `feature-012-perfil-personal`).
2. **Implementar** dentro de esa rama.
3. **Commit** de la feature.
4. **Preguntar** por el push de la rama → si aprobas, pusheamos.
5. **Preguntar** por el merge a `main` de la rama terminada → si aprobas, fusionamos.
6. Las ramas terminadas se **mantienen** (no se borran).

Hoy se continúa desde `main`; la siguiente feature (012 · Perfil del personal) arrancará con la rama nueva cuando se acelere.
