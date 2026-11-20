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
## Flujo para una feature nueva

1. Crear `features/NNN-nombre-feature/` con el siguiente número libre (`001`, `002`, …).
2. Escribir `spec.md`: qué hace, por qué y criterios de aceptación medibles.
3. Escribir `plan.md`: enfoque técnico y decisiones, respetando `constitution/tech-stack.md`.
4. Desglosar en `tasks.md` y marcar el progreso.
5. Implementar y validar (build/tests/lint o lo que defina la constitución),
   con comentarios mínimos: solo para lo que el código no se explica por sí
   mismo —el "por qué", no el "qué"—, en español, en tercera persona y directos
   ("asigna", "calcula", "valida"). Comentar solo las secciones importantes; nada
   redundante.
6. Actualizar la documentación de la feature en el mismo commit: `spec.md` (Estado → Hecho ✅, criterios aceptados ✅), `plan.md` (Decisiones y Riesgos), `tasks.md` (checkmarks). Mover la feature a "Hecho" en `constitution/roadmap.md`.
