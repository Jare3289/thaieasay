/* Service Worker สำหรับ PWA ระบบประเมินเรียงความ
   กลยุทธ์แบบระมัดระวัง: แคชเฉพาะไฟล์สแตติก (ไอคอน/CSS) เท่านั้น
   ไม่แคชไฟล์ .php หรือ api.php เพื่อไม่ให้ข้อมูล session/คะแนน ค้างเก่า */
const CACHE = 'teg-static-v1';
const STATIC_ASSETS = [
  'index.css',
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
  // ข้ามคำขอข้ามโดเมน และคำขอที่เป็น PHP/พลวัต — ให้ผ่านเครือข่ายตรง ๆ เสมอ
  if (url.origin !== self.location.origin) return;
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
