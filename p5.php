<?php
declare(strict_types=1);

/*
| Práctica 5: login con sesiones y CRUD de estudiantes.
|
| Las contraseñas se guardan encriptadas con password_hash() y se revisan
| con password_verify(): nunca se guarda ni se compara en texto plano.
*/

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/parciales/tablas.php';

$partes = array_slice(explode('/', $ruta), 1);
$vista = $partes[0] ?? '';
$accion = $partes[1] ?? '';
$id = isset($partes[2]) ? (int) $partes[2] : null;

crear_tabla_estudiantes();

/**
 * Si no hay sesión abierta, manda al login.
 */
function pedir_login(): void
{
    if (!isset($_SESSION['usuario'])) {
        redirigir('p5/login');
    }
}

/**
 * Revisa que la contraseña sea válida y esté escrita dos veces.
 */
function revisar_contrasena(string $contrasena, string $repetir): string
{
    if (strlen($contrasena) < 8) {
        return 'La contraseña debe tener al menos 8 caracteres.';
    }

    if ($contrasena !== $repetir) {
        return 'Las dos contraseñas no son iguales.';
    }

    return '';
}

/**
 * Inserta un estudiante con su contraseña ya encriptada.
 */
function guardar_estudiante(array $datos): void
{
    $consulta = bd()->prepare(
        'INSERT INTO estudiantes
            (matricula, nombre, apellido, carrera, correo, promedio, password_hash)
         VALUES
            (:matricula, :nombre, :apellido, :carrera, :correo, :promedio, :password_hash)'
    );

    $consulta->execute([
        'matricula' => $datos['matricula'],
        'nombre' => $datos['nombre'],
        'apellido' => $datos['apellido'],
        'carrera' => $datos['carrera'],
        'correo' => $datos['correo'],
        'promedio' => (float) $datos['promedio'],
        // ENVÍO SEGURO: la contraseña se convierte en un hash.
        'password_hash' => password_hash($datos['contrasena'], PASSWORD_DEFAULT),
    ]);
}

// ------------------------------------------------------------------
// LOGIN Y LOGOUT
// ------------------------------------------------------------------

if ($vista === 'login') {
    $titulo = 'Iniciar sesión';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $matricula = trim($_POST['usuario'] ?? '');
        $contrasena = $_POST['contrasena'] ?? '';

        $consulta = bd()->prepare('SELECT * FROM estudiantes WHERE matricula = :matricula');
        $consulta->execute(['matricula' => $matricula]);
        $estudiante = $consulta->fetch();

        // password_verify() compara la contraseña con el hash guardado.
        if ($estudiante !== false
            && password_verify($contrasena, $estudiante['password_hash'])
        ) {
            $_SESSION['usuario'] = $estudiante['matricula'];
            aviso('¡Hola ' . $estudiante['nombre'] . '! Iniciaste sesión.', 'exito');
            redirigir('p5');
        }

        // El mismo mensaje para los dos casos: no decimos si la matrícula existe.
        aviso('Matrícula o contraseña incorrectos.', 'error');
    }

    require __DIR__ . '/parciales/cabecera.php';
    ?>

    <div class="contenedor-ancho">
        <div class="tarjeta">
            <div class="encabezado centrado">Iniciar sesión (Práctica 5)</div>
            <div class="cuerpo">
                <form method="POST">
                    <div class="campo">
                        <label for="usuario">Matrícula</label>
                        <input type="text" id="usuario" name="usuario" required autofocus>
                    </div>
                    <div class="campo">
                        <label for="contrasena">Contraseña</label>
                        <input type="password" id="contrasena" name="contrasena" required>
                    </div>
                    <button type="submit" class="boton">Entrar</button>
                </form>
            </div>
            <div class="pie centrado">
                ¿No tienes cuenta? <a href="<?= e(enlace('p5/registro')) ?>">Regístrate aquí</a>
            </div>
        </div>
    </div>

    <?php
    require __DIR__ . '/parciales/pie.php';
    exit;
}

if ($vista === 'logout') {
    pedir_login();

    unset($_SESSION['usuario']);
    aviso('Cerraste sesión correctamente.', 'info');
    redirigir('p5/login');
}

if ($vista === 'registro') {
    $titulo = 'Registrar estudiante';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $problema = revisar_contrasena(
            $_POST['contrasena'] ?? '',
            $_POST['repetir_contrasena'] ?? ''
        );

        if ($problema !== '') {
            aviso($problema, 'error');
            redirigir('p5/registro');
        }

        try {
            guardar_estudiante([
                'matricula' => trim($_POST['matricula'] ?? ''),
                'nombre' => trim($_POST['nombre'] ?? ''),
                'apellido' => trim($_POST['apellido'] ?? ''),
                'carrera' => trim($_POST['carrera'] ?? ''),
                'correo' => trim($_POST['correo'] ?? ''),
                'promedio' => $_POST['promedio'] ?? '0',
                'contrasena' => $_POST['contrasena'] ?? '',
            ]);
        } catch (PDOException) {
            aviso('Ya existe un estudiante con esa matrícula.', 'error');
            redirigir('p5/registro');
        }

        aviso('¡Cuenta creada! Ya puedes iniciar sesión.', 'exito');
        redirigir('p5/login');
    }

    require __DIR__ . '/parciales/cabecera.php';
    ?>

    <div class="contenedor-ancho">
        <div class="tarjeta">
            <div class="encabezado">Registrar estudiante</div>
            <div class="cuerpo">
                <p class="pequena">
                    Te registrarás con tu <strong>matrícula</strong> como usuario.
                </p>
                <form method="POST">
                    <div class="campo">
                        <label for="matricula">Matrícula</label>
                        <input type="text" id="matricula" name="matricula" placeholder="A2025001" required>
                    </div>
                    <div class="fila">
                        <div class="campo">
                            <label for="nombre">Nombre</label>
                            <input type="text" id="nombre" name="nombre" required>
                        </div>
                        <div class="campo">
                            <label for="apellido">Apellido</label>
                            <input type="text" id="apellido" name="apellido" required>
                        </div>
                    </div>
                    <div class="campo">
                        <label for="carrera">Carrera</label>
                        <input type="text" id="carrera" name="carrera" required>
                    </div>
                    <div class="campo">
                        <label for="correo">Correo</label>
                        <input type="email" id="correo" name="correo" required>
                    </div>
                    <div class="campo">
                        <label for="promedio">Promedio</label>
                        <input type="number" id="promedio" name="promedio" step="0.01" min="0" max="10" required>
                    </div>
                    <div class="campo">
                        <label for="contrasena">Contraseña</label>
                        <input type="password" id="contrasena" name="contrasena" required>
                    </div>
                    <div class="campo">
                        <label for="repetir_contrasena">Repetir contraseña</label>
                        <input type="password" id="repetir_contrasena" name="repetir_contrasena" required>
                        <p class="pequena">Debe tener al menos 8 caracteres y ser igual a la de arriba.</p>
                    </div>
                    <button type="submit" class="boton">Crear cuenta</button>
                    <a class="boton boton-gris" href="<?= e(enlace('p5/login')) ?>">Ya tengo cuenta</a>
                </form>
            </div>
        </div>
    </div>

    <?php
    require __DIR__ . '/parciales/pie.php';
    exit;
}

// ------------------------------------------------------------------
// CRUD DE ESTUDIANTES
// ------------------------------------------------------------------

pedir_login();

if ($vista === 'estudiantes' && $accion === '') {
    $titulo = 'Lista de estudiantes';

    $estudiantes = bd()->query(
        'SELECT id, matricula, nombre, apellido, carrera, correo, promedio
         FROM estudiantes ORDER BY apellido'
    )->fetchAll();

    require __DIR__ . '/parciales/cabecera.php';
    ?>

    <h2>Lista de estudiantes</h2>

    <p>
        <a class="boton boton-verde" href="<?= e(enlace('p5/estudiantes/crear')) ?>">Agregar estudiante</a>
    </p>

    <?php if ($estudiantes): ?>
        <table class="tabla">
            <tr>
                <th>Matrícula</th>
                <th>Nombre</th>
                <th>Carrera</th>
                <th>Correo</th>
                <th>Promedio</th>
                <th>Acciones</th>
            </tr>
            <?php foreach ($estudiantes as $estudiante): ?>
                <tr>
                    <td><?= e($estudiante['matricula']) ?></td>
                    <td><?= e($estudiante['nombre'] . ' ' . $estudiante['apellido']) ?></td>
                    <td><?= e($estudiante['carrera']) ?></td>
                    <td><?= e($estudiante['correo']) ?></td>
                    <td><?= e($estudiante['promedio']) ?></td>
                    <td class="acciones">
                        <a class="boton boton-chico"
                           href="<?= e(enlace('p5/estudiantes/editar/' . (int) $estudiante['id'])) ?>">Editar</a>

                        <form class="formulario-en-linea" method="POST"
                              action="<?= e(enlace('p5/estudiantes/eliminar/' . (int) $estudiante['id'])) ?>">
                            <button type="submit" class="boton boton-chico boton-rojo">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <div class="aviso aviso-info">
            Todavía no hay estudiantes registrados.
        </div>
    <?php endif; ?>

    <?php
    require __DIR__ . '/parciales/pie.php';
    exit;
}

if ($vista === 'estudiantes' && $accion === 'crear') {
    $titulo = 'Agregar estudiante';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $contrasena = $_POST['contrasena'] ?? '';

        if ($contrasena === '') {
            aviso('La contraseña no puede quedar vacía.', 'error');
            redirigir('p5/estudiantes/crear');
        }

        try {
            guardar_estudiante([
                'matricula' => trim($_POST['matricula'] ?? ''),
                'nombre' => trim($_POST['nombre'] ?? ''),
                'apellido' => trim($_POST['apellido'] ?? ''),
                'carrera' => trim($_POST['carrera'] ?? ''),
                'correo' => trim($_POST['correo'] ?? ''),
                'promedio' => $_POST['promedio'] ?? '0',
                'contrasena' => $contrasena,
            ]);
        } catch (PDOException) {
            aviso('Ya existe un estudiante con esa matrícula.', 'error');
            redirigir('p5/estudiantes/crear');
        }

        aviso('Estudiante agregado correctamente.', 'exito');
        redirigir('p5/estudiantes');
    }

    require __DIR__ . '/parciales/cabecera.php';
    ?>

    <div class="contenedor-ancho">
        <div class="tarjeta">
            <div class="encabezado">Agregar estudiante</div>
            <div class="cuerpo">
                <form method="POST">
                    <div class="campo">
                        <label for="matricula">Matrícula</label>
                        <input type="text" id="matricula" name="matricula" placeholder="A2025001" required>
                    </div>
                    <div class="fila">
                        <div class="campo">
                            <label for="nombre">Nombre</label>
                            <input type="text" id="nombre" name="nombre" required>
                        </div>
                        <div class="campo">
                            <label for="apellido">Apellido</label>
                            <input type="text" id="apellido" name="apellido" required>
                        </div>
                    </div>
                    <div class="campo">
                        <label for="carrera">Carrera</label>
                        <input type="text" id="carrera" name="carrera" required>
                    </div>
                    <div class="campo">
                        <label for="correo">Correo</label>
                        <input type="email" id="correo" name="correo" required>
                    </div>
                    <div class="campo">
                        <label for="promedio">Promedio</label>
                        <input type="number" id="promedio" name="promedio" step="0.01" min="0" max="10" required>
                    </div>
                    <div class="campo">
                        <label for="contrasena">Contraseña de la cuenta</label>
                        <input type="password" id="contrasena" name="contrasena" required>
                        <p class="pequena">Entrará con su matrícula y esta contraseña.</p>
                    </div>
                    <button type="submit" class="boton boton-verde">Guardar</button>
                    <a class="boton boton-gris" href="<?= e(enlace('p5/estudiantes')) ?>">Cancelar</a>
                </form>
            </div>
        </div>
    </div>

    <?php
    require __DIR__ . '/parciales/pie.php';
    exit;
}

if ($vista === 'estudiantes' && $accion === 'editar' && $id !== null) {
    $titulo = 'Editar estudiante';

    $consulta = bd()->prepare(
        'SELECT id, matricula, nombre, apellido, carrera, correo, promedio
         FROM estudiantes WHERE id = :id'
    );
    $consulta->execute(['id' => $id]);
    $estudiante = $consulta->fetch();

    if ($estudiante === false) {
        aviso('El estudiante que buscas no existe.', 'error');
        redirigir('p5/estudiantes');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $consulta = bd()->prepare(
            'UPDATE estudiantes SET
                matricula = :matricula,
                nombre = :nombre,
                apellido = :apellido,
                carrera = :carrera,
                correo = :correo,
                promedio = :promedio
             WHERE id = :id'
        );

        try {
            $consulta->execute([
                'matricula' => trim($_POST['matricula'] ?? ''),
                'nombre' => trim($_POST['nombre'] ?? ''),
                'apellido' => trim($_POST['apellido'] ?? ''),
                'carrera' => trim($_POST['carrera'] ?? ''),
                'correo' => trim($_POST['correo'] ?? ''),
                'promedio' => (float) ($_POST['promedio'] ?? 0),
                'id' => $id,
            ]);

            aviso('Estudiante modificado correctamente.', 'exito');
        } catch (PDOException) {
            aviso('Ya existe un estudiante con esa matrícula.', 'error');
        }

        redirigir('p5/estudiantes');
    }

    require __DIR__ . '/parciales/cabecera.php';
    ?>

    <div class="contenedor-ancho">
        <div class="tarjeta">
            <div class="encabezado">Editar estudiante</div>
            <div class="cuerpo">
                <form method="POST">
                    <div class="campo">
                        <label for="matricula">Matrícula</label>
                        <input type="text" id="matricula" name="matricula"
                               value="<?= e($estudiante['matricula']) ?>" required>
                    </div>
                    <div class="fila">
                        <div class="campo">
                            <label for="nombre">Nombre</label>
                            <input type="text" id="nombre" name="nombre"
                                   value="<?= e($estudiante['nombre']) ?>" required>
                        </div>
                        <div class="campo">
                            <label for="apellido">Apellido</label>
                            <input type="text" id="apellido" name="apellido"
                                   value="<?= e($estudiante['apellido']) ?>" required>
                        </div>
                    </div>
                    <div class="campo">
                        <label for="carrera">Carrera</label>
                        <input type="text" id="carrera" name="carrera"
                               value="<?= e($estudiante['carrera']) ?>" required>
                    </div>
                    <div class="campo">
                        <label for="correo">Correo</label>
                        <input type="email" id="correo" name="correo"
                               value="<?= e($estudiante['correo']) ?>" required>
                    </div>
                    <div class="campo">
                        <label for="promedio">Promedio</label>
                        <input type="number" id="promedio" name="promedio" step="0.01" min="0" max="10"
                               value="<?= e($estudiante['promedio']) ?>" required>
                    </div>
                    <button type="submit" class="boton">Guardar cambios</button>
                    <a class="boton boton-gris" href="<?= e(enlace('p5/estudiantes')) ?>">Cancelar</a>
                </form>
            </div>
        </div>
    </div>

    <?php
    require __DIR__ . '/parciales/pie.php';
    exit;
}

if ($vista === 'estudiantes' && $accion === 'eliminar' && $id !== null) {
    $consulta = bd()->prepare('DELETE FROM estudiantes WHERE id = :id');
    $consulta->execute(['id' => $id]);

    aviso('Estudiante eliminado correctamente.', 'info');
    redirigir('p5/estudiantes');
}

// Portada de la práctica 5.
$titulo = 'Práctica 5 · Gestión de estudiantes';

require __DIR__ . '/parciales/cabecera.php';
?>

<div class="text-center mb-4">
    <img src="<?= e(enlace('assets/foto.jpg')) ?>" alt="Mi foto" class="foto-encuesta">
    <p class="ocupacion mt-2 mb-0">Cristhopher Robledo</p>
</div>

<h1>Práctica 5 · Gestión de estudiantes</h1>
<p class="pequena">Sesiones con contraseñas encriptadas y CRUD completo.</p>

<div class="rejilla-tarjetas">
    <div class="tarjeta">
        <div class="encabezado">Bienvenido, <?= e($_SESSION['usuario']) ?></div>
        <div class="cuerpo">
            <p>Este módulo incluye:</p>
            <ul>
                <li>Funcionamiento de las sesiones.</li>
                <li>Login y logout con contraseñas encriptadas.</li>
                <li>Conexión a una base de datos MySQL.</li>
                <li>CRUD: insertar, consultar, modificar y eliminar.</li>
            </ul>
        </div>
        <div class="pie">
            <a class="boton boton-verde" href="<?= e(enlace('p5/estudiantes/crear')) ?>">Agregar estudiante</a>
            <a class="boton" href="<?= e(enlace('p5/estudiantes')) ?>">Ver lista</a>
        </div>
    </div>
</div>

<?php require __DIR__ . '/parciales/pie.php'; ?>