// ES Module overlay 3D para el coche, usando THREE como módulo (SIN ruta simulada)
import * as THREE from 'three';
import { GLTFLoader } from 'https://unpkg.com/three@0.159.0/examples/jsm/loaders/GLTFLoader.js';
import { DRACOLoader } from 'https://unpkg.com/three@0.159.0/examples/jsm/loaders/DRACOLoader.js';

const map = window._leafletMap;
console.log('[car-overlay] módulo inicializado');
if (!map) {
  console.warn('Leaflet map no disponible para overlay 3D');
} else {
  const parent = document.querySelector('.map');
  const overlay = document.createElement('div');
  overlay.id = 'three-overlay';
  parent.appendChild(overlay);

  // Tamaño compacto tipo Uber
  const W = 140, H = 140;
  const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
  renderer.setSize(W, H);
  overlay.appendChild(renderer.domElement);

  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(35, W / H, 0.1, 1000);
  camera.position.set(0, 2.2, 4.5);
  camera.lookAt(0, 0, 0);

  const hemi = new THREE.HemisphereLight(0xffffff, 0x8899aa, 0.9); scene.add(hemi);
  const dir  = new THREE.DirectionalLight(0xffffff, 0.85); dir.position.set(2, 4, 2); scene.add(dir);

  const carGroup = new THREE.Group();    // contenedor base
  scene.add(carGroup);
  const modelGroup = new THREE.Group();  // grupo del modelo (bobbing)
  carGroup.add(modelGroup);

  // Halo/sombra bajo el coche
  const floor = new THREE.Mesh(
    new THREE.CircleGeometry(0.55, 32),
    new THREE.MeshBasicMaterial({ color: 0x000000, transparent: true, opacity: 0.12 })
  );
  floor.rotation.x = -Math.PI / 2;
  floor.position.y = -0.6;
  carGroup.add(floor);

  // Estado de carga de modelo
  let carReady = false;

  const loader = new GLTFLoader();
  const draco = new DRACOLoader();
  draco.setDecoderPath('https://unpkg.com/three@0.159.0/examples/jsm/libs/draco/');
  loader.setDRACOLoader(draco);

  const glbUrl = new URL('model/carro.glb', window.location.href).href;
  loader.load(glbUrl, (gltf) => {
    const car = gltf.scene;

    // Autoscale según bounding box
    const box1 = new THREE.Box3().setFromObject(car);
    const size1 = box1.getSize(new THREE.Vector3());
    const maxDim = Math.max(size1.x, size1.y, size1.z) || 1;
    const target = 2.2;
    const k = target / maxDim;
    car.scale.setScalar(k);

    // Recalcula BB y centra al origen
    const box2 = new THREE.Box3().setFromObject(car);
    const center = box2.getCenter(new THREE.Vector3());
    car.position.sub(center);

    // Frente del modelo apuntando “hacia arriba” por defecto
    car.rotation.y = Math.PI;

    modelGroup.add(car);
    car.updateMatrixWorld(true);
    carReady = true;
  }, undefined, (err) => console.warn('No se pudo cargar carro.glb', err));

  // ======= SIN SIMULACIÓN: seguimos la posición real =======

  // marcador 2D (opcional)
  let carMarker = null;

  // Suavizado de posición/orientación
  let last = null;      // { lat, lng, yawRad }
  let target = null;    // { lat, lng, yawDeg } desde window._liveTarget
  let lastT = performance.now();
  let followAcc = 0;

  function toRad(d) { return d * Math.PI / 180; }
  function toDeg(r) { return r * 180 / Math.PI; }

  function computeBearingDeg(a, b) {
    const lat1 = toRad(a.lat), lon1 = toRad(a.lng);
    const lat2 = toRad(b.lat), lon2 = toRad(b.lng);
    const dLon = lon2 - lon1;
    let brng = Math.atan2(
      Math.sin(dLon) * Math.cos(lat2),
      Math.cos(lat1) * Math.sin(lat2) - Math.sin(lat1) * Math.cos(lat2) * Math.cos(dLon)
    );
    brng = (toDeg(brng) + 360) % 360;
    return brng;
  }

  function positionOverlay(lat, lng) {
    const p = map.latLngToContainerPoint([lat, lng]);
    const x = p.x - W / 2, y = p.y - H / 2;
    const dom = renderer.domElement;
    dom.style.position = 'absolute';
    dom.style.left = `${x}px`;
    dom.style.top  = `${y}px`;

    // Escala ligera por zoom (se ve pequeño a z bajos, mayor a z altos)
    const z = map.getZoom();
    const s = Math.max(0.55, Math.min(0.95, 0.7 + 0.12 * (z - 18)));
    carGroup.scale.set(s, s, s);

    // Crear/actualizar marcador 2D cuando el GLB esté listo
    if (carReady) {
      if (!carMarker) {
        carMarker = L.circleMarker([lat, lng], {
          radius: 5, weight: 2, color: '#0B8FFF', fillColor: '#0B8FFF', fillOpacity: 0.9
        }).addTo(map);
      } else {
        carMarker.setLatLng([lat, lng]);
      }
    }
  }

  function render(now) {
    const dt = Math.min(0.05, (now - lastT) / 1000);
    lastT = now;

    // 1) Leer target externo (window._liveTarget), si existe
    const ext = window._liveTarget;
    if (ext && (isFinite(ext.lat) && isFinite(ext.lng))) {
      target = { lat: Number(ext.lat), lng: Number(ext.lng), yawDeg: Number(ext.yaw || 0) };
    }

    // Si aún no hay target, oculta overlay y espera
    if (!target) {
      renderer.domElement.style.display = 'none';
      requestAnimationFrame(render);
      return;
    } else {
      renderer.domElement.style.display = 'block';
    }

    // 2) Suavizar posición (lerp)
    if (!last) {
      last = { lat: target.lat, lng: target.lng, yawRad: toRad(target.yawDeg || 0) };
      // centrar mapa inicialmente
      try { map.setView([last.lat, last.lng], Math.max(18, map.getZoom() || 18), { animate: false }); } catch (_) {}
    } else {
      const alpha = 0.15; // suavizado pos
      last.lat = last.lat + (target.lat - last.lat) * alpha;
      last.lng = last.lng + (target.lng - last.lng) * alpha;
    }

    // 3) Bobbing vertical suave
    const freq = 1.2;      // Hz
    const omega = Math.PI * 2 * freq;
    const amp = 0.06;      // amplitud
    const bob = Math.sin(now / 1000 * omega) * amp;
    modelGroup.position.y = bob;
    modelGroup.rotation.x = 0;

    // 4) Orientación del coche (yaw)
    //    Usa yaw del server si viene; si no, calcula por delta last→target
    let yawDeg = isFinite(target.yawDeg) && target.yawDeg !== 0
      ? target.yawDeg
      : computeBearingDeg({ lat: last.lat, lng: last.lng }, { lat: target.lat, lng: target.lng });

    // Corrige frente del modelo (como antes)
    const FRONT_OFFSET = Math.PI; // 180°
    const targetYawRad = ((Math.PI - toRad(yawDeg)) + FRONT_OFFSET);

    // Suavizado yaw (filtro 1º orden)
    const tau = 0.25;  // constante de tiempo ~250ms
    const a = 1 - Math.exp(-dt / tau);
    if (!isFinite(last.yawRad)) last.yawRad = targetYawRad;
    // normalizar dif. de ángulo a [-PI, PI]
    let diff = (targetYawRad - last.yawRad + Math.PI) % (Math.PI * 2) - Math.PI;
    last.yawRad = last.yawRad + diff * a;

    carGroup.rotation.y = last.yawRad;

    // 5) Posicionar overlay y marcador
    positionOverlay(last.lat, last.lng);

    // 6) Auto-follow suave del mapa (no todo frame)
    followAcc += dt;
    if (followAcc > 0.3) {
      followAcc = 0;
      try { map.panTo([target.lat, target.lng], { animate: true, duration: 0.3 }); } catch (e) {}
    }

    renderer.render(scene, camera);
    requestAnimationFrame(render);
  }
  requestAnimationFrame(render);
}
