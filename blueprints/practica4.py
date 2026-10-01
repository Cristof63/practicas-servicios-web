"""Práctica 4: sitio personal con mapas.

La parte visual es HTML + JavaScript (Google Maps), pero se sirve desde
Flask para que viva dentro de la misma aplicación que las demás prácticas.
"""

from flask import Blueprint, render_template

practica4 = Blueprint("practica4", __name__, url_prefix="/p4")


@practica4.route("/")
def index():
    """Muestra la página personal con los dos mapas."""
    return render_template("p4/index.html")