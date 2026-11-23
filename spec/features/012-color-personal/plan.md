# 012 · Color del personal — Plan

## Enfoque
Agregar la columna aditiva `color VARCHAR(7) DEFAULT '#ffffff' NOT NULL` en `Personal`, definir una paleta cerrada de 20 colores claros/pastel en `Personal::COLORES_DISPONIBLES`, exponerla en `PersonalType` con `ChoiceType` (`expanded: true`, `empty_data: '#ffffff'`, constraint `Choice`) y renderizarla en Bootstrap 5 como círculos seleccionables (`btn-check` + `.selector-color-swatch`), sin JS ni CDN.

## Implementación
1. `src/Entity/Personal.php`: añadir constante `COLORES_DISPONIBLES` (20 colores claros), propiedad `$color = '#ffffff'` (`VARCHAR(7)`, default `'#ffffff'`) y `getColor()` / `setColor(?string)`.
2. `src/Form/PersonalType.php`: añadir `color` (`ChoiceType::class`, `expanded: true`, `multiple: false`, `required: false`, `empty_data: '#ffffff'`, `Choice` sobre `Personal::COLORES_DISPONIBLES`).
3. `assets/styles/app.css` y `templates/personal/_form.html.twig`: renderizar las 20 muestras circulares clicables (`btn-check` + `.selector-color-swatch`) sin permitir colores libres/oscuros.
4. `templates/personal/index.html.twig`: mostrar muestra circular de color (`border`) junto al nombre en cada tarjeta.
5. `tests/Controller/PersonalControllerTest.php`: cubrir 20 opciones en el formulario, default `#ffffff`, guardado de color de la paleta y rechazo por POST de colores fuera de la paleta (`#000000`).
6. Generar y aplicar migración Doctrine en `dev` y `test`; validar `doctrine:schema:validate`, `lint:*` y `php bin/phpunit`.
7. Actualizar `dominio.md`, `roadmap.md`, `spec/README.md` y estado de `012-color-personal`.

## Decisiones
- Paleta cerrada de 20 colores claros/pastel (`Personal::COLORES_DISPONIBLES`) en lugar de selector libre `<input type="color">` para garantizar contraste y legibilidad con texto oscuro en la tabla semanal.
- `VARCHAR(7)` hexadecimal `#RRGGBB` en minúsculas con default `#ffffff` (nunca `NULL`), compatible directo con CSS y futuras exportaciones.

## Riesgos
- Contraste de blanco `#ffffff` sobre tarjeta blanca: mitigado aplicando borde suave (`border: 1px solid #ced4da`) a la muestra circular en `/personal`.
