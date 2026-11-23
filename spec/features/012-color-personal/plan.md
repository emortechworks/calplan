# 012 · Color del personal — Plan

## Enfoque
Agregar la columna aditiva `color VARCHAR(7) DEFAULT '#ffffff' NOT NULL` en `Personal`, exponerla en `PersonalType` con `ColorType` (`empty_data: '#ffffff'`, validación regex `#RRGGBB`) y renderizarla en Bootstrap 5 con `form-control-color` y `<datalist>` HTML5 de tonos pastel sugeridos (cero JS, cero CDN).

## Implementación
1. `src/Entity/Personal.php`: añadir propiedad `$color = '#ffffff'` (`VARCHAR(7)`, default `'#ffffff'`) y `getColor()` / `setColor(?string)` normalizando vacío/`null` a `'#ffffff'` y a minúsculas.
2. `src/Form/PersonalType.php`: añadir `color` (`ColorType::class`, `required: false`, `empty_data: '#ffffff'`, `Regex('/^#[0-9a-fA-F]{6}$/')`, `attr: ['list' => 'personal-colores-sugeridos']`).
3. `templates/personal/_form.html.twig`: ubicar `color` en la fila de datos básicos con `form-control form-control-color` + `<datalist id="personal-colores-sugeridos">` (blanco + 8 tonos pastel).
4. `templates/personal/index.html.twig`: mostrar muestra circular de color (`border`) junto al nombre en cada tarjeta.
5. `tests/Controller/PersonalControllerTest.php`: cubrir default `#ffffff`, guardado/edición de color y rechazo de formato inválido.
6. Generar y aplicar migración Doctrine en `dev` y `test`; validar `doctrine:schema:validate`, `lint:*` y `php bin/phpunit`.
7. Actualizar `dominio.md`, `roadmap.md`, `spec/README.md` y estado de `012-color-personal`.

## Decisiones
- `VARCHAR(7)` hexadecimal `#RRGGBB` en minúsculas con default `#ffffff` (nunca `NULL`), compatible directo con `<input type="color">`, CSS y futuras exportaciones.
- Validación estricta regex en servidor para impedir inyección en el atributo `style` de Twig.

## Riesgos
- Contraste de blanco `#ffffff` sobre tarjeta blanca: mitigado aplicando borde suave (`border: 1px solid #ced4da`) a la muestra circular en `/personal`.
