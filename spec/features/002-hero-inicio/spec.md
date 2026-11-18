# 002 · Hero para inicio

**Estado:** Propuesta

## Qué hace
Reemplaza la página de inicio (ancla raíz `/`) por un hero de presentación del proyecto, usando las clases por defecto de Bootstrap 5.3 (sin CSS propio ni JS adicional). Muestra el propósito del sitio y un CTA deshabilitado "Autenticación próximamente" (el registro aún no existe).

## Por qué
Primera impresión del producto para quien llega al sitio local, mientras termina de armarse la autenticación (003).

## Criterios de aceptación
- [ ] La página `/` muestra un hero con título "Planeación semanal para tu tienda" y un subtítulo descriptivo, con clases Bootstrap por defecto.
- [ ] Incluye un botón deshabilitado con el texto "Autenticación próximamente".
- [ ] La página hereda de `base.html.twig` (base compartida por todas las páginas).
- [ ] Sin CSS propio inline ni clases inventadas.

## Fuera de alcance
- Registro/login (feature 003 · Autenticación local).
- Otras páginas/secciones; solo el hero.
