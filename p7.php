<?php
declare(strict_types=1);

/*
| Práctica 7: Incrustación de un video.
|
| Muestra una presentación con un botón que lleva a la página donde se
| incrusta (embed) un video propio usando la etiqueta <video> de HTML5.
*/

require_once __DIR__ . '/config.php';

$partes = array_slice(explode('/', $ruta), 1);
$vista = $partes[0] ?? '';

if ($vista === 'video') {
    $titulo = 'Mi video · Práctica 7';

    $video = 'assets/video/mi_video.mp4';
    $poster = 'assets/foto.jpg';

    require __DIR__ . '/parciales/cabecera.php';
    ?>

    <div class="text-center mb-4">
        <h1 class="h3 mb-1">Incrustación de un video</h1>
        <p class="text-muted small mb-0">Práctica 7 · Video incrustado con la etiqueta
           <code>&lt;video&gt;</code> de HTML5</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-9">
            <div class="card shadow-sm">
                <div class="card-body p-3 p-md-4">
                    <div class="video-marco">
                        <video class="video-practica" controls preload="metadata"
                               poster="<?= e(enlace($poster)) ?>">
                            <source src="<?= e(enlace($video)) ?>" type="video/mp4">
                            <source src="<?= e(enlace('assets/video/mi_video.webm')) ?>" type="video/webm">
                            Tu navegador no soporta la reproducción de video en HTML5.
                        </video>
                    </div>

                    <p class="text-muted small text-center mt-3 mb-0">
                        Video propio de Cristhopher Robledo · Archivo:
                        <code><?= e($video) ?></code>
                    </p>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="<?= e(enlace('p7')) ?>" class="btn btn-outline-secondary">Volver</a>
            </div>
        </div>
    </div>

    <?php
    require __DIR__ . '/parciales/pie.php';
    exit;
}

$titulo = 'Práctica 7 · Incrustación de un video';

require __DIR__ . '/parciales/cabecera.php';
?>

<div class="text-center mb-4">
    <img src="<?= e(enlace('assets/foto.jpg')) ?>" alt="Mi foto" class="foto-encuesta">
    <p class="ocupacion mt-2 mb-0">Cristhopher Robledo</p>
</div>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body text-center p-4">
                <h1 class="card-title h3">Incrustación de un video</h1>
                <p class="card-text text-muted">
                    Práctica 7 · En esta práctica se incrusta un video propio dentro de la
                    página web. Presiona el botón para verlo.
                </p>
                <a href="<?= e(enlace('p7/video')) ?>" class="btn btn-primary btn-lg mt-2">
                    Ver mi video
                </a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/parciales/pie.php'; ?>
