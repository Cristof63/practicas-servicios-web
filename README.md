# AWOS · Prácticas 3 a 9 en PHP

Todas las prácticas en un solo sitio, separadas por el menú de la barra superior.

| Ruta  | Contenido                                                      |
|-------|----------------------------------------------------------------|
| `/`   | Portada con el menú de prácticas                                |
| `/p3` | Práctica 3 · CRUD de productos                                  |
| `/p4` | Práctica 4 · Sitio personal con Google Maps                     |
| `/p5` | Práctica 5 · Login con sesiones y CRUD de estudiantes           |
| `/p6` | Práctica 6 · Encuesta de satisfacción                           |
| `/p7` | Práctica 7 · Incrustación de un video                           |
| `/p8` | Práctica 8 · Panel de métricas del sitio                        |
| `/p9` | Práctica 9 · CRUD de una tienda tipo OXXO y chat               |

---

## 1. Requisitos

- PHP 8.1 o superior con las extensiones `pdo_mysql` y `session`
- MySQL corriendo

> El PHP instalado en el sistema (`/usr/local/bin/php`) **no** trae `pdo_mysql`.
> Usa el de MAMP: `/Applications/MAMP/bin/php/php8.4.1/bin/php`

## 2. Base de datos

Los datos de conexión están en `config.php`:

```php
define('DB_HOST', '127.0.0.1:8889');   // MAMP usa el puerto 8889, XAMPP el 3306
define('DB_USUARIO', 'root');
define('DB_PASSWORD', 'root');   // XAMPP suele dejarla vacía: ''
define('DB_NOMBRE', 'awos');
```

Crea la base `awos` en `http://localhost:8889/phpmyadmin`. Si prefieres otro
nombre, cámbialo en `DB_NOMBRE`.

> **Ojo con el puerto.** MAMP no usa el 3306: su MySQL 5.7 escucha en el
> **8889** y su phpMyAdmin también. Si tu MAMP está configurado en otro puerto,
> el que importa es el de MySQL, no el de phpMyAdmin.

Las tablas se crean solas la primera vez que se abre cada práctica:

| Tabla              | Práctica | Contenido                                      |
|--------------------|----------|------------------------------------------------|
| `productos`        | p3       | Productos del CRUD básico                      |
| `estudiantes`      | p5       | Usuarios, contraseñas encriptadas y carreras   |
| `respuestas`       | p6       | Respuestas de la encuesta                      |
| `eventos`          | p8       | Vistas, clics y tiempo de todo el sitio        |
| `productos_tienda` | p9       | Productos, precios y existencias de la tienda  |
| `chat_mensajes`    | p9       | Mensajes del chat entre usuarios               |

## 3. Ejecutar

```bash
cd web_general
/Applications/MAMP/bin/php/php8.4.1/bin/php -S localhost:8000 router.php
```

Abre <http://localhost:8000>

`router.php` sirve los archivos de `assets/` y pasa todo lo demás a `index.php`.

### Desde MAMP

1. Abre `MAMP.app` e inicia los servicios.
2. Copia o enlaza la carpeta dentro de `/Applications/MAMP/htdocs/`:

   ```bash
   ln -s "$(pwd)" /Applications/MAMP/htdocs/web_general
   ```

3. Entra en <http://localhost/web_general/>

---

## 4. Desplegar en Render

Render **no** tiene runtime nativo de PHP, así que el sitio se publica con
**Docker** (incluido en `Dockerfile`: Apache + PHP 8.3 + `pdo_mysql`).

1. En Render: **New → Web Service** y conecta este repositorio de Git.
2. En *Language* elige **Docker** (Render detecta el `Dockerfile` solo).
3. Crea una base de datos MySQL (Render no da MySQL administrado; sirve
   PlanetScale, Aiven, Clever Cloud, etc.) y copia sus datos.
4. Agrega las **Environment Variables** en Render:

   | Variable      | Ejemplo                    |
   |---------------|----------------------------|
   | `DB_HOST`     | `tu-host.mysql.database`   |
   | `DB_PORT`     | `3306`                     |
   | `DB_USUARIO`  | `usuario`                  |
   | `DB_PASSWORD` | `contraseña`               |
   | `DB_NOMBRE`   | `awos`                     |

   `config.php` lee esas variables; si no existen usa los valores locales.

5. Deploy. Las tablas se crean solas al abrir cada práctica.

> El archivo `render.yaml` permite crear el servicio como *Blueprint*; en ese
> caso los valores de `DB_*` marcados con `sync: false` se piden en el panel.

### Compartirlo con un enlace público (local)

### Opción A · Túnel de VS Code

1. Corre el sitio en el puerto `8000`.
2. Abre la carpeta `web_general` en VS Code.
3. `Ctrl+Shift+P` → **Ports: Focus on Ports View**.
4. **Forward a Ports** → `8000`.
5. En la fila del puerto, **Port Visibility** → **Public**.
6. Se genera un enlace `*.vscode.dev`.

### Opción B · Túnel con ngrok

```bash
ngrok http 8000
```

Muestra una URL pública en la consola, sin salir de la terminal.

---

## 5. Estructura

```
web_general/
├── router.php              # router del servidor integrado
├── index.php               # decide qué práctica se abre según la ruta
├── config.php              # sesión, conexión MySQL, avisos, redirecciones
├── portada.php
├── p3.php  p4.php  p5.php  p6.php  p7.php  p8.php  p9.php
├── parciales/
│   ├── cabecera.php        # barra de menú (común a todas)
│   ├── pie.php
│   └── tablas.php          # tablas compartidas entre p5 y p8
└── assets/
    ├── estilos.css
    ├── foto.jpg
    ├── p4/                 # api.json, main.js, mapa.js, estilos.css
    ├── p8/                 # tracker.js, panel.js, estilos.css
    └── p9/                 # estilos.css, chat.js, mapa.js, video-tienda.mp4, logo.png
```

### Práctica 8 · Métricas del sitio

La práctica 8 es un “mini Google Analytics”:

- `assets/p8/tracker.js` se carga en **todas** las páginas y, con permiso del
  visitante, registra vistas, clics y tiempo visible.
- `POST /p8/eventos` guarda cada evento en la tabla `eventos`.
- `GET /p8/metricas` entrega las métricas en JSON.
- `/p8/panel` las dibuja con JavaScript y se actualiza cada 5 segundos.

El aviso de cookies aparece una vez por navegador. Si se rechaza, no se registra
nada. El enlace **Privacidad** del pie borra la decisión para volver a preguntar.
Si hay una sesión abierta en la práctica 5, los eventos se asocian al estudiante;
si no, quedan como anónimos.

Para reiniciar las métricas antes de una demostración:

```sql
TRUNCATE TABLE eventos;
```

### Práctica 9 · Tienda tipo OXXO

CRUD de productos con búsqueda, filtro por categoría, control de existencias y
promociones. Usa la tabla `productos_tienda`. La tienda se puede abrir sin
iniciar sesión; el chat sí requiere una cuenta.

- `/p9` lista los productos y muestra el valor del inventario.
- `/p9/crear` agrega un producto.
- `/p9/editar/{id}` lo modifica.
- `/p9/eliminar/{id}` lo borra mediante POST.
- `/p9/ejemplo` carga un catálogo de ejemplo si la tienda está vacía.
- `/p9/admin` abre el **panel de administración**. Credenciales de ejemplo:
  matrícula `777` y contraseña `admin`. La sesión de administrador es
  independiente de la sesión de la práctica 5 y se cierra en
  `/p9/admin/salir`.
- `/p9/chat` abre el chat de la tienda. Usa la cuenta con la que se inició
  sesión en la práctica 5 y guarda los mensajes en `chat_mensajes`.
  - `GET /p9/chat/mensajes?desde={id}` devuelve los mensajes nuevos en JSON.
  - `POST /p9/chat/enviar` guarda un mensaje.
  - La página se actualiza sola cada 3 segundos. Si JavaScript está apagado,
    el formulario sigue funcionando con un POST normal.

> Para usar el chat hay que iniciar sesión o crear una cuenta desde la
> práctica 5. Los mensajes se muestran escapados y se guardan con consultas
> preparadas. Para reiniciar la conversación:

```sql
TRUNCATE TABLE chat_mensajes;
```

El panel `/p9/admin` muestra el resumen del inventario y la tabla completa de
productos con acciones para agregar, editar y eliminar. Las credenciales
`777` / `admin` son solo para esta práctica; para cambiarlas se edita la
constante `ADMIN_P9_PASSWORD_HASH` en `p9.php` con un hash generado por
`password_hash()`.

Al final de `/p9` también están las secciones **Sucursales de la tienda** y
**Video de la tienda**:

- **Sucursales** muestra un mapa de Leaflet con OpenStreetMap y seis sucursales
  de ejemplo con coordenadas aleatorias alrededor de la tienda. Las ubicaciones
  se guardan en la sesión para que no cambien al recargar; el enlace
  `/p9?nuevas_sucursales=1#sucursales` genera otras distintas.
- **Video de la tienda** reproduce `assets/p9/video-tienda.mp4` con la etiqueta
  HTML5 `<video>`. El botón **Video de la tienda** lleva directo a esa sección.

El logo del banner de la tienda y del chat se toma de
`assets/p9/logo.png`.

### La práctica 4 necesita tu API Key

Pega tu clave en `assets/p4/api.json`:

```json
"apiKeyGoogle": "TU_CLAVE"
```

Si la dejas como `AQUI_TU_API_KEY`, el mapa interactivo no carga, pero el resto
del sitio funciona igual.

---

## 6. Problemas frecuentes

**`No se pudo conectar a MySQL`**
MySQL no está corriendo o las credenciales de `config.php` no coinciden.
Comprueba el puerto y la contraseña en MAMP → *Start/Stop* (MAMP usa el 8889 y
`root/root`; XAMPP usa el 3306 y `root` sin contraseña).

**`Address already in use` en el puerto 8000**
Otro programa ya lo ocupa. Corre en otro puerto y cambia el que publiques:

```bash
/Applications/MAMP/bin/php/php8.4.1/bin/php -S localhost:8080 router.php
```

**No carga el mapa de la práctica 4**
Falta la API Key, o la clave tiene restringidos los HTTP referrers. En Google
Cloud Console agrega `http://localhost:8000/*` y `http://localhost:*/*`.

**Las contraseñas de la práctica 5**
Se guardan con `password_hash()` y se revisan con `password_verify()`.
Nunca se almacenan en texto plano.

**El panel de la práctica 8 no muestra datos**
Acepta el aviso de cookies y navega por el sitio para generar eventos. El panel
se actualiza cada 5 segundos. Si ya habías rechazado, usa **Privacidad** en el
pie y vuelve a aceptar.

**“Ya existe un producto con ese código de barras” en la práctica 9**
El código de barras es único. Cambia el código o edita el producto que ya lo
tiene.
