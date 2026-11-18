# 004 · Autenticación local

**Estado:** Hecho ✅

## Qué hace
Permite que cualquier persona de la red local se registre con email y contraseña, inicie y cierre sesión, y protege la página `/cuenta` (muestra el email de la sesión). Rol único `ROLE_USER`. Recuperación de contraseña solo por comando CLI (`app:user:reset-password`); sin flujo por email.

## Por qué
Primer paso del sistema multiusuario: separar sesiones y datos por cuenta; base para la propiedad de tiendas y personal (005+).

## Criterios de aceptación
- [x] Un visitante puede registrarse en `/registro` (email único, contraseña con confirmación) y queda autenticado tras registrarse.
- [x] `/login` autentica con email + contraseña; `/logout` cierra sesión.
- [x] `/cuenta` redirige a `/login` cuando no hay sesión y muestra el email cuando hay sesión.
- [x] Existe el comando `app:user:reset-password <email>` que genera una contraseña temporal y la imprime en consola.
- [x] `php bin/phpunit` pasa; el `HomeControllerTest` sigue en verde.

## Fuera de alcance
- Verificación de email, recuperación por correo, OAuth/SSO.
- Roles adicionales (admin), panel de administración.
- Gestión de usuarios desde la interfaz (listar, borrar, desactivar).
