# 006 · Íconos de Bootstrap locales (UX Icons) — Plan

## Enfoque
Instalar el paquete oficial `symfony/ux-icons` mediante Composer dentro del contenedor `php` dejando que Symfony Flex registre el bundle y genere la configuración base. Configurar `config/packages/ux_icons.yaml` para leer íconos desde `assets/icons` y deshabilitar `iconify.on_demand` en runtime, de modo que únicamente se rendericen los `.svg` versionados localmente (descargados en desarrollo mediante CLI con `ux:icons:import` o `ux:icons:lock`).

## Implementación
1. Instalar `symfony/ux-icons` vía `composer require symfony/ux-icons` en el contenedor Docker `php`.
2. Revisar `config/packages/ux_icons.yaml` generado por Flex y fijar:
   - `icon_dir: '%kernel.project_dir%/assets/icons'`
   - `iconify.on_demand: false` (bloquea descargas externas en runtime; el CLI `ux:icons:import` / `ux:icons:lock` sigue funcionando en desarrollo).
   - `default_icon_attributes` compatibles con Bootstrap (`width: 1em`, `height: 1em`, `fill: currentColor`, `aria-hidden: true`).
3. Importar el ícono de Bootstrap `bi:calendar-week` con `php bin/console ux:icons:import bi:calendar-week` → `assets/icons/bi/calendar-week.svg`.
4. Renderizar `{{ ux_icon('bi:calendar-week', {class: 'me-2 align-text-bottom'}) }}` junto al título en la barra de navegación (`templates/base.html.twig`).
5. Agregar pruebas en `tests/Icons/UxIconsTest.php` (`WebTestCase`):
   - Renderizado inline `<svg>` del ícono local `bi:calendar-week` en `/` y vía servicio/Twig sin URLs de CDN.
   - Verificación de que un ícono inexistente lanza `IconNotFoundException` (garantizando que `on_demand` está desactivado).
6. Validar `lint:container`, `lint:twig templates/`, `lint:yaml config/` y `php bin/phpunit`.
7. Actualizar `spec/constitution/tech-stack.md`, `spec/constitution/roadmap.md` y el estado de la feature `006-bootstrap-icons`.

## Decisiones
- Se elige `symfony/ux-icons` sobre la fuente CSS `bootstrap-icons` para servir únicamente `<svg>` inline de los íconos usados (menor peso, sin webfonts, soporta Bootstrap Icons `bi:*` y cualquier pack de Iconify o SVG propio).
- Se fija `iconify.on_demand: false` en todos los entornos para cumplir el límite duro "nada externo en frontend" de `tech-stack.md`; cada ícono nuevo se importa explícitamente por CLI (`ux:icons:import` / `ux:icons:lock`) y se versiona en `assets/icons/`.

## Riesgos
- Verificado: mantener `iconify.enabled: true` (por defecto) junto con `iconify.on_demand: false` elimina los servicios de descarga en runtime (`.ux_icons.iconify_on_demand_registry` y `.ux_icons.auto_lock_icon_registry`) mientras conserva operativos `ux:icons:import` y `ux:icons:lock` por CLI.
- `UXIconsBundle` asigna automáticamente `aria-hidden="true"` cuando no se pasa etiqueta accesible (`aria-label`/`title`), por lo que no hace falta repetirlo manualmente en íconos decorativos.
