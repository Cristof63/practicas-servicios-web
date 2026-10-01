"""Práctica 3: CRUD de productos (INSERT, SELECT, UPDATE, DELETE).

Cada ruta corresponde a una consulta SQL distinta, que es justo lo que pide
la práctica: ver cómo se hacen las cuatro operaciones de un CRUD.
"""

import sqlite3
from pathlib import Path

from flask import Blueprint, flash, redirect, render_template, request, url_for

BASE_DIR = Path(__file__).resolve().parent.parent
RUTA_BASE_DATOS = BASE_DIR / "db" / "practica3.db"

practica3 = Blueprint("practica3", __name__, url_prefix="/p3")


def conectar_base_datos():
    """Abre la base de datos de la práctica con acceso por nombre de columna."""
    conexion = sqlite3.connect(RUTA_BASE_DATOS)
    conexion.row_factory = sqlite3.Row
    return conexion


def crear_tabla():
    """Crea la tabla productos si todavía no existe."""
    conexion = conectar_base_datos()

    conexion.execute("""
        CREATE TABLE IF NOT EXISTS productos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            descripcion TEXT NOT NULL,
            precio REAL NOT NULL,
            existencia INTEGER NOT NULL
        )
    """)

    conexion.commit()
    conexion.close()


def leer_datos_del_formulario():
    """Saca los valores del formulario y convierte los números."""
    try:
        precio = float(request.form.get("precio"))
        existencia = int(request.form.get("existencia"))
    except (TypeError, ValueError):
        return None

    return (
        request.form.get("nombre"),
        request.form.get("descripcion"),
        precio,
        existencia,
    )


# ------------------------------------------------------------------
# CONSULTA (SELECT)
# ------------------------------------------------------------------

@practica3.route("/")
def index():
    """Muestra todos los productos guardados."""
    conexion = conectar_base_datos()
    productos = conexion.execute(
        "SELECT * FROM productos ORDER BY nombre"
    ).fetchall()
    conexion.close()

    return render_template("p3/index.html", productos=productos)


# ------------------------------------------------------------------
# INSERCIÓN (INSERT)
# ------------------------------------------------------------------

@practica3.route("/crear", methods=["GET", "POST"])
def crear():
    """Da de alta un producto nuevo."""
    if request.method == "POST":
        datos = leer_datos_del_formulario()

        if datos is None:
            flash("El precio y la existencia deben ser números.", "error")
            return redirect(url_for("practica3.crear"))

        conexion = conectar_base_datos()
        conexion.execute("""
            INSERT INTO productos (nombre, descripcion, precio, existencia)
            VALUES (?, ?, ?, ?)
        """, datos)
        conexion.commit()
        conexion.close()

        flash("Producto agregado correctamente.", "exito")
        return redirect(url_for("practica3.index"))

    return render_template("p3/crear.html")


# ------------------------------------------------------------------
# MODIFICACIÓN (UPDATE)
# ------------------------------------------------------------------

@practica3.route("/<int:id>/editar", methods=["GET", "POST"])
def editar(id):
    """Cambia los datos de un producto."""
    conexion = conectar_base_datos()
    producto = conexion.execute(
        "SELECT * FROM productos WHERE id = ?", (id,)
    ).fetchone()
    conexion.close()

    if producto is None:
        flash("El producto que buscas no existe.", "error")
        return redirect(url_for("practica3.index"))

    if request.method == "POST":
        datos = leer_datos_del_formulario()

        if datos is None:
            flash("El precio y la existencia deben ser números.", "error")
            return redirect(url_for("practica3.editar", id=id))

        conexion = conectar_base_datos()
        conexion.execute("""
            UPDATE productos SET
                nombre = ?, descripcion = ?, precio = ?, existencia = ?
            WHERE id = ?
        """, datos + (id,))
        conexion.commit()
        conexion.close()

        flash("Producto modificado correctamente.", "exito")
        return redirect(url_for("practica3.index"))

    return render_template("p3/editar.html", producto=producto)


# ------------------------------------------------------------------
# ELIMINACIÓN (DELETE)
# ------------------------------------------------------------------

@practica3.route("/<int:id>/eliminar", methods=["POST"])
def eliminar(id):
    """Borra un producto. Solo por POST para no borrar al navegar."""
    conexion = conectar_base_datos()
    conexion.execute("DELETE FROM productos WHERE id = ?", (id,))
    conexion.commit()
    conexion.close()

    flash("Producto eliminado correctamente.", "info")
    return redirect(url_for("practica3.index"))


crear_tabla()