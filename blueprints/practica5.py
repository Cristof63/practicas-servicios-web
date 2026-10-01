"""Práctica 5: login con sesiones y CRUD de estudiantes.

Igual que la práctica original, pero con prefijo /p5 para convivir con las
demás dentro del mismo sitio. La clave de sesión se toma de la configuración
de la aplicación principal.
"""

import sqlite3
from pathlib import Path

from flask import (
    Blueprint,
    flash,
    redirect,
    render_template,
    request,
    session,
    url_for,
)
from werkzeug.security import check_password_hash, generate_password_hash

BASE_DIR = Path(__file__).resolve().parent.parent
RUTA_BASE_DATOS = BASE_DIR / "db" / "practica5.db"

practica5 = Blueprint("practica5", __name__, url_prefix="/p5")


def conectar_base_datos():
    """Abre la base de datos de la práctica con acceso por nombre de columna."""
    conexion = sqlite3.connect(RUTA_BASE_DATOS)
    conexion.row_factory = sqlite3.Row
    return conexion


def crear_tabla():
    """Crea la tabla estudiantes si todavía no existe."""
    conexion = conectar_base_datos()

    conexion.execute("""
        CREATE TABLE IF NOT EXISTS estudiantes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            matricula TEXT NOT NULL UNIQUE,
            nombre TEXT NOT NULL,
            apellido TEXT NOT NULL,
            carrera TEXT NOT NULL,
            correo TEXT NOT NULL,
            promedio REAL NOT NULL,
            password_hash TEXT NOT NULL
        )
    """)

    conexion.commit()
    conexion.close()


def revisar_contrasena(contrasena, repetir):
    """Revisa que la contraseña sea válida y esté escrita dos veces."""
    if len(contrasena) < 8:
        return "La contraseña debe tener al menos 8 caracteres."

    if contrasena != repetir:
        return "Las dos contraseñas no son iguales."

    return ""


def guardar_estudiante(matricula, nombre, apellido, carrera, correo,
                       promedio, contrasena):
    """Inserta un estudiante con su contraseña encriptada."""
    hash_contrasena = generate_password_hash(contrasena)

    conexion = conectar_base_datos()
    conexion.execute("""
        INSERT INTO estudiantes
            (matricula, nombre, apellido, carrera, correo, promedio, password_hash)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    """, (matricula, nombre, apellido, carrera, correo, promedio,
          hash_contrasena))
    conexion.commit()
    conexion.close()


def pedir_login():
    """Si no hay sesión abierta, manda al login."""
    if "usuario" not in session:
        return redirect(url_for("practica5.login"))
    return None


# ------------------------------------------------------------------
# INICIO, LOGIN Y LOGOUT
# ------------------------------------------------------------------

@practica5.route("/")
def index():
    """Portada de la práctica: pide sesión primero."""
    respuesta = pedir_login()
    if respuesta is not None:
        return respuesta

    return render_template("p5/index.html")


@practica5.route("/login", methods=["GET", "POST"])
def login():
    """Revisa las credenciales y abre la sesión."""
    if request.method == "POST":
        matricula = request.form.get("usuario")
        contrasena = request.form.get("contrasena")

        conexion = conectar_base_datos()
        estudiante = conexion.execute(
            "SELECT * FROM estudiantes WHERE matricula = ?", (matricula,)
        ).fetchone()
        conexion.close()

        if estudiante is not None and check_password_hash(
            estudiante["password_hash"], contrasena
        ):
            session["usuario"] = estudiante["matricula"]
            flash("¡Hola " + estudiante["nombre"] + "! Iniciaste sesión.", "exito")
            return redirect(url_for("practica5.index"))

        flash("Matrícula o contraseña incorrectos.", "error")

    return render_template("p5/login.html")


@practica5.route("/logout")
def logout():
    """Cierra la sesión."""
    respuesta = pedir_login()
    if respuesta is not None:
        return respuesta

    session.clear()
    flash("Cerraste sesión correctamente.", "info")
    return redirect(url_for("practica5.login"))


@practica5.route("/registro", methods=["GET", "POST"])
def registro():
    """Crea la cuenta de un estudiante."""
    if request.method == "POST":
        matricula = request.form.get("matricula")
        nombre = request.form.get("nombre")
        apellido = request.form.get("apellido")
        carrera = request.form.get("carrera")
        correo = request.form.get("correo")
        promedio = request.form.get("promedio")
        contrasena = request.form.get("contrasena")
        repetir = request.form.get("repetir_contrasena")

        try:
            promedio = float(promedio)
        except ValueError:
            flash("El promedio debe ser un número (ejemplo: 8.5).", "error")
            return redirect(url_for("practica5.registro"))

        problema = revisar_contrasena(contrasena, repetir)
        if problema != "":
            flash(problema, "error")
            return redirect(url_for("practica5.registro"))

        try:
            guardar_estudiante(matricula, nombre, apellido, carrera, correo,
                               promedio, contrasena)
        except sqlite3.IntegrityError:
            flash("Ya existe un estudiante con esa matrícula.", "error")
            return redirect(url_for("practica5.registro"))

        flash("¡Cuenta creada! Ya puedes iniciar sesión.", "exito")
        return redirect(url_for("practica5.login"))

    return render_template("p5/estudiantes/registro.html")


# ------------------------------------------------------------------
# CRUD DE ESTUDIANTES
# ------------------------------------------------------------------

@practica5.route("/estudiantes")
def listar_estudiantes():
    """CONSULTAR: todos los estudiantes ordenados por apellido."""
    respuesta = pedir_login()
    if respuesta is not None:
        return respuesta

    conexion = conectar_base_datos()
    estudiantes = conexion.execute("""
        SELECT id, matricula, nombre, apellido, carrera, correo, promedio
        FROM estudiantes ORDER BY apellido
    """).fetchall()
    conexion.close()

    return render_template("p5/estudiantes/listar.html",
                           estudiantes=estudiantes)


@practica5.route("/estudiantes/crear", methods=["GET", "POST"])
def crear_estudiante():
    """INSERTAR: da de alta un estudiante."""
    respuesta = pedir_login()
    if respuesta is not None:
        return respuesta

    if request.method == "POST":
        matricula = request.form.get("matricula")
        nombre = request.form.get("nombre")
        apellido = request.form.get("apellido")
        carrera = request.form.get("carrera")
        correo = request.form.get("correo")
        promedio = request.form.get("promedio")
        contrasena = request.form.get("contrasena")

        try:
            promedio = float(promedio)
        except ValueError:
            flash("El promedio debe ser un número (ejemplo: 8.5).", "error")
            return redirect(url_for("practica5.crear_estudiante"))

        if contrasena == "":
            flash("La contraseña no puede quedar vacía.", "error")
            return redirect(url_for("practica5.crear_estudiante"))

        try:
            guardar_estudiante(matricula, nombre, apellido, carrera, correo,
                               promedio, contrasena)
        except sqlite3.IntegrityError:
            flash("Ya existe un estudiante con esa matrícula.", "error")
            return redirect(url_for("practica5.crear_estudiante"))

        flash("Estudiante agregado correctamente.", "exito")
        return redirect(url_for("practica5.listar_estudiantes"))

    return render_template("p5/estudiantes/crear.html")


@practica5.route("/estudiantes/<int:id>/editar", methods=["GET", "POST"])
def editar_estudiante(id):
    """MODIFICAR: cambia los datos de un estudiante."""
    respuesta = pedir_login()
    if respuesta is not None:
        return respuesta

    conexion = conectar_base_datos()
    estudiante = conexion.execute("""
        SELECT id, matricula, nombre, apellido, carrera, correo, promedio
        FROM estudiantes WHERE id = ?
    """, (id,)).fetchone()
    conexion.close()

    if estudiante is None:
        flash("El estudiante que buscas no existe.", "error")
        return redirect(url_for("practica5.listar_estudiantes"))

    if request.method == "POST":
        matricula = request.form.get("matricula")
        nombre = request.form.get("nombre")
        apellido = request.form.get("apellido")
        carrera = request.form.get("carrera")
        correo = request.form.get("correo")
        promedio = request.form.get("promedio")

        try:
            promedio = float(promedio)
        except ValueError:
            flash("El promedio debe ser un número (ejemplo: 8.5).", "error")
            return redirect(url_for("practica5.editar_estudiante", id=id))

        conexion = conectar_base_datos()
        try:
            conexion.execute("""
                UPDATE estudiantes SET
                    matricula = ?, nombre = ?, apellido = ?,
                    carrera = ?, correo = ?, promedio = ?
                WHERE id = ?
            """, (matricula, nombre, apellido, carrera, correo, promedio, id))
            conexion.commit()
            mensaje = "Estudiante modificado correctamente."
            categoria = "exito"
        except sqlite3.IntegrityError:
            mensaje = "Ya existe un estudiante con esa matrícula."
            categoria = "error"
        conexion.close()

        flash(mensaje, categoria)
        return redirect(url_for("practica5.listar_estudiantes"))

    return render_template("p5/estudiantes/editar.html",
                           estudiante=estudiante)


@practica5.route("/estudiantes/<int:id>/eliminar", methods=["POST"])
def eliminar_estudiante(id):
    """ELIMINAR: borra un estudiante de la base de datos."""
    respuesta = pedir_login()
    if respuesta is not None:
        return respuesta

    conexion = conectar_base_datos()
    conexion.execute("DELETE FROM estudiantes WHERE id = ?", (id,))
    conexion.commit()
    conexion.close()

    flash("Estudiante eliminado correctamente.", "info")
    return redirect(url_for("practica5.listar_estudiantes"))


crear_tabla()