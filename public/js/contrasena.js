/**
 * Botón "ojito" para mostrar u ocultar contraseñas.
 * Se aplica solo a TODOS los <input type="password"> de la página.
 */
(() => {
    const iconoVer =
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
        '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';

    const iconoOcultar =
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
        '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>' +
        '<path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>' +
        '<path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>';

    document.querySelectorAll('input[type="password"]').forEach((input) => {
        if (input.closest('.campo-clave')) return; // ya tiene ojito

        // Envolver el input para poder poner el botón encima
        const envoltura = document.createElement('div');
        envoltura.className = 'campo-clave';
        input.parentNode.insertBefore(envoltura, input);
        envoltura.appendChild(input);

        const boton = document.createElement('button');
        boton.type = 'button';
        boton.className = 'ver-clave';
        boton.innerHTML = iconoVer;
        boton.setAttribute('aria-label', 'Mostrar contraseña');
        boton.setAttribute('aria-pressed', 'false');
        envoltura.appendChild(boton);

        const ocultar = () => {
            input.type = 'password';
            boton.innerHTML = iconoVer;
            boton.setAttribute('aria-label', 'Mostrar contraseña');
            boton.setAttribute('aria-pressed', 'false');
        };

        boton.addEventListener('click', () => {
            if (input.type === 'password') {
                input.type = 'text';
                boton.innerHTML = iconoOcultar;
                boton.setAttribute('aria-label', 'Ocultar contraseña');
                boton.setAttribute('aria-pressed', 'true');
            } else {
                ocultar();
            }
            input.focus();
        });

        // Al enviar el formulario se vuelve a ocultar,
        // para que el navegador no la guarde como texto visible
        input.form?.addEventListener('submit', ocultar);
    });
})();