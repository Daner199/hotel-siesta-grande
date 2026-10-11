/* Pantalla de pago: atajos de monto y paneles de cada método.
   <form data-pago>
     · [data-monto="716,25"] → llena el campo monto
     · input[name=metodo] → muestra su [data-panel="QR|TARJETA|EFECTIVO"]; los demás se ocultan
       y se deshabilitan (así no se validan ni se envían los datos de tarjeta)  */
(() => {
    const form = document.querySelector('form[data-pago]');
    if (!form) return;

    const monto = form.querySelector('[name="monto"]');
    const textoBoton = form.querySelector('[data-texto-pagar]');
    const verbo = textoBoton?.textContent.trim() ?? 'Pagar';

    // El botón dice cuánto se va a pagar: "Pagar Bs 716,25"
    const actualizarBoton = () => {
        if (!textoBoton) return;
        const v = monto.value.trim();
        textoBoton.textContent = v ? `${verbo} Bs ${v}` : verbo;
    };

    form.querySelectorAll('[data-monto]').forEach((boton) => {
        boton.addEventListener('click', () => {
            monto.value = boton.dataset.monto;
            monto.dispatchEvent(new Event('input', { bubbles: true }));
            monto.focus();
        });
    });
    monto.addEventListener('input', actualizarBoton);

    const mostrarPanel = () => {
        const elegido = form.querySelector('input[name="metodo"]:checked')?.value;
        form.querySelectorAll('[data-panel]').forEach((panel) => {
            const visible = panel.dataset.panel === elegido;
            panel.hidden = !visible;
            if (panel.tagName === 'FIELDSET') panel.disabled = !visible;
        });
    };

    form.querySelectorAll('input[name="metodo"]').forEach((r) => r.addEventListener('change', mostrarPanel));

    mostrarPanel();
    actualizarBoton();
})();
