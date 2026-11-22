# 010 · Roles por tienda — Plan

## Enfoque
Modelo 1 (`Tienda ↔ Rol` `ManyToMany` mediante tabla `tienda_rol`, manteniendo `Rol` como catálogo reutilizable por usuario) combinado con Opción 2B (`PersonalRol(personal, tienda, rol, prioridad)` con clave única `(personal_id, tienda_id, rol_id)`). En el formulario de `Personal`, cada tienda seleccionada despliega su propio panel de roles priorizados controlado por Stimulus, sin peticiones AJAX ni dependencias externas.

## Implementación
1. **Entidades y repositorios:**
   - `Tienda`: añadir `ManyToMany` a `Rol` (`tienda_rol`) + helpers `getRoles()`, `addRol()`, `removeRol()`.
   - `TiendaRepository::delUsuario()`: `leftJoin('t.roles', 'r')->addSelect('r')`.
   - `PersonalRol`: añadir `ManyToOne` a `Tienda` (`nullable: false`, `onDelete: 'CASCADE'`) y actualizar índice único a `UNIQ_PERSONAL_TIENDA_ROL (personal_id, tienda_id, rol_id)`.
   - `Personal`: actualizar `reemplazarRolesPorTienda(array $rolesPorTienda)` (actualiza `prioridad` en sitio por clave `tiendaId:rolId` en un único `flush()`, eliminando asignaciones de tiendas desmarcadas o roles quitados) y `getRolesOrdenadosPorTiendaIds()`.
   - `PersonalRepository::delUsuario()`: cargar `pr.tienda` junto a `pr.rol`, ordenado por `pr.prioridad ASC`.
2. **Migración Doctrine:**
   - Generar migración creando `tienda_rol`, ajustando `personal_rol` (`tienda_id` FK `ON DELETE CASCADE` + `UNIQ_PERSONAL_TIENDA_ROL`). Si existen filas previas en `personal_rol` sin `tienda_id`, limpiar o migrar antes de aplicar `NOT NULL`.
   - Aplicar en `dev` y `test`.
3. **Tienda CRUD:**
   - `TiendaType`: añadir `roles` (`EntityType` múltiple/expandido filtrado a roles propios activos o ya asignados a esa tienda), opciones requeridas `usuario` y `tienda`.
   - `TiendaController`: pasar `usuario` y `tienda` al crear el formulario.
   - `templates/tienda/_form.html.twig`: chips `btn-check` de roles; `index.html.twig`: badges de roles por tienda.
4. **Personal CRUD + Stimulus:**
   - `PersonalType`: reemplazar `rolesOrdenados` único por un mapa/JSON (`rolesPorTienda` en `HiddenType` no mapeado, ej. `{"1":[3,2],"4":[2]}`), validado estrictamente en servidor.
   - `PersonalController`: construir los paneles por cada tienda seleccionable con únicamente los roles activos de esa tienda (más roles inactivos ya asignados a esa persona en esa tienda). Validar en servidor que toda tienda del mapa esté seleccionada en `$persona->getTiendas()` y que todo rol pertenezca a esa tienda (o a una asignación previa de rol inactivo en esa tienda).
   - `assets/controllers/roles_ordenados_controller.js`: escuchar cambios en los chips `btn-check` de tiendas (`data-action="change->roles-ordenados#actualizarTiendas"`), mostrar/ocultar el panel de cada tienda marcada, gestionar `+`/`↑`/`↓`/`×` de forma independiente dentro de cada panel y serializar solo los paneles visibles en JSON dentro del `HiddenType`.
   - `templates/personal/_form.html.twig` e `index.html.twig`: renderizar bloques por tienda en el formulario y badges agrupados por tienda en el listado.
5. **Pruebas y documentación:**
   - Ampliar `TiendaControllerTest` (asignar/editar roles por tienda, rechazo de rol ajeno) y actualizar/ampliar `PersonalControllerTest` (prioridades independientes por tienda, rechazo de rol que no pertenece a la tienda, reordenamiento en sitio sin colisión).
   - Validar `lint:container`, `lint:twig`, `lint:yaml` y `php bin/phpunit`.
   - Actualizar `dominio.md`, `roadmap.md` (`010` en Hecho, backlog renumerado `011`–`015`), `spec/README.md` y estado de `010-roles-por-tienda`.

## Decisiones
- Catálogo global de `Rol` + `tienda_rol` (`ManyToMany`) permite reutilizar roles idénticos entre varias sucursales y tener roles exclusivos por sucursal sin duplicación.
- `PersonalRol` con `(personal_id, tienda_id, rol_id, prioridad)` evita cualquier conflicto entre roles distintos de distintas tiendas cuando una persona trabaja en múltiples sucursales.
- Se serializa `rolesPorTienda` como JSON `{ "<tiendaId>": [<rolId1>, <rolId2>, ...] }` en un campo oculto validado en servidor, evitando endpoints AJAX y manteniendo la operación 100 % síncrona y testeable con `WebTestCase`.

## Riesgos
- Filas existentes en `personal_rol` sin `tienda_id` en entornos dev/test al ejecutar la migración: verificar antes de migrar y limpiar filas sin `tienda_id` en el `up()` de la migración si hubiera alguna (o backfillear desde `personal_tienda`).
