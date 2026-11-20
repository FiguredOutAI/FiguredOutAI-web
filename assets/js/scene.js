import * as THREE from '../vendor/three.module.min.js';

/*
 * Hero scene. The brand's two planet rims frame the viewport corners (cyan top left,
 * orange bottom right) and the 3D S mark is pinned to #logoAnchor, a slot in the hero
 * layout behind the inbox preview, so it moves with the page instead of floating free.
 * The canvas fades out once the hero has scrolled away and stops drawing.
 */
const canvas = document.getElementById('scene');
const anchor = document.getElementById('logoAnchor');
const hero = document.querySelector('.hero');
const mobile = matchMedia('(max-width: 820px)').matches;
const lite = mobile || (navigator.hardwareConcurrency || 8) <= 4 || (navigator.deviceMemory || 8) <= 4;
const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;

let renderer;
try { renderer = new THREE.WebGLRenderer({ canvas, antialias: !lite, alpha: false, powerPreference: 'high-performance' }); }
catch (err) { canvas.remove(); throw err; }
renderer.setPixelRatio(Math.min(devicePixelRatio, lite ? 1.25 : 1.75));
renderer.setClearColor(0x05070A, 1);

const scene = new THREE.Scene();
const FOV = 40, CAM_Z = 12;
const camera = new THREE.PerspectiveCamera(FOV, 1, 0.1, 200);
camera.position.set(0, 0, CAM_Z);
const halfTan = Math.tan(THREE.MathUtils.degToRad(FOV / 2));
const visH = z => 2 * (CAM_Z - z) * halfTan; // visible height of the plane at depth z

/* ---------- planets: thin glowing rims, as in the brand art ---------- */
const rimVS = `varying vec3 vN; varying vec3 vV; varying vec3 vP;
void main(){ vec4 mv = modelViewMatrix*vec4(position,1.); vN = normalize(normalMatrix*normal); vV = normalize(-mv.xyz); vP = position; gl_Position = projectionMatrix*mv; }`;
const planetFS = `uniform vec3 uC; varying vec3 vN; varying vec3 vV; varying vec3 vP;
float h(vec3 p){ return fract(sin(dot(p, vec3(12.9898,78.233,37.719)))*43758.5453); }
void main(){ float f = 1. - max(dot(vN, vV), 0.);
  float rim = pow(f, 8.0)*1.3 + pow(f, 24.0)*2.2;
  float n = h(floor(vP*30.));
  gl_FragColor = vec4(uC*rim + uC*0.02*n*f, 1.); }`;
const atmoFS = `uniform vec3 uC; uniform float uI; varying vec3 vN; varying vec3 vV;
void main(){ float d = dot(vN, vV); float a = pow(clamp(0.62 + d, 0., 1.), 6.0)*uI; gl_FragColor = vec4(uC*a, a); }`;
function planet(color, intensity) {
  const g = new THREE.Group(), c = new THREE.Color(color), seg = lite ? 64 : 128;
  g.add(new THREE.Mesh(new THREE.SphereGeometry(1, seg, seg), new THREE.ShaderMaterial({ vertexShader: rimVS, fragmentShader: planetFS, uniforms: { uC: { value: c } } })));
  g.add(new THREE.Mesh(new THREE.SphereGeometry(1.1, seg * .75, seg * .75), new THREE.ShaderMaterial({ vertexShader: rimVS, fragmentShader: atmoFS,
    uniforms: { uC: { value: c }, uI: { value: intensity } }, side: THREE.BackSide, transparent: true, blending: THREE.AdditiveBlending, depthWrite: false })));
  scene.add(g);
  return g;
}
const PZ = -8;
const pCyan = planet(0x22E3F2, .9), pOrange = planet(0xFF7A2F, .85);

/* ---------- dust: a few quiet stars ---------- */
function dotTex() {
  const c = document.createElement('canvas'); c.width = c.height = 64; const x = c.getContext('2d');
  const g = x.createRadialGradient(32, 32, 0, 32, 32, 32); g.addColorStop(0, 'rgba(255,255,255,1)'); g.addColorStop(.3, 'rgba(255,255,255,.5)'); g.addColorStop(1, 'rgba(255,255,255,0)');
  x.fillStyle = g; x.fillRect(0, 0, 64, 64); return new THREE.CanvasTexture(c);
}
const tex = dotTex();
const N = lite ? 700 : 1800;
const pos = new Float32Array(N * 3), col = new Float32Array(N * 3);
const cA = new THREE.Color(0x22E3F2), cB = new THREE.Color(0xFF7A2F), cW = new THREE.Color(0xBFC8D2);
for (let i = 0; i < N; i++) {
  const x = (Math.random() - .5) * 46, y = (Math.random() - .5) * 30, z = -Math.random() * 20 + 2;
  pos.set([x, y, z], i * 3);
  const t = (x - y * .8) / 40 + .5;
  const c = (Math.random() < .65 ? cW : (t < .5 ? cA : cB)).clone().multiplyScalar(.25 + Math.random() * .5);
  col.set([c.r, c.g, c.b], i * 3);
}
const dustGeo = new THREE.BufferGeometry();
dustGeo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
dustGeo.setAttribute('color', new THREE.BufferAttribute(col, 3));
const dust = new THREE.Points(dustGeo, new THREE.PointsMaterial({ size: .06, map: tex, vertexColors: true, transparent: true, depthWrite: false, blending: THREE.AdditiveBlending }));
scene.add(dust);

/* ---------- the S mark as a lit 3D tube ---------- */
const P = (x, y) => new THREE.Vector3((x - 201) / 100, -(y - 182) / 100, 0);
const segs = [
  ['L', 302, 130, 375, 96], ['Q', 375, 96, 398, 88, 398, 110], ['L', 398, 110, 398, 215], ['Q', 398, 215, 398, 232, 380, 242],
  ['L', 380, 242, 148, 356], ['Q', 148, 356, 125, 366, 125, 345], ['L', 125, 345, 125, 248], ['Q', 125, 248, 125, 228, 103, 236],
  ['L', 103, 236, 28, 268], ['Q', 28, 268, 5, 276, 5, 255], ['L', 5, 255, 5, 152], ['Q', 5, 152, 5, 130, 25, 120],
  ['L', 25, 120, 258, 8], ['Q', 258, 8, 280, -2, 280, 22], ['L', 280, 22, 280, 128], ['Q', 280, 128, 280, 145, 262, 153], ['L', 262, 153, 140, 212]];
const path = new THREE.CurvePath();
for (const s of segs) {
  if (s[0] === 'L') path.add(new THREE.LineCurve3(P(s[1], s[2]), P(s[3], s[4])));
  else path.add(new THREE.QuadraticBezierCurve3(P(s[1], s[2]), P(s[3], s[4]), P(s[5], s[6])));
}
const LOGO_W = 3.93; // width of the mark in scene units at scale 1

scene.add(new THREE.AmbientLight(0xffffff, .55));
const key = new THREE.DirectionalLight(0xffffff, 1.6); key.position.set(-4, 5, 8); scene.add(key);
const fill = new THREE.DirectionalLight(0xFF7A2F, .55); fill.position.set(6, -5, 3); scene.add(fill);

const logo = new THREE.Group();
const tubes = [];
const tube = (r, mat, z = 0, segments = 640) => {
  const m = new THREE.Mesh(new THREE.TubeGeometry(path, segments, r, 20, false), mat);
  m.position.z = z; logo.add(m); tubes.push(m); return m;
};
// front face, a dimmer back face for depth, and one soft halo
tube(.075, new THREE.MeshStandardMaterial({ color: 0x9FF4FA, emissive: 0x22E3F2, emissiveIntensity: .75, roughness: .28, metalness: .15 }));
tube(.06, new THREE.MeshStandardMaterial({ color: 0x0E8C99, emissive: 0x0E8C99, emissiveIntensity: .35, roughness: .5, metalness: .1, transparent: true, opacity: .55 }), -.42, 400);
tube(.2, new THREE.MeshBasicMaterial({ color: 0x22E3F2, transparent: true, opacity: .08, blending: THREE.AdditiveBlending, depthWrite: false }), 0, 400);
// one message travelling through the mark
const pulse = new THREE.Sprite(new THREE.SpriteMaterial({ map: tex, color: 0xffffff, blending: THREE.AdditiveBlending, transparent: true, depthWrite: false, opacity: 0 }));
pulse.scale.set(.5, .5, 1); logo.add(pulse);
const totals = tubes.map(t => t.geometry.index.count);
logo.visible = !!anchor;
scene.add(logo);

/* ---------- layout ---------- */
let W = 0, H = 0;
function layout() {
  W = innerWidth; H = innerHeight;
  renderer.setSize(W, H, false);
  camera.aspect = W / H; camera.updateProjectionMatrix();
  const h = visH(PZ), w = h * camera.aspect;
  const r1 = h * (W < 700 ? .38 : .5), r2 = h * (W < 700 ? .46 : .62);
  pCyan.scale.setScalar(r1); pCyan.userData.base = new THREE.Vector3(-w / 2 - r1 * .2, h / 2 + r1 * .35, PZ);
  pOrange.scale.setScalar(r2); pOrange.userData.base = new THREE.Vector3(w / 2 + r2 * .12, -h / 2 - r2 * .48, PZ);
}
layout(); addEventListener('resize', layout);

// place the mark over the anchor element, every frame (the anchor scrolls with the page)
function placeLogo() {
  if (!anchor) return;
  const r = anchor.getBoundingClientRect();
  if (!r.width) { logo.visible = false; return; }
  logo.visible = true;
  const h0 = visH(0), w0 = h0 * camera.aspect;
  logo.userData.x = ((r.left + r.width / 2) / W - .5) * w0;
  logo.userData.y = (.5 - (r.top + r.height / 2) / H) * h0;
  logo.userData.s = (r.width / W) * w0 / LOGO_W;
}

/* ---------- interaction ---------- */
let mx = 0, my = 0, tx = 0, ty = 0;
addEventListener('pointermove', e => { tx = e.clientX / W * 2 - 1; ty = e.clientY / H * 2 - 1; }, { passive: true });

const ease = t => 1 - Math.pow(1 - t, 3);
const clock = new THREE.Clock();
let frame = 0, lastOp = '', running = true;
function tick() {
  const t = clock.getElapsedTime();
  mx += (tx - mx) * .05; my += (ty - my) * .05;

  // the hero's scroll progress drives the fade, nothing else moves on scroll
  const hb = hero ? hero.getBoundingClientRect() : { bottom: H };
  const gone = Math.min(1, Math.max(0, 1 - hb.bottom / H)); // 0 while the hero fills the screen, 1 once it has left
  const intro = reduce ? 1 : ease(Math.min(1, t / 1.6));
  const op = (Math.min(1, t / .6) * (1 - gone)).toFixed(3);
  if (op !== lastOp) { canvas.style.opacity = op; lastOp = op; }

  // planets drift a touch with the pointer
  for (const [p, k] of [[pCyan, .5], [pOrange, -.4]]) {
    const b = p.userData.base;
    p.position.set(b.x + mx * k * .6, b.y - my * .3 + gone * 2, b.z);
    p.rotation.y = t * .015 * Math.sign(k);
  }
  dust.rotation.y = t * .006 + mx * .03;

  // the mark: draws itself in, then tilts toward the pointer
  placeLogo();
  if (logo.visible) {
    const u = logo.userData;
    logo.position.set(u.x, u.y + (reduce ? 0 : Math.sin(t * .7) * .04 * u.s), 0);
    logo.scale.setScalar(u.s * (.94 + .06 * intro));
    logo.rotation.y = (reduce ? 0 : Math.sin(t * .3) * .12) + mx * .32 - .18;
    logo.rotation.x = -my * .16 + .06;
    tubes.forEach((m, i) => m.geometry.setDrawRange(0, Math.floor(totals[i] * intro / 6) * 6));
    if (!reduce && intro >= 1) {
      const k = (t * .09) % 1;
      pulse.position.copy(path.getPointAt(k)); pulse.position.z = .08;
      pulse.material.opacity = Math.min(1, (t - 1.6) * 1.5) * .9;
    }
  }

  frame++;
  if (+op > 0.01 && (!lite || (frame & 1) === 0)) renderer.render(scene, camera);
  if (!document.hidden) requestAnimationFrame(tick); else running = false;
}
document.addEventListener('visibilitychange', () => { if (!document.hidden && !running) { running = true; requestAnimationFrame(tick); } });
tick();
window.__sceneReady = true;
