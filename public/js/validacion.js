/**
 * Validación en vivo - Hotel Siesta Grande
 *
 * Uso:
 *   <form data-validar-form>
 *     <input name="nombre" data-validar="letras" required data-vacio="Escribe tu nombre.">
 *
 * Tipos: letras, email, telefono, password, confirmar, nit
 * Son las MISMAS reglas que valida Laravel. Laravel siempre vuelve a validar.
 */
(() => {
    const LETRAS = /^\p{L}+(?:[\s'-]\p{L}+)*$/u;
    const EMAIL  = /^[^@\s]+@[^@\s]+\.[a-z]{2,}$/i;

    const vacio = (campo) => campo.dataset.vacio || 'Este campo es obligatorio.';

    // ----- Reglas: devuelven el mensaje de error, o null si está bien -----
    const reglas = {
        letras(valor, campo) {
            if (!valor) return campo.required ? vacio(campo) : null;
            if (valor.length < 2) return 'Debe tener al menos 2 letras.';
            if (valor.length > 100) return 'Es demasiado largo.';
            if (!LETRAS.test(valor)) return 'Solo puede tener letras.';
            return null;
        },

        email(valor, campo) {
            if (!valor) return campo.required ? vacio(campo) : null;
            if (valor.length > 150) return 'El correo es demasiado largo.';
            if (!EMAIL.test(valor)) return 'Escribe el correo completo, por ejemplo nombre@gmail.com.';
            return null;
        },

        telefono(valor, campo, form) {
            if (!valor) return campo.required ? vacio(campo) : null;
            if (!/^\d{4,15}$/.test(valor)) return 'Solo números, entre 4 y 15.';

            const pais = form.querySelector(`[name="${campo.dataset.pais}"]`)?.value;
            if (window.libphonenumber && pais &&
                !window.libphonenumber.isValidPhoneNumber(valor, pais)) {
                return 'Ese número no es válido para el país elegido.';
            }
            return null;
        },

        password(valor, campo) {
            if (!valor) return campo.required ? vacio(campo) : null;
            if (valor.length < 8) return 'Debe tener al menos 8 caracteres.';
            if (valor.length > 72) return 'No puede tener más de 72 caracteres.';
            if (!/[A-Za-z]/.test(valor) || !/[0-9]/.test(valor)) {
                return 'Debe tener al menos una letra y un número.';
            }
            return null;
        },

        confirmar(valor, campo, form) {
            const original = form.querySelector(`[name="${campo.dataset.igual}"]`)?.value ?? '';
            if (!valor) return original ? 'Repite la contraseña.' : null;
            if (valor !== original) return 'Las contraseñas no coinciden.';
            return null;
        },

               nit(valor, campo) {
            if (!valor) return campo.required ? vacio(campo) : null;
            if (!/^\d{7,12}$/.test(valor)) return 'El NIT debe tener solo números, entre 7 y 12.';
            return null;
        },

        // Nombre de empresa: letras, números y . , & - '
        empresa(valor, campo) {
            if (!valor) return campo.required ? vacio(campo) : null;
            if (valor.length < 2) return 'Debe tener al menos 2 caracteres.';
            if (valor.length > 150) return 'Es demasiado largo.';
            if (!/^[\p{L}\p{N}\s.,&'-]+$/u.test(valor) || !/\p{L}/u.test(valor)) {
                return "Usa letras, números y los signos . , & - '";
            }
            return null;
        },
    };

    // ----- Filtros: impiden escribir caracteres no permitidos -----
    const filtros = {
        letras:   (v) => v.replace(/[0-9]/g, ''),
        telefono: (v) => v.replace(/\D/g, ''),
        nit:      (v) => v.replace(/\D/g, ''),
    };

    // ----- Mostrar u ocultar el error de un campo -----
    function mostrar(campo, error, form) {
        const caja = campo.closest('.campo');
        if (!caja) return;

        // Quitar los errores que vinieron del servidor para este campo
        caja.querySelectorAll('.campo-error:not([data-vivo])').forEach((e) => e.remove());
        form.querySelectorAll(`[data-error-de="${campo.name}"]`).forEach((e) => e.remove());

        let p = caja.querySelector('.campo-error[data-vivo]');

        if (error) {
            if (!p) {
                p = document.createElement('p');
                p.className = 'campo-error';
                p.dataset.vivo = '';
                p.id = `${campo.id}-error`;
                caja.appendChild(p);
            }
            p.textContent = error;
            campo.setAttribute('aria-invalid', 'true');
            campo.setAttribute('aria-describedby', p.id);
        } else {
            p?.remove();
            campo.removeAttribute('aria-invalid');
            campo.removeAttribute('aria-describedby');
        }

        caja.classList.toggle('es-invalido', Boolean(error));
        caja.classList.toggle('es-valido', !error && campo.value.trim() !== '');
    }

    function validar(campo, form) {
        const tipo = campo.dataset.validar;
        const esClave = tipo === 'password' || tipo === 'confirmar';
        const valor = esClave ? campo.value : campo.value.trim();
        const error = reglas[tipo] ? reglas[tipo](valor, campo, form) : null;

        mostrar(campo, error, form);
        return error;
    }

    // ----- Medidor de seguridad de la contraseña -----
    function medir(campo) {
        const caja = campo.closest('.campo');
        const barra = caja?.querySelector('.medidor span');
        const texto = caja?.querySelector('.medidor-texto');
        if (!barra) return;

        const v = campo.value;
        let puntos = 0;
        if (v.length >= 8) puntos++;
        if (/[A-Za-z]/.test(v) && /\d/.test(v)) puntos++;
        if (v.length >= 12 && /[^A-Za-z0-9]/.test(v)) puntos++;
        if (v && puntos === 0) puntos = 1;

        const niveles = [
            ['0%',   'transparent', ''],
            ['34%',  '#C4507A',     'Débil'],
            ['67%',  '#C99A3B',     'Aceptable'],
            ['100%', '#3C8D63',     'Fuerte'],
        ];
        const [ancho, color, etiqueta] = niveles[v ? puntos : 0];

        barra.style.width = ancho;
        barra.style.backgroundColor = color;
        if (texto) texto.textContent = etiqueta ? `Seguridad: ${etiqueta}` : '';
    }

    // ----- Activar en cada formulario marcado -----
    document.querySelectorAll('form[data-validar-form]').forEach((form) => {
        const campos = [...form.querySelectorAll('[data-validar]')];

        campos.forEach((campo) => {
            const tipo = campo.dataset.validar;

            campo.addEventListener('input', () => {
                if (filtros[tipo]) {
                    const limpio = filtros[tipo](campo.value);
                    if (limpio !== campo.value) campo.value = limpio;
                }
                campo.dataset.tocado = '1';
                validar(campo, form);

                if (tipo === 'password') {
                    medir(campo);
                    // Si ya escribió la confirmación, revisarla de nuevo
                    const conf = form.querySelector(`[data-validar="confirmar"][data-igual="${campo.name}"]`);
                    if (conf?.dataset.tocado) validar(conf, form);
                }
            });

            // Al salir del campo también se valida (ej. obligatorio vacío)
            campo.addEventListener('blur', () => {
                campo.dataset.tocado = '1';
                validar(campo, form);
            });

            // Si cambia el país, revisar el teléfono otra vez
            if (tipo === 'telefono' && campo.dataset.pais) {
                form.querySelector(`[name="${campo.dataset.pais}"]`)
                    ?.addEventListener('change', () => { if (campo.value) validar(campo, form); });
            }
        });

        // Al enviar: validar todo; si hay errores, no se envía
        form.addEventListener('submit', (e) => {
            const hayErrores = campos.map((c) => validar(c, form)).some(Boolean);

            if (hayErrores) {
                e.preventDefault();
                campos.find((c) => c.getAttribute('aria-invalid') === 'true')?.focus();
                return;
            }

            // Evitar doble envío
            const boton = form.querySelector('[type="submit"]');
            if (boton) {
                boton.disabled = true;
                boton.classList.add('cargando');
            }
        });
    });
})();