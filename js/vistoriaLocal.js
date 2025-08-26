let latitude = null;
let longitude = null;
let map, marker;

document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("formVistoria");
  const status = document.getElementById("msgStatus");

  // Inicializa mapa
  iniciarMapa();

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    status.classList.add("hidden");

    if (!latitude || !longitude) {
      status.textContent = "Erro: localização não capturada.";
      status.classList.remove("hidden");
      status.classList.add("text-red-400");
      return;
    }

    const formData = new FormData(form);
    formData.append("protocolo", gerarProtocolo());
    formData.append("latitude", latitude);
    formData.append("longitude", longitude);

    const fotos = document.getElementById("fotos").files;
    for (let i = 0; i < fotos.length && i < 4; i++) {
      formData.append("fotos[]", fotos[i]);
    }

    const res = await fetch("../php/salvar_vistoria_local.php", {
      method: "POST",
      body: formData
    });

    const json = await res.json();
    if (json.sucesso) {
      status.textContent = "Vistoria enviada com sucesso!";
      status.classList.remove("hidden", "text-red-400");
      status.classList.add("text-green-400");
      form.reset();
    } else {
      status.textContent = "Erro ao enviar vistoria.";
      status.classList.remove("hidden");
      status.classList.add("text-red-400");
    }
  });
});

// Gera protocolo tipo F26062025151620
function gerarProtocolo() {
  const d = new Date();
  const pad = (v) => String(v).padStart(2, "0");
  return `F${pad(d.getDate())}${pad(d.getMonth() + 1)}${d.getFullYear()}${pad(d.getHours())}${pad(d.getMinutes())}${pad(d.getSeconds())}`;
}

// Inicia o mapa com marcador
function iniciarMapa() {
  map = L.map("map").setView([-22.9, -43.6], 12);
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "Map data © OpenStreetMap contributors"
  }).addTo(map);

  marker = L.marker([0, 0], { draggable: true }).addTo(map);
  marker.on("dragend", () => {
    const pos = marker.getLatLng();
    latitude = pos.lat;
    longitude = pos.lng;
  });

  // Tenta GPS
  navigator.geolocation.getCurrentPosition((pos) => {
    latitude = pos.coords.latitude;
    longitude = pos.coords.longitude;
    marker.setLatLng([latitude, longitude]);
    map.setView([latitude, longitude], 17);
  }, () => {
    latitude = -22.9;
    longitude = -43.6;
    marker.setLatLng([latitude, longitude]);
    map.setView([latitude, longitude], 12);
  });
}
