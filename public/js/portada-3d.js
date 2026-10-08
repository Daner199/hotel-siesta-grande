/**
 * Escena 3D de la portada: pétalos de tajibo que caen despacio y luciérnagas doradas.
 * DOS capas para profundidad real con el título en medio:
 *   - fondo:  muchos pétalos lejanos + luciérnagas (DETRÁS del título)
 *   - frente: pocos pétalos grandes y cercanos que pasan DELANTE del título
 * Ambas capas usan la misma cámara, así el movimiento del mouse las desplaza distinto (paralaje).
 * La carga landing.js solo si hay WebGL y no se pidió "reducir movimiento".
 * Se pausa cuando la portada no se ve o la pestaña está oculta.
 */
import * as THREE from 'https://cdn.jsdelivr.net/npm/three@0.170.0/build/three.module.min.js';

const COLORES_TAJIBO = ['#C4507A', '#D86A94', '#E68DAD', '#B23F6A', '#F3C4D5', '#CF5E88'];

// Pétalo: gota redondeada, apenas curvada para que la luz le dé volumen
function geometriaPetalo() {
    const forma = new THREE.Shape();
    forma.moveTo(0, 0);
    forma.bezierCurveTo(0.55, 0.25, 0.62, 1.0, 0, 1.45);
    forma.bezierCurveTo(-0.62, 1.0, -0.55, 0.25, 0, 0);

    const geo = new THREE.ShapeGeometry(forma, 14);
    geo.translate(0, -0.7, 0);

    const pos = geo.attributes.position;
    for (let i = 0; i < pos.count; i++) {
        const x = pos.getX(i);
        const y = pos.getY(i);
        pos.setZ(i, x * x * 0.35 - y * 0.08);
    }
    geo.computeVertexNormals();
    geo.scale(0.32, 0.32, 0.32);
    return geo;
}

// Luz cálida de tarde (cada escena necesita sus propias luces)
function agregarLuces(escena) {
    escena.add(new THREE.AmbientLight(0xffffff, 1.1));
    const sol = new THREE.DirectionalLight(0xffe2c4, 1.6);
    sol.position.set(6, 8, 6);
    escena.add(sol);
    const brillo = new THREE.PointLight(0xc99a3b, 18, 30);
    brillo.position.set(-6, -3, 4);
    escena.add(brillo);
}

/**
 * Pétalos instanciados (una sola malla por capa: muy liviano).
 * zMin/zMax: profundidad; escala: [mín, máx]; ancho/alto: zona donde caen.
 */
function crearPetalos({ cantidad, zMin, zMax, escala, ancho, alto, opacidad }) {
    const malla = new THREE.InstancedMesh(
        geometriaPetalo(),
        new THREE.MeshLambertMaterial({ side: THREE.DoubleSide, transparent: true, opacity: opacidad }),
        cantidad,
    );

    const color = new THREE.Color();
    const datos = [];
    for (let i = 0; i < cantidad; i++) {
        color.set(COLORES_TAJIBO[i % COLORES_TAJIBO.length]);
        malla.setColorAt(i, color);
        datos.push({
            x: (Math.random() - 0.5) * ancho,
            y: (Math.random() - 0.5) * alto,
            z: zMin + Math.random() * (zMax - zMin),
            caida: 0.35 + Math.random() * 0.55,
            vaiven: 0.4 + Math.random() * 0.9,
            frecuencia: 0.3 + Math.random() * 0.6,
            fase: Math.random() * Math.PI * 2,
            giro: new THREE.Vector3(Math.random() - 0.5, Math.random() - 0.5, Math.random() - 0.5).multiplyScalar(1.6),
            rotacion: new THREE.Euler(Math.random() * 6, Math.random() * 6, Math.random() * 6),
            escala: escala[0] + Math.random() * (escala[1] - escala[0]),
        });
    }

    return { malla, datos, ancho, alto };
}

// Mueve los pétalos un paso y actualiza la malla
const auxiliar = new THREE.Object3D();
function moverPetalos(capa, dt, t) {
    const limite = capa.alto / 2 + 1;
    capa.datos.forEach((p, i) => {
        p.y -= p.caida * dt;
        p.x += Math.sin(t * p.frecuencia + p.fase) * p.vaiven * dt;
        p.rotacion.x += p.giro.x * dt;
        p.rotacion.y += p.giro.y * dt;
        p.rotacion.z += p.giro.z * dt;

        // Al salir por abajo vuelve a caer desde arriba
        if (p.y < -limite) {
            p.y = limite + Math.random() * 2;
            p.x = (Math.random() - 0.5) * capa.ancho;
        }

        auxiliar.position.set(p.x, p.y, p.z);
        auxiliar.rotation.copy(p.rotacion);
        auxiliar.scale.setScalar(p.escala);
        auxiliar.updateMatrix();
        capa.malla.setMatrixAt(i, auxiliar.matrix);
    });
    capa.malla.instanceMatrix.needsUpdate = true;
}

// Luciérnagas: puntos con brillo suave que titilan (shader propio, muy liviano)
function crearLuciernagas(cantidad, pixelRatio) {
    const posiciones = new Float32Array(cantidad * 3);
    const fases = new Float32Array(cantidad);
    const tamanos = new Float32Array(cantidad);

    for (let i = 0; i < cantidad; i++) {
        posiciones[i * 3]     = (Math.random() - 0.5) * 26;
        posiciones[i * 3 + 1] = (Math.random() - 0.5) * 16;
        posiciones[i * 3 + 2] = (Math.random() - 0.5) * 10 - 1;
        fases[i] = Math.random() * Math.PI * 2;
        tamanos[i] = 0.6 + Math.random() * 1.2;
    }

    const geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.BufferAttribute(posiciones, 3));
    geo.setAttribute('fase', new THREE.BufferAttribute(fases, 1));
    geo.setAttribute('tamano', new THREE.BufferAttribute(tamanos, 1));

    const material = new THREE.ShaderMaterial({
        transparent: true,
        depthWrite: false,
        blending: THREE.AdditiveBlending,
        uniforms: {
            uTiempo: { value: 0 },
            uPixel: { value: pixelRatio },
            uColor: { value: new THREE.Color('#F2CF7E') },
        },
        vertexShader: /* glsl */ `
            uniform float uTiempo;
            uniform float uPixel;
            attribute float fase;
            attribute float tamano;
            varying float vBrillo;
            void main() {
                vec3 p = position;
                p.x += sin(uTiempo * 0.25 + fase) * 0.9;
                p.y += cos(uTiempo * 0.2 + fase * 1.3) * 0.6;
                p.z += sin(uTiempo * 0.15 + fase * 0.7) * 0.5;
                vec4 mv = modelViewMatrix * vec4(p, 1.0);
                gl_Position = projectionMatrix * mv;
                gl_PointSize = tamano * 34.0 * uPixel / -mv.z;
                vBrillo = 0.35 + 0.65 * pow(0.5 + 0.5 * sin(uTiempo * 1.6 + fase * 3.0), 2.0);
            }
        `,
        fragmentShader: /* glsl */ `
            uniform vec3 uColor;
            varying float vBrillo;
            void main() {
                float d = length(gl_PointCoord - 0.5);
                float halo = smoothstep(0.5, 0.0, d);
                float nucleo = smoothstep(0.12, 0.0, d);
                gl_FragColor = vec4(uColor + nucleo * 0.4, (halo * 0.55 + nucleo) * vBrillo);
            }
        `,
    });

    return new THREE.Points(geo, material);
}

function crearRenderer(lienzo, pixelRatio) {
    const renderer = new THREE.WebGLRenderer({
        canvas: lienzo,
        alpha: true,
        antialias: true,
        powerPreference: 'low-power',
    });
    renderer.setPixelRatio(pixelRatio);
    renderer.setClearColor(0x000000, 0);
    return renderer;
}

export function iniciar(lienzoFondo, lienzoFrente) {
    const portada = lienzoFondo.closest('[data-portada]');
    const angosta = window.innerWidth < 720;
    const pixelRatio = Math.min(window.devicePixelRatio || 1, 1.75);

    const camara = new THREE.PerspectiveCamera(50, 1, 0.1, 60);
    camara.position.set(0, 0, 14);

    // ----- Capa de fondo: detrás del título -----
    const escenaFondo = new THREE.Scene();
    escenaFondo.fog = new THREE.Fog(0x0e241b, 10, 26);
    agregarLuces(escenaFondo);
    const fondo = crearPetalos({
        cantidad: angosta ? 50 : 100,
        zMin: -7, zMax: 1.5,
        escala: [0.7, 1.4],
        ancho: 26, alto: 18,
        opacidad: 0.9,
    });
    escenaFondo.add(fondo.malla);
    const luciernagas = crearLuciernagas(angosta ? 40 : 75, pixelRatio);
    escenaFondo.add(luciernagas);
    const rendererFondo = crearRenderer(lienzoFondo, pixelRatio);

    // ----- Capa de frente: pasa delante del título (pocos, grandes y lentos) -----
    let frente = null;
    let escenaFrente = null;
    let rendererFrente = null;
    if (lienzoFrente) {
        escenaFrente = new THREE.Scene();
        agregarLuces(escenaFrente);
        frente = crearPetalos({
            cantidad: angosta ? 7 : 14,
            zMin: 5, zMax: 9,
            escala: [1.1, 1.8],
            ancho: 14, alto: 10,
            opacidad: 0.95,
        });
        frente.datos.forEach((p) => { p.caida *= 0.75; });
        escenaFrente.add(frente.malla);
        rendererFrente = crearRenderer(lienzoFrente, pixelRatio);
    }

    // Posición inicial (antes del primer cuadro)
    moverPetalos(fondo, 0, 0);
    if (frente) moverPetalos(frente, 0, 0);

    // ----- Tamaño -----
    const ajustar = () => {
        const ancho = lienzoFondo.clientWidth;
        const alto = lienzoFondo.clientHeight;
        if (!ancho || !alto) return;
        rendererFondo.setSize(ancho, alto, false);
        rendererFrente?.setSize(ancho, alto, false);
        camara.aspect = ancho / alto;
        camara.updateProjectionMatrix();
    };
    ajustar();
    new ResizeObserver(ajustar).observe(lienzoFondo);

    // ----- Paralaje suave con el mouse -----
    const mouse = { x: 0, y: 0 };
    window.addEventListener('pointermove', (e) => {
        mouse.x = (e.clientX / window.innerWidth - 0.5) * 2;
        mouse.y = (e.clientY / window.innerHeight - 0.5) * 2;
    }, { passive: true });

    // ----- Animación (se pausa si no se ve) -----
    const reloj = new THREE.Clock();
    let visible = true;
    let corriendo = false;

    const dibujar = () => {
        rendererFondo.render(escenaFondo, camara);
        if (rendererFrente) rendererFrente.render(escenaFrente, camara);
    };

    const cuadro = () => {
        if (!visible || document.hidden) {
            corriendo = false;
            return;
        }
        corriendo = true;

        const dt = Math.min(reloj.getDelta(), 0.05);
        const t = reloj.elapsedTime;

        moverPetalos(fondo, dt, t);
        if (frente) moverPetalos(frente, dt, t);
        luciernagas.material.uniforms.uTiempo.value = t;

        camara.position.x += (mouse.x * 0.9 - camara.position.x) * 0.03;
        camara.position.y += (-mouse.y * 0.6 - camara.position.y) * 0.03;
        camara.lookAt(0, 0, 0);

        dibujar();
        requestAnimationFrame(cuadro);
    };

    const arrancar = () => {
        if (corriendo || !visible || document.hidden) return;
        reloj.getDelta(); // evita un salto después de la pausa
        requestAnimationFrame(cuadro);
    };

    new IntersectionObserver(([entrada]) => {
        visible = entrada.isIntersecting;
        arrancar();
    }).observe(portada || lienzoFondo);

    document.addEventListener('visibilitychange', arrancar);

    dibujar();
    portada?.classList.add('con-3d');
    arrancar();
}
