"""Sitio único con todas las prácticas (Práctica 3 a Práctica 6).

Cada práctica es un Blueprint de Flask registrado con su propio prefijo
de URL, así todas viven en la misma aplicación y se navigated desde una sola
barra de menú.

    /                    -> portada con el menú de prácticas
    /p3/...              -> Práctica 3  (CRUD de productos)
    /p4/...              -> Práctica 4  (sitio personal con Google Maps)
    /p5/...              -> Práctica 5  (login, sesiones y CRUD de estudiantes)
    /p6/...              -> Práctica 6  (encuesta de satisfacción)
"""

from pathlib import Path

from flask import Flask, render_template

from blueprints.practica3 import practica3
from blueprints.practica4 import practica4
from blueprints.practica5 import practica5
from blueprints.practica6 import practica6

import os

BASE_DIR = Path(__file__).parent

app = Flask(__name__)
app.config["SECRET_KEY"] = "clave_secreta_web_general"

app.register_blueprint(practica3)
app.register_blueprint(practica4)
app.register_blueprint(practica5)
app.register_blueprint(practica6)


@app.route("/")
def inicio():
    """Portada del sitio: tarjeta por cada práctica."""
    return render_template("inicio.html")


if __name__ == "__main__":
    port = int(os.environ.get("PORT", 5000))
    app.run(host="0.0.0.0", port=port)
