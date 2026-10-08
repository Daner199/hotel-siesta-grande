/**
 * Mapa del hotel con Leaflet + OpenStreetMap (requiere leaflet.js cargado antes).
 *
 * Solo ver (landing):
 *   <div data-mapa data-lat="-17.7756" data-lng="-63.1868" data-titulo="Hotel Siesta Grande"></div>
 *
 * Editable (panel del admin): clic en el mapa o arrastrar el pin.
 *   <div data-mapa data-editable data-input-lat="latitud" data-input-lng="longitud"
 *        data-texto="#coordenadas" ...></div>
 *   <button type="button" data-quitar-pin>Quitar pin</button>
 */
(() => {
    // Plaza 24 de Septiembre: centro por defecto si todavía no hay pin
    const SANTA_CRUZ = [-17.7833, -63.1821];

    const icono = () => L.divIcon({
        className: 'pin-hotel',
        html: '<span></span>',
        iconSize: [34, 44],
        iconAnchor: [17, 42],
    });

    document.querySelectorAll('[data-mapa]').forEach((caja) => {
        if (!window.L) {
            caja.classList.add('mapa-sin-conexion');
            caja.textContent = 'No se pudo cargar el mapa. Revisa tu conexión a internet.';
            return;
        }

        const editable = 'editable' in caja.dataset;
        const tienePin = caja.dataset.lat !== '' && caja.dataset.lat !== undefined;
        const punto = tienePin ? [Number(caja.dataset.lat), Number(caja.dataset.lng)] : SANTA_CRUZ;

        const mapa = L.map(caja, { scrollWheelZoom: false }).setView(punto, tienePin ? 16 : 14);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        }).addTo(mapa);

        // Rueda del mouse solo después de hacer clic en el mapa (no estorba al bajar la página)
        mapa.on('click', () => mapa.scrollWheelZoom.enable());
        mapa.on('mouseout', () => mapa.scrollWheelZoom.disable());

        let pin = null;
        const crearPin = (ll) => {
            pin = L.marker(ll, {
                icon: icono(),
                draggable: editable,
                keyboard: true,
                title: caja.dataset.titulo || 'Ubicación del hotel',
            }).addTo(mapa);
            if (editable) pin.on('dragend', () => guardar(pin.getLatLng()));
        };

        if (tienePin) crearPin(punto);
        if (!editable) return;

        // ----- Solo en modo editable -----
        const inLat = document.getElementById(caja.dataset.inputLat);
        const inLng = document.getElementById(caja.dataset.inputLng);
        const texto = caja.dataset.texto ? document.querySelector(caja.dataset.texto) : null;

        const mostrar = () => {
            if (!texto) return;
            texto.textContent = inLat.value
                ? `Pin en ${inLat.value}, ${inLng.value}`
                : 'Sin pin: haz clic en el mapa para marcar el hotel.';
        };

        function guardar(ll) {
            const lat = ll.lat.toFixed(6);
            const lng = ll.lng.toFixed(6);
            if (pin) pin.setLatLng([lat, lng]); else crearPin([lat, lng]);
            inLat.value = lat;
            inLng.value = lng;
            mostrar();
        }

        mapa.on('click', (e) => guardar(e.latlng));

        document.querySelector('[data-quitar-pin]')?.addEventListener('click', () => {
            if (pin) { mapa.removeLayer(pin); pin = null; }
            inLat.value = '';
            inLng.value = '';
            mostrar();
        });

        mostrar();
    });
})();
