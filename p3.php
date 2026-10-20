<?php
declare(strict_types=1);

/*
| Práctica 3: CRUD de productos (INSERT, SELECT, UPDATE, DELETE).
|
| Cada bloque corresponde a una consulta SQL distinta.
*/

require_once __DIR__ . '/config.php';

$partes = array_slice(explode('/', $ruta), 1);
$vista = $partes[0] ?? '';
$id = isset($partes[1]) ? (int) $partes[1] : null;

crear_tabla('
    CREATE TABLE IF NOT EXISTS productos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(100) NOT NULL,
        descripcion VARCHAR(255) NOT NULL,
        precio DECIMAL(10, 2) NOT NULL,
        existencia INT NOT NULL
    ) ENGINE = InnoDB
');

// ------------------------------------------------------------------
// CONSULTA (SELECT)
// ------------------------------------------------------------------

if ($vista === '') {
    $titulo = 'Práctica 3 · CRUD de productos';

    $productos = bd()->query('SELECT * FROM productos ORDER BY nombre')->fetchAll();

    require __DIR__ . '/parciales/cabecera.php';
    ?>

    <div class="text-center mb-4">
        <img src="<?= e(enlace('assets/foto.jpg')) ?>" alt="Mi foto" class="foto-encuesta">
        <p class="ocupacion mt-2 mb-0">Cristhopher Robledo</p>
    </div>

    <h1>Práctica 3 · CRUD de productos</h1>

    <p>
        <a class="boton boton-verde" href="<?= e(enlace('p3/crear')) ?>">Agregar producto</a>
    </p>

    <?php if ($productos): ?>
        <table class="tabla">
            <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Precio</th>
                <th>Existencia</th>
                <th>Acciones</th>
            </tr>
            <?php foreach ($productos as $producto): ?>
                <tr>
                    <td><?= e($producto['nombre']) ?></td>
                    <td><?= e($producto['descripcion']) ?></td>
                    <td>$<?= e($producto['precio']) ?></td>
                    <td><?= e($producto['existencia']) ?></td>
                    <td class="acciones">
                        <a class="boton boton-chico"
                           href="<?= e(enlace('p3/editar/' . (int) $producto['id'])) ?>">Editar</a>

                        <form class="formulario-en-linea" method="POST"
                              action="<?= e(enlace('p3/eliminar/' . (int) $producto['id'])) ?>">
                            <button type="submit" class="boton boton-chico boton-rojo">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <div class="aviso aviso-info">
            Todavía no hay productos registrados.
        </div>
    <?php endif; ?>

    <?php
    require __DIR__ . '/parciales/pie.php';
    exit;
}

// ------------------------------------------------------------------
// INSERCIÓN (INSERT)
// ------------------------------------------------------------------

if ($vista === 'crear') {
    $titulo = 'Agregar producto';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $precio = $_POST['precio'] ?? '';
        $existencia = $_POST['existencia'] ?? '';

        if (!is_numeric($precio) || !is_numeric($existencia)) {
            aviso('El precio y la existencia deben ser números.', 'error');
            redirigir('p3/crear');
        }

        $consulta = bd()->prepare(
            'INSERT INTO productos (nombre, descripcion, precio, existencia)
             VALUES (:nombre, :descripcion, :precio, :existencia)'
        );
        $consulta->execute([
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'precio' => (float) $precio,
            'existencia' => (int) $existencia,
        ]);

        aviso('Producto agregado correctamente.', 'exito');
        redirigir('p3');
    }

    require __DIR__ . '/parciales/cabecera.php';
    ?>

    <div class="contenedor-ancho">
        <div class="tarjeta">
            <div class="encabezado">Agregar producto</div>
            <div class="cuerpo">
                <form method="POST">
                    <div class="campo">
                        <label for="nombre">Nombre</label>
                        <input type="text" id="nombre" name="nombre" required>
                    </div>
                    <div class="campo">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="3" required></textarea>
                    </div>
                    <div class="fila">
                        <div class="campo">
                            <label for="precio">Precio</label>
                            <input type="number" id="precio" name="precio" step="0.01" min="0" required>
                        </div>
                        <div class="campo">
                            <label for="existencia">Existencia</label>
                            <input type="number" id="existencia" name="existencia" step="1" min="0" required>
                        </div>
                    </div>
                    <button type="submit" class="boton boton-verde">Guardar</button>
                    <a class="boton boton-gris" href="<?= e(enlace('p3')) ?>">Cancelar</a>
                </form>
            </div>
        </div>
    </div>

    <?php
    require __DIR__ . '/parciales/pie.php';
    exit;
}

// ------------------------------------------------------------------
// MODIFICACIÓN (UPDATE)
// ------------------------------------------------------------------

if ($vista === 'editar' && $id !== null) {
    $titulo = 'Editar producto';

    $consulta = bd()->prepare('SELECT * FROM productos WHERE id = :id');
    $consulta->execute(['id' => $id]);
    $producto = $consulta->fetch();

    if ($producto === false) {
        aviso('El producto que buscas no existe.', 'error');
        redirigir('p3');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $precio = $_POST['precio'] ?? '';
        $existencia = $_POST['existencia'] ?? '';

        if (!is_numeric($precio) || !is_numeric($existencia)) {
            aviso('El precio y la existencia deben ser números.', 'error');
            redirigir("p3/editar/$id");
        }

        $consulta = bd()->prepare(
            'UPDATE productos SET
                nombre = :nombre,
                descripcion = :descripcion,
                precio = :precio,
                existencia = :existencia
             WHERE id = :id'
        );
        $consulta->execute([
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'precio' => (float) $precio,
            'existencia' => (int) $existencia,
            'id' => $id,
        ]);

        aviso('Producto modificado correctamente.', 'exito');
        redirigir('p3');
    }

    require __DIR__ . '/parciales/cabecera.php';
    ?>

    <div class="contenedor-ancho">
        <div class="tarjeta">
            <div class="encabezado">Editar producto</div>
            <div class="cuerpo">
                <form method="POST">
                    <div class="campo">
                        <label for="nombre">Nombre</label>
                        <input type="text" id="nombre" name="nombre"
                               value="<?= e($producto['nombre']) ?>" required>
                    </div>
                    <div class="campo">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" rows="3" required><?= e($producto['descripcion']) ?></textarea>
                    </div>
                    <div class="fila">
                        <div class="campo">
                            <label for="precio">Precio</label>
                            <input type="number" id="precio" name="precio" step="0.01" min="0"
                                   value="<?= e($producto['precio']) ?>" required>
                        </div>
                        <div class="campo">
                            <label for="existencia">Existencia</label>
                            <input type="number" id="existencia" name="existencia" step="1" min="0"
                                   value="<?= e($producto['existencia']) ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="boton">Guardar cambios</button>
                    <a class="boton boton-gris" href="<?= e(enlace('p3')) ?>">Cancelar</a>
                </form>
            </div>
        </div>
    </div>

    <?php
    require __DIR__ . '/parciales/pie.php';
    exit;
}

// ------------------------------------------------------------------
// ELIMINACIÓN (DELETE)
// ------------------------------------------------------------------

if ($vista === 'eliminar' && $id !== null) {
    $consulta = bd()->prepare('DELETE FROM productos WHERE id = :id');
    $consulta->execute(['id' => $id]);

    aviso('Producto eliminado correctamente.', 'info');
    redirigir('p3');
}

redirigir('p3');