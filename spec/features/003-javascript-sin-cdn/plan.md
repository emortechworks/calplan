# 003 · JavaScript sin CDN — Plan

## Enfoque
Borrar el único bloque con recursos externos (`frankenphp_hot_reload`, scripts desde jsdelivr) en `templates/base.html.twig` y fijar la regla en la constitución (`tech-stack.md`, sección Límites duros): nada externo se referencia jamás; todo por AssetMapper/importmap.

## Implementación
1. Borrar el bloque `{% set frankenphp_hot_reload ... {% endif %}` (y su comentario) de `base.html.twig`.
2. Añadir el límite duro "sin recursos externos" en `tech-stack.md`.
3. Smoke: `curl /` (200) y `php bin/phpunit`.

## Decisiones
- No se sustituyen los scripts FrankenPHP por copias locales: no se usa FrankenPHP; son código muerto.
- Los enlaces de documentación en comentarios YAML no se tocan (no descargan nada).

## Riesgos
- Ninguno relevante; solo eliminación.
