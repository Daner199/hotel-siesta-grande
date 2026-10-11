# Hotel Siesta Grande

Sistema web de reservas y gestión hotelera del **Hotel Siesta Grande** (Santa Cruz de la Sierra, Bolivia).
Proyecto universitario hecho con **Laravel 12 + PostgreSQL + Blade**.

- 4 roles: administrador, recepcionista, agencia y cliente.
- Habitaciones, tipos, tarifas con historial y galerías de fotos.
- Moneda: solo bolivianos (Bs).

---

## Requisitos

| Programa | Versión | Nota |
|---|---|---|
| PHP | 8.2 o superior | Con las extensiones `pdo_pgsql`, `pgsql`, `intl`, `fileinfo` y `gd` activas |
| Composer | 2 | |
| PostgreSQL | 13 o superior | Probado con la 18 |
| Git | cualquiera | |

En Windows con XAMPP, las extensiones se activan en `C:\xampp\php\php.ini` quitando el `;`
del inicio de `extension=pdo_pgsql`, `extension=pgsql`, `extension=intl`, `extension=fileinfo`
y `extension=gd` (GD pone el logo del hotel en el PDF de los recibos). Después reinicia `php artisan serve`.

---

## Instalación paso a paso

Los comandos son para **PowerShell** en Windows, desde la carpeta del proyecto.

### 1. Clonar e instalar dependencias

```powershell
git clone https://github.com/Daner199/hotel-siesta-grande.git
cd hotel-siesta-grande
composer install
```

### 2. Configurar el `.env`

```powershell
copy .env.example .env
php artisan key:generate
```

Abre `.env` y escribe la contraseña de **tu** usuario `postgres`:

```
DB_PASSWORD=tu_contraseña
```

El resto ya viene listo: PostgreSQL en `127.0.0.1:5432`, base `hotel_siesta_grande`,
y sesión, caché y colas en archivos.

Cambia también `PAGOS_API_CLAVE` y `PAGOS_WEBHOOK_SECRETO` por claves largas cualquiera
(las usan la API simulada de pagos y el banco simulado, ver más abajo **"6. Pagos"**).

> Si ya tenías el proyecto y alguien agregó librerías nuevas (por ejemplo las del QR y del PDF),
> vuelve a ejecutar `composer install`.

### 3. Crear la base de datos

> ⚠️ **NUNCA ejecutes `php artisan migrate`.** Este proyecto **no usa migraciones**: la base se crea
> con scripts SQL. `migrate` crearía tablas que no son del proyecto (`users`, `sessions`, `cache`...).

Si `psql` no se reconoce como comando, usa la ruta completa, por ejemplo
`& "C:\Program Files\PostgreSQL\18\bin\psql.exe"` (cambia el 18 por tu versión).

```powershell
# Crear la base vacía en UTF-8 (pide la contraseña de postgres)
psql -U postgres -c "CREATE DATABASE hotel_siesta_grande ENCODING 'UTF8' TEMPLATE template0"

# Crear tablas y cargar los datos iniciales
psql -U postgres -d hotel_siesta_grande -v ON_ERROR_STOP=1 -f database/sql/instalar.sql
```

`instalar.sql` crea toda la estructura y carga:
- los catálogos
- los 4 tipos de habitación
- las 60 habitaciones (pisos 1 a 4)
- las tarifas iniciales
- los datos del hotel
- los usuarios de prueba

### 4. Enlace para las fotos

Las fotos que sube el administrador se guardan en `storage/app/public`. Para que el navegador las vea:

```powershell
php artisan storage:link
```

> En Windows este comando a veces falla con *"A required privilege is not held by the client"*.
> En ese caso abre **PowerShell como administrador** (clic derecho → *Ejecutar como administrador*),
> entra a la carpeta del proyecto y vuelve a ejecutarlo.
> Otra opción es activar el *Modo de desarrollador* de Windows.

### 5. Iniciar

```powershell
php artisan serve
```

Abre **http://127.0.0.1:8000**

### 6. Pagos

No hay banco real: todo es **simulado** para fines académicos.

| Forma de pago | Quién | Cómo funciona |
|---|---|---|
| **QR** | Cliente, agencia, recepción | Se genera un QR por el monto, válido 15 min. Se escanea con el celular, se abre **Banco Siesta** (banco simulado), se confirma y el banco avisa al hotel por **webhook**. La pantalla de la PC se actualiza sola. |
| **Tarjeta** | Cliente, agencia, recepción | Pasarela simulada con validación de Luhn y 3-D Secure. Del pago solo se guardan la marca y los 4 últimos dígitos. |
| **Transferencia** | Cliente y agencia | Se transfiere a la cuenta del hotel (datos de ejemplo en *Datos del hotel*), se sube el comprobante y queda **en revisión** hasta que recepción lo aprueba o lo rechaza (menú *Transferencias*). |
| **Efectivo** | Solo recepción | Se aprueba al momento (referencia `EFE-…`). |

Cada pago aprobado genera un **recibo** (`REC-000001`, `REC-000002`…) con PDF y un QR de verificación.
El recibo **no es una factura**. Si actualizas el proyecto y ya tenías pagos, crea sus recibos con:

```powershell
php artisan recibos:generar
```

#### Tarjetas de prueba

| Número | Resultado |
|---|---|
| `4242 4242 4242 4242` (Visa) | Aprobada |
| `5555 5555 5555 4444` (Mastercard) | Aprobada |
| `4000 0000 0000 0002` (Visa) | Rechazada por fondos insuficientes |
| `4000 0000 0000 3220` (Visa) | Pide un código por SMS (3-D Secure). El código de prueba se muestra en pantalla. |

Cualquier vencimiento futuro (ej. `12/30`) y cualquier CVV de 3 dígitos. Un número que no cumple Luhn
(ej. `4242 4242 4242 4241`) se rechaza antes de enviarse.

#### Pagar con QR desde el celular (misma red WiFi)

El celular tiene que poder llegar a tu PC, así que `127.0.0.1` no sirve: hay que usar la IP de la PC.

1. En PowerShell ejecuta `ipconfig` y copia la **Dirección IPv4** del WiFi (ej. `192.168.1.50`).
2. En `.env`: `PAGOS_URL_PUBLICA=http://192.168.1.50:8000` y luego `php artisan optimize:clear`.
3. Inicia el servidor escuchando en la red: `php artisan serve --host=0.0.0.0`
4. La primera vez Windows pregunta si permite a **php.exe** usar la red: marca **Redes privadas** y acepta.
   Tu WiFi debe estar como red *privada* (Configuración → Red e Internet → WiFi → Tipo de perfil de red).
   Si no te preguntó: Panel de control → Firewall de Windows Defender → *Permitir una aplicación* →
   busca `C:\xampp\php\php.exe` y marca *Privada*.
5. Genera el QR de un pago y escanéalo con la cámara del celular. Se abre Banco Siesta:
   ingresa cualquier PIN de 4 dígitos y pulsa **Confirmar pago**. Para mostrar un rechazo usa
   *"simular fondos insuficientes"*.

Si la pantalla del QR dice *"Esta dirección solo funciona en esta computadora"*, falta el paso 2.

#### Modo `interno` o `http`

En el `.env` se elige cómo se comunican el hotel y el "banco":

| `PAGOS_API_MODO` | Qué hace | Cuándo usarlo |
|---|---|---|
| `interno` (por defecto) | La pasarela de tarjetas y Banco Siesta funcionan **dentro** del mismo servidor. Nunca se cuelga. | Siempre que trabajes normal |
| `http` | El banco es **otro servidor** (puerto 8001): la tarjeta se cobra con `POST /api/pagos/simular` y Banco Siesta avisa al hotel con un **webhook HTTP real**. | Para mostrarlo en la defensa |

En Windows `php artisan serve` atiende una petición a la vez y no puede llamarse a sí mismo; por eso
en modo `http` el banco corre en un segundo servidor. **Modo `http` paso a paso:**

1. En `.env` (con la IP de tu PC):
   ```
   PAGOS_API_MODO=http
   PAGOS_URL_PUBLICA=http://192.168.1.50:8000
   PAGOS_BANCO_URL=http://192.168.1.50:8001
   PAGOS_API_URL=http://127.0.0.1:8001/api/pagos/simular
   PAGOS_WEBHOOK_URL=http://127.0.0.1:8000/api/pagos/webhook
   ```
2. `php artisan optimize:clear`
3. Terminal 1 (el hotel): `php artisan serve --host=0.0.0.0`
4. Terminal 2 (el banco): `php artisan serve --host=0.0.0.0 --port=8001`
5. Paga con QR: el celular abre la página del banco en el puerto 8001 y, al confirmar, en la
   terminal 1 se ve llegar `POST /api/pagos/webhook`. Con tarjeta, en la terminal 2 llega `POST /api/pagos/simular`.

Si el servidor 8001 no está encendido, el sistema avisa *"No se pudo conectar…"* y no cobra nada.

#### Probar la API con Postman

```
POST http://127.0.0.1:8000/api/pagos/simular
Cabeceras:  X-Api-Clave: <tu PAGOS_API_CLAVE>
            Accept: application/json
Cuerpo (JSON):  {"monto": 300, "metodo": "QR"}
            o   {"monto": 300, "metodo": "TARJETA", "ultimos4": "0002"}
```

Respuestas: `{"estado":"APROBADO","codigo":"PAY-123456"}` · `{"estado":"RECHAZADO","motivo":"Fondos insuficientes."}` ·
`401` si la clave no coincide · `422` si faltan datos · `429` si pasas de 30 pedidos por minuto.

El webhook `POST /api/pagos/webhook` solo acepta avisos firmados con `PAGOS_WEBHOOK_SECRETO`
(cabecera `X-Firma`, HMAC-SHA256 del cuerpo): sin la firma correcta responde `401`.

---|---|---|
| `interno` (por defecto) | Laravel usa la lógica de la API **por dentro**. Nunca se cuelga. | Siempre que trabajes normal |
| `http` | Laravel hace una **petición HTTP real** a `PAGOS_API_URL`. | Para mostrar la API en la defensa |

**Modo `http` paso a paso** (en Windows `php artisan serve` atiende una petición a la vez y no se
puede llamar a sí mismo, por eso la API corre en un segundo servidor):

1. En `.env`: `PAGOS_API_MODO=http` y `PAGOS_API_URL=http://127.0.0.1:8001/api/pagos/simular`
2. `php artisan optimize:clear`
3. Terminal 1: `php artisan serve` (la página, puerto 8000)
4. Terminal 2: `php artisan serve --port=8001` (la API)
5. Paga una reserva: en la terminal 2 se ve llegar `POST /api/pagos/simular`.

Si el servidor 8001 no está encendido, el sistema avisa *"No se pudo conectar con la pasarela de pagos"*
y no cobra nada.

**Probar la API con Postman** (funciona en los dos modos):

```
POST http://127.0.0.1:8000/api/pagos/simular
Cabeceras:  X-Api-Clave: <tu PAGOS_API_CLAVE>
            Accept: application/json
Cuerpo (JSON):  {"monto": 300, "metodo": "QR"}
            o   {"monto": 300, "metodo": "TARJETA", "ultimos4": "0002"}
```

Respuestas: `{"estado":"APROBADO","codigo":"PAY-123456"}` · `{"estado":"RECHAZADO","motivo":"..."}` ·
`401` si la clave no coincide · `422` si faltan datos · `429` si pasas de 30 pedidos por minuto.

---

## Primeros pasos

### 1. Usuarios de prueba

`instalar.sql` crea estos 3 usuarios. Inicia sesión en **http://127.0.0.1:8000/login**:

| Rol | Correo | Contraseña | Entra a |
|---|---|---|---|
| Administrador | admin@siestagrande.com | Admin12345 | `/admin` (panel de administración) |
| Recepcionista | recepcion@siestagrande.com | Recepcion123 | `/recepcion` |
| Agencia (Viajes Bolivia, contacto Ana Gutiérrez) | agencia@siestagrande.com | Agencia123 | `/agencia` |

### 2. Crea tu cuenta de cliente

No hay cliente de prueba: **crea tu propia cuenta en http://127.0.0.1:8000/registro**.
Después de registrarte entras directo a tu panel de cliente (`/cliente`).

### 3. Qué revisar con cada rol

| Rol | Qué revisar |
|---|---|
| **Administrador** | **Recepcionistas** y **Agencias**: crear, editar, activar y desactivar · **Clientes**: lista, buscador y activar o desactivar · **Tipos y tarifas**: crear tipos, programar precios (línea de tiempo) y pestaña **Fotos** · **Habitaciones**: filtros, cambiar estado, fotos propias y **bloqueos por fechas** · **Datos del hotel**: fotos, contacto, redes, horarios, textos de la página y pin en el mapa |
| **Recepcionista** | **Reservas**: crear eligiendo habitaciones exactas, editar titular, cancelar · **Registrar pago** (efectivo, QR o tarjeta) · **Pagos** · **Mis comisiones** (Bs 5 por reserva que confirma) |
| **Agencia** | **Nueva reserva** para un titular con 10 % de descuento · **Mis reservas** · pagar el adelanto (QR o tarjeta) · **Pagos** |
| **Cliente** | **Nueva reserva** con resumen en vivo · **Mis reservas** · pagar el adelanto o el saldo · cancelar · **Mis pagos** |
| **Página pública** (http://127.0.0.1:8000) | Portada 3D, buscador de disponibilidad con precio total en Bs, habitaciones, servicios, beneficios, promociones vigentes, mapa y contacto |

### 4. Las fotos no vienen en el repositorio

Súbelas desde el panel del **administrador**:

- **Tipos y tarifas → Fotos**: galería de cada tipo de habitación (la usa la página pública).
- **Datos del hotel**: logo, portada, fachada, piscina y restaurante.
- **Habitaciones → Editar**: fotos propias de una habitación (opcionales).

Las fotos deben ser JPG, PNG o WEBP de 2 MB como máximo. Mientras no haya fotos, la página
muestra fondos elegantes en su lugar. Si las fotos se ven rotas, falta `php artisan storage:link`.

---

## Problemas comunes

| Problema | Solución |
|---|---|
| `could not find driver` | Falta activar `extension=pdo_pgsql` y `extension=pgsql` en `php.ini`. Reinicia `php artisan serve`. |
| `Class "NumberFormatter" not found` o errores con teléfonos | Activa `extension=intl` en `php.ini`. |
| `password authentication failed for user "postgres"` | La contraseña de `DB_PASSWORD` en `.env` no es la de tu PostgreSQL. |
| `database "hotel_siesta_grande" does not exist` | Falta el paso 3 (crear la base). |
| `relation "usuario" does not exist` | La base existe pero está vacía: ejecuta `instalar.sql` (paso 3). |
| Ejecutaste `migrate` por error | Borra la base, créala otra vez y ejecuta `instalar.sql` (paso 3). |
| Las fotos no se ven (imagen rota) | Falta `php artisan storage:link` (paso 4). |
| `storage:link` dice *A required privilege is not held* | Ejecútalo en PowerShell como administrador (paso 4). |
| Al subir una foto: *"no se pudo subir"* | La foto pesa más de 2 MB, o en `php.ini` `upload_max_filesize` / `post_max_size` son muy bajos (XAMPP trae 40M). |
| *419 Page Expired* al enviar un formulario | La sesión venció: recarga la página (Ctrl+F5) y vuelve a intentar. |
| En `psql` las tildes se ven raras (Habitaci¢n) | Es solo la consola de Windows: ejecuta `SET client_encoding = 'WIN1252';` en `psql`. Los datos están bien. |
| `No application encryption key has been specified` | Falta `php artisan key:generate` (paso 2). |
| Cambié algo y no se ve | `php artisan optimize:clear` y recarga con Ctrl+F5. |
| *Demasiados intentos* al iniciar sesión | Es la protección del login (5 intentos fallidos): espera 60 segundos. |
| *No se pudo conectar con la pasarela de pagos* | Estás en `PAGOS_API_MODO=http` sin el segundo servidor: abre otra terminal con `php artisan serve --port=8001`, o vuelve a `PAGOS_API_MODO=interno` y `php artisan optimize:clear`. |
| La API responde `401` | La cabecera `X-Api-Clave` no es igual a `PAGOS_API_CLAVE` del `.env` (o está vacía). |
| El celular no abre el QR (*no se puede acceder al sitio*) | Revisa `PAGOS_URL_PUBLICA` con la IP de `ipconfig`, que el servidor esté con `--host=0.0.0.0`, que el celular esté en el mismo WiFi y que el firewall permita `php.exe` en redes privadas. |
| El PDF del recibo sale sin logo | Activa `extension=gd` en `php.ini` y reinicia el servidor. |
| *Class "chillerlan\QRCode\QRCode" not found* o *Pdf not found* | Faltan las librerías nuevas: `composer install`. |

---

## Para quien desarrolla

- **Cambios en la base de datos**: van como script numerado en `database/sql/cambios/`.
  Cada script guarda en UTF-8, empieza con `SET client_encoding = 'UTF8';` y usa `BEGIN/COMMIT`.
  Después hay que regenerar `database/sql/estructura_actual.sql` e `instalar.sql`.
- **Datos iniciales**: están en `database/sql/datos_iniciales.sql`.
- **Estructura**:
  - controladores del admin en `app/Http/Controllers/Admin/`
  - vistas en `resources/views/`
  - CSS y JS propios en `public/css` y `public/js` (sin Vite)
