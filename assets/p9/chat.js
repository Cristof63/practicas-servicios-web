// Práctica 9 · Chat entre usuarios.
// Pide los mensajes nuevos cada 3 segundos y envía sin recargar la página.
// Los textos se insertan con textContent para que un mensaje con HTML no se ejecute.

const chat = document.getElementById('chat');
const lista = document.getElementById('chat-mensajes');
const formulario = document.getElementById('chat-form');
const texto = document.getElementById('chat-texto');
const boton = document.getElementById('chat-enviar');
const estado = document.getElementById('chat-estado');
const estadoTexto = document.getElementById('chat-estado-texto');

if (chat && lista && formulario && texto && boton && estado && estadoTexto) {
    let ultimo = Number(chat.dataset.ultimo) || 0;
    let ocupado = false;
    let vivo = true;

    function crearMensaje(datos) {
        const caja = document.createElement('div');
        caja.className = 'chat-mensaje' + (datos.es_mio ? ' mio' : '');
        caja.dataset.id = datos.id;

        const cabeza = document.createElement('div');
        cabeza.className = 'chat-cabeza';

        const autor = document.createElement('strong');
        autor.textContent = datos.autor_nombre || 'Usuario';

        const hora = document.createElement('time');
        hora.textContent = datos.hora || '';

        cabeza.append(autor, hora);

        const cuerpo = document.createElement('p');
        cuerpo.textContent = datos.mensaje || '';

        caja.append(cabeza, cuerpo);
        return caja;
    }

    function alFinal() {
        return lista.scrollHeight - lista.scrollTop - lista.clientHeight < 80;
    }

    function bajar(forzar = false) {
        if (forzar || alFinal()) {
            lista.scrollTop = lista.scrollHeight;
        }
    }

    function agregar(datos) {
        if (!datos || lista.querySelector(`[data-id="${datos.id}"]`)) return;

        const vacio = document.getElementById('chat-vacio');
        if (vacio) vacio.remove();

        lista.appendChild(crearMensaje(datos));
    }

    function avisar(mensaje, esError = false) {
        estadoTexto.textContent = mensaje;
        estado.classList.toggle('error', esError);
    }

    async function cargar() {
        if (ocupado || !vivo) return;
        ocupado = true;

        try {
            const respuesta = await fetch(`${chat.dataset.mensajes}?desde=${ultimo}`, {
                cache: 'no-store',
                headers: { Accept: 'application/json' },
            });

            if (respuesta.status === 401) {
                vivo = false;
                avisar('Tu sesión expiró. Recarga la página para volver a entrar.', true);
                return;
            }

            if (!respuesta.ok) {
                throw new Error(`HTTP ${respuesta.status}`);
            }

            const datos = await respuesta.json();
            const estabaAlFinal = alFinal();

            (datos.mensajes || []).forEach(agregar);
            ultimo = Number(datos.ultimo) || ultimo;

            if (estabaAlFinal && datos.mensajes && datos.mensajes.length > 0) {
                bajar(true);
            }

            avisar(`Actualizado ${new Date().toLocaleTimeString()}`);
        } catch (error) {
            avisar(`No se pudieron cargar los mensajes (${error.message}).`, true);
        } finally {
            ocupado = false;
        }
    }

    formulario.addEventListener('submit', async (evento) => {
        evento.preventDefault();

        const mensaje = texto.value.trim();
        if (mensaje === '') {
            texto.focus();
            return;
        }

        boton.disabled = true;
        const datos = new FormData(formulario);
        datos.set('mensaje', mensaje);

        try {
            const respuesta = await fetch(chat.dataset.enviar, {
                method: 'POST',
                body: datos,
                headers: { Accept: 'application/json' },
            });

            const resultado = await respuesta.json().catch(() => ({}));

            if (!respuesta.ok) {
                throw new Error(resultado.error || `HTTP ${respuesta.status}`);
            }

            if (resultado.mensaje) {
                agregar(resultado.mensaje);
                ultimo = Number(resultado.ultimo) || ultimo;
                bajar(true);
            }

            texto.value = '';
            texto.focus();
            avisar('Mensaje enviado.');
        } catch (error) {
            avisar(`No se pudo enviar: ${error.message}`, true);
        } finally {
            boton.disabled = false;
        }
    });

    // Enter envía el mensaje; Shift+Enter hace un salto de línea.
    texto.addEventListener('keydown', (evento) => {
        if (evento.key === 'Enter' && !evento.shiftKey) {
            evento.preventDefault();
            formulario.requestSubmit();
        }
    });

    document.addEventListener('visibilitychange', () => {
        vivo = !document.hidden;
        if (vivo) cargar();
    });

    bajar(true);
    cargar();
    setInterval(cargar, 3000);
}
