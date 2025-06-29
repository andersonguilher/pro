const CACHE_NAME = "fiscaliza-v1";
const urlsToCache = [
  "/fiscaliza-pwa/",
  "/fiscaliza-pwa/index.html",
  "/fiscaliza-pwa/pages/home.html",
  "/fiscaliza-pwa/pages/vistoria_1746.html",
  "/fiscaliza-pwa/pages/vistoria_local.html",
  "/fiscaliza-pwa/js/login.js",
  "/fiscaliza-pwa/js/vistoria1746.js",
  "/fiscaliza-pwa/js/vistoriaLocal.js",
  "/fiscaliza-pwa/js/compressImage.js",
  "/fiscaliza-pwa/js/offlineStorage.js",
  "/fiscaliza-pwa/assets/icons/icon-192.png",
  "https://cdn.tailwindcss.com",
  "https://unpkg.com/leaflet@1.9.4/dist/leaflet.css",
  "https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
];

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(urlsToCache))
  );
});

self.addEventListener("fetch", (event) => {
  event.respondWith(
    caches.match(event.request).then(response => response || fetch(event.request))
  );
});
