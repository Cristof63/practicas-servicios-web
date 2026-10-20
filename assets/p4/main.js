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
  document.getElementById("foto").alt = "Foto de " + datos.nombre;

  document.title = datos.nombre + " · " + datos.ocupacion;

  const iframe = document.getElementById("mapa-iframe");
  iframe.src =
    "https://maps.google.com/maps?q=" + ubicacion.lat + "," + ubicacion.lng +
    "&z=" + ubicacion.zoom + "&output=embed";
  iframe.title = "Mapa de " + ubicacion.etiqueta;

  const coordenadas = [ubicacion.lat, ubicacion.lng];

  const mapa = L.map("mapa-leaflet").setView(coordenadas, ubicacion.zoom || 15);

  L.tileLayer("https://tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
    attribution: "© OpenStreetMap contributors",
  }).addTo(mapa);

  L.marker(coordenadas)
    .addTo(mapa)
    .bindPopup(ubicacion.etiqueta)
    .openPopup();
}

inicializar();
