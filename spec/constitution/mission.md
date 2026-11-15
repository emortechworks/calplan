# Misión

Este es un proyecto personal con baja afluencia de peticiones.

Este es un proyecto web, que permite proponer una calendirización de los días de trabajo, descanso y asignación de área de un conjunto de trabajadores de tienda.

Los días de descanso serán propuestos en cuanto a las necesidades mensuales: propuestas con antelación (peticiones), días de descanso semanal, vacaciones, incapacidades, cursos, días de cumpleaños, días repuestos por apoyos, etc.

Los días de trabajo y descanso se expresarán al final en una tabla del mes con colores distintos para cada trabajador.

El objetivo es obtener una propuesta viable de la planeación en un archivo descargable PDF ó CSV ó SHEET de google ó MXL (faltaría decidirlo después una vez se encuentre en un estado final el proyecto).

## Qué construimos

Un sitio web **local** que, a partir de un conjunto de formularios, permita:

1. **Seleccionar la tienda** a la que aplica la planeación.
2. **Seleccionar el intervalo de tiempo** del plan (normalmente el mes).
3. **Seleccionar el personal** que pertenece a esa tienda.

Con eso, generar una **propuesta mensual** de calendario con:

- Asignación de días de trabajo y descanso por colaborador.
- Asignación de roles/asignaciones según las necesidades de la tienda.
- **Horario de comida diario** por colaborador, cubierto por **otro encargado de área** para que ninguna área quede sin vigilancia (debe haber varios colaboradores con ese rol para poder hacer esos movimientos).

El personal se compone por ahora de (ampliables): **gerentes titulares, gerentes suplentes, encargados de área y otros**. Se selecciona de acuerdo a su **perfil**: sexo, velocidad, fuerza, puntualidad, si es persona confiable, si ya va a terminar su contrato, o **cualquier atributo que el usuario defina** (el catálogo de atributos es configurable).

Cada personal debe cumplir un total de **horas de trabajo al mes** menos descansos e incidencias (vacaciones, día de cumpleaños, incapacidades y demás ausencias que apliquen por ley o política de la tienda).

## Para quién

Es un sitio de ayuda a Gerentes de tienda que tienen varios colaboradores a su cargo y requieren hacer una planeación mensual de acuerdo a las necesidades del negocio.

## Principios

- **Orientado al gerente no técnico:** interfaz por formularios, sin configuraciones técnicas; operable por un gerente de tienda.
- **Perfil flexible:** los criterios de selección (velocidad, fuerza, puntualidad, sexo, etc.) los define el usuario, no un catálogo fijo.
- **Legalidad y equidad primero:** las horas y descansos de ley se satisfacen antes que la optimización por cobertura o conveniencia.
- **Local-first, datos sensibles:** todo corre en local y los datos laborales no salen de la red; nada se envía a la nube.
- **Resultado accionable:** el producto final es un documento descargable/compartible, no solo una pantalla.

## Qué NO es

- **No es un sistema de nómina:** no calcula pagos ni impuestos; las horas son de planeación, no de nómina.
- **No es un RRHH completo:** no gestiona expedientes, contratos, normas legales ni capacitaciones formales.
- **No es integración con checador/asistencia:** no recibe datos de entrada ni faltas en tiempo real; las incidencias se capturan manualmente.
- **No es multiempresa en la nube:** sitio local para el usuario, sus tiendas y su red — sin sincronización externa (por ahora).
- **No decide por el gerente:** el sistema propone, el gerente valida y ajusta.
