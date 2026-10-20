<?php
declare(strict_types=1);

/*
| Router del servidor integrado de PHP.
|
| Sirve los archivos que existen en la carpeta (CSS, JS, imágenes) y todo lo
| demás lo pasa a index.php, que decide qué práctica se muestra.
| Uso:  php -S localhost:8000 router.php
*/

$ruta = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
$archivo = __DIR__ . $ruta;

// Si el archivo existe (por ejemplo /assets/estilos.css), lo entrega PHP.
if ($ruta !== '/' && is_file($archivo)) {
    return false;
}

$_GET['ruta'] = trim($ruta, '/');

require __DIR__ . '/index.php';