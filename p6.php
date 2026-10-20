<?php
declare(strict_types=1);

/*
| Práctica 6: encuesta de satisfacción sobre la UPVM.
|
| Guarda cada respuesta en su base de datos y al terminar muestra el regalo
| de agradecimiento.
*/

require_once __DIR__ . '/config.php';

$partes = array_slice(explode('/', $ruta), 1);
$vista = $partes[0] ?? '';

crear_tabla('
    CREATE TABLE IF NOT EXISTS respuestas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(120) NOT NULL,
        carrera VARCHAR(120) NOT NULL,
        factor VARCHAR(120) NOT NULL,
        instalaciones VARCHAR(60) NOT NULL,
        mejora TEXT NOT NULL
    ) ENGINE = InnoDB
');

if ($vista === 'encuesta') {
    $titulo = 'Formulario de encuesta';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $consulta = bd()->prepare(
            'INSERT INTO respuestas (nombre, carrera, factor, instalaciones, mejora)
             VALUES (:nombre, :carrera, :factor, :instalaciones, :mejora)'
        );
        $consulta->execute([
            'nombre' => trim($_POST['nombre'] ?? ''),
            'carrera' => $_POST['carrera'] ?? '',
            'factor' => $_POST['factor'] ?? '',
            'instalaciones' => $_POST['instalaciones'] ?? '',
            'mejora' => trim($_POST['mejora'] ?? ''),
        ]);

        redirigir('p6/regalo');
    }

    $total = bd()->query('SELECT COUNT(*) FROM respuestas')->fetchColumn();

    require __DIR__ . '/parciales/cabecera.php';
    ?>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h1 class="h4 mb-1">Formulario de encuesta</h1>
                    <p class="text-muted small">Respuestas recibidas: <?= (int) $total ?></p>

                    <form method="POST" action="<?= e(enlace('p6/encuesta')) ?>">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">1. Nombre completo</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>

                        <div class="mb-3">
                            <label for="carrera" class="form-label">2. Carrera que estudias</label>
                            <select class="form-select" id="carrera" name="carrera" required>
                                <option value="" selected disabled>Selecciona una opción</option>
                                <option>Sistemas</option>
                                <option>Industrial</option>
                                <option>Robótica</option>
                                <option>Civil</option>
                                <option>Industrial y Electrónica</option>
                                <option>Química</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="factor" class="form-label">3. ¿Qué factor valoras más de la UPVM?</label>
                            <select class="form-select" id="factor" name="factor" required>
                                <option value="" selected disabled>Selecciona una opción</option>
                                <option>Profesores</option>
                                <option>Instalaciones y laboratorios</option>
                                <option>Biblioteca</option>
                                <option>Planta académica</option>
                                <option>Servicios administrativos</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="instalaciones" class="form-label">4. ¿Cómo calificarías las instalaciones?</label>
                            <select class="form-select" id="instalaciones" name="instalaciones" required>
                                <option value="" selected disabled>Selecciona una opción</option>
                                <option>Excelente</option>
                                <option>Buena</option>
                                <option>Regular</option>
                                <option>Mala</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="mejora" class="form-label">5. ¿Qué mejorarías en la UPVM?</label>
                            <textarea class="form-control" id="mejora" name="mejora" rows="3" required></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Enviar respuestas</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php
    require __DIR__ . '/parciales/pie.php';
    exit;
}

if ($vista === 'regalo') {
    $titulo = '¡Gracias por tu respuesta!';

    require __DIR__ . '/parciales/cabecera.php';
    ?>

    <div class="row justify-content-center">
        <div class="col-12 col-lg-6">
            <div class="card shadow-sm text-center">
                <div class="card-body p-5">
                    <h1 class="display-6">Gracias por tu respuesta</h1>
                    <p class="text-muted">Tu participación fue registrada correctamente en la
                       base de datos <strong>encuesta</strong>.</p>
                    <div class="card bg-warning bg-opacity-25 border-warning my-4">
                        <div class="card-body">
                            <h2 class="h5 mb-2">Tu regalo</h2>
                            <p class="mb-0">Un café de la cafetería de la UPVM por responder la encuesta.</p>
                        </div>
                    </div>
                    <a href="<?= e(enlace('p6')) ?>" class="btn btn-outline-secondary">Volver al inicio</a>
                </div>
            </div>
        </div>
    </div>

    <?php
    require __DIR__ . '/parciales/pie.php';
    exit;
}

$titulo = 'Práctica 6 · Encuesta UPVM';

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
                <h1 class="card-title h3">Encuesta de satisfacción sobre la UPVM</h1>
                <p class="card-text text-muted">
                    Tu opinión nos ayuda a mejorar. Llena la encuesta, toma menos de
                    2 minutos y al terminar recibirás un regalo de agradecimiento.
                </p>
                <a href="<?= e(enlace('p6/encuesta')) ?>" class="btn btn-primary btn-lg mt-2">Iniciar encuesta</a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/parciales/pie.php'; ?>