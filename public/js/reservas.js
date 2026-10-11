/* Nueva reserva: resumen en vivo.
   Cada vez que cambian las fechas o las habitaciones, pide a Laravel (data-cotizar) las
   habitaciones libres y el total. El navegador NO hace cuentas: solo muestra lo que llega.
   <form data-reserva data-cotizar="URL">
     · cliente/agencia: input name="tipos[ID]" dentro de [data-tipo="ID"] (con [data-menos]/[data-mas])
     · recepción:       input name="habitaciones[]" dentro de [data-habitacion="ID"]  */
(() => {
    const form = document.querySelector('form[data-reserva]');
    if (!form) return;

    const llegada = form.querySelector('[name="fecha_entrada"]');
    const salida = form.querySelector('[name="fecha_salida"]');
    const personas = form.querySelector('[name="cantidad_personas"]');
    const caja = form.querySelector('[data-resumen-caja]');
    const $ = (sel) => caja.querySelector(sel);

    const cantidades = () => [...form.querySelectorAll('input[name^="tipos["]')];
    const casillas = () => [...form.querySelectorAll('input[name="habitaciones[]"]')];

    let ultimo = null; // último resumen recibido (para el aviso de capacidad)

    // ----- Pintar el resumen -----
    const mostrar = (resumen, noches, error = '') => {
        ultimo = resumen;
        $('[data-resumen-vacio]').hidden = Boolean(resumen) || Boolean(error);
        $('[data-resumen-error]').hidden = !error;
        $('[data-resumen-error]').textContent = error;
        $('[data-resumen]').hidden = !resumen;
        form.querySelector('[data-reservar]').disabled = !resumen;

        if (!resumen) return;

        // "1 habitación Doble × 3 noches": se cobra por habitación y por noche
        $('[data-resumen-lineas]').replaceChildren(...resumen.lineas.map((texto) => {
            const li = document.createElement('li');
            li.textContent = texto;
            return li;
        }));

        const lista = $('[data-resumen-etiquetas]');
        lista.replaceChildren(...resumen.etiquetas.map((texto) => {
            const li = document.createElement('li');
            li.innerHTML = '<i data-lucide="badge-percent"></i>';
            li.append(texto);
            return li;
        }));

        const tachado = $('[data-resumen-sin-descuento]');
        tachado.textContent = resumen.sin_descuento ?? '';
        tachado.hidden = !resumen.sin_descuento;
        if (resumen.sin_descuento) tachado.setAttribute('aria-label', `Antes ${resumen.sin_descuento}`);

        $('[data-resumen-total]').textContent = resumen.total;
        $('[data-resumen-adelanto]').textContent = resumen.adelanto;

        avisoCapacidad();
        window.lucide?.createIcons();
    };

    // Aviso (no bloquea: Laravel vuelve a validar) si hay más personas que camas
    const avisoCapacidad = () => {
        const aviso = $('[data-resumen-capacidad]');
        const n = Number(personas?.value) || 0;
        if (ultimo && n > ultimo.capacidad) {
            aviso.textContent = `Estas habitaciones son para ${ultimo.capacidad} ${ultimo.capacidad === 1 ? 'persona' : 'personas'} como máximo.`;
            aviso.hidden = false;
        } else {
            aviso.hidden = true;
        }
    };

    // ----- Libres y precios por tipo -----
    // Devuelve true si tuvo que bajar alguna cantidad o desmarcar alguna habitación ocupada
    const pintarDisponibilidad = (datos) => {
        let corregido = false;

        datos.tipos.forEach((t) => {
            const bloque = form.querySelector(`[data-tipo="${t.id}"]`);
            if (!bloque) return;

            bloque.querySelector('[data-precio]').textContent = t.precio;
            const libres = bloque.querySelector('[data-libres]');
            libres.textContent = t.libres > 0
                ? `${t.libres} ${t.libres === 1 ? 'libre' : 'libres'}`
                : 'Sin habitaciones libres';
            libres.classList.toggle('agotado', t.libres === 0);

            const input = bloque.querySelector('input[name^="tipos["]');
            if (input) {
                input.max = t.libres;
                if (Number(input.value) > t.libres) { input.value = t.libres; corregido = true; }
                bloque.classList.toggle('sin-libres', t.libres === 0);
                bloque.classList.toggle('elegido', Number(input.value) > 0);
            }
        });

        const libres = new Set(datos.libres);
        casillas().forEach((c) => {
            const libre = libres.has(Number(c.value));
            c.disabled = !libre;
            c.closest('[data-habitacion]').classList.toggle('ocupada', !libre);
            if (!libre && c.checked) { c.checked = false; corregido = true; }
        });

        return corregido;
    };

    const sinFechas = () => {
        form.querySelectorAll('[data-tipo] [data-libres]').forEach((el) => { el.textContent = 'Elige las fechas'; });
        form.querySelectorAll('[data-tipo] [data-precio]').forEach((el) => { el.textContent = '—'; });
        cantidades().forEach((i) => { i.max = 0; });
        casillas().forEach((c) => { c.disabled = true; });
    };

    // ----- Pedir el resumen a Laravel -----
    let espera;
    let peticion;

    const actualizar = async () => {
        if (llegada.value) salida.min = siguienteDia(llegada.value);

        if (!llegada.value || !salida.value) {
            sinFechas();
            mostrar(null, 0);
            return;
        }

        const url = new URL(form.dataset.cotizar, location.href);
        url.searchParams.set('llegada', llegada.value);
        url.searchParams.set('salida', salida.value);
        cantidades().forEach((i) => { if (Number(i.value) > 0) url.searchParams.set(i.name, i.value); });
        casillas().forEach((c) => { if (c.checked) url.searchParams.append('habitaciones[]', c.value); });

        peticion?.abort();
        peticion = new AbortController();
        caja.classList.add('cargando-resultados');

        try {
            const respuesta = await fetch(url, { headers: { Accept: 'application/json' }, signal: peticion.signal });
            const datos = await respuesta.json();

            if (!respuesta.ok) {
                const errores = Object.values(datos.errors ?? {}).flat();
                sinFechas();
                mostrar(null, 0, errores[0] ?? 'Revisa las fechas.');
            } else if (pintarDisponibilidad(datos)) {
                actualizar(); // se corrigió la selección: volver a calcular
                return;
            } else {
                mostrar(datos.resumen, datos.noches);
            }
        } catch (error) {
            if (error.name !== 'AbortError') mostrar(null, 0, 'No se pudo calcular. Revisa tu conexión.');
        }
        caja.classList.remove('cargando-resultados');
    };

    const programar = () => {
        clearTimeout(espera);
        espera = setTimeout(actualizar, 250);
    };

    const siguienteDia = (fecha) => {
        const d = new Date(`${fecha}T12:00:00`);
        d.setDate(d.getDate() + 1);
        return d.toISOString().slice(0, 10);
    };

    // ----- Botones − y + de cada tipo -----
    form.querySelectorAll('[data-tipo]').forEach((bloque) => {
        const input = bloque.querySelector('input[name^="tipos["]');
        if (!input) return;
        const cambiar = (paso) => {
            const nuevo = Math.min(Math.max(0, (Number(input.value) || 0) + paso), Number(input.max) || 0);
            if (nuevo !== Number(input.value)) {
                input.value = nuevo;
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
        };
        bloque.querySelector('[data-menos]')?.addEventListener('click', () => cambiar(-1));
        bloque.querySelector('[data-mas]')?.addEventListener('click', () => cambiar(1));
    });

    form.addEventListener('input', (e) => {
        if (e.target === personas) { avisoCapacidad(); return; }
        if (e.target.matches('input[name^="tipos["]')) {
            e.target.closest('[data-tipo]')?.classList.toggle('elegido', Number(e.target.value) > 0);
        }
        if (e.target.matches('[name="fecha_entrada"], [name="fecha_salida"], input[name^="tipos["]')) programar();
    });
    form.addEventListener('change', (e) => {
        if (e.target.matches('input[name="habitaciones[]"]')) programar();
    });

    actualizar();
})();
