# Dominio

_Modelo de datos del negocio. Entidades centrales, relaciones y reglas no obvias. Se amplía con cada feature._

## Entidades

### Usuario
- Persona que accede al sistema. **Cualquiera en la red local puede registrarse**; no hay aprobación ni cuenta de administrador.
- Se identifica por su **email** y guarda su contraseña con hash seguro. Rol único: `ROLE_USER`.
- **Propiedad de datos (privacidad por cuenta):** cada usuario ve únicamente sus tiendas, su personal, sus incidencias y sus propuestas; no hay vista global ni datos compartidos entre usuarios.
- Un `Usuario` tiene N `Tienda` y N `Personal`; cada `Personal` pertenece directamente a su `Usuario` y se vincula `ManyToMany` con 0, 1 o varias `Tienda` del mismo usuario.

### Tienda
- Sucursal **propiedad de un usuario** donde se hace la planeación. Define qué roles del catálogo del usuario existen en esa sucursal (`ManyToMany` `tienda_rol`, `010`). El personal puede asignarse a una o varias tiendas del mismo usuario; la propuesta se genera por tienda.

### Personal
- Persona asignable, propiedad directa de un `Usuario` (solo su propietario puede verla y asignarla).
- **Relación con tienda:** `ManyToMany` con `Tienda` (0, 1 o varias sucursales del mismo usuario; permite dar de alta sin bloquear si aún no tiene tienda asignada).
- **Campos fijos operativos y de calendario:**
  - `nombre`: nombre completo en un solo campo (obligatorio).
  - `horasSemanales`: jornada semanal pactada en horas (entero obligatorio, default `48`).
  - `fechaNacimiento`: opcional (`nullable`); alimenta alertas/incidencias de cumpleaños.
  - `finContrato`: opcional (`nullable`); alerta de vencimiento durante la planeación semanal.
  - `activo`: borrado lógico (default `true`), igual que `Tienda`.
- **Roles y aptitudes por tienda:** se sirve de los roles habilitados en cada `Tienda` (`010`) mediante asignación ordenada por sucursal (`PersonalRol(personal, tienda, rol, prioridad)`, `#1` = mayor peso / rol principal en esa tienda, `#2`, `#3`… secundarios; roles ausentes = no los desempeña en esa tienda).
- **Perfil flexible:** cualidades descriptivas definibles por el usuario (`011`); no van en columnas fijas.

### Rol / Asignación
- Catálogo ampliable de roles propiedad del `Usuario` (`007`): `nombre` único por usuario, `descripcion` opcional y `activo` (borrado lógico). Cada `Tienda` selecciona qué roles de este catálogo existen en ella (`tienda_rol`, `010`).
- Por miembro del personal y por tienda (`009` / `010`): relación ordenada con `Rol` (`PersonalRol` con `(personal_id, tienda_id, rol_id, prioridad)`, donde `#1` tiene el mayor peso dentro de esa sucursal); los roles ausentes son los que no desempeña en esa tienda.

### Atributo de perfil (clave-valor)
- Catálogo de atributos de cualidades propiedad del `Usuario` (compartido entre todas sus tiendas), con borrado lógico (`activo`) para no perder valores históricos.
- Cada definición de atributo incluye:
  - `nombre` (único por usuario).
  - `tipo`: `escala` (1–5), `booleano` (Sí/No), `lista` (select con `opciones`), `numero` o `texto` corto. No se permite cambiar de `tipo` una vez tiene valores capturados.
  - `opciones`: lista cerrada de valores (solo cuando `tipo = lista`).
  - `peso`: importancia global para el algoritmo de propuesta semanal (`0` = solo informativo, `1–5` = prioridad; en `013` se complementa con reglas por día/tienda).
  - `orden`: orden personalizable en pantalla.
- **Valores por persona (`ValorPerfil`):** todos los atributos del perfil son **opcionales** al dar de alta o editar personal.
- **Catálogo inicial sugerido** (editable/desactivable al crear cuenta o mediante acción "Cargar sugeridos"):
  - `Velocidad` (`escala` 1–5, peso `3`)
  - `Fuerza` (`escala` 1–5, peso `3`)
  - `Puntualidad` (`escala` 1–5, peso `4`)
  - `Confiabilidad` (`escala` 1–5, peso `5`)
  - `Sexo` (`lista`: Femenino / Masculino / Otro, peso `0`)

### Período / intervalo de tiempo
- La **semana calendario (lunes–domingo)** sobre la que se genera la propuesta; acota la contabilidad de horas (**48 h de ley** o las `horasSemanales` pactadas) y la tabla resultante.

### Incidencia / ausencia
- Días que reducen la carga de la semana: vacaciones, descanso semanal, cumpleaños, incapacidad, curso, repuesto por apoyo, etc. Fecha + tipo; impactan las horas a la semana.

### Asignación diaria
- Día concreto: fecha, miembro del personal, rol asignado, horario de comida y quién lo cubre (otro encargado de área). Regla: en ningún momento un área queda sin vigilar.

### Propuesta semanal
- Resultado agregado de la semana: tabla de la semana coloreada por trabajador, descargable/compartible. Utiliza los `peso` de los atributos del perfil y reglas configurables por día/tienda.

## Reglas no obvias

- Toda comida asignada debe ser cubierta por otro encargado de área (misma u otra área); nunca sin cobertura — por eso debe haber varios con ese rol.
- La contabilidad es **por semana**, no por día: **48 horas** pactadas por semana menos incidencias.
- Restricciones de rol: "no siempre" ciertas asignaciones; "algunos no pueden tener ciertos roles".
