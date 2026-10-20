<?php
declare(strict_types=1);

/*
| Configuración común: sesión, conexión a la base de datos y funciones de
| apoyo (escapar texto, mensajes de aviso y redirecciones).
*/

session_start();

define('BASE_DIR', __DIR__);

/*
| Datos de la conexión MySQL.
|
| En local (XAMPP / MAMP) se usan los valores por defecto. En un hosting como
| Render se toman de las variables de entorno (Environment Variables), por
| ejemplo: DB_HOST=... DB_PORT=3306 DB_USUARIO=... DB_PASSWORD=... DB_NOMBRE=...
*/
function variable_entorno(string $nombre, string $porDefecto): string
{
    $valor = getenv($nombre);

    return ($valor === false || $valor === '') ? $porDefecto : $valor;
}

$puertoBd = variable_entorno('DB_PORT', '8889');   // MAMP usa 8889, XAMPP/Render 3306
define('DB_HOST', variable_entorno('DB_HOST', '127.0.0.1') . ':' . $puertoBd);
define('DB_USUARIO', variable_entorno('DB_USUARIO', 'root'));
define('DB_PASSWORD', variable_entorno('DB_PASSWORD', 'root'));
define('DB_NOMBRE', variable_entorno('DB_NOMBRE', 'awos'));

// ------------------------------------------------------------------
// BASE DE DATOS
// ------------------------------------------------------------------

/**
 * Abre (y recuerda) la conexión PDO a MySQL.
 */
function bd(): PDO
{
    static $conexion = null;

    if ($conexion === null) {
        $conexion = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NOMBRE . ';charset=utf8mb4',
            DB_USUARIO,
            DB_PASSWORD,
            [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    return $conexion;
}

/**
 * Crea una tabla si todavía no existe.
 */
function crear_tabla(string $sql): void
{
    bd()->exec($sql);
}

/**
 * Revisa que la conexión esté viva. Si MySQL no responde avisa y corta.
 */
function revisar_conexion(): void
{
    try {
        bd()->query('SELECT 1');
    } catch (PDOException $error) {
        http_response_code(500);
        echo '<h1>No se pudo conectar a MySQL</h1>';
        echo '<p>' . e($error->getMessage()) . '</p>';
        echo '<p>Revisa que MySQL esté corriendo en XAMPP/MAMP y que la base de datos
              <code>' . e(DB_NOMBRE) . '</code> exista en http://localhost/phpmyadmin</p>';
        exit;
    }
}

// ------------------------------------------------------------------
// APOYO
// ------------------------------------------------------------------

/**
 * Escapa un texto para poder imprimirlo dentro de HTML.
 */
function e(mixed $valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/**
 * Guarda un mensaje que se mostrará en la siguiente página.
 */
function aviso(string $mensaje, string $categoria = 'info'): void
{
    $_SESSION['flash'][] = ['mensaje' => $mensaje, 'categoria' => $categoria];
}

/**
 * Devuelve los mensajes guardados y los borra de la sesión.
 */
function tomar_avisos(): array
{
    $avisos = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);

    return $avisos;
}

/**
 * Descubre la ruta que se pidió, sin importar qué servidor se use.
 *
 *   /p5/estudiantes       -> "p5/estudiantes"
 *
 * Funciona en los tres casos:
 *   - servidor integrado: router.php pasa la ruta en $_GET['ruta']
 *   - Apache con .htaccess: el RewriteRule la pasa en $_GET['ruta']
 *   - Apache con FallbackResource: hay que sacarla de REQUEST_URI
 */
function ruta_actual(): string
{
    if (isset($_GET['ruta'])) {
        return trim((string) $_GET['ruta'], '/');
    }

    $ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

    // Quita el prefijo donde vive el sitio (por ejemplo /web_php) para dejar
    // solo lo que hay después de la barra inicial.
    $base = base_sitio();
    if ($base !== '' && str_starts_with($ruta, $base)) {
        $ruta = substr($ruta, strlen($base));
    }

    return trim($ruta, '/');
}

/**
 * Ruta base del sitio sin la barra final ('' si vive en la raíz del dominio).
 *
 * Sirve para los enlaces del sitio, que deben ser absolutos desde la raíz.
 */
function base_sitio(): string
{
    static $base = null;

    if ($base !== null) {
        return $base;
    }

    // /web_php/index.php -> /web_php
    $guion = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $base = ($guion === '/' || $guion === '.') ? '' : rtrim($guion, '/');

    return $base;
}

/**
 * Construye un enlace del sitio (con la base incluida si hace falta).
 */
function enlace(string $ruta): string
{
    return base_sitio() . '/' . ltrim($ruta, '/');
}

/**
 * Corta la ejecución y manda al navegador a otra ruta del sitio.
 */
function redirigir(string $ruta): never
{
    header('Location: ' . enlace($ruta));
    exit;
}