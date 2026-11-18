# 004 · autenticacion-local — Tareas

- [x] crear spec/plan/tasks
- [x] `make:user` (entidad User + repositorio, `security.yaml` actualizado)
- [x] migración (crear tabla `user`) y `doctrine:migrations:migrate`
- [x] `make:security:form-login` (`/login`, `/logout`, firewall + provider)
- [x] `make:registration-form --auto-login --redirect-route=app_cuenta`
- [x] `/cuenta`: controlador + plantilla protegida (muestra email)
- [x] navbar: enlaces de sesión según `app.user`
- [x] comando `app:user:reset-password <email>`
- [x] base de tests `calplan_db_test` (usuario MySQL propio `calplan_test` con permisos solo sobre esa base) + `php bin/phpunit`
- [x] verificación manual: registro, login/logout, `/cuenta`, reset CLI
- [x] mover la feature a "Hecho" en roadmap.md
