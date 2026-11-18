# 003 · JavaScript sin CDN

**Estado:** Propuesta

## Qué hace
Elimina las referencias a scripts/estilos externos (CDN) y fija la regla de que todo recurso de frontend se sirve de forma local (AssetMapper / importmap) en todo el proyecto.

## Por qué
Proyecto local: la página debe renderizar sin depender de la red ni de servicios externos; privacidad y autonomía total.

## Criterios de aceptación
- [ ] El HTML servido no contiene referencias a recursos externos (CDN) de JS/CSS.
- [ ] Se elimina el bloque `frankenphp_hot_reload` de `base.html.twig` (sus scripts venían de jsdelivr).
- [ ] Queda documentada la regla "sin recursos externos" en `spec/constitution/tech-stack.md`.

## Fuera de alcance
- Verificación/linter automático anti-regresión.
- Cambios en la distribución de assets (importmap ya sirve todo local).
