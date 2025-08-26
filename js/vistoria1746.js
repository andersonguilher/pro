import { compressImage } from "./compressImage.js";

let fotosSelecionadas = [];
let mostrarVencidos = false;
let marcador = null;
let mapa = null;
let localUsuario = null;
let chamados = [];

// NOVOS
let marcadorUsuario = null;
let seguir = true;
let mapaInteragido = false;

document.addEventListener("DOMContentLoaded", () => {
let geoSuccess = false;

navigator.geolocation.watchPosition((pos) => {
  geoSuccess = true;
  atualizarPosicaoUsuario(pos);
}, (erro) => {
  console.warn("Erro watchPosition:", erro);
}, {
  enableHighAccuracy: true,
  maximumAge: 0,
  timeout: 10000
});

setTimeout(() => {
  if (!geoSuccess) {
    console.log("Tentando fallback com getCurrentPosition...");
    navigator.geolocation.getCurrentPosition(atualizarPosicaoUsuario);
  }
}, 5000);

  const params = new URLSearchParams(window.location.search);
  const idChamadoParam = params.get("id");
  const listaChamados = document.getElementById("listaChamados");
  const formVistoria = document.getElementById("formVistoria");
  const dadosChamado = document.getElementById("dadosChamado");
  const formulario = document.getElementById("formularioVistoria");
  const buscaInput = document.getElementById("buscaChamado");
  const msgStatus = document.getElementById("msgStatus");
  const previewFotos = document.getElementById("previewFotos");
  const capturarFoto = document.getElementById("capturarFoto");
  const selecionarFoto = document.getElementById("selecionarFoto");
  const inputEnderecoCompleto = document.getElementById("inputEnderecoCompleto");
  const btnBuscarEndereco = document.getElementById("btnBuscarEndereco");


  function buscarEnderecoManual() {
    const endereco = inputEnderecoCompleto?.value.trim();
    if (!endereco) {
      alert("Digite o endereço completo. Ex: Rua Nome 123 Bairro");
      return;
    }

    const partes = endereco.match(/(.+?)\s(\d+)\s(.+)/);
    if (!partes || partes.length < 4) {
      alert("Formato inválido. Use: Rua Nome 123 Bairro");
      return;
    }

    const logradouro = partes[1].trim();
    const numero = partes[2].trim();
    const bairro = partes[3].trim();

    const params = new URLSearchParams({ logradouro, numero, bairro });

    fetch(`../php/obter_distancias.php?${params.toString()}`)
      .then(res => res.json())
      .then(data => {
        if (data.latitude && data.longitude) {
          const novaLatLng = [parseFloat(data.latitude), parseFloat(data.longitude)];
          if (mapa) {
            if (!marcador) {
              marcador = L.marker(novaLatLng, { draggable: true }).addTo(mapa);
            } else {
              marcador.setLatLng(novaLatLng);
            }
            mapa.setView(novaLatLng, 18);
          }
        } else {
          alert("Endereço não localizado.");
        }
      })
      .catch(err => {
        console.error("Erro ao buscar endereço:", err);
        alert("Erro ao buscar o endereço.");
      });
  }

  btnBuscarEndereco?.addEventListener("click", buscarEnderecoManual);

  inputEnderecoCompleto?.addEventListener("keypress", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      buscarEnderecoManual();
    }
  });


  function criarBarraProgresso() {
    const barra = document.createElement("div");
    barra.id = "barraProgresso";
    barra.style.position = "fixed";
    barra.style.top = "0";
    barra.style.left = "0";
    barra.style.height = "4px";
    barra.style.backgroundColor = "#06b6d4";
    barra.style.zIndex = "9999";
    barra.style.transition = "width 0.3s ease";
    barra.style.width = "0%";
    document.body.appendChild(barra);

    let progresso = 0;
    const intervalo = setInterval(() => {
      if (progresso < 90) {
        progresso += Math.random() * 10;
        barra.style.width = `${Math.min(progresso, 90)}%`;
      }
    }, 300);

    return { barra, intervalo };
  }

  const { barra, intervalo } = criarBarraProgresso();

  if ("geolocation" in navigator) {
    navigator.geolocation.watchPosition((pos) => {
      const lat = pos.coords.latitude;
      const lng = pos.coords.longitude;
      localUsuario = [lat, lng];

      fetch(`../php/obter_distancias.php?lat=${lat}&lng=${lng}`)
        .then((res) => res.json())
        .then((data) => {
          chamados = data;
          if (idChamadoParam) {
            const chamadoEncontrado = chamados.find(c => String(c.id_chamado) === idChamadoParam);
            if (chamadoEncontrado) {
              preencherForm(chamadoEncontrado);
            } else {
              alert("Chamado não encontrado.");
            }
          }
          clearInterval(intervalo);
          barra.style.width = "100%";
          setTimeout(() => barra.remove(), 200);

          const total = chamados.length;
          const comCoords = chamados.filter(c => c.latitude_geolocalizacao).length;
          const semCoords = chamados.filter(c => !c.latitude_geolocalizacao).length;

          const barraAzul = document.createElement("div");
          barraAzul.className = "h-1 bg-blue-500 transition-all duration-500";
          barraAzul.style.width = "100%";

          const barraVermelha = document.createElement("div");
          barraVermelha.className = "h-1 bg-red-500 transition-all duration-500 mt-[1px]";
          barraVermelha.style.width = `${(semCoords / total) * 100}%`;

          const container = document.createElement("div");
          container.className = "fixed top-0 left-0 w-full z-50 flex flex-col";
          container.appendChild(barraAzul);
          container.appendChild(barraVermelha);
          document.body.appendChild(container);

          setTimeout(() => container.remove(), 1200);

          exibirChamados(chamados);
        })
        .catch((erro) => {
          clearInterval(intervalo);
          barra.style.backgroundColor = "red";
          barra.style.width = "100%";
          setTimeout(() => barra.remove(), 600);
          msgStatus.textContent = "Alguns chamados não puderam ser carregados corretamente.";
          console.error("Erro ao carregar os chamados:", erro);
        });
    }, () => {
    }, (erro) => {
      clearInterval(intervalo);
      barra.remove();

      const mensagem = "Para ver a distância entre o chamado e sua localização, habilite o GPS e pressione OK. Ou cancele para exibir apenas a lista classificada por SLA do que está mais próximo do vencimento para o mais distante.";

      if (window.matchMedia("(display-mode: standalone)").matches || navigator.standalone === true) {
        // Está rodando como PWA instalado
        alert(mensagem);
        const continuar = confirm("Deseja ativar o GPS e tentar novamente?");
        if (continuar) {
          location.reload();
          return;
        }
      } else {
        // Versão navegador comum
        alert("Não foi possível obter sua localização.");
      }

      // Coordenada fixa da Gerência
      const latGerencia = -22.91914049918976;
      const lngGerencia = -43.68777925111014;

      fetch("../php/obter_distancias.php")
        .then((res) => res.json())
        .then((data) => {
          chamados = data.map((c) => {
            if (c.latitude_geolocalizacao && c.longitude_geolocalizacao) {
              const dx = (latGerencia - parseFloat(c.latitude_geolocalizacao)) * 111.32;
              const dy = (lngGerencia - parseFloat(c.longitude_geolocalizacao)) * 111.32 * Math.cos(latGerencia * Math.PI / 180);
              const dist = Math.sqrt(dx * dx + dy * dy);
              return { ...c, distancia: dist };
            }
            return { ...c, distancia: 9999 };
          });

          chamados.sort((a, b) => {
            const dataA = new Date(a.data_sla);
            const dataB = new Date(b.data_sla);
            return dataA - dataB;
          });

          exibirChamados(chamados);
        })
        .catch((erro) => {
          msgStatus.textContent = "Erro ao carregar os chamados.";
          msgStatus.classList.remove("hidden");
          msgStatus.classList.add("text-red-400");
          console.error("Erro ao carregar chamados sem localização:", erro);
        });
    }, {
      enableHighAccuracy: true,
      timeout: 10000
    });



  }

  const botaoFiltro = document.createElement("button");
  botaoFiltro.innerHTML = `<i class="fas fa-filter mr-2"></i>Ver Todos`;
  botaoFiltro.className = "w-full mb-3 py-2 rounded font-bold transition flex items-center justify-center gap-2 bg-cyan-700 hover:bg-cyan-600 text-white";
  buscaInput.insertAdjacentElement("afterend", botaoFiltro);

  botaoFiltro.addEventListener("click", () => {
    mostrarVencidos = !mostrarVencidos;

    if (mostrarVencidos) {
      botaoFiltro.innerHTML = `<i class="fas fa-times-circle mr-2"></i>Ocultar Vencidos`;
      botaoFiltro.classList.replace("bg-cyan-700", "bg-amber-500");
      botaoFiltro.classList.replace("hover:bg-cyan-600", "hover:bg-amber-400");
      botaoFiltro.classList.replace("text-white", "text-black");
    } else {
      botaoFiltro.innerHTML = `<i class="fas fa-filter mr-2"></i>Ver Todos`;
      botaoFiltro.classList.replace("bg-amber-500", "bg-cyan-700");
      botaoFiltro.classList.replace("hover:bg-amber-400", "hover:bg-cyan-600");
      botaoFiltro.classList.replace("text-black", "text-white");
    }

    exibirChamados(chamados);
  });

  function exibirChamados(lista) {
    listaChamados.innerHTML = "";
    const hoje = new Date();
    hoje.setHours(0, 0, 0, 0);

    lista.forEach((chamado) => {
      const temFoto = chamado.ds_chamado && chamado.ds_chamado.match(/https?:\/\/[^\s]+/g);
      const iconeFoto = temFoto ? `<i class="fa-solid fa-photo-film text-amber-400 ml-1" title="Possui fotos"></i>` : "";
      const slaDate = new Date(chamado.data_sla);
      slaDate.setHours(0, 0, 0, 0);
      const diffDias = Math.floor((slaDate - hoje) / (1000 * 60 * 60 * 24));
      const slaFormatada = slaDate.toLocaleDateString("pt-BR").replace(/\//g, "-");

      if (!mostrarVencidos && diffDias < 0) return;

      let corSLA = "bg-green-400 text-black";
      if (diffDias < 0) corSLA = "bg-red-500 text-white";
      else if (diffDias <= 3) corSLA = "bg-orange-400 text-black";

      const distanciaKm = chamado.distancia;
      let textoDist = `<span class="text-red-400 font-bold">❌ Não localizado</span>`;
      if (typeof distanciaKm === "number" && !isNaN(distanciaKm) && distanciaKm !== 9999) {
        const distFormatada = distanciaKm < 1
          ? `${Math.round(distanciaKm * 1000)}m`
          : `${distanciaKm.toFixed(1).replace('.', ',')}km`;
        textoDist = `<span class="text-green-500 font-bold">📍 ${distFormatada} <span class="text-xs text-slate-400">(distância a partir da Gerência)</span></span>`;
      }

      let foraAreaTag = "";
      if (chamado.fora_area) {
        foraAreaTag = `<div class="text-xs text-red-400 font-bold">🔺 Fora da Área</div>`;
      }

      const btn = document.createElement("button");
      btn.className = "block w-full text-left bg-slate-800 border border-slate-600 rounded-lg p-3 hover:bg-slate-700 transition";
      btn.innerHTML = `
        <div class="text-sm font-semibold">Chamado ${chamado.id_chamado} ${iconeFoto}</div>
        <div class="text-xs flex justify-between items-center">
          <span class="text-cyan-400 font-semibold">${chamado.subtipo}</span>
          <span class="px-2 py-0.5 rounded text-xs font-bold whitespace-nowrap ${corSLA}">
            SLA: ${slaFormatada}
          </span>
        </div>
        <div class="text-xs">${chamado.endereco}</div>
        <div class="text-xs font-bold">${textoDist}</div>
        ${foraAreaTag}
      `;
      btn.onclick = () => preencherForm(chamado);
      listaChamados.appendChild(btn);
    });
  }

  buscaInput.addEventListener("input", () => {
    const termo = buscaInput.value.trim().toLowerCase();

    const filtrados = chamados.filter((c) => {
      const id = String(c.id_chamado || "").toLowerCase();
      const subtipo = (c.subtipo || "").toLowerCase();
      const endereco = (c.endereco || "").toLowerCase();
      const logradouro = (c.logradouro || "").toLowerCase();
      const bairro = (c.bairro || "").toLowerCase();

      return (
        id.includes(termo) ||
        subtipo.includes(termo) ||
        endereco.includes(termo) ||
        logradouro.includes(termo) ||
        bairro.includes(termo)
      );
    });

    exibirChamados(filtrados);
  });


  function preencherForm(chamado) {
    formVistoria.classList.remove("hidden");
    listaChamados.classList.add("hidden");
    window.scrollTo({ top: formVistoria.offsetTop, behavior: "smooth" });

    let descricao = chamado.ds_chamado || "";
    let contador = 1;
    descricao = descricao.replace(/https?:\/\/[^\s]+/g, (url) => {
      const id = contador++;
      return `<a href="#" class="text-cyan-400 underline foto-link" data-url="${url}">FOTO${id}</a>`;
    });

    dadosChamado.innerHTML = `
      <div><strong>Chamado:</strong> ${chamado.id_chamado}</div>
      <div><strong>SLA:</strong> ${chamado.data_sla}</div>
      <div><strong>Subtipo:</strong> ${chamado.subtipo}</div>
      <div><strong>Endereço:</strong> ${chamado.endereco}</div>
      ${chamado.complemento ? `<div><strong>Complemento:</strong> ${chamado.complemento}</div>` : ""}
      ${chamado.referencia ? `<div><strong>Referência:</strong> ${chamado.referencia}</div>` : ""}
      <div><strong>Descrição:</strong> ${descricao}</div>
    `;

    setTimeout(() => {
      document.querySelectorAll(".foto-link").forEach(link => {
        link.addEventListener("click", (e) => {
          e.preventDefault();
          const url = link.getAttribute("data-url");
          mostrarPopupImagem(url);
        });
      });
    }, 100);

    esperarMapaVisivel(() => inicializarMapaComPrioridade(chamado));
  }

  function inicializarMapaComPrioridade(chamado) {
    const mapDiv = document.getElementById("map");
    if (mapa) {
      mapa.remove();
      mapa = null;
    }
    if (!mapDiv || mapDiv.offsetHeight === 0)
      return setTimeout(() => inicializarMapaComPrioridade(chamado), 200);

    mapa = L.map(mapDiv).setView([-22.9307, -43.6815], 15);

    mapa.on('dragstart zoomstart', () => {
      seguir = false;
      mapaInteragido = true;
      document.getElementById("seguirUsuario")?.classList.remove("hidden");
    });


    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "© OpenStreetMap",
    }).addTo(mapa);

    fetch("../geodata.geojson")
      .then(res => res.json())
      .then(geojson => {
        const camadaPoligono = L.geoJSON(geojson, {
          style: {
            color: "#f87171",
            weight: 2,
            opacity: 1,
            fillColor: "#fecaca",
            fillOpacity: 0.2
          }
        }).addTo(mapa);

        if (chamado.geoloc && chamado.geoloc.includes(",")) {
          const [lat, lng] = chamado.geoloc.split(",").map(Number);
          marcador = L.marker([lat, lng], { draggable: true }).addTo(mapa);
        } else if (chamado.latitude_geolocalizacao && chamado.longitude_geolocalizacao) {
          const logradouro = chamado.logradouro;
          const bairro = chamado.bairro;
          const numero = chamado.numero || 0;

          const logradourosEspeciais = [
            "R. Felipe Cardoso",
            "Av. João XXIII",
            "Av. Areia Branca"
          ];

          // Se for um dos logradouros especiais, refaz a busca usando número
          if (logradourosEspeciais.includes(logradouro)) {
            fetch(`../php/buscar_geolocalizacao.php?logradouro=${encodeURIComponent(logradouro)}&bairro=${encodeURIComponent(bairro)}&numero=${numero}`)
              .then(res => res.json())
              .then(data => {
                if (data.latitude && data.longitude) {
                  const lat = parseFloat(data.latitude);
                  const lng = parseFloat(data.longitude);
                  marcador = L.marker([lat, lng], { draggable: true }).addTo(mapa);
                  // Se localização do usuário existir, adiciona botão de rota
                  if (marcadorUsuario) {
                    const rotaBtn = document.createElement("button");
                    rotaBtn.innerHTML = `<i class="fas fa-route mr-2"></i> Traçar rota`;
                    rotaBtn.className = "w-full mt-3 py-2 rounded font-bold bg-green-600 hover:bg-green-500 text-white transition";

                    rotaBtn.onclick = () => {
                      const latUsuario = marcadorUsuario.getLatLng().lat;
                      const lngUsuario = marcadorUsuario.getLatLng().lng;
                      const latDestino = marcador.getLatLng().lat;
                      const lngDestino = marcador.getLatLng().lng;

                      const url = `https://www.google.com/maps/dir/?api=1&origin=${latUsuario},${lngUsuario}&destination=${latDestino},${lngDestino}&travelmode=driving`;
                      window.open(url, '_blank');
                    };

                    const mapaContainer = document.getElementById("map").parentElement;
                    mapaContainer.appendChild(rotaBtn);
                  }

                  if (marcadorUsuario) {
                    const grupo = L.featureGroup([marcador, marcadorUsuario]);
                    mapa.fitBounds(grupo.getBounds().pad(0.3));
                  }
                }
              });
          } else {
            const lat = parseFloat(chamado.latitude_geolocalizacao);
            const lng = parseFloat(chamado.longitude_geolocalizacao);
            marcador = L.marker([lat, lng], { draggable: true }).addTo(mapa);
            // BOTÃO DE ROTA NO GOOGLE MAPS
            const btnRota = document.createElement("button");
            btnRota.id = "btnRotaGoogle";
            btnRota.innerHTML = `<i class="fas fa-route mr-1"></i> Rota`;
            btnRota.className = "absolute bottom-4 right-4 bg-green-600 hover:bg-green-500 text-white px-4 py-2 rounded-full shadow-lg z-[999]";
            btnRota.onclick = () => {
              if (localUsuario && marcador) {
                const origem = `${localUsuario[0]},${localUsuario[1]}`;
                const destino = `${marcador.getLatLng().lat},${marcador.getLatLng().lng}`;
                window.open(`https://www.google.com/maps/dir/?api=1&origin=${origem}&destination=${destino}&travelmode=driving`, '_blank');
              } else {
                alert("Localização do usuário ou do chamado não disponível.");
              }
            };

            // Remove botão anterior se já existir
            document.getElementById("btnRotaGoogle")?.remove();
            document.getElementById("map").appendChild(btnRota);

          }
        }


        // ✅ Exibe marcador do usuário se já tiver localização
        if (localUsuario && !marcadorUsuario) {
          const [lat, lng] = localUsuario;
          atualizarPosicaoUsuario({ coords: { latitude: lat, longitude: lng, heading: 0 } });
        }

        // Ajusta visual do mapa com base nos marcadores disponíveis
        if (marcador && marcadorUsuario) {
          const grupo = L.featureGroup([marcador, marcadorUsuario]);
          mapa.fitBounds(grupo.getBounds().pad(0.3));
        } else if (marcador) {
          mapa.setView(marcador.getLatLng(), 18);
        } else if (marcadorUsuario) {
          mapa.setView(marcadorUsuario.getLatLng(), 18);
        }


      });
  }

  function esperarMapaVisivel(callback) {
    const check = () => {
      const el = document.getElementById("map");
      if (el && el.offsetHeight > 0 && el.offsetWidth > 0) callback();
      else requestAnimationFrame(check);
    };
    check();
  }

  // 📍 RASTREAMENTO USUÁRIO
  if ("geolocation" in navigator) {
    navigator.geolocation.watchPosition((pos) => {
      const lat = pos.coords.latitude;
      const lng = pos.coords.longitude;
      localUsuario = [lat, lng];

      fetch(`../php/obter_distancias.php?lat=${lat}&lng=${lng}`)
        .then((res) => res.json())
        .then((data) => {
          chamados = data;
          if (idChamadoParam) {
            const chamadoEncontrado = chamados.find(c => String(c.id_chamado) === idChamadoParam);
            if (chamadoEncontrado) {
              preencherForm(chamadoEncontrado);
            } else {
              alert("Chamado não encontrado.");
            }
          }
          clearInterval(intervalo);
          barra.style.width = "100%";
          setTimeout(() => barra.remove(), 200);

          const total = chamados.length;
          const comCoords = chamados.filter(c => c.latitude_geolocalizacao).length;
          const semCoords = chamados.filter(c => !c.latitude_geolocalizacao).length;

          const barraAzul = document.createElement("div");
          barraAzul.className = "h-1 bg-blue-500 transition-all duration-500";
          barraAzul.style.width = "100%";

          const barraVermelha = document.createElement("div");
          barraVermelha.className = "h-1 bg-red-500 transition-all duration-500 mt-[1px]";
          barraVermelha.style.width = `${(semCoords / total) * 100}%`;

          const container = document.createElement("div");
          container.className = "fixed top-0 left-0 w-full z-50 flex flex-col";
          container.appendChild(barraAzul);
          container.appendChild(barraVermelha);
          document.body.appendChild(container);

          setTimeout(() => container.remove(), 1200);

          exibirChamados(chamados);
        })
        .catch((erro) => {
          clearInterval(intervalo);
          barra.style.backgroundColor = "red";
          barra.style.width = "100%";
          setTimeout(() => barra.remove(), 600);
          msgStatus.textContent = "Alguns chamados não puderam ser carregados corretamente.";
          console.error("Erro ao carregar os chamados:", erro);
        });
    }, () => {
      clearInterval(intervalo);
      barra.remove();

      if (confirm("Para ver a distância entre o chamado e sua localização, habilite o GPS e pressione OK. Ou cancele para exibir apenas a lista classificada por SLA do que está mais próximo do vencimento para o mais distante.")) {
        location.reload();
      } else {
        // Coordenada fixa da Gerência
        const latGerencia = -22.91914049918976;
        const lngGerencia = -43.68777925111014;

        fetch("../php/obter_distancias.php")
          .then((res) => res.json())
          .then((data) => {
            chamados = data.map((c) => {
              if (c.latitude_geolocalizacao && c.longitude_geolocalizacao) {
                const dx = (latGerencia - parseFloat(c.latitude_geolocalizacao)) * 111.32;
                const dy = (lngGerencia - parseFloat(c.longitude_geolocalizacao)) * 111.32 * Math.cos(latGerencia * Math.PI / 180);
                const dist = Math.sqrt(dx * dx + dy * dy);
                return { ...c, distancia: dist };
              }
              return { ...c, distancia: 9999 }; // marcador de ausência
            });

            chamados.sort((a, b) => {
              const dataA = new Date(a.data_sla);
              const dataB = new Date(b.data_sla);
              return dataA - dataB;
            });

            exibirChamados(chamados);
          })
          .catch((erro) => {
            msgStatus.textContent = "Erro ao carregar os chamados.";
            msgStatus.classList.remove("hidden");
            msgStatus.classList.add("text-red-400");
            console.error("Erro ao carregar chamados sem localização:", erro);
          });
      }

    }, {
      enableHighAccuracy: true,
      timeout: 10000
    });
  }


  document.getElementById("seguirUsuario")?.addEventListener("click", () => {
    seguir = true;
    mapaInteragido = false;
    document.getElementById("seguirUsuario")?.classList.add("hidden");
    if (marcadorUsuario) {
      mapa.setView(marcadorUsuario.getLatLng(), 18);
    }
  });


  let ultimaAtualizacao = 0;

  function atualizarPosicaoUsuario(pos) {
    const agora = Date.now();
    if (agora - ultimaAtualizacao < 1000) return; // atualiza no máx 1x por segundo
    ultimaAtualizacao = agora;

    const { latitude, longitude, heading } = pos.coords;
    const latlng = [latitude, longitude];

    if (!mapa) return;

    if (!marcadorUsuario) {
      marcadorUsuario = L.marker(latlng, { icon: iconeUsuario(heading || 0) }).addTo(mapa);
    } else {
      marcadorUsuario.setLatLng(latlng);

      // atualiza a rotação do ícone se houver heading
      const wrapper = document.getElementById("usuario-icone-wrapper");
      if (wrapper && heading !== null) {
        wrapper.style.transform = `rotate(${heading}deg)`;
      }
    }

    if (seguir && !mapaInteragido) {
      mapa.flyTo(latlng, mapa.getZoom(), { animate: true, duration: 1 });
    }

    if (marcador && marcadorUsuario && !atualizarPosicaoUsuario._ajustou) {
      const grupo = L.featureGroup([marcador, marcadorUsuario]);
      mapa.fitBounds(grupo.getBounds().pad(0.3));
      atualizarPosicaoUsuario._ajustou = true;
    }
  }


  function iconeUsuario(grau = 0) {
    return L.divIcon({
      className: '',
      html: `
      <div id="usuario-icone-wrapper" style="
        width: 32px;
        height: 32px;
        background-color: rgba(0,123,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transform: rotate(${grau}deg);
      ">
        <i class="fas fa-location-arrow" style="color: #007bff; font-size: 18px;"></i>
      </div>`,
      iconSize: [32, 32],
      iconAnchor: [16, 16]
    });
  }



  formulario.addEventListener("submit", (e) => {
    e.preventDefault();

    const formData = new FormData(formulario);

    if (marcador) {
      const { lat, lng } = marcador.getLatLng();
      formData.append("latitude", lat);
      formData.append("longitude", lng);
    } else {
      alert("Por favor, selecione a localização no mapa.");
      return;
    }

    fotosSelecionadas.forEach((foto) => {
      formData.append("fotos[]", foto);
    });

    const idChamado = dadosChamado.querySelector("div").innerText.split(" ")[1];
    formData.append("id_chamado", idChamado);

    fetch("../php/salvar_vistoria_1746.php", {
      method: "POST",
      body: formData,
    })
      .then((res) => {
        if (!res.ok) throw new Error("Erro no envio");
        return res.text();
      })
      .then((resposta) => {
        msgStatus.classList.remove("hidden");
        msgStatus.textContent = "✅ Vistoria enviada com sucesso!";
        msgStatus.classList.add("text-green-400");

        limparFormulario();
      })
      .catch(() => {
        msgStatus.classList.remove("hidden");
        msgStatus.textContent = "⚠️ Sem internet. Vistoria será enviada automaticamente mais tarde.";
        msgStatus.classList.add("text-yellow-400");

        salvarVistoriaOffline(formData);
      });
  });

  function salvarVistoriaOffline(formData) {
    const itens = [];
    let arquivosPendentes = 0;

    for (const [key, value] of formData.entries()) {
      if (value instanceof File) {
        arquivosPendentes++;
        const reader = new FileReader();
        reader.onload = function (e) {
          itens.push({ key, isFile: true, name: value.name, type: value.type, data: e.target.result });
          arquivosPendentes--;
          if (arquivosPendentes === 0) {
            enfileirarVistoria(itens);
          }
        };
        reader.readAsDataURL(value);
      } else {
        itens.push({ key, value });
      }
    }

    if (arquivosPendentes === 0) {
      enfileirarVistoria(itens);
    }
  }

  function enfileirarVistoria(itens) {
    const fila = JSON.parse(localStorage.getItem("fila_vistorias") || "[]");
    fila.push({ id: Date.now(), itens });
    localStorage.setItem("fila_vistorias", JSON.stringify(fila));
  }

  function tentarReenviarVistorias() {
    const fila = JSON.parse(localStorage.getItem("fila_vistorias") || "[]");
    if (!fila.length) return;

    const proxima = fila[0];
    const formData = new FormData();

    for (const item of proxima.itens) {
      if (item.isFile) {
        const blob = dataURLToBlob(item.data, item.type);
        formData.append(item.key, blob, item.name);
      } else {
        formData.append(item.key, item.value);
      }
    }

    fetch("../php/salvar_vistoria_1746.php", {
      method: "POST",
      body: formData,
    })
      .then((res) => {
        if (!res.ok) throw new Error("Erro no reenvio");
        return res.text();
      })
      .then(() => {
        const novaFila = fila.slice(1);
        localStorage.setItem("fila_vistorias", JSON.stringify(novaFila));
        tentarReenviarVistorias(); // Tenta o próximo da fila
      })
      .catch(() => {
        // Ainda sem conexão — mantém na fila
      });
  }

  function dataURLToBlob(dataurl, type) {
    const arr = dataurl.split(",");
    const bstr = atob(arr[1]);
    let n = bstr.length;
    const u8arr = new Uint8Array(n);
    while (n--) {
      u8arr[n] = bstr.charCodeAt(n);
    }
    return new Blob([u8arr], { type: type });
  }

  function limparFormulario() {
    formulario.reset();
    previewFotos.innerHTML = "";
    formVistoria.classList.add("hidden");
    listaChamados.classList.remove("hidden");
    fotosSelecionadas = [];

    marcador = null;
    if (mapa) {
      mapa.remove();
      mapa = null;
    }

    if ("geolocation" in navigator) {
      navigator.geolocation.watchPosition((pos) => {
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;
        fetch(`../php/obter_distancias.php?lat=${lat}&lng=${lng}`)
          .then((res) => res.json())
          .then((data) => {
            chamados = data;
            exibirChamados(chamados);
          });
      });
    }
  }


  capturarFoto.addEventListener("change", (e) => {
    tratarArquivos(e.target.files);
    capturarFoto.value = "";
  });

  selecionarFoto.addEventListener("change", (e) => {
    tratarArquivos(e.target.files);
    selecionarFoto.value = "";
  });

  function tratarArquivos(arquivos) {
    const limite = 4 - fotosSelecionadas.length;
    const arquivosSelecionados = Array.from(arquivos).slice(0, limite);
    if (limite <= 0) {
      alert("Você já selecionou o máximo de 4 fotos.");
      return;
    }

    arquivosSelecionados.forEach(async (file) => {
      const comprimida = await compressImage(file);
      fotosSelecionadas.push(comprimida);

      const url = URL.createObjectURL(comprimida);
      const img = document.createElement("img");
      img.src = url;
      img.className = "w-full h-32 object-cover rounded";
      previewFotos.appendChild(img);
    });
  }
  setInterval(() => {
    if (navigator.onLine) {
      tentarReenviarVistorias();
    }
  }, 30000);

  window.addEventListener("online", tentarReenviarVistorias);
});

function mostrarPopupImagem(url) {
  const overlay = document.createElement("div");
  overlay.className = "fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50";
  overlay.innerHTML = `
    <div class="bg-white p-4 rounded shadow-lg max-w-[90vw] max-h-[90vh] relative">
      <button 
        class="absolute top-2 right-2 text-slate-600 hover:text-red-500 text-2xl transition" 
        onclick="this.closest('.fixed').remove()" 
        title="Fechar"
      >
        <i class="fa-solid fa-circle-xmark"></i>
      </button>
      <div class="w-[80vw] h-auto max-h-[70vh] flex justify-center items-center overflow-hidden">
        <img src="${url}" class="w-full h-auto object-contain rounded" />
      </div>
    </div>
  `;
  document.body.appendChild(overlay);
}