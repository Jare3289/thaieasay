/* Service Worker สำหรับ PWA ระบบประเมินเรียงความ
   กลยุทธ์แบบระมัดระวัง: แคชเฉพาะไฟล์สแตติก (ไอคอน/CSS) เท่านั้น
   ไม่แคชไฟล์ .php หรือ api.php เพื่อไม่ให้ข้อมูล session/คะแนน ค้างเก่า
   สำหรับการเปิดหน้า (navigation) ขณะออฟไลน์ จะแสดงหน้า offline.html แทน */
const CACHE = 'teg-static-v2';
const STATIC_ASSETS = [
  'index.css',
  'offline.html',
  'icons/icon-192.png',
  'icons/icon-512.png',
  'icons/maskable-192.png',
  'icons/maskable-512.png',
  'manifest.json'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE).then((c) => c.addAll(STATIC_ASSETS)).catch(() => {})
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
    )
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;

  const url = new URL(req.url);
  // ข้ามคำขอข้ามโดเมนเสมอ — ให้ผ่านเครือข่ายตรง ๆ
  if (url.origin !== self.location.origin) return;

  // การเปิดหน้า (นำทาง) เช่น index.php, dashboard.php ฯลฯ:
  // ไปที่เครือข่ายก่อนเสมอ (ข้อมูล session/คะแนนต้องเป็นปัจจุบัน)
  // แต่ถ้าออฟไลน์/เครือข่ายล้มเหลว ให้ตกไปที่หน้า offline.html แทนหน้า error ของเบราว์เซอร์
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req).catch(() => caches.match('offline.html'))
    );
    return;
  }

  // ไฟล์ .php อื่น ๆ (เช่น api.php) หรือคำขอที่เป็นพลวัต — ให้ผ่านเครือข่ายตรง ๆ เสมอ ไม่แคช
  if (url.pathname.endsWith('.php') || url.search.includes('action=')) return;

  // ไฟล์สแตติก: cache-first แล้วอัปเดตพื้นหลัง
  event.respondWith(
    caches.match(req).then((cached) => {
      const fetchPromise = fetch(req)
        .then((res) => {
          if (res && res.status === 200 && res.type === 'basic') {
            const copy = res.clone();
            caches.open(CACHE).then((c) => c.put(req, copy)).catch(() => {});
          }
          return res;
        })
        .catch(() => cached);
      return cached || fetchPromise;
    })
  );
});
