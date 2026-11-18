# 004 · Autenticación local — Plan

## Enfoque
Usar los makers de Symfony sobre el Security bundle ya instalado: `make:user` (entidad `User`), `make:security:form-login` (controlador/plantillas login+logout y firewall), `make:registration-form --auto-login` (registro completo). `/cuenta` es un controlador pequeño y protegido. El comando CLI `app:user:reset-password` es un comando simple que reutiliza el password hasher de Symfony. Sin paquetes nuevos.

## Implementación
1. `make:user` → `src/Entity/User.php` + `src/Repository/UserRepository.php` (+ `security.yaml` actualizado por el maker).
2. `make:migration` + `doctrine:migrations:migrate` → tabla `user`.
3. `make:security:form-login` → `SecurityController` (`/login`, `/logout`) y `security.yaml` con `form_login` + `logout` + `users_in_memory` → provider de entidad.
4. `make:registration-form --auto-login --redirect-route=app_cuenta` → `RegistrationController`, `RegistrationFormType` y plantilla.
5. `/cuenta` → controlador + plantilla; protegido vía `access_control`/`#[IsGranted('ROLE_USER')]`.
6. Navbar en `base.html.twig`: muestra login/registro o cuenta/logout según `app.user`.
7. Comando `app:user:reset-password <email>` → busca el usuario, genera contraseña temporal aleatoria, hashea y persiste; imprime el email y la contraseña temporal.
8. Tests funcionales: registro → redirige, `/cuenta` protegida/visible. Verificación manual: registro, login/logout, reset CLI.

## Decisiones
- **Sin verificación de email ni reset por email** hasta decidir servidor de correo; la opción CLI queda para quien tenga el host (`bin/console app:user:reset-password mejor@correo.com`).
- **Registro abierto** a cualquiera en la red (especificado en misión/dominio).
- **Auto-login** tras registro, mejor UX.
- `/cuenta` mínima a propósito: demuestra la cadena auth completa (registro → login → ruta protegida) sin introducir más pantallas antes de 005.
- El maker puede generar código que después se ajuste a mano (plantillas en español, mensajes, etc.); no se cambia la lógica del maker, solo texto/estilo.

## Riesgos
- Los tests funcionales tocan `calplan_db_test` (`dbname_suffix: '_test%env(TEST_TOKEN)%'` en `doctrine.yaml`): se crea con `doctrine:database:create --env=test` + migraciones en env test.
- `make:registration-form` puede generar un coste de hasher bajo a propósito para test.
