# 013 · Perfil del personal — Tareas

- [x] Crear entidades `AtributoPerfil` y `ValorPerfil`, `AtributoPerfilRepository` y actualizar `Personal` / `PersonalRepository`.
- [x] Generar y aplicar migración Doctrine en `dev` y `test`.
- [x] Implementar CRUD de `/perfil/atributos` (`AtributoPerfilType`, `AtributoPerfilController`, Stimulus `atributo_tipo_controller.js` y vistas Twig) incluyendo acción idempotente **Cargar sugeridos**.
- [x] Integrar sección opcional de perfil en el formulario de `Personal` y mostrar botón **Atributos de perfil** + badges en `/personal`.
- [x] Importar íconos locales con `ux:icons:lock` (sin CDN).
- [x] Añadir `AtributoPerfilControllerTest` y pruebas de perfil en `PersonalControllerTest`.
- [x] Validar `doctrine:schema:validate`, `lint:*` y `php bin/phpunit`.
- [x] Actualizar `dominio.md`, `roadmap.md`, `tech-stack.md`, `spec/README.md` y cerrar `013-perfil-personal`.
