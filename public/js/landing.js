/**
 * Página pública del Hotel Siesta Grande.
 * - Cabecera que se vuelve sólida al bajar + menú en celular
 * - Aparición suave de secciones y cifras que suben (solo sin "reducir movimiento")
 * - Carruseles de fotos
 * - Buscador de disponibilidad (GET /disponibilidad, JSON)
 * - Mapa: Leaflet se carga solo cuando el usuario se acerca a la sección
 * - Escena 3D de la portada: Three.js se carga solo si hay WebGL y no se pidió reducir movimiento
 */
(() => {
    const config = JSON.parse(document.getElementById('config-landing')?.textContent || '{}');
    const reducir = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // ---------- Cabecera sólida al bajar ----------
    const cabecera = document.querySelector('[data-cabecera]');
    const revisarCabecera = () => cabecera?.classList.toggle('solida', window.scrollY > 40);
    revisarCabecera();
    window.addEventListener('scroll', revisarCabecera, { passive: true });

    // ---------- Menú en celular ----------
    const botonMenu = document.querySelector('[data-boton-menu]');
    const menu = document.querySelector('[data-menu]');

    const abrirMenu = (abrir) => {
        menu.classList.toggle('abierto', abrir);
        cabecera.classList.toggle('menu-abierto', abrir);
        botonMenu.setAttribute('aria-expanded', String(abrir));
        botonMenu.setAttribute('aria-label', abrir ? 'Cerrar menú' : 'Abrir menú');
        botonMenu.innerHTML = `<i data-lucide="${abrir ? 'x' : 'menu'}"></i>`;
        window.lucide?.createIcons();
    };

    botonMenu?.addEventListener('click', () => abrirMenu(!menu.classList.contains('abierto')));
    menu?.querySelectorAll('a').forEach((a) => a.addEventListener('click', () => abrirMenu(false)));
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && menu?.classList.contains('abierto')) {
            abrirMenu(false);
            botonMenu.focus();
        }
    });

    // ---------- Aparición al hacer scroll + cifras que suben ----------
    const contar = (el) => {
        const final = Number(el.dataset.contar) || 0;
        const inicio = performance.now();
        const paso = (ahora) => {
            const p = Math.min((ahora - inicio) / 1200, 1);
            el.textContent = Math.round(final * (1 - Math.pow(1 - p, 3)));
            if (p < 1) requestAnimationFrame(paso);
        };
        requestAnimationFrame(paso);
    };

    if (!reducir && 'IntersectionObserver' in window) {
        document.documentElement.classList.add('animar');

        const observador = new IntersectionObserver((entradas) => {
            entradas.forEach((entrada) => {
                if (!entrada.isIntersecting) return;
                entrada.target.classList.add('visible');
                entrada.target.querySelectorAll('[data-contar]').forEach(contar);
                observador.unobserve(entrada.target);
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

        document.querySelectorAll('.revelar').forEach((el) => observador.observe(el));
    }

    // ---------- Carruseles ----------
    document.querySelectorAll('[data-carrusel]').forEach((carrusel) => {
        const pista = carrusel.querySelector('[data-carrusel-pista]');
        const anterior = carrusel.querySelector('[data-carrusel-anterior]');
        const siguiente = carrusel.querySelector('[data-carrusel-siguiente]');
        const puntos = [...carrusel.querySelectorAll('[data-carrusel-puntos] button')];
        if (!pista || puntos.length < 2) return;

        const actual = () => Math.round(pista.scrollLeft / pista.clientWidth);
        const ir = (i) => pista.scrollTo({ left: i * pista.clientWidth, behavior: reducir ? 'auto' : 'smooth' });

        const actualizar = () => {
            const i = actual();
            puntos.forEach((p, n) => {
                p.classList.toggle('activo', n === i);
                p.setAttribute('aria-current', n === i ? 'true' : 'false');
            });
            anterior.disabled = i === 0;
            siguiente.disabled = i === puntos.length - 1;
        };

        anterior.addEventListener('click', () => ir(actual() - 1));
        siguiente.addEventListener('click', () => ir(actual() + 1));
        puntos.forEach((p, n) => p.addEventListener('click', () => ir(n)));
        pista.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') { e.preventDefault(); ir(actual() - 1); }
            if (e.key === 'ArrowRight') { e.preventDefault(); ir(actual() + 1); }
        });

        let espera;
        pista.addEventListener('scroll', () => {
            clearTimeout(espera);
            espera = setTimeout(actualizar, 60);
        }, { passive: true });
        actualizar();
    });

    // ---------- Buscador de disponibilidad ----------
    const form = document.querySelector('[data-buscador]');
    if (form) {
        const llegada = form.querySelector('#llegada');
        const salida = form.querySelector('#salida');
        const tipo = form.querySelector('#tipo');
        const boton = form.querySelector('[data-buscador-boton]');
        const error = document.querySelector('[data-buscador-error]');
        const caja = document.querySelector('[data-buscador-resultados]');

        // Fecha local en formato AAAA-MM-DD (sin problemas de zona horaria)
        const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
        const masUnDia = (texto) => {
            const [a, m, d] = texto.split('-').map(Number);
            return iso(new Date(a, m - 1, d + 1));
        };

        // La salida siempre después de la llegada
        llegada.addEventListener('change', () => {
            if (!llegada.value) return;
            salida.min = masUnDia(llegada.value);
            if (!salida.value || salida.value <= llegada.value) salida.value = salida.min;
        });

        const mostrarError = (texto) => {
            error.textContent = texto;
            error.hidden = !texto;
        };

        // Crear elementos sin innerHTML con datos (evita inyectar HTML)
        const el = (etiqueta, clase, texto) => {
            const nodo = document.createElement(etiqueta);
            if (clase) nodo.className = clase;
            if (texto !== undefined) nodo.textContent = texto;
            return nodo;
        };

        const enlace = (texto, url, clase) => {
            const a = el('a', clase, texto);
            a.href = url;
            return a;
        };

        // Ícono de Lucide (se dibuja con lucide.createIcons al final)
        const icono = (nombre) => {
            const i = el('i');
            i.dataset.lucide = nombre;
            return i;
        };

        // Resultado para el huésped: sin operaciones, solo total, tachado y promedio
        const pintar = (datos) => {
            caja.innerHTML = '';

            const resumen = el('p', 'resultados-resumen');
            resumen.append(
                `${datos.noches} ${datos.noches === 1 ? 'noche' : 'noches'} `,
                el('span', '', `· ${datos.llegada} → ${datos.salida}`),
            );
            caja.append(resumen);

            if (!datos.resultados.length) {
                caja.append(el('p', 'sin-resultados', 'No hay habitaciones con precio para esas fechas. Prueba con otras fechas o escríbenos.'));
                return;
            }

            const lista = el('ul', 'resultados');

            datos.resultados.forEach((r) => {
                const item = el('li', 'resultado');

                // Foto grande (o fondo elegante)
                const foto = el('div', 'resultado-foto');
                if (r.foto) {
                    const img = el('img');
                    img.src = r.foto;
                    img.alt = `Habitación ${r.nombre}`;
                    img.loading = 'lazy';
                    foto.append(img);
                } else {
                    const fondo = el('span', 'fondo-elegante');
                    fondo.append(icono('bed-double'));
                    foto.append(fondo);
                }

                const cuerpo = el('div', 'resultado-cuerpo');
                cuerpo.append(el('h3', '', r.nombre));

                const datosTipo = el('div', 'resultado-datos');
                const capacidad = el('span');
                capacidad.append(icono('users'), `${r.capacidad} ${r.capacidad === 1 ? 'persona' : 'personas'}`);
                const libres = el('span', r.libres > 0 ? '' : 'agotado');
                libres.append(icono(r.libres > 0 ? 'circle-check' : 'circle-x'),
                    r.libres > 0 ? `${r.libres} ${r.libres === 1 ? 'disponible' : 'disponibles'}` : 'Sin habitaciones libres');
                datosTipo.append(capacidad, libres);
                cuerpo.append(datosTipo);

                // Solo si son más de 7 noches
                if (r.estadia_larga) {
                    const etiqueta = el('span', 'etiqueta-larga');
                    etiqueta.append(icono('badge-percent'), 'Estadía larga: 15 % menos desde la 8.ª noche');
                    cuerpo.append(etiqueta);
                }

                const precio = el('div', 'resultado-precio');
                const montos = el('p');
                if (r.sin_descuento) {
                    const tachado = el('s', 'precio-tachado', r.sin_descuento);
                    tachado.setAttribute('aria-label', `Antes ${r.sin_descuento}`);
                    montos.append(tachado);
                }
                montos.append(
                    el('strong', 'precio-total', r.total),
                    el('span', 'precio-promedio', `${r.promedio} por noche en promedio`),
                );
                precio.append(montos);

                if (r.libres > 0 && config.reservar) {
                    precio.append(enlace(config.reservar.texto, config.reservar.url, 'boton boton-tajibo'));
                }

                cuerpo.append(precio);
                item.append(foto, cuerpo);
                lista.append(item);
            });

            caja.append(lista);
            window.lucide?.createIcons();
        };

        const buscar = async () => {
            mostrarError('');

            if (!llegada.value || !salida.value) {
                mostrarError('Elige las fechas de llegada y salida.');
                return;
            }
            if (salida.value <= llegada.value) {
                mostrarError('La salida debe ser después de la llegada.');
                return;
            }

            const url = new URL(config.disponibilidad || form.action, window.location.origin);
            url.searchParams.set('llegada', llegada.value);
            url.searchParams.set('salida', salida.value);
            if (tipo.value) url.searchParams.set('tipo', tipo.value);

            boton.disabled = true;
            caja.setAttribute('aria-busy', 'true');

            try {
                const respuesta = await fetch(url, { headers: { Accept: 'application/json' } });
                const datos = await respuesta.json().catch(() => ({}));

                if (respuesta.status === 422) {
                    const primero = Object.values(datos.errors || {})[0];
                    mostrarError((primero && primero[0]) || datos.message || 'Revisa las fechas.');
                } else if (respuesta.status === 429) {
                    mostrarError('Hiciste muchas consultas seguidas. Espera un minuto y vuelve a intentar.');
                } else if (!respuesta.ok) {
                    mostrarError('No pudimos consultar la disponibilidad. Inténtalo de nuevo.');
                } else {
                    pintar(datos);
                }
            } catch {
                mostrarError('No hay conexión. Revisa tu internet e inténtalo de nuevo.');
            } finally {
                boton.disabled = false;
                caja.removeAttribute('aria-busy');
            }
        };

        form.addEventListener('submit', (e) => {
            e.preventDefault();
            buscar();
        });

        // Botón "Ver disponibilidad" de cada tipo: elige el tipo, sube al buscador y busca
        document.querySelectorAll('[data-elegir-tipo]').forEach((b) => {
            b.addEventListener('click', () => {
                tipo.value = b.dataset.elegirTipo;
                document.getElementById('disponibilidad').scrollIntoView({ behavior: reducir ? 'auto' : 'smooth' });
                buscar();
            });
        });
    }

    // ---------- Mapa diferido (Leaflet solo al acercarse) ----------
    const mapa = document.querySelector('[data-mapa-diferido]');
    if (mapa && config.leaflet) {
        const cargarScript = (src, sri) => new Promise((ok, falla) => {
            const s = document.createElement('script');
            s.src = src;
            if (sri) { s.integrity = sri; s.crossOrigin = ''; }
            s.onload = ok;
            s.onerror = falla;
            document.body.append(s);
        });

        const cargarMapa = async () => {
            const css = document.createElement('link');
            css.rel = 'stylesheet';
            css.href = config.leaflet.css;
            css.integrity = config.leaflet.cssSri;
            css.crossOrigin = '';
            document.head.append(css);

            try {
                await cargarScript(config.leaflet.js, config.leaflet.jsSri);
            } catch {
                // sin Leaflet, mapa-hotel.js muestra un aviso en la caja
            }
            await cargarScript(config.mapa);
        };

        if ('IntersectionObserver' in window) {
            const vigia = new IntersectionObserver((entradas) => {
                if (entradas.some((e) => e.isIntersecting)) {
                    vigia.disconnect();
                    cargarMapa();
                }
            }, { rootMargin: '400px 0px' });
            vigia.observe(mapa);
        } else {
            cargarMapa();
        }
    }

    // ---------- Barra "Reservar" en celular: aparece después de la portada ----------
    const barra = document.querySelector('[data-barra-reservar]');
    const portada = document.querySelector('[data-portada]');
    const buscador = document.getElementById('disponibilidad');
    if (barra && portada && 'IntersectionObserver' in window) {
        let enPortada = true;
        let enBuscador = false;
        const actualizarBarra = () => barra.classList.toggle('visible', !enPortada && !enBuscador);

        new IntersectionObserver(([e]) => { enPortada = e.isIntersecting; actualizarBarra(); },
            { threshold: 0.15 }).observe(portada);
        // Sobre el buscador no hace falta (ya está ahí)
        if (buscador) {
            new IntersectionObserver(([e]) => { enBuscador = e.isIntersecting; actualizarBarra(); },
                { threshold: 0.3 }).observe(buscador);
        }
    }

    // ---------- Parallax suave en las fotos ([data-parallax], máx. 40 px) ----------
    const conParallax = [...document.querySelectorAll('[data-parallax]')];
    if (!reducir && conParallax.length && 'IntersectionObserver' in window) {
        const visibles = new Set();
        let pendiente = false;

        const mover = () => {
            pendiente = false;
            const alto = window.innerHeight;
            visibles.forEach((caja) => {
                const r = caja.getBoundingClientRect();
                // -1 cuando entra por abajo, +1 cuando sale por arriba
                const avance = ((r.top + r.height / 2) - alto / 2) / (alto / 2 + r.height / 2);
                const intensidad = Math.min(Number(caja.dataset.parallax) || 30, 40);
                caja.style.setProperty('--parallax', `${(avance * -intensidad).toFixed(1)}px`);
            });
        };
        const pedir = () => {
            if (!pendiente) {
                pendiente = true;
                requestAnimationFrame(mover);
            }
        };

        const vigiaParallax = new IntersectionObserver((entradas) => {
            entradas.forEach((e) => (e.isIntersecting ? visibles.add(e.target) : visibles.delete(e.target)));
            pedir();
        }, { rootMargin: '100px 0px' });
        conParallax.forEach((c) => vigiaParallax.observe(c));

        window.addEventListener('scroll', pedir, { passive: true });
        window.addEventListener('resize', pedir, { passive: true });
    }

    // ---------- Escena 3D de la portada (dos capas: detrás y delante del título) ----------
    const lienzo = document.querySelector('[data-portada-3d]');
    const lienzoFrente = document.querySelector('[data-portada-3d-frente]');
    const tieneWebGL = () => {
        try {
            const c = document.createElement('canvas');
            return Boolean(window.WebGLRenderingContext && (c.getContext('webgl2') || c.getContext('webgl')));
        } catch {
            return false;
        }
    };

    if (lienzo && !reducir && config.portada3d && tieneWebGL()) {
        import(config.portada3d)
            .then((modulo) => modulo.iniciar(lienzo, lienzoFrente))
            .catch(() => { /* si falla, queda la foto o el degradado de la portada */ });
    }
})();
