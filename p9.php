<?php
declare(strict_types=1);

/*
| Práctica 9: CRUD de una tienda tipo OXXO y chat entre usuarios.
|
| Lista, busca, agrega, modifica y elimina productos de una tienda mexicana.
| También incluye un chat general para las cuentas de la práctica 5, con
| actualización automática mediante JSON.
| Usa la misma base de datos awos y las mismas convenciones de las prácticas
| anteriores (PDO, consultas preparadas y la función e() para escapar HTML).
*/

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/parciales/tablas.php';

$partes = array_slice(explode('/', $ruta), 1);
$vista = $partes[0] ?? '';
$accion = $partes[1] ?? '';
$id = isset($partes[1]) ? (int) $partes[1] : null;

$scriptsP9 = true;

// Credenciales del administrador de la tienda.
const ADMIN_P9_MATRICULA = '777';
const ADMIN_P9_PASSWORD_HASH = '$2y$12$s.fXFDO7BPiOnsY5JmSiveHScrd2ZBnF5Ph3HCryM9AuHBTu0A1/O';

crear_tabla('
    CREATE TABLE IF NOT EXISTS productos_tienda (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(120) NOT NULL,
        marca VARCHAR(80) NOT NULL,
        categoria VARCHAR(60) NOT NULL,
        precio DECIMAL(10, 2) NOT NULL,
        existencia INT NOT NULL,
        codigo_barras VARCHAR(20) NOT NULL UNIQUE,
        promocion VARCHAR(120) NULL
    ) ENGINE = InnoDB
');

/**
 * Revisa los datos de un producto y devuelve el problema encontrado.
 */
function revisar_producto(array $datos): string
{
    $nombre = trim((string) ($datos['nombre'] ?? ''));
    $marca = trim((string) ($datos['marca'] ?? ''));
    $categoria = trim((string) ($datos['categoria'] ?? ''));
    $precio = $datos['precio'] ?? '';
    $existencia = $datos['existencia'] ?? '';
    $codigo = trim((string) ($datos['codigo_barras'] ?? ''));
    $promocion = trim((string) ($datos['promocion'] ?? ''));

    if ($nombre === '') {
        return 'El nombre del producto es obligatorio.';
    }
    if (strlen($nombre) > 120) {
        return 'El nombre no puede pasar de 120 caracteres.';
    }
    if ($marca === '') {
        return 'La marca es obligatoria.';
    }
    if (strlen($marca) > 80) {
        return 'La marca no puede pasar de 80 caracteres.';
    }
    if ($categoria === '') {
        return 'La categoría es obligatoria.';
    }
    if (strlen($categoria) > 60) {
        return 'La categoría no puede pasar de 60 caracteres.';
    }
    if (!is_numeric($precio) || (float) $precio < 0 || (float) $precio > 99999999.99) {
        return 'El precio debe ser un número entre 0 y 99,999,999.99.';
    }
    if (!is_numeric($existencia)
        || (float) $existencia != (int) $existencia
        || (int) $existencia < 0
    ) {
        return 'La existencia debe ser un número entero mayor o igual a cero.';
    }
    if ($codigo === '') {
        return 'El código de barras es obligatorio.';
    }
    if (strlen($codigo) > 20) {
        return 'El código de barras no puede pasar de 20 caracteres.';
    }
    if (strlen($promocion) > 120) {
        return 'La promoción no puede pasar de 120 caracteres.';
    }

    return '';
}

/**
 * Toma los datos del formulario y los limpia.
 */
function datos_producto(): array
{
    return [
        'nombre' => trim((string) ($_POST['nombre'] ?? '')),
        'marca' => trim((string) ($_POST['marca'] ?? '')),
        'categoria' => trim((string) ($_POST['categoria'] ?? '')),
        'precio' => (string) ($_POST['precio'] ?? '0'),
        'existencia' => (string) ($_POST['existencia'] ?? '0'),
        'codigo_barras' => trim((string) ($_POST['codigo_barras'] ?? '')),
        'promocion' => trim((string) ($_POST['promocion'] ?? '')),
    ];
}

/**
 * Inserta un producto nuevo.
 */
function guardar_producto(array $datos): void
{
    $consulta = bd()->prepare(
        'INSERT INTO productos_tienda
            (nombre, marca, categoria, precio, existencia, codigo_barras, promocion)
         VALUES
            (:nombre, :marca, :categoria, :precio, :existencia, :codigo_barras, :promocion)'
    );
    $consulta->execute([
        'nombre' => $datos['nombre'],
        'marca' => $datos['marca'],
        'categoria' => $datos['categoria'],
        'precio' => (float) $datos['precio'],
        'existencia' => (int) $datos['existencia'],
        'codigo_barras' => $datos['codigo_barras'],
        'promocion' => $datos['promocion'] !== '' ? $datos['promocion'] : null,
    ]);
}

/**
 * Indica si hay una sesión de administrador de la tienda abierta.
 */
function admin_p9_activo(): bool
{
    return !empty($_SESSION['admin_p9']);
}

/**
 * Ruta a la que se vuelve después de guardar o eliminar un producto.
 */
function destino_productos(): string
{
    return admin_p9_activo() ? 'p9/admin' : 'p9';
}

// ------------------------------------------------------------------
// CHAT ENTRE USUARIOS
// ------------------------------------------------------------------

/**
 * Crea la tabla donde se guardan los mensajes del chat.
 */
function crear_tabla_chat(): void
{
    crear_tabla('
        CREATE TABLE IF NOT EXISTS chat_mensajes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            autor_matricula VARCHAR(20) NOT NULL,
            autor_nombre VARCHAR(180) NOT NULL,
            mensaje VARCHAR(500) NOT NULL,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_chat_creado (creado_en)
        ) ENGINE = InnoDB
    ');
}

/**
 * Devuelve el estudiante que tiene la sesión abierta en la práctica 5.
 * Si la cuenta ya no existe, cierra la sesión.
 */
function chat_autor(): ?array
{
    if (!isset($_SESSION['usuario'])) {
        return null;
    }

    $consulta = bd()->prepare(
        'SELECT matricula, nombre, apellido
         FROM estudiantes
         WHERE matricula = :matricula'
    );
    $consulta->execute(['matricula' => $_SESSION['usuario']]);
    $autor = $consulta->fetch();

    if ($autor === false) {
        unset($_SESSION['usuario']);

        return null;
    }

    return $autor;
}

/**
 * Token para que otra página no pueda enviar mensajes en nombre del usuario.
 */
function chat_token(): string
{
    if (empty($_SESSION['chat_token'])) {
        $_SESSION['chat_token'] = bin2hex(random_bytes(16));
    }

    return (string) $_SESSION['chat_token'];
}

/**
 * Cuenta caracteres sin depender de mbstring (la imagen Docker no la incluye).
 */
function largo_chat(string $texto): int
{
    return function_exists('mb_strlen') ? mb_strlen($texto, 'UTF-8') : strlen($texto);
}

/**
 * Trae los últimos mensajes o solo los que llegaron después de $desde.
 */
function chat_mensajes(int $desde = 0, int $limite = 100): array
{
    $limite = max(1, min(200, $limite));

    if ($desde > 0) {
        $consulta = bd()->prepare(
            "SELECT id, autor_matricula, autor_nombre, mensaje,
                    DATE_FORMAT(creado_en, '%d/%m %H:%i') AS hora
             FROM chat_mensajes
             WHERE id > :desde
             ORDER BY id ASC
             LIMIT $limite"
        );
        $consulta->execute(['desde' => $desde]);

        return $consulta->fetchAll();
    }

    $consulta = bd()->query(
        "SELECT id, autor_matricula, autor_nombre, mensaje,
                DATE_FORMAT(creado_en, '%d/%m %H:%i') AS hora
         FROM chat_mensajes
         ORDER BY id DESC
         LIMIT $limite"
    );

    return array_reverse($consulta->fetchAll());
}

// ------------------------------------------------------------------
// SUCURSALES DE EJEMPLO
// ------------------------------------------------------------------

/**
 * Genera sucursales con coordenadas aleatorias alrededor de la tienda.
 */
function generar_sucursales(): array
{
    $centroLat = 19.66556;
    $centroLng = -99.09167;

    $datos = [
        ['Sucursal Centro', 'Av. Juárez', 'Centro'],
        ['Sucursal Norte', 'Av. Insurgentes', 'San Pablo'],
        ['Sucursal Sur', 'Calle Hidalgo', 'Tultitlán'],
        ['Sucursal Oriente', 'Blvd. Aeropuerto', 'Coacalco'],
        ['Sucursal Poniente', 'Av. Morelos', 'Cuautitlán'],
        ['Sucursal Plaza', 'Calle 5 de Mayo', 'Ecatepec'],
    ];

    $sucursales = [];

    foreach ($datos as [$nombre, $calle, $colonia]) {
        $sucursales[] = [
            'nombre' => $nombre,
            'direccion' => $calle . ' ' . random_int(100, 999) . ', Col. ' . $colonia,
            'horario' => 'Lun a Dom · ' . random_int(7, 9) . ':00 a ' . random_int(20, 23) . ':00',
            'telefono' => '55 ' . random_int(1000, 9999) . ' ' . random_int(1000, 9999),
            // ±0.045 grados ≈ ±5 km alrededor de la ubicación de la tienda.
            'lat' => round($centroLat + (random_int(-4500, 4500) / 100000), 6),
            'lng' => round($centroLng + (random_int(-4500, 4500) / 100000), 6),
        ];
    }

    return $sucursales;
}

/**
 * Devuelve las sucursales guardadas en la sesión; si no hay, las genera.
 */
function sucursales_tienda(): array
{
    if (empty($_SESSION['sucursales_p9']) || !is_array($_SESSION['sucursales_p9'])) {
        $_SESSION['sucursales_p9'] = generar_sucursales();
    }

    return $_SESSION['sucursales_p9'];
}

// ------------------------------------------------------------------
// CATÁLOGO DE EJEMPLO
// ------------------------------------------------------------------

if ($vista === 'ejemplo') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirigir('p9');
    }

    $ejemplos = [
        ['Coca-Cola', 'Coca-Cola', 'Bebidas', 19.00, 24, '7501055363513', '3 por $50'],
        ['Agua Bonafont', 'Bonafont', 'Bebidas', 14.50, 30, '7501086801015', null],
        ['Sabritas Original 45 g', 'Sabritas', 'Botanas', 18.00, 18, '7501011113970', null],
        ['Carlos V 50 g', 'Nestlé', 'Dulces', 16.00, 12, '7501011120016', '2 por $28'],
        ['Gansito 50 g', 'Marinela', 'Panadería', 20.00, 10, '7501000111234', null],
        ['Electrolit Suero 625 ml', 'Electrolit', 'Bebidas', 25.00, 9, '7501125112345', null],
        ['Yogurt Natural 900 g', 'Danone', 'Lácteos', 32.00, 6, '7501058612345', null],
        ['Jabón Zote 200 g', 'Zote', 'Higiene', 15.00, 20, '7501020512345', null],
    ];

    $consulta = bd()->prepare(
        'INSERT IGNORE INTO productos_tienda
            (nombre, marca, categoria, precio, existencia, codigo_barras, promocion)
         VALUES
            (:nombre, :marca, :categoria, :precio, :existencia, :codigo_barras, :promocion)'
    );

    $insertados = 0;
    foreach ($ejemplos as [$nombre, $marca, $categoria, $precio, $existencia, $codigo, $promocion]) {
        $consulta->execute([
            'nombre' => $nombre,
            'marca' => $marca,
            'categoria' => $categoria,
            'precio' => $precio,
            'existencia' => $existencia,
            'codigo_barras' => $codigo,
            'promocion' => $promocion,
        ]);
        $insertados += $consulta->rowCount();
    }

    if ($insertados > 0) {
        aviso("Se cargaron $insertados productos de ejemplo.", 'exito');
    } else {
        aviso('Los productos de ejemplo ya estaban cargados.', 'info');
    }

    redirigir(destino_productos());
}

// ------------------------------------------------------------------
// ADMINISTRACIÓN DE LA TIENDA
// ------------------------------------------------------------------

if ($vista === 'admin') {
    // Cerrar la sesión de administrador.
    if ($accion === 'salir') {
        unset($_SESSION['admin_p9'], $_SESSION['admin_p9_matricula']);
        aviso('Cerraste la sesión de administrador.', 'info');
        redirigir('p9/admin');
    }

    // Validar las credenciales del administrador.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $matricula = trim((string) ($_POST['matricula'] ?? ''));
        $contrasena = (string) ($_POST['contrasena'] ?? '');

        if (hash_equals(ADMIN_P9_MATRICULA, $matricula)
            && password_verify($contrasena, ADMIN_P9_PASSWORD_HASH)
        ) {
            session_regenerate_id(true);
            $_SESSION['admin_p9'] = true;
            $_SESSION['admin_p9_matricula'] = ADMIN_P9_MATRICULA;
            aviso('Bienvenido al panel de administración.', 'exito');
            redirigir('p9/admin');
        }

        aviso('Matrícula o contraseña de administrador incorrectas.', 'error');
    }

    $titulo = 'Práctica 9 · Administración de la tienda';

    // Sin sesión de administrador se muestra el formulario de acceso.
    if (!admin_p9_activo()) {
        require __DIR__ . '/parciales/cabecera.php';
        ?>

        <div class="tienda-oxxo admin-tienda">
            <div class="banner-oxxo admin-banner">
                <img class="logo-oxxo" src="<?= e(enlace('assets/p9/logo.png')) ?>" alt="Logo OXXO">
                <div>
                    <h1>Administración de la tienda</h1>
                    <p>Acceso exclusivo para el administrador de la tienda OXXO.</p>
                </div>
            </div>

            <div class="admin-login">
                <div class="admin-login-tarjeta">
                    <h2>Iniciar sesión como administrador</h2>
                    <p class="pequena">
                        Ingresa la matrícula y la contraseña de administrador para
                        administrar los productos de la tienda.
                    </p>

                    <form method="POST" action="<?= e(enlace('p9/admin')) ?>">
                        <div class="campo">
                            <label for="admin-matricula">Matrícula</label>
                            <input type="text" id="admin-matricula" name="matricula"
                                   autocomplete="username" required autofocus>
                        </div>

                        <div class="campo">
                            <label for="admin-contrasena">Contraseña</label>
                            <input type="password" id="admin-contrasena" name="contrasena"
                                   autocomplete="current-password" required>
                        </div>

                        <button type="submit" class="boton boton-admin">
                            Entrar al panel
                        </button>
                    </form>

                    <p class="pequena centrado admin-login-volver">
                        <a href="<?= e(enlace('p9')) ?>">Volver a la tienda</a>
                    </p>
                </div>
            </div>
        </div>

        <?php
        require __DIR__ . '/parciales/pie.php';
        exit;
    }

    $busqueda = trim((string) ($_GET['q'] ?? ''));
    $categoria = trim((string) ($_GET['categoria'] ?? ''));

    $condiciones = [];
    $parametros = [];

    if ($busqueda !== '') {
        $condiciones[] = '(nombre LIKE :q_nombre
            OR marca LIKE :q_marca
            OR categoria LIKE :q_categoria
            OR codigo_barras LIKE :q_codigo)';
        $parametros['q_nombre'] = '%' . $busqueda . '%';
        $parametros['q_marca'] = '%' . $busqueda . '%';
        $parametros['q_categoria'] = '%' . $busqueda . '%';
        $parametros['q_codigo'] = '%' . $busqueda . '%';
    }

    if ($categoria !== '') {
        $condiciones[] = 'categoria = :categoria';
        $parametros['categoria'] = $categoria;
    }

    $sql = 'SELECT * FROM productos_tienda';
    if ($condiciones !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $condiciones);
    }
    $sql .= ' ORDER BY categoria, nombre';

    $consulta = bd()->prepare($sql);
    $consulta->execute($parametros);
    $productos = $consulta->fetchAll();

    $categorias = bd()->query(
        'SELECT DISTINCT categoria FROM productos_tienda ORDER BY categoria'
    )->fetchAll(PDO::FETCH_COLUMN);

    $totalProductos = (int) bd()->query(
        'SELECT COUNT(*) FROM productos_tienda'
    )->fetchColumn();
    $totalPiezas = (int) bd()->query(
        'SELECT COALESCE(SUM(existencia), 0) FROM productos_tienda'
    )->fetchColumn();
    $valorInventario = (float) bd()->query(
        'SELECT COALESCE(SUM(precio * existencia), 0) FROM productos_tienda'
    )->fetchColumn();
    $productosBajos = (int) bd()->query(
        'SELECT COUNT(*) FROM productos_tienda WHERE existencia > 0 AND existencia < 10'
    )->fetchColumn();
    $productosAgotados = (int) bd()->query(
        'SELECT COUNT(*) FROM productos_tienda WHERE existencia = 0'
    )->fetchColumn();
    $totalPromociones = (int) bd()->query(
        "SELECT COUNT(*) FROM productos_tienda
         WHERE promocion IS NOT NULL AND promocion <> ''"
    )->fetchColumn();

    require __DIR__ . '/parciales/cabecera.php';
    ?>

    <div class="tienda-oxxo admin-tienda">
        <div class="banner-oxxo admin-banner">
            <img class="logo-oxxo" src="<?= e(enlace('assets/p9/logo.png')) ?>" alt="Logo OXXO">
            <div>
                <h1>Panel de administración</h1>
                <p>
                    Tienda OXXO · Sesión de administrador
                    (<?= e($_SESSION['admin_p9_matricula'] ?? ADMIN_P9_MATRICULA) ?>)
                </p>
            </div>
        </div>

        <div class="admin-barra">
            <span class="admin-insignia">Admin</span>
            <a class="boton boton-verde" data-track="p9 admin: agregar producto"
               href="<?= e(enlace('p9/crear')) ?>">Agregar producto</a>
            <a class="boton boton-gris" data-track="p9 admin: ver tienda"
               href="<?= e(enlace('p9')) ?>">Ver tienda</a>
            <a class="boton boton-chat" data-track="p9 admin: abrir chat"
               href="<?= e(enlace('p9/chat')) ?>">Chat</a>
            <a class="boton boton-gris" data-track="p9 admin: ver métricas"
               href="<?= e(enlace('p8/panel')) ?>">Métricas</a>
            <a class="boton boton-rojo" data-track="p9 admin: cerrar sesión"
               href="<?= e(enlace('p9/admin/salir')) ?>">Cerrar sesión admin</a>
        </div>

        <div class="resumen-tienda admin-resumen">
            <div><strong><?= $totalProductos ?></strong><span>Productos</span></div>
            <div><strong><?= $totalPiezas ?></strong><span>Piezas en tienda</span></div>
            <div><strong>$<?= e(number_format($valorInventario, 2)) ?></strong><span>Valor del inventario</span></div>
            <div><strong><?= $productosBajos ?></strong><span>Con pocas piezas</span></div>
            <div><strong><?= $productosAgotados ?></strong><span>Agotados</span></div>
            <div><strong><?= $totalPromociones ?></strong><span>Con promoción</span></div>
        </div>

        <form class="filtros-tienda" method="GET" action="<?= e(enlace('p9/admin')) ?>">
            <input type="search" name="q" value="<?= e($busqueda) ?>"
                   placeholder="Buscar producto, marca o código…">
            <select name="categoria">
                <option value="">Todas las categorías</option>
                <?php foreach ($categorias as $opcion): ?>
                    <option value="<?= e($opcion) ?>" <?= $opcion === $categoria ? 'selected' : '' ?>>
                        <?= e($opcion) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="boton" data-track="p9 admin: filtrar productos">Filtrar</button>
            <a class="boton boton-gris" data-track="p9 admin: limpiar filtros"
               href="<?= e(enlace('p9/admin')) ?>">Limpiar</a>
        </form>

        <?php if ($productos): ?>
            <div class="tabla-scroll">
                <table class="tabla admin-tabla">
                    <tr>
                        <th>ID</th>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Marca</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Existencia</th>
                        <th>Promoción</th>
                        <th>Acciones</th>
                    </tr>
                    <?php foreach ($productos as $producto): ?>
                        <?php
                        $existencia = (int) $producto['existencia'];
                        $claseStock = $existencia === 0
                            ? 'agotado'
                            : ($existencia < 10 ? 'pocas' : 'disponible');
                        $textoStock = $existencia === 0
                            ? 'Agotado'
                            : ($existencia < 10 ? 'Pocas piezas' : 'Disponible');
                        ?>
                        <tr>
                            <td><?= (int) $producto['id'] ?></td>
                            <td><code><?= e($producto['codigo_barras']) ?></code></td>
                            <td><?= e($producto['nombre']) ?></td>
                            <td><?= e($producto['marca']) ?></td>
                            <td><?= e($producto['categoria']) ?></td>
                            <td class="precio-tienda">$<?= e(number_format((float) $producto['precio'], 2)) ?></td>
                            <td>
                                <?= $existencia ?>
                                <span class="etiqueta-stock <?= e($claseStock) ?>"><?= e($textoStock) ?></span>
                            </td>
                            <td>
                                <?php if ((string) $producto['promocion'] !== ''): ?>
                                    <span class="etiqueta-promo"><?= e($producto['promocion']) ?></span>
                                <?php else: ?>
                                    <span class="texto-tenue">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="acciones">
                                <a class="boton boton-chico"
                                   data-track="p9 admin: editar <?= e($producto['nombre']) ?>"
                                   href="<?= e(enlace('p9/editar/' . (int) $producto['id'])) ?>">Editar</a>

                                <form class="formulario-en-linea" method="POST"
                                      action="<?= e(enlace('p9/eliminar/' . (int) $producto['id'])) ?>">
                                    <button type="submit" class="boton boton-chico boton-rojo"
                                            data-track="p9 admin: eliminar <?= e($producto['nombre']) ?>">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php else: ?>
            <div class="aviso aviso-info">
                <?php if ($totalProductos === 0): ?>
                    <p>Todavía no hay productos en la tienda.</p>
                    <form method="POST" action="<?= e(enlace('p9/ejemplo')) ?>">
                        <button type="submit" class="boton boton-verde"
                                data-track="p9 admin: cargar catálogo de ejemplo">
                            Cargar catálogo de ejemplo
                        </button>
                    </form>
                <?php else: ?>
                    No se encontraron productos con esa búsqueda o categoría.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <p class="pequena admin-nota">
            Desde este panel puedes agregar, editar y eliminar productos de la tienda.
            La tienda pública sigue disponible para consultar el catálogo.
        </p>
    </div>

    <?php
    require __DIR__ . '/parciales/pie.php';
    exit;
}

// ------------------------------------------------------------------
// CHAT DE LA TIENDA
// ------------------------------------------------------------------

if ($vista === 'chat') {
    crear_tabla_estudiantes();
    crear_tabla_chat();

    $autor = chat_autor();

    // Servicio: mensajes nuevos en formato JSON.
    if ($accion === 'mensajes') {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            header('Allow: GET');
            echo json_encode(['error' => 'Usa GET para consultar los mensajes.']);
            exit;
        }

        if ($autor === null) {
            http_response_code(401);
            echo json_encode(['error' => 'Inicia sesión para ver el chat.']);
            exit;
        }

        $desde = max(0, (int) ($_GET['desde'] ?? 0));
        $mensajes = chat_mensajes($desde);

        foreach ($mensajes as &$mensaje) {
            $mensaje['es_mio'] = $mensaje['autor_matricula'] === $autor['matricula'];
        }
        unset($mensaje);

        $ultimo = $desde;
        foreach ($mensajes as $mensaje) {
            $ultimo = max($ultimo, (int) $mensaje['id']);
        }

        echo json_encode(
            ['mensajes' => $mensajes, 'ultimo' => $ultimo],
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
        exit;
    }

    // Servicio: guardar un mensaje. Responde JSON a fetch y redirige al formulario normal.
    if ($accion === 'enviar') {
        $quiereJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

        $responder = static function (int $codigo, array $datos) use ($quiereJson): never {
            if ($quiereJson) {
                http_response_code($codigo);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                exit;
            }

            aviso((string) ($datos['error'] ?? 'No se pudo enviar el mensaje.'), 'error');
            redirigir('p9/chat');
        };

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $responder(405, ['error' => 'Usa POST para enviar mensajes.']);
        }

        if ($autor === null) {
            $responder(401, ['error' => 'Inicia sesión para escribir en el chat.']);
        }

        $token = (string) ($_POST['token'] ?? '');
        if (!hash_equals(chat_token(), $token)) {
            $responder(403, ['error' => 'La sesión expiró. Recarga la página e inténtalo de nuevo.']);
        }

        $mensaje = trim((string) ($_POST['mensaje'] ?? ''));

        if ($mensaje === '') {
            $responder(400, ['error' => 'Escribe un mensaje antes de enviarlo.']);
        }

        if (largo_chat($mensaje) > 500) {
            $responder(400, ['error' => 'El mensaje no puede pasar de 500 caracteres.']);
        }

        // Evita que un mismo usuario mande varios mensajes en el mismo segundo.
        $ahora = time();
        if (isset($_SESSION['chat_ultimo']) && $ahora - (int) $_SESSION['chat_ultimo'] < 1) {
            $responder(429, ['error' => 'Espera un segundo antes de enviar otro mensaje.']);
        }
        $_SESSION['chat_ultimo'] = $ahora;

        $consulta = bd()->prepare(
            'INSERT INTO chat_mensajes (autor_matricula, autor_nombre, mensaje)
             VALUES (:matricula, :nombre, :mensaje)'
        );
        $consulta->execute([
            'matricula' => $autor['matricula'],
            'nombre' => trim($autor['nombre'] . ' ' . $autor['apellido']),
            'mensaje' => $mensaje,
        ]);

        $idNuevo = (int) bd()->lastInsertId();
        $consulta = bd()->prepare(
            "SELECT id, autor_matricula, autor_nombre, mensaje,
                    DATE_FORMAT(creado_en, '%d/%m %H:%i') AS hora
             FROM chat_mensajes
             WHERE id = :id"
        );
        $consulta->execute(['id' => $idNuevo]);
        $nuevo = $consulta->fetch();
        $nuevo['es_mio'] = true;

        if ($quiereJson) {
            echo json_encode(
                ['ok' => true, 'mensaje' => $nuevo, 'ultimo' => $idNuevo],
                JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
            );
            exit;
        }

        redirigir('p9/chat');
    }

    $titulo = 'Práctica 9 · Chat de la tienda';

    // Sin sesión no se puede conversar, pero se muestra cómo entrar.
    if ($autor === null) {
        require __DIR__ . '/parciales/cabecera.php';
        ?>

        <div class="tienda-oxxo">
            <div class="contenedor-ancho">
                <div class="tarjeta">
                    <div class="encabezado">Chat de la tienda</div>
                    <div class="cuerpo">
                        <p>
                            Para conversar con otros usuarios necesitas iniciar sesión
                            con tu cuenta de la práctica 5.
                        </p>
                        <a class="boton" href="<?= e(enlace('p5/login')) ?>">Iniciar sesión</a>
                        <a class="boton boton-gris" href="<?= e(enlace('p5/registro')) ?>">Crear cuenta</a>
                        <a class="boton boton-gris" href="<?= e(enlace('p9')) ?>">Volver a la tienda</a>
                    </div>
                </div>
            </div>
        </div>

        <?php
        require __DIR__ . '/parciales/pie.php';
        exit;
    }

    $mensajes = chat_mensajes();
    $ultimoId = 0;

    if ($mensajes !== []) {
        $ultimoMensaje = end($mensajes);
        $ultimoId = (int) $ultimoMensaje['id'];
    }

    require __DIR__ . '/parciales/cabecera.php';
    ?>

    <div class="tienda-oxxo">
        <div class="banner-oxxo chat-banner">
            <img class="logo-oxxo" src="<?= e(enlace('assets/p9/logo.png')) ?>" alt="Logo OXXO">
            <div>
                <h1>Chat de la tienda</h1>
                <p>
                    Conversación entre usuarios ·
                    <?= e($autor['nombre'] . ' ' . $autor['apellido']) ?>
                </p>
            </div>
        </div>

        <div class="acciones-tienda">
            <a class="boton boton-gris" data-track="p9: volver de chat"
               href="<?= e(enlace('p9')) ?>">Volver a la tienda</a>
            <a class="boton boton-gris" data-track="p9: cerrar sesión desde chat"
               href="<?= e(enlace('p5/logout')) ?>">Cerrar sesión</a>
        </div>

        <div class="chat-marco" id="chat"
             data-mensajes="<?= e(enlace('p9/chat/mensajes')) ?>"
             data-enviar="<?= e(enlace('p9/chat/enviar')) ?>"
             data-ultimo="<?= $ultimoId ?>">
            <div class="chat-mensajes" id="chat-mensajes" aria-live="polite">
                <?php if ($mensajes === []): ?>
                    <p class="chat-vacio" id="chat-vacio">
                        Todavía no hay mensajes. ¡Escribe el primero!
                    </p>
                <?php else: ?>
                    <?php foreach ($mensajes as $mensaje): ?>
                        <div class="chat-mensaje <?= $mensaje['autor_matricula'] === $autor['matricula'] ? 'mio' : '' ?>"
                             data-id="<?= (int) $mensaje['id'] ?>">
                            <div class="chat-cabeza">
                                <strong><?= e($mensaje['autor_nombre']) ?></strong>
                                <time><?= e($mensaje['hora']) ?></time>
                            </div>
                            <p><?= e($mensaje['mensaje']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <form class="chat-form" id="chat-form" method="POST"
                  action="<?= e(enlace('p9/chat/enviar')) ?>">
                <input type="hidden" name="token" value="<?= e(chat_token()) ?>">
                <label class="visually-hidden" for="chat-texto">Mensaje</label>
                <textarea id="chat-texto" name="mensaje" maxlength="500" rows="2"
                          placeholder="Escribe un mensaje…" required></textarea>
                <button type="submit" class="boton boton-verde" id="chat-enviar">Enviar</button>
            </form>

            <p class="chat-estado" id="chat-estado">
                <span class="chat-punto"></span>
                <span id="chat-estado-texto">Se actualiza automáticamente cada 3 segundos.</span>
            </p>
        </div>
    </div>

    <script src="<?= e(enlace('assets/p9/chat.js')) ?>"></script>

    <?php
    require __DIR__ . '/parciales/pie.php';
    exit;
}

// ------------------------------------------------------------------
// LISTADO, BÚSQUEDA Y FILTRO
// ------------------------------------------------------------------

if ($vista === '') {
    $titulo = 'Práctica 9 · Tienda OXXO';

    // Permite generar otras ubicaciones aleatorias para las sucursales.
    if (isset($_GET['nuevas_sucursales'])) {
        unset($_SESSION['sucursales_p9']);
        redirigir('p9#sucursales');
    }

    $sucursales = sucursales_tienda();
    $scriptsMapaP9 = true;

    $busqueda = trim((string) ($_GET['q'] ?? ''));
    $categoria = trim((string) ($_GET['categoria'] ?? ''));

    $condiciones = [];
    $parametros = [];

    if ($busqueda !== '') {
        $condiciones[] = '(nombre LIKE :q_nombre
            OR marca LIKE :q_marca
            OR categoria LIKE :q_categoria
            OR codigo_barras LIKE :q_codigo)';
        $parametros['q_nombre'] = '%' . $busqueda . '%';
        $parametros['q_marca'] = '%' . $busqueda . '%';
        $parametros['q_categoria'] = '%' . $busqueda . '%';
        $parametros['q_codigo'] = '%' . $busqueda . '%';
    }

    if ($categoria !== '') {
        $condiciones[] = 'categoria = :categoria';
        $parametros['categoria'] = $categoria;
    }

    $sql = 'SELECT * FROM productos_tienda';
    if ($condiciones !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $condiciones);
    }
    $sql .= ' ORDER BY categoria, nombre';

    $consulta = bd()->prepare($sql);
    $consulta->execute($parametros);
    $productos = $consulta->fetchAll();

    $categorias = bd()->query(
        'SELECT DISTINCT categoria FROM productos_tienda ORDER BY categoria'
    )->fetchAll(PDO::FETCH_COLUMN);

    $totalProductos = (int) bd()->query(
        'SELECT COUNT(*) FROM productos_tienda'
    )->fetchColumn();
    $totalPiezas = (int) bd()->query(
        'SELECT COALESCE(SUM(existencia), 0) FROM productos_tienda'
    )->fetchColumn();
    $valorInventario = (float) bd()->query(
        'SELECT COALESCE(SUM(precio * existencia), 0) FROM productos_tienda'
    )->fetchColumn();

    require __DIR__ . '/parciales/cabecera.php';
    ?>

    <div class="tienda-oxxo">
        <div class="banner-oxxo">
            <img class="logo-oxxo" src="<?= e(enlace('assets/p9/logo.png')) ?>" alt="Logo OXXO">
            <div>
                <h1>Tienda OXXO · CRUD de productos</h1>
                <p>Productos, precios y existencias de una tienda mexicana.</p>
            </div>
        </div>

        <div class="resumen-tienda">
            <div><strong><?= $totalProductos ?></strong><span>Productos</span></div>
            <div><strong><?= $totalPiezas ?></strong><span>Piezas en tienda</span></div>
            <div><strong>$<?= e(number_format($valorInventario, 2)) ?></strong><span>Valor del inventario</span></div>
        </div>

        <form class="filtros-tienda" method="GET" action="<?= e(enlace('p9')) ?>">
            <input type="search" name="q" value="<?= e($busqueda) ?>"
                   placeholder="Buscar producto, marca o código…">
            <select name="categoria">
                <option value="">Todas las categorías</option>
                <?php foreach ($categorias as $opcion): ?>
                    <option value="<?= e($opcion) ?>" <?= $opcion === $categoria ? 'selected' : '' ?>>
                        <?= e($opcion) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="boton" data-track="p9: filtrar productos">Filtrar</button>
            <a class="boton boton-gris" data-track="p9: limpiar filtros"
               href="<?= e(enlace('p9')) ?>">Limpiar</a>
        </form>

        <div class="acciones-tienda">
            <a class="boton boton-verde" data-track="p9: agregar producto"
               href="<?= e(enlace('p9/crear')) ?>">Agregar producto</a>
            <a class="boton boton-chat" data-track="p9: abrir chat"
               href="<?= e(enlace('p9/chat')) ?>">Chat de la tienda</a>
            <a class="boton boton-video" data-track="p9: ver video"
               href="#video-tienda">Video de la tienda</a>
            <a class="boton boton-mapa" data-track="p9: ver sucursales"
               href="#sucursales">Sucursales</a>
            <a class="boton boton-admin" data-track="p9: abrir administración"
               href="<?= e(enlace('p9/admin')) ?>">
                <?= admin_p9_activo() ? 'Panel admin' : 'Admin' ?>
            </a>
            <a class="boton boton-gris" data-track="p9: ver métricas"
               href="<?= e(enlace('p8/panel')) ?>">Ver métricas de la tienda</a>
        </div>

        <div id="productos-tienda">
        <?php if ($productos): ?>
            <div class="tabla-scroll">
                <table class="tabla">
                    <tr>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Marca</th>
                        <th>Categoría</th>
                        <th>Precio</th>
                        <th>Existencia</th>
                        <th>Promoción</th>
                        <th>Acciones</th>
                    </tr>
                    <?php foreach ($productos as $producto): ?>
                        <?php
                        $existencia = (int) $producto['existencia'];
                        $claseStock = $existencia === 0
                            ? 'agotado'
                            : ($existencia < 10 ? 'pocas' : 'disponible');
                        $textoStock = $existencia === 0
                            ? 'Agotado'
                            : ($existencia < 10 ? 'Pocas piezas' : 'Disponible');
                        ?>
                        <tr>
                            <td><code><?= e($producto['codigo_barras']) ?></code></td>
                            <td><?= e($producto['nombre']) ?></td>
                            <td><?= e($producto['marca']) ?></td>
                            <td><?= e($producto['categoria']) ?></td>
                            <td class="precio-tienda">$<?= e(number_format((float) $producto['precio'], 2)) ?></td>
                            <td>
                                <?= $existencia ?>
                                <span class="etiqueta-stock <?= e($claseStock) ?>"><?= e($textoStock) ?></span>
                            </td>
                            <td>
                                <?php if ((string) $producto['promocion'] !== ''): ?>
                                    <span class="etiqueta-promo"><?= e($producto['promocion']) ?></span>
                                <?php else: ?>
                                    <span class="texto-tenue">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="acciones">
                                <a class="boton boton-chico"
                                   data-track="p9: editar <?= e($producto['nombre']) ?>"
                                   href="<?= e(enlace('p9/editar/' . (int) $producto['id'])) ?>">Editar</a>

                                <form class="formulario-en-linea" method="POST"
                                      action="<?= e(enlace('p9/eliminar/' . (int) $producto['id'])) ?>">
                                    <button type="submit" class="boton boton-chico boton-rojo"
                                            data-track="p9: eliminar <?= e($producto['nombre']) ?>">
                                        Eliminar
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php else: ?>
            <div class="aviso aviso-info">
                <?php if ($totalProductos === 0): ?>
                    <p>Todavía no hay productos en la tienda.</p>
                    <form method="POST" action="<?= e(enlace('p9/ejemplo')) ?>">
                        <button type="submit" class="boton boton-verde"
                                data-track="p9: cargar catálogo de ejemplo">
                            Cargar catálogo de ejemplo
                        </button>
                    </form>
                <?php else: ?>
                    No se encontraron productos con esa búsqueda o categoría.
                <?php endif; ?>
            </div>
        <?php endif; ?>
        </div>

        <section class="sucursales-tienda" id="sucursales">
            <div class="sucursales-encabezado">
                <div>
                    <h2>Sucursales de la tienda</h2>
                    <p class="pequena">
                        Ubicaciones de ejemplo generadas aleatoriamente alrededor de la tienda.
                    </p>
                </div>
                <a class="boton boton-gris" data-track="p9: cambiar sucursales"
                   href="<?= e(enlace('p9?nuevas_sucursales=1#sucursales')) ?>">
                    Cambiar sucursales
                </a>
            </div>

            <div class="sucursales-grid">
                <div id="mapa-sucursales" class="mapa-sucursales"
                     data-sucursales="<?= e(json_encode($sucursales, JSON_UNESCAPED_UNICODE)) ?>"
                     aria-label="Mapa de sucursales de la tienda"></div>

                <ul class="lista-sucursales">
                    <?php foreach ($sucursales as $sucursal): ?>
                        <li>
                            <strong><?= e($sucursal['nombre']) ?></strong>
                            <span><?= e($sucursal['direccion']) ?></span>
                            <small>
                                <?= e($sucursal['horario']) ?> ·
                                Tel. <?= e($sucursal['telefono']) ?>
                            </small>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </section>

        <section class="video-tienda" id="video-tienda">
            <div class="video-tienda-encabezado">
                <div>
                    <h2>Video de la tienda</h2>
                    <p class="pequena">
                        Un recorrido por la tienda OXXO y sus productos.
                    </p>
                </div>
                <a class="boton boton-gris" data-track="p9: volver a productos"
                   href="#productos-tienda">Volver a productos</a>
            </div>

            <div class="video-marco">
                <video class="video-practica" controls preload="metadata">
                    <source src="<?= e(enlace('assets/p9/video-tienda.mp4')) ?>" type="video/mp4">
                    Tu navegador no soporta la reproducción de video en HTML5.
                </video>
            </div>

            <p class="pequena centrado">
                Video de la tienda · Archivo:
                <code>assets/p9/video-tienda.mp4</code>
            </p>
        </section>
    </div>

    <?php
    require __DIR__ . '/parciales/pie.php';
    exit;
}

// ------------------------------------------------------------------
// ALTA
// ------------------------------------------------------------------

if ($vista === 'crear') {
    $titulo = 'Práctica 9 · Agregar producto';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $datos = datos_producto();
        $problema = revisar_producto($datos);

        if ($problema !== '') {
            aviso($problema, 'error');
            redirigir('p9/crear');
        }

        try {
            guardar_producto($datos);
        } catch (PDOException) {
            aviso('Ya existe un producto con ese código de barras.', 'error');
            redirigir('p9/crear');
        }

        aviso('Producto agregado correctamente.', 'exito');
        redirigir(destino_productos());
    }

    require __DIR__ . '/parciales/cabecera.php';
    ?>

    <div class="tienda-oxxo">
        <div class="contenedor-ancho">
            <div class="tarjeta">
                <div class="encabezado">Agregar producto a la tienda</div>
                <div class="cuerpo">
                    <form method="POST">
                        <div class="campo">
                            <label for="nombre">Nombre del producto</label>
                            <input type="text" id="nombre" name="nombre"
                                   maxlength="120" placeholder="Coca-Cola 600 ml" required>
                        </div>
                        <div class="fila">
                            <div class="campo">
                                <label for="marca">Marca</label>
                                <input type="text" id="marca" name="marca"
                                       maxlength="80" placeholder="Coca-Cola" required>
                            </div>
                            <div class="campo">
                                <label for="categoria">Categoría</label>
                                <input type="text" id="categoria" name="categoria"
                                       list="categorias-tienda" maxlength="60"
                                       placeholder="Bebidas" required>
                                <datalist id="categorias-tienda">
                                    <option value="Bebidas">
                                    <option value="Botanas">
                                    <option value="Dulces">
                                    <option value="Panadería">
                                    <option value="Lácteos">
                                    <option value="Higiene">
                                    <option value="Hogar">
                                </datalist>
                            </div>
                        </div>
                        <div class="fila">
                            <div class="campo">
                                <label for="precio">Precio (MXN)</label>
                                <input type="number" id="precio" name="precio"
                                       step="0.01" min="0" max="99999999.99" required>
                            </div>
                            <div class="campo">
                                <label for="existencia">Existencia</label>
                                <input type="number" id="existencia" name="existencia"
                                       step="1" min="0" required>
                            </div>
                        </div>
                        <div class="campo">
                            <label for="codigo_barras">Código de barras</label>
                            <input type="text" id="codigo_barras" name="codigo_barras"
                                   maxlength="20" inputmode="numeric"
                                   placeholder="7501055363513" required>
                        </div>
                        <div class="campo">
                            <label for="promocion">Promoción (opcional)</label>
                            <input type="text" id="promocion" name="promocion"
                                   maxlength="120" placeholder="3 por $50">
                        </div>
                        <button type="submit" class="boton boton-verde"
                                data-track="p9: guardar producto nuevo">Guardar</button>
                        <a class="boton boton-gris" data-track="p9: cancelar alta"
                           href="<?= e(enlace(destino_productos())) ?>">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php
    require __DIR__ . '/parciales/pie.php';
    exit;
}

// ------------------------------------------------------------------
// EDICIÓN
// ------------------------------------------------------------------

if ($vista === 'editar' && $id !== null) {
    $titulo = 'Práctica 9 · Editar producto';

    $consulta = bd()->prepare('SELECT * FROM productos_tienda WHERE id = :id');
    $consulta->execute(['id' => $id]);
    $producto = $consulta->fetch();

    if ($producto === false) {
        aviso('El producto que buscas no existe.', 'error');
        redirigir('p9');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $datos = datos_producto();
        $problema = revisar_producto($datos);

        if ($problema !== '') {
            aviso($problema, 'error');
            redirigir("p9/editar/$id");
        }

        $consulta = bd()->prepare(
            'UPDATE productos_tienda SET
                nombre = :nombre,
                marca = :marca,
                categoria = :categoria,
                precio = :precio,
                existencia = :existencia,
                codigo_barras = :codigo_barras,
                promocion = :promocion
             WHERE id = :id'
        );

        try {
            $consulta->execute([
                'nombre' => $datos['nombre'],
                'marca' => $datos['marca'],
                'categoria' => $datos['categoria'],
                'precio' => (float) $datos['precio'],
                'existencia' => (int) $datos['existencia'],
                'codigo_barras' => $datos['codigo_barras'],
                'promocion' => $datos['promocion'] !== '' ? $datos['promocion'] : null,
                'id' => $id,
            ]);
        } catch (PDOException) {
            aviso('Ya existe otro producto con ese código de barras.', 'error');
            redirigir("p9/editar/$id");
        }

        aviso('Producto modificado correctamente.', 'exito');
        redirigir(destino_productos());
    }

    require __DIR__ . '/parciales/cabecera.php';
    ?>

    <div class="tienda-oxxo">
        <div class="contenedor-ancho">
            <div class="tarjeta">
                <div class="encabezado">Editar producto</div>
                <div class="cuerpo">
                    <form method="POST">
                        <div class="campo">
                            <label for="nombre">Nombre del producto</label>
                            <input type="text" id="nombre" name="nombre" maxlength="120"
                                   value="<?= e($producto['nombre']) ?>" required>
                        </div>
                        <div class="fila">
                            <div class="campo">
                                <label for="marca">Marca</label>
                                <input type="text" id="marca" name="marca" maxlength="80"
                                       value="<?= e($producto['marca']) ?>" required>
                            </div>
                            <div class="campo">
                                <label for="categoria">Categoría</label>
                                <input type="text" id="categoria" name="categoria"
                                       list="categorias-tienda" maxlength="60"
                                       value="<?= e($producto['categoria']) ?>" required>
                                <datalist id="categorias-tienda">
                                    <option value="Bebidas">
                                    <option value="Botanas">
                                    <option value="Dulces">
                                    <option value="Panadería">
                                    <option value="Lácteos">
                                    <option value="Higiene">
                                    <option value="Hogar">
                                </datalist>
                            </div>
                        </div>
                        <div class="fila">
                            <div class="campo">
                                <label for="precio">Precio (MXN)</label>
                                <input type="number" id="precio" name="precio"
                                       step="0.01" min="0" max="99999999.99"
                                       value="<?= e($producto['precio']) ?>" required>
                            </div>
                            <div class="campo">
                                <label for="existencia">Existencia</label>
                                <input type="number" id="existencia" name="existencia"
                                       step="1" min="0"
                                       value="<?= e($producto['existencia']) ?>" required>
                            </div>
                        </div>
                        <div class="campo">
                            <label for="codigo_barras">Código de barras</label>
                            <input type="text" id="codigo_barras" name="codigo_barras"
                                   maxlength="20" inputmode="numeric"
                                   value="<?= e($producto['codigo_barras']) ?>" required>
                        </div>
                        <div class="campo">
                            <label for="promocion">Promoción (opcional)</label>
                            <input type="text" id="promocion" name="promocion" maxlength="120"
                                   value="<?= e($producto['promocion']) ?>" placeholder="3 por $50">
                        </div>
                        <button type="submit" class="boton"
                                data-track="p9: guardar cambios">Guardar cambios</button>
                        <a class="boton boton-gris" data-track="p9: cancelar edición"
                           href="<?= e(enlace(destino_productos())) ?>">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php
    require __DIR__ . '/parciales/pie.php';
    exit;
}

// ------------------------------------------------------------------
// BAJA
// ------------------------------------------------------------------

if ($vista === 'eliminar' && $id !== null) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        redirigir('p9');
    }

    $consulta = bd()->prepare('DELETE FROM productos_tienda WHERE id = :id');
    $consulta->execute(['id' => $id]);

    aviso('Producto eliminado correctamente.', 'info');
    redirigir(destino_productos());
}

redirigir('p9');
