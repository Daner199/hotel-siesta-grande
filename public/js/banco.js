/* Banco Siesta (banco simulado): PIN → detalle → procesando → comprobante.
   Al confirmar, el servidor del banco avisa al hotel por webhook; la PC del hotel se actualiza sola. */
(() => {
    const app = document.querySelector('[data-banco]');
    if (!app || !app.dataset.pagarUrl) return;

    const $ = (sel) => app.querySelector(sel);
    const titulo = $('[data-titulo]');
    const atras = $('[data-atras]');
    const nombreBanco = titulo.textContent;

    const ir = (nombre) => {
        app.querySelectorAll('[data-pantalla]').forEach((p) => { p.hidden = p.dataset.pantalla !== nombre; });
        titulo.textContent = { detalle: 'Pagar con QR', exito: 'Comprobante', rechazo: 'Comprobante' }[nombre] ?? nombreBanco;
        atras.hidden = nombre !== 'detalle';
        window.scrollTo(0, 0);
    };

    // ---------- 1. PIN (cualquier PIN de 4 dígitos) ----------
    let pin = '';
    const puntos = [...app.querySelectorAll('[data-pin-puntos] span')];
    const pintarPin = () => {
        puntos.forEach((p, i) => p.classList.toggle('lleno', i < pin.length));
        $('[data-pin-puntos]').setAttribute('aria-label', `${pin.length} de 4 dígitos`);
    };
    app.querySelectorAll('[data-tecla]').forEach((b) => b.addEventListener('click', () => {
        if (pin.length >= 4) return;
        pin += b.dataset.tecla;
        pintarPin();
        if (pin.length === 4) setTimeout(() => ir('detalle'), 150);
    }));
    $('[data-borrar]').addEventListener('click', () => { pin = pin.slice(0, -1); pintarPin(); });
    atras.addEventListener('click', () => { pin = ''; pintarPin(); ir('pin'); });

    // ---------- 2. Cuenta regresiva del cobro ----------
    let segundos = Number(app.dataset.segundos) || 0;
    const vence = $('[data-vence]');
    const reloj = setInterval(() => {
        segundos = Math.max(0, segundos - 1);
        vence.textContent = `${Math.floor(segundos / 60)}:${String(segundos % 60).padStart(2, '0')} min`;
        if (segundos === 0) {
            clearInterval(reloj);
            $('[data-confirmar]').disabled = true;
            vence.textContent = 'Vencido';
        }
    }, 1000);

    // ---------- 3. Confirmar (o simular fondos insuficientes) ----------
    const pagar = async (resultado) => {
        ir('procesando');
        const espera = new Promise((r) => setTimeout(r, 1300)); // un banco real tarda un momento
        let datos;
        try {
            const r = await fetch(app.dataset.pagarUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ resultado }),
            });
            datos = await r.json();
        } catch (e) {
            datos = { ok: false, error: 'Sin conexión. Revisa que estés en la misma red WiFi.' };
        }
        await espera;
        clearInterval(reloj);

        if (datos.ok && datos.resultado === 'APROBADO') {
            $('[data-transaccion]').textContent = datos.transaccion;
            $('[data-fecha]').textContent = datos.fecha;
            $('[data-saldo-inicio]').textContent = app.dataset.saldo;
            ir('exito');
        } else {
            $('[data-motivo]').textContent = datos.ok
                ? 'Fondos insuficientes en la cuenta de débito.'
                : (datos.error ?? 'No se pudo completar el pago.');
            ir('rechazo');
        }
    };
    $('[data-confirmar]').addEventListener('click', () => pagar('aprobar'));
    $('[data-fondos]').addEventListener('click', () => pagar('fondos'));

    // ---------- 4. Comprobante ----------
    $('[data-compartir]').addEventListener('click', async () => {
        const lineas = [...app.querySelectorAll('[data-pantalla="exito"] .lista > div')]
            .map((d) => `${d.querySelector('dt').textContent}: ${d.querySelector('dd').textContent}`);
        const texto = [`${nombreBanco} - Pago exitoso`, $('.resultado-monto').textContent, ...lineas].join('\n');
        if (navigator.share) {
            try { await navigator.share({ title: 'Comprobante', text: texto }); } catch (e) { /* cancelado */ }
        } else {
            await navigator.clipboard?.writeText(texto);
            const aviso = Object.assign(document.createElement('div'), { className: 'toast', textContent: 'Comprobante copiado' });
            document.body.append(aviso);
            setTimeout(() => aviso.remove(), 1800);
        }
    });
    app.querySelectorAll('[data-inicio]').forEach((b) => b.addEventListener('click', () => ir('inicio')));
})();
