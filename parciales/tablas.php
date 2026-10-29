<?php
declare(strict_types=1);

/*
| Tablas compartidas entre prácticas.
|
| La tabla estudiantes nació en la práctica 5 y la tabla eventos pertenece a la
| práctica 8. Se crean desde aquí para que p8 pueda usar eventos aunque la
| práctica 5 todavía no se haya abierto.
*/

require_once __DIR__ . '/../config.php';

/**
 * Crea la tabla de estudiantes si no existe.
 */
function crear_tabla_estudiantes(): void
{
    crear_tabla('
        CREATE TABLE IF NOT EXISTS estudiantes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            matricula VARCHAR(20) NOT NULL UNIQUE,
            nombre VARCHAR(80) NOT NULL,
            apellido VARCHAR(80) NOT NULL,
            carrera VARCHAR(120) NOT NULL,
            correo VARCHAR(120) NOT NULL,
            promedio DECIMAL(4, 2) NOT NULL,
            password_hash VARCHAR(255) NOT NULL
        ) ENGINE = InnoDB
    ');
}

/**
 * Crea la tabla de eventos de la práctica 8.
 *
 * usuario_id queda NULL mientras el visitante es anónimo. Si se borra un
 * estudiante, sus eventos se conservan pero vuelven a ser anónimos.
 */
function crear_tabla_eventos(): void
{
    crear_tabla_estudiantes();

    crear_tabla('
        CREATE TABLE IF NOT EXISTS eventos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            visitante_id CHAR(36) NOT NULL,
            sesion_id CHAR(36) NOT NULL,
            usuario_id INT NULL,
            tipo ENUM(\'vista\', \'clic\', \'tiempo\') NOT NULL,
            pagina VARCHAR(200) NOT NULL,
            elemento VARCHAR(100) NULL,
            segundos SMALLINT UNSIGNED NULL,
            creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (usuario_id) REFERENCES estudiantes(id) ON DELETE SET NULL,
            INDEX idx_eventos_visitante (visitante_id),
            INDEX idx_eventos_sesion (sesion_id)
        ) ENGINE = InnoDB
    ');
}
