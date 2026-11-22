# 011 · renombrar-sucursal — Tareas

- [x] cerrar `style-indicador-carga` en `main` y crear rama `feature-011-renombrar-sucursal` + spec/plan/tasks
- [x] renombrar entidades, repositorios, formularios y controladores (`Tienda` → `Sucursal`)
- [x] renombrar vistas Twig (`templates/sucursal/`), Stimulus (`roles_ordenados_controller.js`) y tests (`SucursalControllerTest`)
- [x] crear migración Doctrine con `RENAME TABLE` / `CHANGE` preservando datos y aplicarla en `dev` y `test`
- [x] validar `doctrine:schema:validate`, `lint:*`, `php bin/phpunit` y cero referencias residuales
- [x] actualizar `spec/constitution/` (`dominio.md`, `mission.md`, `roadmap.md`), `spec/README.md` y marcar spec `011` como Hecho
