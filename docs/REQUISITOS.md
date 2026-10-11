Quiero desarrollar un sistema web completo para el HOTEL SIESTA GRANDE, ubicado en Santa Cruz, Bolivia.

Quiero que trabajes como analista de sistemas, diseñador de bases de datos PostgreSQL y desarrollador Laravel.

IMPORTANTE: no inventes requisitos nuevos. Debes respetar exactamente las reglas que describo a continuación. Si detectas una ambigüedad, primero explícala y propón una solución sencilla antes de modificar el diseño.

==================================================

1. INFORMACIÓN DEL HOTEL
   ==================================================

Nombre:
HOTEL SIESTA GRANDE

Inicio de actividades:
21 de junio de 2016

Ubicación:
Santa Cruz, Bolivia

El hotel tiene 60 habitaciones.

Tipos de habitación:

* SIMPLE
* DOBLE
* MATRIMONIAL
* SUITE

Cada tipo de habitación tiene una capacidad y una tarifa por noche.

El sistema trabaja únicamente con moneda boliviana:
BOLIVIANOS (Bs).

NO utilizar dólares estadounidenses.

El hotel ofrece:

* Habitaciones
* Salón de eventos
* Restaurante
* Piscina

==================================================
2. TECNOLOGÍAS
==============

Base de datos:
PostgreSQL

Backend:
Laravel / PHP

Frontend:
Puede utilizarse Blade, React o la tecnología que se defina posteriormente.

La aplicación Laravel será la que se conecte a PostgreSQL.

El frontend NO debe conectarse directamente a PostgreSQL.

La comunicación debe ser:

Frontend
↓
Laravel
↓
PostgreSQL

==================================================
3. ROLES DEL SISTEMA
====================

Existirán cuatro roles:

1. CLIENTE
2. AGENCIA
3. RECEPCIONISTA
4. ADMINISTRADOR

No crear una tabla PERSONA.

No crear una tabla EMPLEADO.

La autenticación debe centralizarse en una sola tabla:

USUARIO

La tabla usuario tendrá información como:

* id
* nombre
* apellido
* teléfono
* email
* password
* rol
* activo
* timestamps

Las contraseñas deben almacenarse utilizando hash mediante Laravel.

Nunca almacenar contraseñas en texto plano.

==================================================
4. CLIENTES
===========

Los clientes se registran desde la página pública del hotel.

Después pueden iniciar sesión.

El cliente tendrá su propia área privada donde podrá:

* consultar sus reservas;
* consultar el estado de sus reservas;
* consultar detalles de sus reservas;
* realizar nuevas reservas;
* solicitar cancelaciones según las reglas;
* consultar sus pagos;
* consultar su cuenta cuando corresponda.

==================================================
5. AGENCIAS
===========

Las agencias son organizaciones externas que pueden realizar reservas.

Sin embargo, también tendrán una cuenta de usuario dentro del sistema.

Una agencia tendrá:

* usuario asociado;
* nombre de agencia;
* NIT;
* teléfono;
* estado activo/inactivo.

La agencia podrá iniciar sesión.

Cuando una agencia realice una reserva debe registrar los datos del TITULAR de la reserva.

Ejemplo:

Agencia:
Viajes Bolivia

Titular:
Carlos Pérez

Cantidad de personas:
4

Habitaciones:
1 habitación doble
1 habitación simple

Fechas:
10/10/2026 al 14/10/2026

La agencia puede realizar reservas para sus clientes.

No asumir que la agencia y el huésped/titular son la misma persona.

==================================================
6. RECEPCIONISTA
================

El recepcionista puede:

* crear reservas;
* modificar reservas según permisos;
* registrar check-in;
* registrar check-out;
* registrar consumos;
* registrar pagos;
* consultar habitaciones disponibles;
* consultar huéspedes alojados;
* gestionar no-show;
* consultar cuentas de huéspedes.

El recepcionista recibe una comisión de Bs 5 por cada reserva confirmada.

La comisión debe estar relacionada con el recepcionista correspondiente.

==================================================
7. ADMINISTRADOR
================

El administrador tiene acceso administrativo al sistema.

Puede gestionar:

* usuarios;
* recepcionistas;
* agencias;
* habitaciones;
* tipos de habitación;
* tarifas;
* promociones;
* beneficios;
* salón de eventos;
* estados;
* consultas y reportes.

El administrador crea las cuentas de recepcionistas.

Los recepcionistas NO se registran públicamente.

==================================================
8. LANDING PAGE
===============

El sistema tendrá una landing page pública del hotel.

La landing debe mostrar:

* nombre del Hotel Siesta Grande;
* información general;
* habitaciones;
* tipos de habitación;
* precios/tarifas;
* capacidad;
* promociones;
* beneficios;
* restaurante;
* piscina;
* salón de eventos;
* información de contacto;
* botón para iniciar sesión;
* botón para registrarse como cliente;
* opción para consultar disponibilidad.

La landing debe permitir que el visitante conozca el hotel antes de iniciar sesión.

La landing no debe conectarse directamente a PostgreSQL.

Debe obtener información mediante Laravel.

==================================================
9. HABITACIONES
===============

El hotel tiene 60 habitaciones.

Cada habitación tiene:

* número;
* piso;
* tipo;
* estado;
* descripción opcional.

Estados físicos:

* ACTIVA
* MANTENIMIENTO
* FUERA_SERVICIO

Los tipos de habitación son:

* SIMPLE
* DOBLE
* MATRIMONIAL
* SUITE

Cada tipo tiene una capacidad.

==================================================
10. TARIFAS
===========

Cada tipo de habitación tiene una tarifa por noche.

Las tarifas pueden cambiar con el tiempo.

El sistema debe conservar el precio que se utilizó cuando se realizó una reserva.

Si hoy una habitación cuesta Bs 300 y posteriormente pasa a Bs 400, las reservas anteriores deben conservar el precio original.

No modificar históricamente las reservas existentes.

==================================================
11. PROMOCIONES Y BENEFICIOS
============================

Los beneficios deben ser configurables.

NO crear columnas fijas como:

incluye_desayuno
incluye_almuerzo
incluye_cena

El administrador debe poder crear beneficios.

Ejemplos:

* Desayuno
* Almuerzo
* Cena
* Acceso a piscina
* Estacionamiento
* Otros beneficios

Una promoción puede tener varios beneficios.

Ejemplo:

PROMOCIÓN:
"Paquete Todo Incluido"

Beneficios:

* Desayuno
* Almuerzo
* Cena
* Piscina

Otra promoción puede ser:

"Fin de Semana"

Beneficios:

* Desayuno
* Piscina

El administrador debe poder crear nuevas promociones sin modificar la estructura de la base de datos.

Las promociones pueden tener vigencia.

También pueden aplicar solamente a determinados tipos de habitación.

==================================================
12. RESERVAS
============

Los clientes pueden reservar directamente.

Las agencias pueden reservar para sus clientes.

Los recepcionistas pueden registrar reservas.

Una reserva debe permitir:

* código de reserva;
* titular;
* cantidad de personas;
* fecha de entrada;
* fecha de salida;
* habitaciones;
* promoción seleccionada cuando corresponda;
* agencia cuando corresponda;
* usuario que creó la reserva;
* estado;
* observaciones.

Una reserva puede contener varias habitaciones.

Ejemplo:

Reserva R001

4 personas

Habitación doble:
201

Habitación simple:
202

Por lo tanto, no asumir que una reserva equivale siempre a una sola habitación.

==================================================
13. REGLA DE SOLAPAMIENTO
=========================

Una habitación NO puede reservarse dos veces para fechas que se solapen.

Ejemplo:

Reserva A:
10/10/2026 - 15/10/2026

No se puede realizar:

Reserva B:
12/10/2026 - 14/10/2026

para la misma habitación.

Pero sí se permite:

Reserva B:
15/10/2026 - 18/10/2026

porque el huésped anterior hace check-out el día 15.

La disponibilidad debe validarse correctamente.

==================================================
14. POLÍTICAS DE RESERVA
========================

Política 1:

Toda reserva requiere un adelanto mínimo del 30% del total de la estadía.

Política 2:

Las reservas realizadas por agencias tienen un descuento del 10%.

Política 3:

Si el huésped se queda más de 7 noches, desde la octava noche recibe un descuento del 15%.

Política 4:

Una reserva puede cancelarse hasta 48 horas antes del check-in con devolución total del adelanto.

Si se cancela con menos de 48 horas de anticipación, el adelanto no se devuelve.

Política 5:

Si el huésped no llega hasta las 12:00 del día siguiente a la fecha de check-in, la reserva pasa a NO_SHOW y la habitación se libera.

==================================================
15. ESTADOS DE RESERVA
======================

Utilizar como mínimo:

* PENDIENTE
* CONFIRMADA
* CANCELADA
* NO_SHOW
* CHECK_IN
* CHECK_OUT

==================================================
16. PAGOS
=========

Los pagos se realizan en bolivianos.

Métodos:

* EFECTIVO
* QR
* TARJETA

Una reserva puede tener varios pagos.

Ejemplo:

Total:
Bs 1.000

Adelanto:
Bs 300

Pago posterior:
Bs 700

El sistema debe conocer cuánto se ha pagado y cuánto falta pagar.

==================================================
17. API SIMULADA DE PAGOS
=========================

No utilizar inicialmente una API bancaria real.

Crear posteriormente una API simulada para fines académicos.

Ejemplo:

Laravel solicita:

POST /api/pagos/simular

con:

{
"monto": 300,
"metodo": "QR"
}

La API puede responder:

{
"estado": "APROBADO",
"codigo": "PAY-123456"
}

o:

{
"estado": "RECHAZADO"
}

La reserva solamente debe confirmarse cuando el pago requerido haya sido aprobado.

El sistema debe registrar la referencia del pago simulado.

==================================================
18. CONSUMOS
============

El requerimiento indica:

"El huésped puede adicionar consumos del restaurante o del frigobar a su cuenta, que se cancelan al hacer el check-out."

Los consumos serán registrados principalmente por el recepcionista.

NO es necesario crear un sistema completo de restaurante.

No se necesita administrar:

* recetas;
* ingredientes;
* cocina;
* menú completo;
* inventario completo.

Solo necesitamos registrar los consumos cargados a la cuenta de una reserva.

Tipos iniciales:

* RESTAURANTE
* FRIGOBAR

Ejemplos:

FRIGOBAR:
Agua
Cantidad: 2
Precio unitario: Bs 5

RESTAURANTE:
Cena
Cantidad: 1
Precio unitario: Bs 45

Cada consumo debe guardar:

* reserva;
* tipo;
* descripción;
* cantidad;
* precio unitario;
* fecha;
* usuario que lo registró.

El precio aplicado debe conservarse para no alterar consumos históricos.

==================================================
19. CHECK-IN
============

Cuando llega el huésped:

El recepcionista busca la reserva.

Comprueba que esté confirmada.

Realiza el check-in.

La reserva pasa a:

CHECK_IN

Desde ese momento pueden registrarse consumos.

==================================================
20. CHECK-OUT
=============

Al hacer check-out:

El sistema debe calcular:

TOTAL DE ESTADÍA
+
TOTAL DE CONSUMOS
-----------------

# PAGOS REALIZADOS

SALDO PENDIENTE

Ejemplo:

Estadía:
Bs 900

Consumos:
Bs 100

Total:
Bs 1.000

Adelanto:
Bs 300

Saldo:
Bs 700

El huésped paga el saldo y luego la reserva pasa a:

CHECK_OUT

==================================================
21. SALÓN DE EVENTOS
====================

El hotel cuenta con salón de eventos.

Debe permitir:

* nombre;
* capacidad;
* costo por hora;
* estado;
* reservas;
* fecha y hora;
* duración.

No se pueden realizar dos reservas del mismo salón en horarios solapados.

==================================================
22. RESTAURANTE Y PISCINA
=========================

El restaurante y la piscina forman parte de los servicios del hotel.

No crear un sistema complejo para estos servicios.

El restaurante sí debe permitir registrar consumos asociados a una reserva.

La piscina puede funcionar como servicio/beneficio.

==================================================
23. CONSULTAS REQUERIDAS
========================

El sistema debe permitir obtener:

1. Lista de habitaciones disponibles entre dos fechas, por tipo.

2. Lista de reservas realizadas por agencias entre determinadas fechas.

3. Lista de reservas con estado NO_SHOW.

4. Lista de huéspedes actualmente alojados.

5. Detalle de la cuenta de un huésped:

   * estadía;
   * consumos;
   * pagos;
   * saldo pendiente.

6. Ocupación de cada tipo de habitación en un mes.

==================================================
24. BASE DE DATOS
=================

La base de datos debe estar normalizada hasta 3FN.

Evitar tablas y columnas innecesarias.

No crear:

* tabla persona;
* tabla empleado;
* tabla cliente separada únicamente para duplicar datos de usuario.

Usar una sola tabla usuario para autenticación.

Crear agencia como entidad adicional porque una agencia representa una organización.

Usar claves primarias y foráneas correctamente.

Usar UNIQUE cuando corresponda.

Usar CHECK para reglas simples de integridad.

Usar restricciones de PostgreSQL cuando sea conveniente para evitar solapamientos.

==================================================
25. SEPARACIÓN DE RESPONSABILIDADES
===================================

PostgreSQL debe encargarse principalmente de:

* integridad de datos;
* claves primarias;
* claves foráneas;
* valores obligatorios;
* valores válidos;
* restricciones;
* evitar inconsistencias;
* evitar reservas solapadas cuando sea apropiado.

Laravel debe encargarse principalmente de:

* autenticación;
* autorización;
* registro;
* hash de contraseñas;
* cálculo de reservas;
* descuentos;
* aplicación del 30%;
* descuento de agencia del 10%;
* descuento del 15% desde la octava noche;
* reglas de cancelación;
* no-show;
* check-in;
* check-out;
* cálculo de cuenta;
* API simulada de pagos;
* permisos por rol;
* interfaz web.

==================================================
26. FORMA DE TRABAJO
====================

Quiero que me expliques el proyecto de forma modular.

NO quiero que me entregues todo el proyecto de golpe.

Primero quiero terminar y comprobar la base de datos PostgreSQL.

Después:

MÓDULO 1:
Usuarios y autenticación.

MÓDULO 2:
Habitaciones y tarifas.

MÓDULO 3:
Promociones y beneficios.

MÓDULO 4:
Reservas.

MÓDULO 5:
Pagos.

MÓDULO 6:
Recepción, check-in y check-out.

MÓDULO 7:
Consumos.

MÓDULO 8:
Salón de eventos.

MÓDULO 9:
Reportes y consultas.

MÓDULO 10:
Landing page e integración completa.

Para cada módulo quiero:

1. Explicación sencilla.
2. Tablas involucradas.
3. Relaciones.
4. Regla de negocio.
5. Qué hace PostgreSQL.
6. Qué hace Laravel.
7. Código completo necesario.
8. Nombre exacto de cada archivo.
9. Dónde colocar cada archivo.
10. Cómo probarlo.
11. Ejemplo práctico.

No cambies los requisitos sin explicarme primero por qué.

Quiero una solución apropiada para un proyecto universitario, entendible, profesional y sin sobreingeniería.