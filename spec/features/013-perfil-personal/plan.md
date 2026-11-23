# 013 · Perfil del personal — Plan

## Enfoque
Modelar dos entidades nuevas (`AtributoPerfil` propiedad de `User` y `ValorPerfil` puente entre `Personal` y `AtributoPerfil`) sin columna `peso`, exponer un CRUD completo en `/perfil/atributos` accesible mediante botón desde `/personal`, e integrar la captura opcional de valores en el formulario de `Personal` con validación estricta en servidor según `tipo` y `opciones`.

## Implementación
1. `src/Entity/AtributoPerfil.php`, `src/Entity/ValorPerfil.php` y `src/Repository/AtributoPerfilRepository.php`: entidades con índices únicos `(usuario_id, nombre)` y `(personal_id, atributo_id)`, constantes de tipos y sugeridos iniciales, y consultas ordenadas por `activo DESC, orden ASC, nombre ASC`.
2. `src/Entity/Personal.php` y `src/Repository/PersonalRepository.php`: relación `OneToMany` `$valoresPerfil`, método `reemplazarValoresPerfil()` actualizando en sitio para evitar colisiones de índice único en un solo `flush()`, y precarga `leftJoin` + `addSelect` en el listado.
3. Migración Doctrine (`make:migration` → `doctrine:migrations:migrate` en `dev` y `test`).
4. `src/Form/AtributoPerfilType.php`, `src/Controller/AtributoPerfilController.php`, `assets/controllers/atributo_tipo_controller.js` y `templates/atributo_perfil/*`: CRUD de atributos, bloqueo de cambio de `tipo` cuando ya hay valores y acción POST idempotente `sugeridos`.
5. `src/Controller/PersonalController.php`, `templates/personal/_form.html.twig` y `templates/personal/index.html.twig`: sección opcional de perfil en el formulario, validación en servidor de `perfil[atributoId]`, botón **Atributos de perfil** y badges en el listado.
6. `php bin/console ux:icons:lock` para importar localmente los íconos nuevos (`bi:sliders`, `bi:stars`).
7. `tests/Controller/AtributoPerfilControllerTest.php` y ampliación de `tests/Controller/PersonalControllerTest.php`.
8. Actualización de `dominio.md`, `roadmap.md`, `tech-stack.md`, `spec/README.md` y documentación de `013`.

## Decisiones
- **100 % descriptivo:** se omite el campo `peso` y se elimina la dependencia de `013` en `015 · Propuesta semanal` hasta una futura iteración si se decide ponderar cualidades.
- **Acceso al catálogo desde `/personal`:** botón **Atributos de perfil** en lugar de nuevo enlace en la navbar para mantener la navegación principal enfocada en las entidades centrales.
- **Valores almacenados en `VARCHAR(255)` normalizado:** simplifica un único `ValorPerfil` por `(personal, atributo)` manteniendo validación estricta por `tipo` (`escala` 1–5, `booleano` `1`/`0`, `lista` en `opciones`, `numero` numérico, `texto` ≤ 120 caracteres).

## Riesgos
- **Colisión en `UNIQ_VALOR_PERFIL_PERSONAL_ATRIBUTO` al actualizar valores en un solo `flush()`:** mitigado actualizando en sitio los `ValorPerfil` existentes dentro de `Personal::reemplazarValoresPerfil()`.
- **Cambio de `tipo` con datos previos inconsistentes:** mitigado consultando si el atributo tiene registros en `valor_perfil` para deshabilitar el selector y rechazar el cambio en servidor.
