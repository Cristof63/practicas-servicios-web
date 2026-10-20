<?php
declare(strict_types=1);

/*
| Punto único de entrada del sitio.
|
| Aquí se decide qué parte del sitio se abre según la ruta:
|   /            -> portada con el menú
|   /p3/...      -> Práctica 3
|   /p4/...      -> Práctica 4
|   /p5/...      -> Práctica 5
|   /p6/...      -> Práctica 6
|   /p7/...      -> Práctica 7
*/

require_once __DIR__ . '/config.php';

$ruta = ruta_actual();
$seccion = explode('/', $ruta)[0];

// Todas las prácticas usan MySQL: si no hay conexión se avisa y se corta.
revisar_conexion();

switch ($seccion) {
    case 'p3':
        require __DIR__ . '/p3.php';
        break;

    case 'p4':
        require __DIR__ . '/p4.php';
        break;

    case 'p5':
        require __DIR__ . '/p5.php';
        break;

    case 'p6':
        require __DIR__ . '/p6.php';
        break;

    case 'p7':
        require __DIR__ . '/p7.php';
        break;

    default:
        require __DIR__ . '/portada.php';
        break;
}