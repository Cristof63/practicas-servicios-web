function esperarInnerMap(elemento) {
  return new Promise(function (resolve) {
    if (elemento.innerMap) return resolve(elemento.innerMap);

    const inicio = Date.now();
    const timer = setInterval(function () {
      if (elemento.innerMap) {
        clearInterval(timer);
        resolve(elemento.innerMap);
      } else if (Date.now() - inicio > 12000) {
        clearInterval(timer);
        resolve(null);
      }
    }, 100);
  });
}

export async function crearMapaInteractivo(apiKey, solutionChannel, ubicacion) {
  if (!document.querySelector("gmpx-api-loader")) {
    const loader = document.createElement("gmpx-api-loader");
    loader.setAttribute("key", apiKey);
    loader.setAttribute("solution-channel", solutionChannel);
    document.body.appendChild(loader);
  }

  await customElements.whenDefined("gmp-map");

  const gmpMap = document.querySelector("gmp-map");
  const marker = document.querySelector("gmp-advanced-marker");
  const placePicker = document.querySelector("gmpx-place-picker");
  const map = await esperarInnerMap(gmpMap);

  if (!map) {
    throw new Error(
      "Google Maps no inicializó. Revisa tu API Key y sus restricciones de dominio/API."
    );
  }

  const infowindow = new google.maps.InfoWindow();

  map.setOptions({ mapTypeControl: false });

  gmpMap.center = { lat: ubicacion.lat, lng: ubicacion.lng };
  gmpMap.zoom = ubicacion.zoom;

  marker.position = { lat: ubicacion.lat, lng: ubicacion.lng };
  infowindow.setContent(
    "<strong>" + ubicacion.etiqueta + "</strong><br>" + ubicacion.direccion
  );
  infowindow.open(map, marker);

  placePicker.addEventListener("gmpx-placechange", function () {
    const place = placePicker.value;

    if (!place.location) {
      window.alert("No details available for input: '" + place.name + "'");
      infowindow.close();
      marker.position = null;
      return;
    }

    if (place.viewport) {
      map.fitBounds(place.viewport);
    } else {
      gmpMap.center = place.location;
      gmpMap.zoom = 17;
    }

    marker.position = place.location;
    infowindow.setContent(
      "<strong>" + place.displayName + "</strong><br>" +
      "<span>" + place.formattedAddress + "</span>"
    );
    infowindow.open(map, marker);
  });
}