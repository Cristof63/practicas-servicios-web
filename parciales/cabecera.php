<?php
declare(strict_types=1);

/*
| Barra de menú común a todas las prácticas.
|
| Se incluye al inicio de cada página. Define $titulo antes de incluirlo.
*/

$titulo = $titulo ?? 'Aplicaciones Web Orientadas a Servicios';
$haySesion = isset($_SESSION['usuario']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($titulo) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(enlace('assets/estilos.css')) ?>">
    <?php if (!empty($scriptsP4)): ?>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
              integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
        <link rel="stylesheet" href="<?= e(enlace('assets/p4/estilos.css')) ?>">
    <?php endif; ?>
    <?php if (!empty($scriptsP8)): ?>
        <link rel="stylesheet" href="<?= e(enlace('assets/p8/estilos.css')) ?>">
    <?php endif; ?>
    <?php if (!empty($scriptsP9)): ?>
        <link rel="stylesheet" href="<?= e(enlace('assets/p9/estilos.css')) ?>">
    <?php endif; ?>
</head>
<body data-api="<?= e(enlace('assets/p4/api.json')) ?>"
      data-eventos="<?= e(enlace('p8/eventos')) ?>">

<nav class="barra">
    <div class="barra-contenedor">
        <a class="titulo" href="<?= e(enlace('')) ?>">AWOS · Prácticas</a>

        <div class="barra-links">
            <a href="<?= e(enlace('')) ?>">Inicio</a>

            <div class="menu-desplegable">
                <a href="#" class="desplegable-boton">Prácticas ▾</a>
                <div class="desplegable-contenido">
                    <a href="<?= e(enlace('p3')) ?>">Práctica 3 · CRUD</a>
                    <a href="<?= e(enlace('p4')) ?>">Práctica 4 · Mapa</a>
                    <a href="<?= e(enlace('p5')) ?>">Práctica 5 · Estudiantes</a>
                    <a href="<?= e(enlace('p6')) ?>">Práctica 6 · Encuesta</a>
                    <a href="<?= e(enlace('p7')) ?>">Práctica 7 · Video</a>
                    <a href="<?= e(enlace('p8')) ?>">Práctica 8 · Métricas</a>
                    <a href="<?= e(enlace('p9')) ?>">Práctica 9 · Tienda OXXO</a>
                </div>
            </div>

            <?php if ($haySesion): ?>
                <a href="<?= e(enlace('p5')) ?>"><?= e($_SESSION['usuario']) ?></a>
                <a href="<?= e(enlace('p5/logout')) ?>">Salir</a>
            <?php else: ?>
                <a href="<?= e(enlace('p5/login')) ?>">Iniciar sesión</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="contenedor espacio">
    <?php foreach (tomar_avisos() as $aviso): ?>
        <div class="aviso aviso-<?= e($aviso['categoria']) ?>">
            <?= e($aviso['mensaje']) ?>
        </div>
    <?php endforeach; ?>