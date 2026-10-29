<?php
declare(strict_types=1);

/*
| Práctica 8: métricas de uso del sitio.
|
| El navegador manda eventos (vista, clic y tiempo) al servicio /p8/eventos.
| El panel pide los datos a /p8/metricas y los dibuja con JavaScript.
*/

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/parciales/tablas.php';

crear_tabla_eventos();

$partes = array_slice(explode('/', $ruta), 1);
$vista = $partes[0] ?? '';

/**
 * Devuelve el id del estudiante que tiene la sesión abierta en la práctica 5.
 */
function estudiante_de_sesion(): ?int
{
    if (!isset($_SESSION['usuario'])) {
        return null;
    }

    $consulta = bd()->prepare('SELECT id FROM estudiantes WHERE matricula = :matricula');
    $consulta->execute(['matricula' => $_SESSION['usuario']]);
    $id = $consulta->fetchColumn();

    return $id === false ? null : (int) $id;
}

/**
 * Recorta un texto sin depender de mbstring (la imagen Docker no la incluye).
 */
function recortar_texto(string $texto, int $largo): string
{
    if (function_exists('mb_substr')) {
        return mb_substr($texto, 0, $largo);
    }

    $corte = substr($texto, 0, $largo);

    // Si el corte partió un carácter UTF-8, se quitan los bytes incompletos.
    while ($corte !== '' && preg_match('//u', $corte) !== 1) {
        $corte = substr($corte, 0, -1);
    }

    return $corte;
}

/**
 * Ejecuta una consulta de solo lectura para el panel.
 */
function consultar_metricas(string $sql): array
{
    return bd()->query($sql)->fetchAll();
}

// Une los eventos anónimos de este navegador con la cuenta abierta en p5.
$idSesion = estudiante_de_sesion();
if ($idSesion !== null && isset($_COOKIE['awos_visitante_id'])) {
    $consulta = bd()->prepare(
        'UPDATE eventos
         SET usuario_id = :usuario
         WHERE visitante_id = :visitante AND usuario_id IS NULL'
    );
    $consulta->execute([
        'usuario' => $idSesion,
        'visitante' => (string) $_COOKIE['awos_visitante_id'],
    ]);
}

// ------------------------------------------------------------------
// SERVICIOS
// ------------------------------------------------------------------

if ($vista === 'eventos') {
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        echo json_encode(['error' => 'Usa POST para registrar eventos.']);
        exit;
    }

    $datos = json_decode(file_get_contents('php://input'), true);
    $tipo = is_array($datos) ? (string) ($datos['tipo'] ?? '') : '';
    $visitante = is_array($datos) ? (string) ($datos['visitante_id'] ?? '') : '';
    $sesion = is_array($datos) ? (string) ($datos['sesion_id'] ?? '') : '';
    $patronUuid = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

    // Nunca se confía en el cliente: se revisa el tipo y el formato de los ID.
    if (!is_array($datos)
        || !in_array($tipo, ['vista', 'clic', 'tiempo'], true)
        || !preg_match($patronUuid, $visitante)
        || !preg_match($patronUuid, $sesion)
    ) {
        http_response_code(400);
        echo json_encode(['error' => 'Evento inválido.']);
        exit;
    }

    $pagina = recortar_texto((string) ($datos['pagina'] ?? ''), 200);
    if ($pagina === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Falta la página del evento.']);
        exit;
    }

    $elemento = $tipo === 'clic'
        ? recortar_texto((string) ($datos['elemento'] ?? ''), 100)
        : null;
    $segundos = $tipo === 'tiempo'
        ? max(0, min(3600, (int) ($datos['segundos'] ?? 0)))
        : null;

    $consulta = bd()->prepare(
        'INSERT INTO eventos
            (visitante_id, sesion_id, usuario_id, tipo, pagina, elemento, segundos)
         VALUES
            (:visitante, :sesion, :usuario, :tipo, :pagina, :elemento, :segundos)'
    );
    $consulta->execute([
        'visitante' => $visitante,
        'sesion' => $sesion,
        'usuario' => $idSesion,
        'tipo' => $tipo,
        'pagina' => $pagina,
        'elemento' => $elemento,
        'segundos' => $segundos,
    ]);

    http_response_code(204);
    exit;
}

if ($vista === 'metricas') {
    header('Content-Type: application/json; charset=utf-8');

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        header('Allow: GET');
        echo json_encode(['error' => 'Usa GET para consultar las métricas.']);
        exit;
    }

    $resumen = consultar_metricas("
        SELECT COUNT(DISTINCT visitante_id) AS visitantes,
               COUNT(DISTINCT sesion_id)    AS sesiones,
               COUNT(DISTINCT usuario_id)   AS usuarios,
               COALESCE(SUM(tipo = 'vista'), 0) AS vistas,
               COALESCE(SUM(tipo = 'clic'), 0)  AS clics
        FROM eventos
    ")[0];

    // Tiempo promedio por sesión: primero se suma por sesión y luego se promedia.
    $resumen['tiempo_promedio'] = consultar_metricas("
        SELECT COALESCE(ROUND(AVG(total)), 0) AS promedio
        FROM (
            SELECT SUM(segundos) AS total
            FROM eventos
            WHERE tipo = 'tiempo'
            GROUP BY sesion_id
        ) AS t
    ")[0]['promedio'];

    $paginas = consultar_metricas("
        SELECT pagina,
               SUM(tipo = 'vista') AS vistas,
               SUM(tipo = 'clic')  AS clics,
               COALESCE(SUM(segundos), 0) AS segundos
        FROM eventos
        GROUP BY pagina
        ORDER BY vistas DESC
        LIMIT 10
    ");

    $clics = consultar_metricas("
        SELECT elemento, pagina, COUNT(*) AS total
        FROM eventos
        WHERE tipo = 'clic'
        GROUP BY elemento, pagina
        ORDER BY total DESC
        LIMIT 10
    ");

    // La analítica se cruza con los datos de los estudiantes de la práctica 5.
    $usuarios = consultar_metricas("
        SELECT COALESCE(u.nombre, '(anónimos)') AS nombre,
               COALESCE(u.carrera, '—') AS carrera,
               COUNT(DISTINCT e.sesion_id) AS sesiones,
               SUM(e.tipo = 'vista') AS vistas,
               SUM(e.tipo = 'clic')  AS clics,
               COALESCE(SUM(e.segundos), 0) AS segundos,
               MAX(e.creado_en) AS ultima
        FROM eventos e
        LEFT JOIN estudiantes u ON u.id = e.usuario_id
        GROUP BY e.usuario_id, u.nombre, u.carrera
        ORDER BY ultima DESC
    ");

    $carreras = consultar_metricas("
        SELECT u.carrera,
               COUNT(DISTINCT u.id) AS usuarios,
               SUM(e.tipo = 'clic') AS clics,
               COALESCE(SUM(e.segundos), 0) AS segundos
        FROM eventos e
        JOIN estudiantes u ON u.id = e.usuario_id
        GROUP BY u.carrera
        ORDER BY segundos DESC
    ");

    $recientes = consultar_metricas("
        SELECT DATE_FORMAT(e.creado_en, '%H:%i:%s') AS hora,
               COALESCE(u.nombre, 'Anónimo') AS nombre,
               e.tipo, e.pagina, e.elemento, e.segundos
        FROM eventos e
        LEFT JOIN estudiantes u ON u.id = e.usuario_id
        ORDER BY e.id DESC
        LIMIT 15
    ");

    echo json_encode(
        compact('resumen', 'paginas', 'clics', 'usuarios', 'carreras', 'recientes'),
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

// ------------------------------------------------------------------
// PÁGINAS
// ------------------------------------------------------------------

if ($vista === 'panel') {
    $titulo = 'Práctica 8 · Panel de métricas';
    $scriptsP8 = true;

    require __DIR__ . '/parciales/cabecera.php';
    ?>

    <div id="panel-metricas" data-metricas="<?= e(enlace('p8/metricas')) ?>">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
            <div>
                <h1 class="h3 mb-1">Panel de métricas</h1>
                <p class="text-muted small mb-0">
                    Se actualiza automáticamente cada 5 segundos.
                    <span id="estado"></span>
                </p>
            </div>
            <a class="boton boton-gris" data-track="p8: volver"
               href="<?= e(enlace('p8')) ?>">Volver</a>
        </div>

        <section class="kpis" id="kpis"></section>

        <h2 class="h5 mt-4">Actividad reciente</h2>
        <div class="tabla-scroll">
            <table id="tabla-recientes" class="tabla"></table>
        </div>

        <h2 class="h5 mt-4">Páginas</h2>
        <div class="tabla-scroll">
            <table id="tabla-paginas" class="tabla"></table>
        </div>

        <h2 class="h5 mt-4">¿Dónde hacen clic?</h2>
        <div class="tabla-scroll">
            <table id="tabla-clics" class="tabla"></table>
        </div>

        <h2 class="h5 mt-4">Usuarios</h2>
        <div class="tabla-scroll">
            <table id="tabla-usuarios" class="tabla"></table>
        </div>

        <h2 class="h5 mt-4">Por carrera</h2>
        <div class="tabla-scroll">
            <table id="tabla-carreras" class="tabla"></table>
        </div>

        <p class="text-muted small mt-4 mb-0">
            Los datos se guardan en la tabla <code>eventos</code> de MySQL.
            Para generar actividad, navega por las demás prácticas del sitio.
        </p>
    </div>

    <script src="<?= e(enlace('assets/p8/panel.js')) ?>"></script>

    <?php
    require __DIR__ . '/parciales/pie.php';
    exit;
}

$titulo = 'Práctica 8 · Métricas del sitio';

require __DIR__ . '/parciales/cabecera.php';
?>

<div class="text-center mb-4">
    <img src="<?= e(enlace('assets/foto.jpg')) ?>" alt="Mi foto" class="foto-encuesta">
    <p class="ocupacion mt-2 mb-0">Cristhopher Robledo</p>
</div>

<h1>Práctica 8 · Métricas del sitio</h1>
<p class="pequena">
    Un “mini Google Analytics” hecho con PHP, MySQL y JavaScript. Mide lo que
    ocurre en todas las prácticas de este sitio.
</p>

<div class="rejilla-tarjetas">
    <div class="tarjeta">
        <div class="encabezado">Panel en vivo</div>
        <div class="cuerpo">
            <p>Consulta en tiempo real:</p>
            <ul>
                <li>Visitantes y sesiones.</li>
                <li>Páginas más vistas.</li>
                <li>Elementos con más clics.</li>
                <li>Tiempo visible en el sitio.</li>
            </ul>
        </div>
        <div class="pie">
            <a class="boton" data-track="p8: abrir panel"
               href="<?= e(enlace('p8/panel')) ?>">Abrir panel</a>
        </div>
    </div>

    <div class="tarjeta">
        <div class="encabezado">Cómo funciona</div>
        <div class="cuerpo">
            <ol>
                <li>El rastreador identifica tu navegador con una cookie.</li>
                <li>Cada vista, clic o tiempo visible se envía a <code>/p8/eventos</code>.</li>
                <li>El panel pide las métricas a <code>/p8/metricas</code> en formato JSON.</li>
            </ol>
        </div>
        <div class="pie">
            <a class="boton boton-gris" data-track="p8: ir a la tienda"
               href="<?= e(enlace('p9')) ?>">Ir a la tienda p9</a>
        </div>
    </div>

    <div class="tarjeta">
        <div class="encabezado">Privacidad</div>
        <div class="cuerpo">
            <p>
                El aviso de cookies aparece una vez por navegador. Si lo rechazas,
                no se registra ningún dato. Nunca se guarda lo que escribes en los
                formularios.
            </p>
        </div>
        <div class="pie">
            <a href="#" data-cambiar-privacidad data-track="p8: cambiar privacidad">
                Cambiar mi preferencia
            </a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/parciales/pie.php'; ?>
