# 006 · Íconos de Bootstrap locales (UX Icons)

**Estado:** Hecho ✅

## Qué hace
Instala y configura `symfony/ux-icons` en modo estrictamente local (`iconify.on_demand: false`) para renderizar íconos de Bootstrap Icons (`bi:*`) como `<svg>` inline desde Twig mediante `{{ ux_icon('bi:...') }}`, sin CDN ni peticiones a servicios externos en tiempo de ejecución.

## Por qué
Permitir el uso de íconos de Bootstrap en toda la aplicación manteniendo la regla constitucional de cero recursos externos en frontend y aprovechando el ecosistema oficial Symfony UX.

## Criterios de aceptación
- [x] `symfony/ux-icons` instalado vía Composer con su recipe de Symfony Flex.
- [x] `config/packages/ux_icons.yaml` desactiva las descargas en caliente (`iconify.on_demand: false`) y define atributos por defecto limpios para alinear los SVG con texto de Bootstrap.
- [x] Al menos un ícono local de Bootstrap Icons (`bi:calendar-week`) importado en `assets/icons/bi/` y mostrado en la navbar (`templates/base.html.twig`) como `<svg>` inline.
- [x] El HTML servido en `/` no contiene referencias a CDN ni recursos externos y renderiza el SVG inline del ícono local.
- [x] Un ícono no importado falla localmente (`IconNotFoundException`) sin intentar descargarlo de internet.
- [x] `php bin/phpunit` en verde (incluyendo tests específicos de UX Icons).
- [x] `spec/constitution/tech-stack.md` y `spec/constitution/roadmap.md` actualizados en el mismo commit.

## Fuera de alcance
- Rediseño masivo de vistas existentes o reemplazo de textos por íconos en todos los CRUDs (se irán incorporando por vista según necesidad).
- Instalación de la fuente completa `bootstrap-icons` por CSS/woff2 o bundlers npm externos.
