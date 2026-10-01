"""Práctica 6: encuesta de satisfacción sobre la UPVM.

Guarda cada respuesta en su propia base de datos y al terminar muestra el
regalo de agradecimiento. Va con prefijo /p6 para convivir con las demás.
"""

import sqlite3
from pathlib import Path

from flask import Blueprint, redirect, render_template, request, url_for

BASE_DIR = Path(__file__).resolve().parent.parent
RUTA_BASE_DATOS = BASE_DIR / "db" / "practica6.db"

practica6 = Blueprint("practica6", __name__, url_prefix="/p6")


def conectar_base_datos():
    """Abre la base de datos de la encuesta."""
    return sqlite3.connect(RUTA_BASE_DATOS)


def crear_tabla():
    """Crea la tabla respuestas si todavía no existe."""
    conexion = conectar_base_datos()
    conexion.execute("""
        CREATE TABLE IF NOT EXISTS respuestas (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nombre TEXT NOT NULL,
            carrera TEXT NOT NULL,
            factor TEXT NOT NULL,
            instalaciones TEXT NOT NULL,
            mejora TEXT NOT NULL
        )
    """)
    conexion.commit()
    conexion.close()


def total_respuestas():
    """Cuenta cuántas respuestas hay guardadas."""
    conexion = conectar_base_datos()
    total = conexion.execute("SELECT COUNT(*) FROM respuestas").fetchone()[0]
    conexion.close()
    return total


@practica6.route("/")
def index():
    """Portada de la encuesta."""
    return render_template("p6/index.html")


@practica6.route("/encuesta", methods=["GET", "POST"])
def encuesta():
    """Muestra el formulario y guarda la respuesta."""
    if request.method == "POST":
        conexion = conectar_base_datos()
        conexion.execute("""
            INSERT INTO respuestas
                (nombre, carrera, factor, instalaciones, mejora)
            VALUES (?, ?, ?, ?, ?)
        """, (
            request.form["nombre"],
            request.form["carrera"],
            request.form["factor"],
            request.form["instalaciones"],
            request.form["mejora"],
        ))
        conexion.commit()
        conexion.close()

        return redirect(url_for("practica6.regalo"))

    return render_template("p6/encuesta.html", total=total_respuestas())


@practica6.route("/regalo")
def regalo():
    """Muestra el agradecimiento final."""
    return render_template("p6/regalo.html")


crear_tabla()