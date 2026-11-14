# 001 · Controlador de inicio — Plan

## Enfoque

Se crea un controlador de que proporcione un punto de anclage raíz para el sitio.

## Implementación

1. Usar el comando de symfony para la creación genérica del controlador

## Decisiones

- El maker genera por defecto `/home`; se cambió a `/` porque el plan pide un punto de anclaje raíz para el sitio.
- Se creó el controlador con `--with-tests` (la constitución exige un test por feature): `tests/Controller/HomeControllerTest.php`, GET `/` → 200.

## Riesgos

- Ninguno detectado.
