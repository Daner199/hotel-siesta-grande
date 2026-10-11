/* Formulario de pago.
   - Atajos de monto (adelanto / saldo / otro) y texto del botón según la forma de pago.
   - Paneles por método: el que no está elegido se oculta y se deshabilita (no se valida ni se envía).
   - Tarjeta: vista previa que se llena al escribir, marca Visa/Mastercard, se da vuelta en el CVV.
     El navegador solo da formato al número y a la fecha; Laravel vuelve a validar todo (Luhn incluido). */
(() => {
    const form = document.querySelector('form[data-pago]');
    if (!form) return;

    const monto = form.querySelector('[name="monto"]');
    const boton = form.querySelector('[data-boton-pagar]');
    const espejos = form.querySelectorAll('[data-monto-espejo]');
    const metodoElegido = () => form.querySelector('input[name="metodo"]:checked')?.value;

    // ---------- Monto ----------
    const pintarMonto = () => {
        const v = monto.value.trim() || '0,00';
        espejos.forEach((e) => { e.textContent = v; });
        const textos = {
            QR: `Generar QR de Bs ${v}`,
            TARJETA: `Pagar Bs ${v}`,
            TRANSFERENCIA: 'Enviar comprobante',
            EFECTIVO: `Registrar pago de Bs ${v}`,
        };
        boton.textContent = textos[metodoElegido()] ?? 'Continuar';
    };

    form.querySelectorAll('[data-opcion-monto]').forEach((r) => r.addEventListener('change', () => {
        monto.value = r.value;
        monto.dispatchEvent(new Event('input', { bubbles: true }));
    }));
    form.querySelector('[data-opcion-otro]')?.addEventListener('change', () => { monto.focus(); monto.select(); });
    monto.addEventListener('input', () => {
        // Si escribe un monto distinto, queda marcado "Otro monto"
        const igual = [...form.querySelectorAll('[data-opcion-monto]')].find((r) => r.value === monto.value.trim());
        (igual ?? form.querySelector('[data-opcion-otro]')).checked = true;
        pintarMonto();
    });

    // ---------- Paneles por método ----------
    const mostrarPanel = () => {
        const elegido = metodoElegido();
        form.querySelectorAll('[data-panel]').forEach((panel) => {
            const visible = panel.dataset.panel === elegido;
            panel.hidden = !visible;
            if (panel.tagName === 'FIELDSET') panel.disabled = !visible;
        });
        pintarMonto();
    };
    form.querySelectorAll('input[name="metodo"]').forEach((r) => r.addEventListener('change', mostrarPanel));

    // ---------- Tarjeta: vista previa ----------
    const vista = form.querySelector('[data-tarjeta-vista]');
    if (vista) {
        const numero = form.querySelector('[name="tarjeta_numero"]');
        const vence = form.querySelector('[name="tarjeta_vence"]');
        const cvv = form.querySelector('[name="tarjeta_cvv"]');
        const marcaEl = vista.querySelector('[data-vista-marca]');

        // Visa: empieza con 4 · Mastercard: 51–55 o 2221–2720
        const marca = (d) => {
            if (/^4/.test(d)) return 'visa';
            if (/^(5[1-5]|222[1-9]|22[3-9]\d|2[3-6]\d{2}|27[01]\d|2720)/.test(d)) return 'mastercard';
            return '';
        };
        const logos = {
            visa: '<span class="logo-visa">VISA</span>',
            mastercard: '<svg class="logo-mc" viewBox="0 0 40 24"><circle cx="15" cy="12" r="10" fill="#EB001B"/><circle cx="25" cy="12" r="10" fill="#F79E1B" fill-opacity=".9"/></svg>',
        };

        const pintarTarjeta = () => {
            const d = numero.value.replace(/\D/g, '');
            const relleno = (d + '•'.repeat(Math.max(0, 16 - d.length))).slice(0, Math.max(16, d.length));
            vista.querySelector('[data-vista-numero]').textContent = relleno.replace(/(.{4})(?=.)/g, '$1 ');
            vista.querySelector('[data-vista-vence]').textContent = vence.value || 'MM/AA';
            vista.querySelector('[data-vista-cvv]').textContent = cvv.value ? '•'.repeat(cvv.value.length) : '•••';
            const m = marca(d);
            vista.dataset.marca = m;
            marcaEl.innerHTML = logos[m] ?? '';
        };

        [numero, vence, cvv].forEach((c) => c.addEventListener('input', pintarTarjeta));
        cvv.addEventListener('focus', () => vista.classList.add('girada'));
        cvv.addEventListener('blur', () => vista.classList.remove('girada'));
        pintarTarjeta();
    }

    // ---------- Transferencia: copiar el número de cuenta ----------
    form.querySelectorAll('[data-copiar]').forEach((b) => b.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(b.dataset.copiar);
            b.textContent = 'Copiado';
            setTimeout(() => { b.textContent = 'Copiar'; }, 1500);
        } catch (e) { /* sin permiso: el número se puede seleccionar a mano */ }
    }));

    mostrarPanel();
})();
