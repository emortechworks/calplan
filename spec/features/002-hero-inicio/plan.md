# 002 · Hero para inicio — Plan

## Enfoque
Reescribir únicamente `templates/home/index.html.twig` con componentes Bootstrap estándar: `container`, bloque hero `bg-body-tertiary rounded-3`, `display-3`/`lead`, `hr`, botón `.btn .btn-dark .btn-lg` deshabilitado. Sin CSS propio ni JS adicional. `templates/base.html.twig` no cambia: sigue siendo la base compartida de todas las páginas del proyecto.

## Implementación
1. Copiar el hero dentro de `templates/home/index.html.twig` (solo Twig + clases Bootstrap).
2. Verificar `/` (200) y `php bin/phpunit`.

## Decisiones
- CTA **deshabilitado** ("Autenticación próximamente"): no hay registro hasta la feature 003; el botón no lleva a ninguna ruta.
- Se omite el saludo "Hello HomeController!"; el criterio de 001 ya se cumplió y el rediseño lo reemplaza.
- `base.html.twig` NO se modifica (ya es la base de todas las páginas, con navbar y footer Bootstrap).

## Riesgos
- Ninguno relevante; cambio solo de vista.
