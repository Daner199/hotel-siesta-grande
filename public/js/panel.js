/* Paneles: menú en celular, avisos, confirmaciones, búsqueda en vivo,
   números animados y efecto 3D */
(() => {

    // ----- Menú en celular -----
    const lateral = document.getElementById('lateral');
    const velo = document.querySelector('[data-cerrar-menu]');
    const abrir = document.querySelector('[data-abrir-menu]');

    const cerrarMenu = () => {
        lateral?.classList.remove('abierto');
        if (velo) velo.hidden = true;
    };

    abrir?.addEventListener('click', () => {
        lateral.classList.add('abierto');
        velo.hidden = false;
    });
    velo?.addEventListener('click', cerrarMenu);
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') cerrarMenu(); });


    // ----- Aviso flotante: se va solo a los 5 segundos -----
    const aviso = document.querySelector('[data-aviso]');
    if (aviso) {
        const quitar = () => {
            aviso.classList.add('saliendo');
            setTimeout(() => aviso.remove(), 300);
        };
        aviso.querySelector('[data-cerrar-aviso]')?.addEventListener('click', quitar);
        setTimeout(quitar, 5000);
    }


    // ----- Confirmación antes de enviar: <form data-confirmar="¿Seguro?"> -----
    // Se escucha en todo el documento para que funcione también en
    // los resultados que llegan con la búsqueda en vivo.
    document.addEventListener('submit', (e) => {
        const form = e.target.closest('form[data-confirmar]');
        if (form && !window.confirm(form.dataset.confirmar)) {
            e.preventDefault();
        }
    });


    // ----- Búsqueda en vivo: <form data-busqueda-vivo> + <div data-resultados> -----
    document.querySelectorAll('form[data-busqueda-vivo]').forEach((form) => {
        const resultados = document.querySelector('[data-resultados]');
        if (!resultados) return;

        let espera;
        let peticion;

        const buscar = async () => {
            // Armar la URL con lo que hay en el formulario (sin campos vacíos)
            const url = new URL(form.action);
            new FormData(form).forEach((valor, clave) => {
                if (valor !== '') url.searchParams.set(clave, valor);
            });

            // Cancelar la búsqueda anterior si todavía no terminó
            peticion?.abort();
            peticion = new AbortController();
            resultados.classList.add('cargando-resultados');

            try {
                const respuesta = await fetch(url, { signal: peticion.signal });
                const html = await respuesta.text();
                const nuevos = new DOMParser()
                    .parseFromString(html, 'text/html')
                    .querySelector('[data-resultados]');

                if (nuevos) {
                    resultados.innerHTML = nuevos.innerHTML;
                    window.lucide?.createIcons();
                    history.replaceState(null, '', url); // la URL refleja la búsqueda
                }
                resultados.classList.remove('cargando-resultados');
            } catch (error) {
                if (error.name !== 'AbortError') {
                    form.submit(); // si algo falla, búsqueda normal con recarga
                }
            }
        };

        // Al escribir: esperar 300 ms desde la última tecla
        form.addEventListener('input', (e) => {
            if (e.target.matches('input[type="search"]')) {
                clearTimeout(espera);
                espera = setTimeout(buscar, 300);
            }
        });

        // Al cambiar el filtro de estado: buscar enseguida
        form.addEventListener('change', (e) => {
            if (e.target.matches('select')) buscar();
        });

        // Botón "Buscar" o Enter: sin recargar la página
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            clearTimeout(espera);
            buscar();
        });
    });


    // ----- Selector que guarda solo: <select data-autoenviar> dentro de un <form> -----
    // Al elegir otra opción envía su formulario (pasa por data-confirmar si lo tiene).
    // Escucha en todo el documento para funcionar también tras la búsqueda en vivo.
    document.addEventListener('change', (e) => {
        const selector = e.target.closest('select[data-autoenviar]');
        if (selector?.form) selector.form.requestSubmit();
    });


    // ----- Subir fotos: <form data-subir-fotos data-libres="N" data-max-kb="2048"> -----
    // Vista previa, revisa formato y peso antes de enviar, y permite arrastrar y soltar.
    // Laravel vuelve a validar todo.
    document.querySelectorAll('form[data-subir-fotos]').forEach((form) => {
        const input  = form.querySelector('input[type="file"]');
        const zona   = form.querySelector('.zona-subida');
        const lista  = form.querySelector('[data-vista-previa]');
        const error  = form.querySelector('[data-error-fotos]');
        const boton  = form.querySelector('[data-boton-subir]');
        const libres = Number(form.dataset.libres);
        const maxBytes = Number(form.dataset.maxKb) * 1024;
        const permitidos = ['image/jpeg', 'image/png', 'image/webp'];

        const revisar = () => {
            lista.querySelectorAll('img').forEach((img) => URL.revokeObjectURL(img.src));
            lista.innerHTML = '';

            const archivos = [...input.files];
            const errores = [];

            if (archivos.length > libres) {
                errores.push(`Solo puedes agregar ${libres} ${libres === 1 ? 'foto' : 'fotos'} más.`);
            }

            archivos.forEach((archivo) => {
                let problema = '';
                if (!permitidos.includes(archivo.type)) problema = 'solo JPG, PNG o WEBP';
                else if (archivo.size > maxBytes) problema = `pesa ${(archivo.size / 1048576).toFixed(1)} MB (máx. 2 MB)`;
                if (problema) errores.push(`«${archivo.name}»: ${problema}.`);

                const li = document.createElement('li');
                li.className = problema ? 'con-error' : '';
                if (!problema) {
                    const img = document.createElement('img');
                    img.src = URL.createObjectURL(archivo);
                    img.alt = '';
                    li.append(img);
                }
                const nombre = document.createElement('span');
                nombre.textContent = archivo.name;
                li.append(nombre);
                lista.append(li);
            });

            error.hidden = errores.length === 0;
            error.textContent = errores.join(' ');
            boton.disabled = archivos.length === 0 || errores.length > 0;
            zona.classList.toggle('con-archivos', archivos.length > 0);
        };

        input.addEventListener('change', revisar);

        ['dragenter', 'dragover'].forEach((evento) => zona.addEventListener(evento, (e) => {
            e.preventDefault();
            zona.classList.add('arrastrando');
        }));
        ['dragleave', 'drop'].forEach((evento) => zona.addEventListener(evento, (e) => {
            e.preventDefault();
            zona.classList.remove('arrastrando');
        }));
        zona.addEventListener('drop', (e) => {
            input.files = e.dataTransfer.files;
            revisar();
        });

        // Evitar doble envío
        form.addEventListener('submit', () => {
            boton.disabled = true;
            boton.classList.add('cargando');
        });
    });


    // ----- Una sola foto con vista previa: <input type="file" data-foto-unica="#id-de-la-vista"> -----
    // Muestra la foto elegida en el marco, y avisa si no es JPG/PNG/WEBP o pesa más de 2 MB.
    document.querySelectorAll('input[type="file"][data-foto-unica]').forEach((input) => {
        const marco = document.querySelector(input.dataset.fotoUnica);
        const caja  = input.closest('.foto-campo');
        const error = caja?.querySelector('[data-error-foto]');
        const quitar = caja?.querySelector('input[name="quitar[]"]');

        input.addEventListener('change', () => {
            const archivo = input.files[0];
            let problema = '';

            if (archivo && !['image/jpeg', 'image/png', 'image/webp'].includes(archivo.type)) {
                problema = 'Solo se aceptan fotos JPG, PNG o WEBP.';
            } else if (archivo && archivo.size > 2 * 1048576) {
                problema = `La foto pesa ${(archivo.size / 1048576).toFixed(1)} MB (máximo 2 MB).`;
            }

            if (error) {
                error.hidden = !problema;
                error.textContent = problema;
            }
            if (problema) {
                input.value = '';
                return;
            }

            if (archivo && marco) {
                marco.querySelector('img')?.remove();
                const img = document.createElement('img');
                img.src = URL.createObjectURL(archivo);
                img.alt = 'Vista previa';
                marco.prepend(img);
                marco.classList.add('con-foto');
                if (quitar) quitar.checked = false;
            }
        });
    });


    // ----- Respetar a quien desactivó las animaciones en su sistema -----
    // Todo lo que está DEBAJO de esta línea son solo animaciones.
    // Lo importante (menú, avisos, confirmación, búsqueda) va ARRIBA.
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;


    // ----- Números que suben al cargar -----
    document.querySelectorAll('[data-contar]').forEach((el) => {
        const final = parseInt(el.dataset.contar, 10) || 0;
        const inicio = performance.now();
        const duracion = 900;

        const paso = (ahora) => {
            const p = Math.min((ahora - inicio) / duracion, 1);
            el.textContent = Math.round(final * (1 - Math.pow(1 - p, 3)));
            if (p < 1) requestAnimationFrame(paso);
        };
        requestAnimationFrame(paso);
    });


    // ----- Inclinación 3D siguiendo el mouse -----
    document.querySelectorAll('[data-inclinar]').forEach((tarjeta) => {
        tarjeta.addEventListener('mousemove', (e) => {
            const r = tarjeta.getBoundingClientRect();
            const x = (e.clientX - r.left) / r.width - 0.5;
            const y = (e.clientY - r.top) / r.height - 0.5;
            tarjeta.style.transform =
                `perspective(700px) rotateX(${-y * 7}deg) rotateY(${x * 7}deg) translateY(-3px)`;
        });
        tarjeta.addEventListener('mouseleave', () => { tarjeta.style.transform = ''; });
    });

})();