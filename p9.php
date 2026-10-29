<?php
declare(strict_types=1);

/*
| Práctica 9: CRUD de una tienda tipo OXXO.
|
| Lista, busca, agrega, modifica y elimina productos de una tienda mexicana.
| Usa la misma base de datos awos y las mismas convenciones de las prácticas
| anteriores (PDO, consultas preparadas y la función e() para escapar HTML).
*/

require_once __DIR__ . '/config.php';

$partes = array_slice(explode('/', $ruta), 1);
$vista = $partes[0] ?? '';
$id = isset($partes[1]) ? (int) $partes[1] : null;

$scriptsP9 = true;

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

    redirigir('p9');
}

// ------------------------------------------------------------------
// LISTADO, BÚSQUEDA Y FILTRO
// ------------------------------------------------------------------

if ($vista === '') {
    $titulo = 'Práctica 9 · Tienda OXXO';

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
            <div class="logo-oxxo">OXXO</div>
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
            <a class="boton boton-gris" data-track="p9: ver métricas"
               href="<?= e(enlace('p8/panel')) ?>">Ver métricas de la tienda</a>
        </div>

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
        redirigir('p9');
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
                           href="<?= e(enlace('p9')) ?>">Cancelar</a>
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
        redirigir('p9');
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
                           href="<?= e(enlace('p9')) ?>">Cancelar</a>
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
    redirigir('p9');
}

redirigir('p9');
