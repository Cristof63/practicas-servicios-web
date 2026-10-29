// Práctica 8 · Rastreador de analítica.
// Se incluye en todas las páginas desde parciales/pie.php.

(() => {
    const endpoint = document.body.dataset.eventos;
    if (!endpoint) return;

    const COOKIE_CONSENTIMIENTO = 'awos_analitica';
    const COOKIE_VISITANTE = 'awos_visitante_id';
    const CLAVE_SESION = 'awos_sesion_id';

    function leerCookie(nombre) {
        const coincide = document.cookie.match(
            new RegExp('(?:^|; )' + nombre + '=([^;]*)')
        );
        return coincide ? coincide[1] : null;
    }

    function guardarCookie(nombre, valor, dias) {
        const maxAge = dias <= 0 ? 0 : dias * 86400;
        document.cookie = `${nombre}=${valor}; max-age=${maxAge}; path=/; SameSite=Lax`;
    }

    function borrarCookie(nombre) {
        guardarCookie(nombre, '', 0);
    }

    // crypto.randomUUID solo existe en HTTPS o localhost; si no, se genera a mano.
    function nuevoId() {
        if (window.crypto && crypto.randomUUID) {
            return crypto.randomUUID();
        }

        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
            const r = (Math.random() * 16) | 0;
            const v = c === 'x' ? r : (r & 0x3) | 0x8;
            return v.toString(16);
        });
    }

    // ---------- 1. Consentimiento ----------

    function mostrarAviso() {
        const aviso = document.createElement('div');
        aviso.className = 'aviso-cookies';
        aviso.setAttribute('role', 'dialog');
        aviso.setAttribute('aria-label', 'Aviso de cookies');
        aviso.innerHTML = `
            <p>Usamos cookies para medir cómo se usa este sitio (páginas, clics y
               tiempo). No registramos lo que escribes en los formularios.</p>
            <button type="button" data-respuesta="si">Aceptar</button>
            <button type="button" data-respuesta="no" class="secundario">Rechazar</button>`;

        aviso.addEventListener('click', (evento) => {
            const respuesta = evento.target.dataset.respuesta;
            if (!respuesta) return;

            guardarCookie(COOKIE_CONSENTIMIENTO, respuesta, 180);
            aviso.remove();

            if (respuesta === 'si') {
                iniciar();
            }
        });

        document.body.appendChild(aviso);
    }

    // Enlaces "Privacidad": borran la decisión para volver a preguntar.
    document.querySelectorAll('[data-cambiar-privacidad]').forEach((enlace) => {
        enlace.addEventListener('click', (evento) => {
            evento.preventDefault();
            borrarCookie(COOKIE_CONSENTIMIENTO);
            borrarCookie(COOKIE_VISITANTE);
            sessionStorage.removeItem(CLAVE_SESION);
            location.reload();
        });
    });

    const consentimiento = leerCookie(COOKIE_CONSENTIMIENTO);
    if (consentimiento === 'si') {
        iniciar();
    } else if (consentimiento === null) {
        mostrarAviso();
    }
    // Si la respuesta fue 'no', no se rastrea nada.

    // ---------- 2. Identificar y observar ----------

    function iniciar() {
        // Visitante: una cookie de un año identifica al mismo navegador.
        let visitanteId = leerCookie(COOKIE_VISITANTE);
        if (!visitanteId) {
            visitanteId = nuevoId();
        }
        guardarCookie(COOKIE_VISITANTE, visitanteId, 365);

        // Sesión de navegación: dura mientras la pestaña siga abierta.
        let sesionId = sessionStorage.getItem(CLAVE_SESION);
        if (!sesionId) {
            sesionId = nuevoId();
            sessionStorage.setItem(CLAVE_SESION, sesionId);
        }

        function enviar(tipo, extra = {}) {
            const evento = {
                tipo,
                visitante_id: visitanteId,
                sesion_id: sesionId,
                pagina: location.pathname,
                ...extra,
            };

            const cuerpo = JSON.stringify(evento);

            // sendBeacon garantiza el envío aunque el usuario cambie de página.
            if (navigator.sendBeacon) {
                navigator.sendBeacon(endpoint, cuerpo);
                return;
            }

            fetch(endpoint, {
                method: 'POST',
                body: cuerpo,
                keepalive: true,
                headers: { 'Content-Type': 'text/plain;charset=UTF-8' },
            }).catch(() => {});
        }

        // a) Vista de página.
        enviar('vista');

        // b) Clics en enlaces, botones y elementos con data-track.
        document.addEventListener('click', (evento) => {
            const elemento = evento.target.closest('a, button, [data-track]');
            if (!elemento) return;
            if (elemento.closest('.aviso-cookies')) return;
            if (elemento.matches('[data-cambiar-privacidad]')) return;

            const nombre = elemento.dataset.track
                || elemento.textContent.trim().slice(0, 60)
                || elemento.tagName;

            enviar('clic', { elemento: nombre });
        });

        // c) Tiempo visible: el reloj se detiene al cambiar de pestaña.
        let inicio = Date.now();

        function enviarTiempo() {
            const segundos = Math.round((Date.now() - inicio) / 1000);
            if (segundos > 0) {
                enviar('tiempo', { segundos });
                inicio = Date.now();
            }
        }

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'hidden') {
                enviarTiempo();
            } else {
                inicio = Date.now();
            }
        });

        window.addEventListener('pagehide', enviarTiempo);
    }
})();
