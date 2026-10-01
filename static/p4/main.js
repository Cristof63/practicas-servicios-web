import { crearMapaInteractivo } from "./mapa.js";

async function inicializar() {
  const urlConfig = document.body.dataset.api;
  const respuesta = await fetch(urlConfig);
  const config = await respuesta.json();

  const datos = config.datosPersonales;
  const ubicacion = config.ubicacion;

  document.getElementById("nombre").textContent = datos.nombre;
  document.getElementById("ocupacion").textContent = datos.ocupacion;
  document.getElementById("correo").textContent = datos.correo;
  document.getElementById("correoEnlace").textContent = datos.correo;
  document.getElementById("correoEnlace").href = "mailto:" + datos.correo;
  document.getElementById("foto").src =
    "https://ui-avatars.com/api/?name=" + encodeURIComponent(datos.iniciales) +
    "&background=0d6efd&color=fff&size=256";
  document.getElementById("foto").alt = "Foto de " + datos.nombre;

  document.title = datos.nombre + " · " + datos.ocupacion;

  const iframe = document.getElementById("mapa-iframe");
  iframe.src =
    "https://maps.google.com/maps?q=" + ubicacion.lat + "," + ubicacion.lng +
    "&z=" + ubicacion.zoom + "&output=embed";
  iframe.title = "Mapa de " + ubicacion.etiqueta;

  const apiKey = String(config.apiKeyGoogle || "").trim();

  if (!apiKey || apiKey.indexOf("AQUI_") === 0) {
    document.getElementById("aviso-api").hidden = false;
    return;
  }

  try {
    await crearMapaInteractivo(
      apiKey,
      config.solutionChannel,
      ubicacion
    );
  } catch (error) {
    document.getElementById("aviso-api").hidden = false;
    document.getElementById("aviso-api").textContent = error.message;
  }
}

inicializar();