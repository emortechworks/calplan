# Dominio

_Modelo de datos del negocio. Entidades centrales, relaciones y reglas no obvias. Se amplía con cada feature._

## Entidades

### Tienda
- Sucursal donde se hace la planeación. Cada colaborador pertenece al menos a una; la propuesta se genera por tienda.

### Colaborador (trabajador)
- Persona asignable. Tipo/rol base: gerente titular, suplente, encargado de área u otros.
- **Perfil flexible:** atributos clave-valor definibles por el usuario (sexo, velocidad, fuerza, puntualidad, confiabilidad, vencimiento de contrato, etc.). No van en columnas fijas; el catálogo lo arma el usuario.

### Rol / Asignación
- Roles desempeñables: gerente titular, gerente suplente, encargado de área, otros (ampliable).
- Por colaborador: qué roles **puede** y cuáles **no** desempeñar (algunos los hacen "no siempre"; algunos no pueden ciertos roles). Un mismo colaborador puede cubrir varios según necesidad.

### Atributo de perfil (clave-valor)
- Catálogo de atributos que el usuario define (nombre, tipo, opciones si aplica) y valores del colaborador por atributo.

### Período / intervalo de tiempo
- La **semana calendario (lunes–domingo)** sobre la que se genera la propuesta; acota la contabilidad de horas (**48 h de ley**) y la tabla resultante.

### Incidencia / ausencia
- Días que reducen la carga de la semana: vacaciones, descanso semanal, cumpleaños, incapacidad, curso, repuesto por apoyo, etc. Fecha + tipo; impactan las horas a la semana.

### Asignación diaria
- Día concreto: fecha, colaborador, rol asignado, horario de comida y quién lo cubre (otro encargado de área). Regla: en ningún momento un área queda sin vigilar.

### Propuesta semanal
- Resultado agregado de la semana: tabla de la semana coloreada por trabajador, descargable/compartible.

## Reglas no obvias

- Toda comida asignada debe ser cubierta por otro encargado de área (misma u otra área); nunca sin cobertura — por eso debe haber varios con ese rol.
- La contabilidad es **por semana**, no por día: **48 horas** pactadas por semana menos incidencias.
- Restricciones de rol: "no siempre" ciertas asignaciones; "algunos no pueden tener ciertos roles".
