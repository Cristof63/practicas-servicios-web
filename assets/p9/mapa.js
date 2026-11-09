// Práctica 9 · Mapa de sucursales con Leaflet y OpenStreetMap.
// Las coordenadas vienen del servidor y cambian de forma aleatoria por sesión.

const contenedorMapa = document.getElementById('mapa-sucursales');

if (contenedorMapa && typeof L !== 'undefined') {
    let sucursales = [];

    try {
        sucursales = JSON.parse(contenedorMapa.dataset.sucursales || '[]');
    } catch (error) {
        sucursales = [];
    }

    const centro = [19.66556, -99.09167];
    const mapa = L.map(contenedorMapa).setView(centro, 12);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '© OpenStreetMap contributors',
    }).addTo(mapa);

    const grupo = L.featureGroup();

    sucursales.forEach((sucursal) => {
        const popup = document.createElement('div');
        popup.className = 'popup-sucursal';

        const titulo = document.createElement('strong');
        titulo.textContent = sucursal.nombre;

        const direccion = document.createElement('p');
        direccion.textContent = sucursal.direccion;

        const horario = document.createElement('small');
        horario.textContent = `${sucursal.horario} · Tel. ${sucursal.telefono}`;

        popup.append(titulo, direccion, horario);

        const marcador = L.marker([sucursal.lat, sucursal.lng]);
        marcador.bindPopup(popup);
        marcador.addTo(grupo);
    });

    grupo.addTo(mapa);

    if (sucursales.length > 0) {
        mapa.fitBounds(grupo.getBounds().pad(0.25));
    }
}
