# AWOS · Prácticas 3 a 6 en PHP

Todas las prácticas en un solo sitio, separadas por el menú de la barra superior.

| Ruta      | Contenido                                             |
|-----------|-------------------------------------------------------|
| `/`       | Portada con el menú de prácticas                       |
| `/p3`     | Práctica 3 · CRUD de productos                         |
| `/p4`     | Práctica 4 · Sitio personal con Google Maps            |
| `/p5`     | Práctica 5 · Login con sesiones y CRUD de estudiantes  |
| `/p6`     | Práctica 6 · Encuesta de satisfacción                   |

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

Las tablas (`productos`, `estudiantes`, `respuestas`) se crean solas la primera
vez que se abre cada práctica.

## 3. Ejecutar

```bash
cd web_php
/Applications/MAMP/bin/php/php8.4.1/bin/php -S localhost:8000 router.php
```

Abre <http://localhost:8000>

`router.php` sirve los archivos de `assets/` y pasa todo lo demás a `index.php`.

### Desde MAMP

1. Abre `MAMP.app` e inicia los servicios.
2. Copia o enlaza la carpeta dentro de `/Applications/MAMP/htdocs/`:

   ```bash
   ln -s "$(pwd)" /Applications/MAMP/htdocs/web_php
   ```

3. Entra en <http://localhost/web_php/>

---

## 4. Compartirlo con un enlace público

### Opción A · Túnel de VS Code

1. Corre el sitio en el puerto `8000`.
2. Abre la carpeta `web_php` en VS Code.
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
web_php/
├── router.php              # router del servidor integrado
├── index.php               # decide qué práctica se abre según la ruta
├── config.php              # sesión, conexión MySQL, avisos, redirecciones
├── portada.php
├── p3.php  p4.php  p5.php  p6.php
├── parciales/
│   ├── cabecera.php        # barra de menú (común a todas)
│   └── pie.php
└── assets/
    ├── estilos.css
    ├── foto.jpg
    └── p4/                 # api.json, main.js, mapa.js, estilos.css
```

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
