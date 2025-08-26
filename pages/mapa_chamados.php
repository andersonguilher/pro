<?php
session_start();
require_once '../php/conexao.php';

$chamados = [];

$sql = "SELECT id_chamado, subtipo, logradouro, numero, bairro, geoloc, data_sla, ds_chamado, complemento, referencia 
        FROM dados1746 
        WHERE status IN ('Aberto', 'Em andamento') 
          AND setor = 'Fiscal'";

$res = $conn->query($sql);

while ($row = $res->fetch_assoc()) {
    $lat = null;
    $lng = null;

    if (!empty($row["geoloc"]) && strpos($row["geoloc"], ",") !== false) {
        [$lat, $lng] = array_map('floatval', explode(",", $row["geoloc"]));
    } else {
        $logradouro = $row["logradouro"];
        $bairro = $row["bairro"];
        $numero = $row["numero"];

        $usarNumero = in_array($logradouro, [
            "R. Felipe Cardoso", "Av. João XXIII", "Av. Areia Branca"
        ]);

        if ($usarNumero) {
            $stmt = $conn->prepare("SELECT latitude, longitude FROM geolocalizacao WHERE logradouro = ? AND numero = ? AND bairro = ? AND status = 'ok' LIMIT 1");
            $stmt->bind_param("sis", $logradouro, $numero, $bairro);
        } else {
            $stmt = $conn->prepare("SELECT latitude, longitude FROM geolocalizacao WHERE logradouro = ? AND bairro = ? AND status = 'ok' LIMIT 1");
            $stmt->bind_param("ss", $logradouro, $bairro);
        }

        $stmt->execute();
        $geo = $stmt->get_result()->fetch_assoc();
        if ($geo) {
            $lat = $geo["latitude"];
            $lng = $geo["longitude"];
        }
    }

    if ($lat && $lng) {
          $vistoria = $conn->prepare("SELECT foto1, foto2, foto3, foto4 FROM demandas WHERE protocolo = ?");
          $vistoria->bind_param("i", $row["id_chamado"]);
          $vistoria->execute();
          $fotos = $vistoria->get_result()->fetch_assoc();

          $chamados[] = [
              "id" => $row["id_chamado"],
              "subtipo" => $row["subtipo"],
              "endereco" => "{$row['logradouro']}, {$row['numero']} - {$row['bairro']}",
              "complemento" => $row["complemento"],
              "referencia" => $row["referencia"],
              "lat" => $lat,
              "lng" => $lng,
              "data_sla" => $row["data_sla"],
              "descricao" => $row["ds_chamado"],
              "fotos" => $fotos ?: [],
          ];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Mapa de Chamados</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="icon" href="../assets/icons/icon-192.png">
  <link rel="manifest" href="../manifest.json">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="ConserVapp">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    html, body {
      height: 100%; margin: 0;
      background-color: #0f172a;
      color: white;
    }
    #map { height: 100vh; width: 100%; }
    .leaflet-popup-content-wrapper {
      background-color: #1e293b;
      color: white;
      border-radius: 10px;
      padding: 8px;
    }
    .leaflet-popup-tip { background: #1e293b; }
    #legenda {
      display: none;
      position: absolute;
      bottom: 60px;
      left: 10px;
      z-index: 1000;
      background-color: rgba(15, 23, 42, 0.85);
      color: white;
      padding: 10px;
      border-radius: 10px;
      max-height: 40vh;
      overflow-y: auto;
      font-size: 0.85rem;
    }
    .legenda-item {
      display: flex;
      align-items: center;
      margin-bottom: 4px;
    }
    .cor-bolinha {
      width: 14px;
      height: 14px;
      border-radius: 50%;
      margin-right: 8px;
    }
    #menuOptions {
      display: none;
      position: absolute;
      top: 60px;
      right: 10px;
      z-index: 1001;
      background: #1e293b;
      color: white;
      padding: 16px;
      border-radius: 12px;
      font-size: 0.9rem;
      width: 280px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
      border: 1px solid #334155;
    }
    .menu-section {
      margin-bottom: 16px;
      border-bottom: 1px solid #334155;
      padding-bottom: 12px;
    }
    .menu-section:last-child {
      border-bottom: none;
      margin-bottom: 0;
    }
    .menu-title {
      font-weight: bold;
      margin-bottom: 8px;
      font-size: 1rem;
      color: #94a3b8;
    }
    .filter-option {
      display: flex;
      align-items: center;
      padding: 8px 0;
      cursor: pointer;
    }
    .filter-option:hover {
      background-color: #334155;
      border-radius: 4px;
    }
    .filter-color {
      width: 12px;
      height: 12px;
      border-radius: 50%;
      margin-right: 8px;
      flex-shrink: 0;
    }
    .filter-label {
      flex-grow: 1;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .filter-active {
      background-color: #3b82f6;
      border-radius: 4px;
    }
    .filter-active:hover {
      background-color: #3b82f6;
    }
    .seta-orientacao {
      width: 0;
      height: 0;
      border-left: 10px solid transparent;
      border-right: 10px solid transparent;
      border-bottom: 20px solid #3b82f6;
      transition: transform 0.2s ease-out;
    }

  </style>
</head>
<body>
  <button id="menuBtn" class="absolute top-4 right-4 z-[1001] bg-white text-black px-3 py-2 rounded-full shadow-md hover:bg-gray-100 text-lg">
    <i class="fas fa-bars"></i>
  </button>
  <div id="menuOptions">
    <div class="menu-section">
      <div class="menu-title">FILTRO POR STATUS</div>
      <div class="filter-option" data-filter="sla" data-value="all">
        <div class="filter-color" style="background-color: #64748b"></div>
        <div class="filter-label">Todos os chamados</div>
      </div>
      <div class="filter-option" data-filter="sla" data-value="future">
        <div class="filter-color" style="background-color: #10b981"></div>
        <div class="filter-label">SLA Futuro</div>
      </div>
      <div class="filter-option" data-filter="sla" data-value="expired">
        <div class="filter-color" style="background-color: #ef4444"></div>
        <div class="filter-label">SLA Vencido</div>
      </div>
    </div>
    
    <div class="menu-section">
      <div class="menu-title">FILTRO POR TIPO</div>
      <div id="typeFilters" style="max-height: 300px; overflow-y: auto;">
        <!-- Os filtros de tipo serão inseridos aqui pelo JavaScript -->
      </div>
    </div>
  </div>
<input id="searchInput" type="text" placeholder="🔍 Buscar chamado..."
  class="absolute top-4 left-14 z-[1001] bg-white text-black px-3 py-2 rounded-full shadow-md text-sm w-64 focus:outline-none"
  onkeydown="if(event.key === 'Enter') buscarChamado()">

  <button id="toggleLegenda" class="absolute bottom-4 right-4 z-[1001] bg-white text-black px-3 py-2 rounded-full shadow-lg text-sm font-bold hover:bg-gray-100">📌 Legenda</button>
  <div id="map"></div>
  <div id="legenda"></div>

  <button id="btnLocalizacao" title="Minha posição"
    style="display:none"
    class="absolute bottom-32 right-4 z-[1001] bg-white text-black px-3 py-2 rounded-full shadow-lg text-sm font-bold hover:bg-gray-100">
    📍 Localização
  </button>

  <script>
    let ultimoAngulo = null;
    let suavizado = null;
    let seguirUsuario = false;
    const chamados = <?= json_encode($chamados, JSON_UNESCAPED_UNICODE) ?>;

    const subtipoColors = {};
    const coresFixas = {
      "Reparo de buraco, deformação ou afundamento na pista": "black",
      "Reparo de asfalto com deformação ou afundamento": "black",
      "Reparo de buraco na pista de túneis e viadutos": "black",
      "Reparo de asfalto com afundamento": "black",
      "Reposição de tampão ou grelha": "red",
      "Renivelamento de tampão ou grelha": "red",
      "Renivelamento de tampão e grelha": "red"
    };
    const coresDisponiveis = ["#f87171", "#60a5fa", "#34d399", "#facc15", "#fb923c", "#a78bfa", "#f472b6", "#38bdf8", "#c084fc"];
    let corIndex = 0;

    const mapa = L.map('map').setView([-22.93, -43.68], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap'
    }).addTo(mapa);

    const grupos = {};
    const marcadores = {};

const hoje = new Date().toISOString().split("T")[0];

chamados.forEach(chamado => {
  const subtipo = chamado.subtipo;

  if (!subtipoColors[subtipo]) {
    subtipoColors[subtipo] = coresFixas[subtipo] || coresDisponiveis[corIndex % coresDisponiveis.length];
    corIndex++;
  }

  if (!grupos[subtipo]) {
    grupos[subtipo] = L.markerClusterGroup({
      iconCreateFunction: cluster => {
        const cor = subtipoColors[subtipo];
        return L.divIcon({
          html: `<div style="background-color:${cor}; border-radius:50%; width:40px; height:40px; display:flex; align-items:center; justify-content:center; color:white; font-weight:bold;">${cluster.getChildCount()}</div>`,
          className: 'custom-cluster',
          iconSize: L.point(40, 40)
        });
      }
    });
    mapa.addLayer(grupos[subtipo]);
  }

  const slaFuturo = chamado.data_sla && chamado.data_sla >= hoje;

  let marker;

  if (slaFuturo) {
    // Marcador personalizado com ícone flutuante
    const icon = L.divIcon({
      className: 'sla-icon',
      html: `
        <div style="
          width: 28px;
          height: 28px;
          background: #3b82f6;
          border-radius: 50%;
          border: 3px solid white;
          display: flex;
          align-items: center;
          justify-content: center;
          color: white;
          font-size: 14px;
          font-weight: bold;
          box-shadow: 0 0 8px #3b82f6;
        ">⏳</div>
      `,
      iconSize: [28, 28],
      iconAnchor: [14, 14]
    });

    marker = L.marker([chamado.lat, chamado.lng], { icon, dataSla: chamado.data_sla });
  } else {
    // Chamados vencidos usam circleMarker
    marker = L.circleMarker([chamado.lat, chamado.lng], {
      radius: 8,
      color: subtipoColors[subtipo],
      fillColor: subtipoColors[subtipo],
      fillOpacity: 0.8,
      weight: 1.5,
      dataSla: chamado.data_sla
    });
  }

let popupHtml = `
  <div class="text-sm leading-tight">
    <div class="font-bold text-cyan-400">
      <a href="vistoria_1746.php?id=${chamado.id}" class="underline hover:text-cyan-300">
        Chamado ${chamado.id}
      </a>
    </div>
    <div class="mt-1 text-white">${chamado.subtipo}</div>
    <div class="text-xs text-white mt-2 space-y-1">
      <div class="flex items-start">
        <span class="w-[100px] shrink-0 text-slate-300 font-bold">
          <i class="fas fa-road mr-1"></i>Logradouro:
        </span>
        <span class="text-slate-300 whitespace-pre-wrap">${chamado.endereco}</span>
      </div>
      ${chamado.complemento ? `
        <div class="flex items-start">
          <span class="w-[100px] shrink-0 text-slate-300 font-bold">
            <i class="fas fa-building mr-1"></i>Complemento:
          </span>
          <span class="text-slate-300 whitespace-pre-wrap">${chamado.complemento}</span>
        </div>` : ""
      }
      ${chamado.referencia ? `
        <div class="flex items-start">
          <span class="w-[100px] shrink-0 text-slate-300 font-bold">
            <i class="fas fa-map-marker-alt mr-1"></i>Referência:
          </span>
          <span class="text-slate-300 whitespace-pre-wrap">${chamado.referencia}</span>
        </div>` : ""
      }
    </div>
    ${slaFuturo
      ? '<div class="text-green-400 font-bold mt-1 text-xs">⏳ SLA dentro do prazo</div>'
      : '<div class="text-red-400 font-bold mt-1 text-xs">⛔ SLA vencido</div>'
    }
    <div class="mt-2 text-xs text-white whitespace-pre-line">${(chamado.descricao || '').replace(/https?:\/\/[^\s"']+/g, '')}</div>
`;

// Imagens por URL (extraídas da descrição)
const urls = [];
let anexos = [];

if (chamado.descricao) {
  const partes = chamado.descricao.split("ANEXOS:");
  if (partes.length > 1) {
    const anexosBrutos = partes[1]
      .split(/[\s|>]+/) // quebra por espaço, || ou >>
      .filter(url => url.startsWith("http"));
    anexos = anexosBrutos;
  }
}


if (anexos.length > 0) {
  popupHtml += `<div class="mt-2 flex flex-wrap gap-2 items-start">`;

  anexos.forEach((url) => {
    const ext = url.split('.').pop().toLowerCase();
    const isHeic = ext === 'heic';

    if (isHeic) {
      popupHtml += `
        <a href="${url}" target="_blank"
           class="inline-block bg-yellow-500 text-black text-xs font-bold px-3 py-1 rounded shadow hover:bg-yellow-400">
          🔍 Imagem HEIC
        </a>
      `;
    } else {
      popupHtml += `
        <img src="${url}" loading="lazy"
             onclick="visualizarImagemGrande('${url}')"
             class="zoomable rounded border border-white shadow cursor-zoom-in"
             style="width: 100px; height: 100px; object-fit: cover;" />
      `;
    }
  });

  popupHtml += `</div>`;
}

// Fotos da vistoria
if (chamado.fotos) {
  Object.values(chamado.fotos).forEach(foto => {
    if (foto) {
      popupHtml += `<div class="mt-2"><img src="${foto}" loading="lazy"
     onclick="visualizarImagemGrande('${foto}')"
     style="width: 100px; height: 100px; object-fit: cover;" 
     class="zoomable rounded shadow border border-white cursor-zoom-in">
</div>`;
    }
  });
}

popupHtml += `
  <div class="mt-2 flex gap-2">
    <button onclick="abrirRota(${chamado.lat}, ${chamado.lng})" class="bg-blue-500 text-white px-2 py-1 text-xs rounded">🧭 Rota</button>
  </div>
</div>`;

marker.bindPopup(popupHtml);


  grupos[subtipo].addLayer(marker);

  if (!marcadores[subtipo]) marcadores[subtipo] = [];
  marcadores[subtipo].push(marker);
});



    function abrirRota(lat, lng) {
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(pos => {
          const origem = `${pos.coords.latitude},${pos.coords.longitude}`;
          const destino = `${lat},${lng}`;
          window.open(`https://www.google.com/maps/dir/?api=1&origin=${origem}&destination=${destino}&travelmode=driving`, '_blank');
        });
      }
    }
    
function buscarChamado() {
  const termo = document.getElementById("searchInput").value.trim().toLowerCase();
  if (!termo) return;

  let encontrado = null;

  chamados.forEach((chamado, idx) => {
    const idMatch = chamado.id.toString() === termo;
    const enderecoMatch = chamado.endereco.toLowerCase().includes(termo);

    if (idMatch || enderecoMatch) {
      encontrado = chamado;
    }
  });

  if (encontrado) {
    mapa.setView([encontrado.lat, encontrado.lng], 18);
    const markerList = marcadores[encontrado.subtipo] || [];
    const marker = markerList.find(m => m.getLatLng().lat === encontrado.lat && m.getLatLng().lng === encontrado.lng);
    if (marker) marker.openPopup();
  } else {
    alert("Chamado não encontrado.");
  }
}


    // Configuração do menu de filtros
    document.addEventListener('DOMContentLoaded', function() {
      const menuBtn = document.getElementById('menuBtn');
      const menuOptions = document.getElementById('menuOptions');
      const typeFilters = document.getElementById('typeFilters');
      const hoje = new Date().toISOString().split("T")[0];      

      // Permissão para rotação em dispositivos iOS
      if (typeof DeviceOrientationEvent !== "undefined" && typeof DeviceOrientationEvent.requestPermission === "function") {
        DeviceOrientationEvent.requestPermission()
          .then(response => {
            if (response === "granted") {
              window.addEventListener("deviceorientation", atualizarRotacao, true);
            }
          })
          .catch(console.error);
      } else {
        window.addEventListener("deviceorientation", atualizarRotacao, true);
      }

      // Estado dos filtros
      const filterState = {
        sla: 'all',
        activeType: null
      };
      
      // Criar filtros por tipo
      Object.keys(grupos).forEach(subtipo => {
        const filterOption = document.createElement('div');
        filterOption.className = 'filter-option';
        filterOption.dataset.filter = 'type';
        filterOption.dataset.value = subtipo;
        filterOption.innerHTML = `
          <div class="filter-color" style="background-color: ${subtipoColors[subtipo]}"></div>
          <div class="filter-label">${subtipo}</div>
        `;
        
        filterOption.addEventListener('click', () => {
          if (filterState.activeType === subtipo) {
            // Desativar filtro
            filterState.activeType = null;
            Object.values(grupos).forEach(g => mapa.addLayer(g));
            filterOption.classList.remove('filter-active');
          } else {
            // Ativar filtro
            filterState.activeType = subtipo;
            Object.entries(grupos).forEach(([key, group]) => {
              if (key === subtipo) {
                mapa.addLayer(group);
              } else {
                mapa.removeLayer(group);
              }
            });
            // Atualizar estilos dos filtros
            document.querySelectorAll('[data-filter="type"]').forEach(f => {
              f.classList.remove('filter-active');
            });
            filterOption.classList.add('filter-active');
          }
        });
        
        typeFilters.appendChild(filterOption);
      });
      
      // Configurar filtros por SLA
      document.querySelectorAll('[data-filter="sla"]').forEach(option => {
        option.addEventListener('click', function() {
          const value = this.dataset.value;
          filterState.sla = value;
          
          // Atualizar estilos
          document.querySelectorAll('[data-filter="sla"]').forEach(o => {
            o.classList.remove('filter-active');
          });
          this.classList.add('filter-active');
          
          // Implementar lógica de filtro por SLA aqui
          Object.entries(marcadores).forEach(([subtipo, listaMarcadores]) => {
            listaMarcadores.forEach(marker => {
              const dataSla = marker.options.dataSla;
              let exibir = true;

              if (value === 'expired') {
                exibir = dataSla && dataSla < hoje;
              } else if (value === 'future') {
                exibir = dataSla && dataSla >= hoje;
              }

              if (exibir || value === 'all') {
                marker.addTo(grupos[subtipo]);
              } else {
                grupos[subtipo].removeLayer(marker);
              }
            });
          });

        });
      });
      
      // Mostrar/ocultar menu
      menuBtn.addEventListener('click', () => {
        menuOptions.style.display = menuOptions.style.display === 'block' ? 'none' : 'block';
      });
      
      // Fechar menu ao clicar fora
document.addEventListener('click', (e) => {
  const isClickInside = menuOptions.contains(e.target) || menuBtn.contains(e.target);
  if (!isClickInside) {
    menuOptions.style.display = 'none';
  }
});

    });

document.getElementById("btnLocalizacao").addEventListener("click", () => {
  seguirUsuario = true;

  if (posicaoAtual) {
    mapa.flyTo(posicaoAtual, 17, { animate: true, duration: 1 });
    if (marcadorUsuario) marcadorUsuario.openPopup();
  } else {
    alert("Localização ainda não disponível.");
  }

  const btn = document.getElementById("btnLocalizacao");
  btn.style.backgroundColor = "#60a5fa";
  btn.style.color = "white";
});

    // Configuração da legenda
    const legenda = document.getElementById("legenda");
    for (const [subtipo, cor] of Object.entries(subtipoColors)) {
      const div = document.createElement("div");
      div.className = "legenda-item";
      div.innerHTML = `<span class="cor-bolinha" style="background-color:${cor}"></span>${subtipo}`;
      legenda.appendChild(div);
    }

    document.getElementById("toggleLegenda").addEventListener("click", () => {
      legenda.style.display = legenda.style.display === "block" ? "none" : "block";
    });

    // Rastreamento da localização do usuário
let marcadorUsuario = null;
let posicaoAtual = null;

function criarMarcadorUsuario(latitude, longitude) {
  posicaoAtual = [latitude, longitude];
  document.getElementById("btnLocalizacao").style.display = "block";

  if (marcadorUsuario) {
    marcadorUsuario.setLatLng(posicaoAtual);
    if (seguirUsuario) mapa.setView(posicaoAtual, mapa.getZoom());
  } else {
    const iconUsuario = L.divIcon({
      className: '', // <-- Evita estilos extras
      html: `
        <div style="
          width: 32px;
          height: 32px;
          background-color: rgba(0,123,255,0.2);
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
        ">
          <i id="usuario-icone-wrapper" class="fas fa-location-arrow" style="
            color: #007bff;
            font-size: 20px;
            transition: transform 0.2s ease-out;
            display: block;
          "></i>
        </div>
      `,
      iconSize: [32, 32],
      iconAnchor: [16, 16],
    });
    marcadorUsuario = L.marker(posicaoAtual, { icon: iconUsuario }).addTo(mapa);
  }
}

// Pega a localização inicial rapidamente
if (navigator.geolocation) {
  navigator.geolocation.getCurrentPosition(pos => {
    const { latitude, longitude } = pos.coords;
    criarMarcadorUsuario(latitude, longitude);
  }, erro => {
    console.warn("Erro ao obter localização inicial:", erro);
  }, {
    enableHighAccuracy: true,
    timeout: 10000,
    maximumAge: 5000
  });

  // Atualizações contínuas
  navigator.geolocation.watchPosition(pos => {
    const { latitude, longitude } = pos.coords;
    criarMarcadorUsuario(latitude, longitude);
  }, erro => {
    console.warn("Erro no watchPosition:", erro);
  }, {
    enableHighAccuracy: true,
    timeout: 10000,
    maximumAge: 0
  });
}


    window.addEventListener("deviceorientationabsolute", atualizarRotacao, true);
    window.addEventListener("deviceorientation", atualizarRotacao, true);

    function atualizarRotacao(event) {
      // Pega o alpha do evento (graus da bússola)
      let angulo = event.alpha;

      if (typeof angulo !== 'number' || isNaN(angulo)) return;

      // Normaliza entre 0 e 360
      angulo = ((angulo % 360) + 360) % 360;

      // Descarta valores que pulam demais
      if (ultimoAngulo !== null && Math.abs(angulo - ultimoAngulo) > 45) return;

      // Filtro suave (low-pass)
      if (suavizado === null) {
        suavizado = angulo;
      } else {
        suavizado = 0.8 * suavizado + 0.2 * angulo;
      }

      ultimoAngulo = angulo;

      const wrapper = document.getElementById("usuario-icone-wrapper");
      if (wrapper) {
        wrapper.style.transform = `rotate(${-(suavizado + 45)}deg)`;
      }
    }

  </script>
  <script>
  document.addEventListener('click', function(e) {
    if (e.target.tagName === 'IMG' && e.target.classList.contains('zoomable')) {
      const overlay = document.createElement('div');
      overlay.style.position = 'fixed';
      overlay.style.top = 0;
      overlay.style.left = 0;
      overlay.style.width = '100vw';
      overlay.style.height = '100vh';
      overlay.style.background = 'rgba(0,0,0,0.85)';
      overlay.style.display = 'flex';
      overlay.style.justifyContent = 'center';
      overlay.style.alignItems = 'center';
      overlay.style.zIndex = 9999;

      const fullImg = document.createElement('img');
      fullImg.src = e.target.src;
      fullImg.style.maxWidth = '95vw';
      fullImg.style.maxHeight = '95vh';
      fullImg.style.border = '4px solid white';
      fullImg.style.boxShadow = '0 0 15px black';

      overlay.appendChild(fullImg);

      // Fechar ao clicar em qualquer lugar da tela
      overlay.addEventListener('click', () => {
        overlay.remove();
      });

      document.body.appendChild(overlay);
    }
  });
</script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js" defer></script>
  <?php include __DIR__ . '/../includes/menu_inferior.php'; ?>  
  <script src="../js/logout.js"></script>
</body>
</html>