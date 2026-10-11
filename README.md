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
| PHP | 8.2 o superior | Con las extensiones `pdo_pgsql`, `pgsql`, `intl` y `fileinfo` activas |
| Composer | 2 | |
| PostgreSQL | 13 o superior | Probado con la 18 |
| Git | cualquiera | |

En Windows con XAMPP, las extensiones se activan en `C:\xampp\php\php.ini` quitando el `;`
del inicio de `extension=pdo_pgsql`, `extension=pgsql`, `extension=intl` y `extension=fileinfo`.

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

Cambia también `PAGOS_API_CLAVE` por una clave larga cualquiera (la usa la API simulada de pagos,
ver más abajo **"Pagos: la API simulada"**).

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

### 6. Pagos: la API simulada

No hay banco real: los pagos con **QR** y **tarjeta** pasan por una API simulada,
`POST /api/pagos/simular`, que responde `APROBADO` (con un código `PAY-123456`) o `RECHAZADO`.
Aprueba siempre, **salvo la tarjeta de prueba que termina en `0002`** (ej. `4000 0000 0000 0002`).
El efectivo solo lo registra recepción y no pasa por la API (referencia `EFE-123456`).

En el `.env` se elige cómo la llama Laravel:

| `PAGOS_API_MODO` | Qué hace | Cuándo usarlo |
|---|---|---|
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
