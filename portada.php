<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

$titulo = 'Portada · Aplicaciones Web Orientadas a Servicios';

$tarjetas = [
    [
        'titulo' => 'Práctica 3',
        'texto' => 'CRUD completo: insertar, consultar, modificar y eliminar.',
        'ruta' => '/p3',
    ],
    [
        'titulo' => 'Práctica 4',
        'texto' => 'Sitio personal con mapa de ubicación y mapa interactivo de Google Maps.',
        'ruta' => '/p4',
    ],
    [
        'titulo' => 'Práctica 5',
        'texto' => 'Sesiones, login y logout con contraseñas encriptadas, y CRUD de estudiantes.',
        'ruta' => '/p5',
    ],
    [
        'titulo' => 'Práctica 6',
        'texto' => 'Encuesta de satisfacción sobre la UPVM con guardado en base de datos.',
        'ruta' => '/p6',
    ],
    [
        'titulo' => 'Práctica 7',
        'texto' => 'Incrustación de un video con un botón que lo muestra en la página.',
        'ruta' => '/p7',
    ],
];

require __DIR__ . '/parciales/cabecera.php';
?>

<h1>Aplicaciones Web Orientadas a Servicios</h1>
<p class="pequena">Todas las prácticas en un solo sitio, separadas por el menú de arriba.</p>

<div class="rejilla-tarjetas">
    <?php foreach ($tarjetas as $tarjeta): ?>
        <div class="tarjeta">
            <div class="encabezado"><?= e($tarjeta['titulo']) ?></div>
            <div class="cuerpo">
                <p><?= e($tarjeta['texto']) ?></p>
            </div>
            <div class="pie">
                <a class="boton" href="<?= e($tarjeta['ruta']) ?>">Entrar</a>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/parciales/pie.php'; ?>