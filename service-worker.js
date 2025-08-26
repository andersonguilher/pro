const CACHE_NAME = "fiscaliza-v1";
const urlsToCache = [
  "/pro/",
  "/pro/index.html",
  "/pro/pages/home.html",
  "/pro/pages/vistoria_1746.html",
  "/pro/pages/vistoria_local.html",
  "/pro/js/login.js",
  "/pro/js/vistoria1746.js",
  "/pro/js/vistoriaLocal.js",
  "/pro/js/compressImage.js",
  "/pro/js/offlineStorage.js",
  "/pro/assets/icons/icon-192.png",
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
