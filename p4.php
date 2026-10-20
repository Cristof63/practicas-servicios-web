<?php
declare(strict_types=1);

/*
| Práctica 4: sitio personal con mapas.
|
| La parte visual es HTML + JavaScript (Leaflet), servida desde el mismo
| sitio que las demás prácticas.
*/

require_once __DIR__ . '/config.php';

$titulo = 'Práctica 4 · Sitio personal';
$scriptsP4 = true;

require __DIR__ . '/parciales/cabecera.php';
?>

<header id="presentacion" class="text-center mb-4">
    <img id="foto" src="assets/foto.jpg" alt="Foto personal" class="foto-encuesta">
    <p id="nombre" class="h4 mt-2 mb-0">Cristhopher Robledo</p>
    <p id="ocupacion" class="ocupacion mb-1"></p>
    <p class="mb-0">
        Correo:
        <a id="correoEnlace" href="#" rel="noopener"></a>
        <span id="correo" class="ocupacion"></span>
    </p>
    <p class="ocupacion mb-0">
        Ubicación: San Pablo de las Salinas, Tultitlán, Edo. Méx.
    </p>
</header>

<section id="como-llegar">
    <h2 class="h4">Etapa 1 · Ubicación / Cómo llegar</h2>
    <p>Mapa incrustado con <code>iframe</code> (sin API Key):</p>
    <div class="marco">
        <iframe id="mapa-iframe" src="" loading="lazy" allowfullscreen
                referrerpolicy="no-referrer-when-downgrade"></iframe>
    </div>
</section>

<section id="mapa-interactivo">
    <h2 class="h4">Etapa 3 · Mapa interactivo (Leaflet)</h2>
    <p>Mapa interactivo con <strong>Leaflet</strong> y <strong>OpenStreetMap</strong>,
       con un marcador en mi ubicación. No requiere API Key.</p>

    <div class="marco">
        <div id="mapa-leaflet"></div>
    </div>
</section>

<?php require __DIR__ . '/parciales/pie.php'; ?>