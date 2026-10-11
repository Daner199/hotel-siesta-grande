/* Cobro con QR: cuenta regresiva y estado del pago.
   Pregunta a Laravel (data-estado-url) cada 2 segundos. Cuando Banco Siesta avisa por webhook,
   el estado pasa a PAGADO: se muestra "Pago recibido" y se abre el recibo. */
(() => {
    const caja = document.querySelector('[data-cobro-qr]');
    if (!caja) return;

    const cuenta = caja.querySelector('[data-cuenta-regresiva]');
    const finales = ['PAGADO', 'RECHAZADO', 'VENCIDO', 'ANULADO'];
    let estado = caja.dataset.estado;
    let segundos = Number(caja.dataset.segundos) || 0;
    let consulta;
    let reloj;

    const mostrarEstado = (nuevo) => {
        estado = nuevo;
        caja.querySelectorAll('[data-panel-estado]').forEach((p) => { p.hidden = p.dataset.panelEstado !== nuevo; });
        const otro = caja.querySelector('[data-generar-otro]');
        if (otro) otro.hidden = nuevo === 'PENDIENTE' || nuevo === 'PAGADO';
        caja.querySelector('[data-anular]')?.toggleAttribute('hidden', nuevo !== 'PENDIENTE');
        caja.querySelector('[data-qr-imagen]')?.classList.toggle('qr-inactivo', nuevo !== 'PENDIENTE');
        if (finales.includes(nuevo)) {
            clearInterval(consulta);
            clearInterval(reloj);
            if (cuenta) cuenta.textContent = '';
        }
    };

    // "(quedan 14:32)"
    const pintarReloj = () => {
        if (!cuenta || estado !== 'PENDIENTE') return;
        const m = Math.floor(segundos / 60);
        const s = String(segundos % 60).padStart(2, '0');
        cuenta.textContent = `(quedan ${m}:${s})`;
        if (segundos <= 0) preguntar();
        segundos = Math.max(0, segundos - 1);
    };

    const preguntar = async () => {
        try {
            const r = await fetch(caja.dataset.estadoUrl, { headers: { Accept: 'application/json' } });
            if (!r.ok) return;
            const datos = await r.json();
            segundos = datos.segundos;
            if (datos.estado === estado) return;

            if (datos.estado === 'PAGADO') {
                const t = caja.querySelector('[data-transaccion]');
                if (t && datos.transaccion) t.textContent = datos.transaccion;
                mostrarEstado('PAGADO');
                if (datos.recibo) setTimeout(() => { window.location.href = datos.recibo; }, 1800);
            } else {
                mostrarEstado(datos.estado);
            }
        } catch (e) {
            // Sin conexión un momento: se vuelve a intentar en la siguiente vuelta
        }
    };

    mostrarEstado(estado);
    if (estado === 'PENDIENTE') {
        pintarReloj();
        reloj = setInterval(pintarReloj, 1000);
        consulta = setInterval(preguntar, 2000);
    }
})();
