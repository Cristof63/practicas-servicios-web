// Práctica 8 · Consume /p8/metricas y dibuja el panel.

function duracion(segundos) {
    segundos = Number(segundos) || 0;
    const minutos = Math.floor(segundos / 60);
    const resto = segundos % 60;
    return minutos > 0 ? `${minutos} min ${resto} s` : `${resto} s`;
}

// Los datos vienen de lo que mandan los navegadores: se insertan con textContent
// (nunca con innerHTML) para que un nombre como <script> no se ejecute (XSS).
function celda(fila, texto, etiqueta = 'td') {
    const c = document.createElement(etiqueta);
    c.textContent = texto ?? '';
    fila.appendChild(c);
    return c;
}

function llenarTabla(id, encabezados, filas, convertir) {
    const tabla = document.getElementById(id);
    if (!tabla) return;

    tabla.replaceChildren();

    const encabezado = tabla.createTHead().insertRow();
    encabezados.forEach((texto) => celda(encabezado, texto, 'th'));

    const cuerpo = tabla.createTBody();

    if (filas.length === 0) {
        const vacia = celda(cuerpo.insertRow(), 'Sin datos todavía.');
        vacia.colSpan = encabezados.length;
        return;
    }

    filas.forEach((dato) => {
        const fila = cuerpo.insertRow();
        convertir(dato).forEach((valor) => celda(fila, valor));
    });
}

// Barra proporcional dentro de la celda para comparar de un vistazo.
function agregarBarras(id, columna, filas, campo) {
    const maximo = Math.max(...filas.map((f) => Number(f[campo])), 1);

    document.querySelectorAll(`#${id} tbody tr`).forEach((tr, indice) => {
        if (!filas[indice] || !tr.cells[columna]) return;

        const barra = document.createElement('span');
        barra.className = 'barra';
        barra.style.width = `${(Number(filas[indice][campo]) / maximo) * 100}%`;
        tr.cells[columna].appendChild(barra);
    });
}

function mostrarKpis(resumen) {
    const kpis = [
        ['Visitantes únicos', resumen.visitantes],
        ['Sesiones', resumen.sesiones],
        ['Usuarios identificados', resumen.usuarios],
        ['Páginas vistas', resumen.vistas],
        ['Clics', resumen.clics],
        ['Tiempo promedio por sesión', duracion(resumen.tiempo_promedio)],
    ];

    const contenedor = document.getElementById('kpis');
    if (!contenedor) return;

    contenedor.replaceChildren();

    kpis.forEach(([titulo, valor]) => {
        const div = document.createElement('div');
        div.className = 'kpi';
        celda(div, valor, 'strong');
        celda(div, titulo, 'span');
        contenedor.appendChild(div);
    });
}

const nombresTipo = {
    vista: '👁 vista',
    clic: '🖱 clic',
    tiempo: '⏱ tiempo',
};

async function cargar() {
    const panel = document.getElementById('panel-metricas');
    const estado = document.getElementById('estado');
    if (!panel || !estado) return;

    try {
        const respuesta = await fetch(panel.dataset.metricas, {
            cache: 'no-store',
            headers: { Accept: 'application/json' },
        });

        if (!respuesta.ok) {
            throw new Error(`HTTP ${respuesta.status}`);
        }

        const datos = await respuesta.json();

        mostrarKpis(datos.resumen);

        llenarTabla('tabla-recientes', ['Hora', 'Quién', 'Qué', 'Página', 'Detalle'], datos.recientes,
            (f) => [
                f.hora,
                f.nombre,
                nombresTipo[f.tipo] ?? f.tipo,
                f.pagina,
                f.tipo === 'clic' ? f.elemento : (f.tipo === 'tiempo' ? duracion(f.segundos) : ''),
            ]);

        llenarTabla('tabla-paginas', ['Página', 'Vistas', 'Clics', 'Tiempo total'], datos.paginas,
            (f) => [f.pagina, f.vistas, f.clics, duracion(f.segundos)]);
        agregarBarras('tabla-paginas', 1, datos.paginas, 'vistas');

        llenarTabla('tabla-clics', ['Elemento', 'Página', 'Clics'], datos.clics,
            (f) => [f.elemento, f.pagina, f.total]);
        agregarBarras('tabla-clics', 2, datos.clics, 'total');

        llenarTabla('tabla-usuarios',
            ['Nombre', 'Carrera', 'Sesiones', 'Vistas', 'Clics', 'Tiempo', 'Última actividad'],
            datos.usuarios,
            (f) => [f.nombre, f.carrera, f.sesiones, f.vistas, f.clics, duracion(f.segundos), f.ultima]);

        llenarTabla('tabla-carreras', ['Carrera', 'Usuarios', 'Clics', 'Tiempo total'], datos.carreras,
            (f) => [f.carrera, f.usuarios, f.clics, duracion(f.segundos)]);
        agregarBarras('tabla-carreras', 3, datos.carreras, 'segundos');

        estado.textContent = `Última actualización: ${new Date().toLocaleTimeString()}`;
    } catch (error) {
        estado.textContent = `No se pudieron cargar las métricas (${error.message}).`;
    }
}

cargar();
setInterval(cargar, 5000);
