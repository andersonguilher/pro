import { compressImage } from "./compressImage.js";

document.addEventListener("DOMContentLoaded", () => {
    // Variáveis de estado do módulo
    let fotosSelecionadas = [];
    let mostrarVencidos = false;
    let marcador = null;
    let mapa = null;
    let localUsuario = null;
    let chamados = [];
    let marcadorUsuario = null;
    let seguir = true;
    let mapaInteragido = false;
    let watchId = null; // Armazena o ID do watchPosition para poder limpá-lo

    // Elementos do DOM
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

    // Função para criar e gerenciar a barra de progresso
    function criarBarraProgresso() {
        document.getElementById("barraProgresso")?.remove();
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

        return {
            finalizar: () => {
                clearInterval(intervalo);
                barra.style.width = "100%";
                setTimeout(() => barra.remove(), 500);
            },
            falhar: () => {
                clearInterval(intervalo);
                barra.style.backgroundColor = "red";
                barra.style.width = "100%";
                setTimeout(() => barra.remove(), 600);
            }
        };
    }

    // Carrega os dados dos chamados UMA ÚNICA VEZ
    function carregarDadosIniciais(pos) {
        const { finalizar, falhar } = criarBarraProgresso();
        const lat = pos?.coords?.latitude;
        const lng = pos?.coords?.longitude;
        localUsuario = lat && lng ? [lat, lng] : null;

        let url = "../php/obter_distancias.php";
        if (lat && lng) {
            url += `?lat=${lat}&lng=${lng}`;
        }

        fetch(url)
            .then(res => res.json())
            .then(data => {
                chamados = data;
                const params = new URLSearchParams(window.location.search);
                const idChamadoParam = params.get("id");

                if (idChamadoParam) {
                    const chamadoEncontrado = chamados.find(c => String(c.id_chamado) === idChamadoParam);
                    if (chamadoEncontrado) {
                        preencherForm(chamadoEncontrado);
                    } else {
                        alert("Chamado não encontrado.");
                        exibirChamados(chamados);
                    }
                } else {
                    exibirChamados(chamados);
                }
                finalizar();
            })
            .catch(erro => {
                falhar();
                msgStatus.textContent = "Erro ao carregar os chamados.";
                console.error("Erro ao carregar os chamados:", erro);
            });
    }

    // Exibe a lista de chamados na tela
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
            const slaFormatada = slaDate.toLocaleDateString("pt-BR", { day: '2-digit', month: '2-digit', year: '2-digit' });

            if (!mostrarVencidos && diffDias < 0) return;

            let corSLA = "bg-green-400 text-black";
            if (diffDias < 0) corSLA = "bg-red-500 text-white";
            else if (diffDias <= 3) corSLA = "bg-orange-400 text-black";

            const distanciaKm = chamado.distancia;
            let textoDist = `<span class="text-gray-400 font-bold">GPS desativado</span>`;
            if (localUsuario) {
                textoDist = `<span class="text-red-400 font-bold">❌ Não localizado</span>`;
                if (typeof distanciaKm === "number" && !isNaN(distanciaKm) && distanciaKm !== 9999) {
                    const distFormatada = distanciaKm < 1 ?
                        `${Math.round(distanciaKm * 1000)}m` :
                        `${distanciaKm.toFixed(1).replace('.', ',')}km`;
                    textoDist = `<span class="text-green-500 font-bold">📍 ${distFormatada}</span>`;
                }
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

    // Preenche o formulário com os dados de um chamado específico
    function preencherForm(chamado) {
        formVistoria.classList.remove("hidden");
        listaChamados.classList.add("hidden");
        buscaInput.parentElement.classList.add("hidden");

        window.scrollTo({ top: 0, behavior: "smooth" });

        let descricao = chamado.ds_chamado || "";
        let contador = 1;
        descricao = descricao.replace(/https?:\/\/[^\s]+/g, (url) => {
            return `<a href="#" class="text-cyan-400 underline foto-link" data-url="${url}">FOTO ${contador++}</a>`;
        });

        const dataSlaFormatada = new Date(chamado.data_sla).toLocaleDateString("pt-BR", { timeZone: 'UTC' });
        dadosChamado.innerHTML = `
            <div><strong>Chamado:</strong> ${chamado.id_chamado}</div>
            <div><strong>SLA:</strong> ${dataSlaFormatada}</div>
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

    // Inicializa o mapa para o formulário
    function inicializarMapaComPrioridade(chamado) {
        const mapDiv = document.getElementById("map");
        if (mapa) {
            mapa.remove();
            mapa = null;
        }
        if (!mapDiv || mapDiv.offsetHeight === 0)
            return setTimeout(() => inicializarMapaComPrioridade(chamado), 200);

        seguir = true;
        mapaInteragido = false;

        mapa = L.map(mapDiv).setView([-22.9307, -43.6815], 15);
        mapa.on('dragstart zoomstart', () => {
            seguir = false;
            mapaInteragido = true;
            document.getElementById("seguirUsuario")?.classList.remove("hidden");
        });

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: "© OpenStreetMap",
        }).addTo(mapa);

        fetch("../geodata.geojson")
            .then(res => res.json())
            .then(geojson => {
                L.geoJSON(geojson, {
                    style: { color: "#f87171", weight: 2, opacity: 1, fillColor: "#fecaca", fillOpacity: 0.2 }
                }).addTo(mapa);

                if (chamado.geoloc && chamado.geoloc.includes(",")) {
                    const [lat, lng] = chamado.geoloc.split(",").map(Number);
                    marcador = L.marker([lat, lng], { draggable: true }).addTo(mapa);
                } else if (chamado.latitude_geolocalizacao && chamado.longitude_geolocalizacao) {
                    const lat = parseFloat(chamado.latitude_geolocalizacao);
                    const lng = parseFloat(chamado.longitude_geolocalizacao);
                    marcador = L.marker([lat, lng], { draggable: true }).addTo(mapa);
                }

                const btnRota = document.getElementById("abrirRota");
                if (btnRota) {
                    btnRota.classList.remove("hidden");
                    btnRota.onclick = () => {
                        if (localUsuario && marcador) {
                            const origem = `${localUsuario[0]},${localUsuario[1]}`;
                            const destino = `${marcador.getLatLng().lat},${marcador.getLatLng().lng}`;
                            window.open(`https://www.google.com/maps/dir/?api=1&origin=${origem}&destination=${destino}&travelmode=driving`, '_blank');
                        } else {
                            alert("Localização do usuário ou do chamado não disponível.");
                        }
                    };
                }

                if (localUsuario) {
                    const [lat, lng] = localUsuario;
                    atualizarPosicaoUsuario({ coords: { latitude: lat, longitude: lng, heading: null } });
                }

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

    let ultimaAtualizacao = 0;
    function atualizarPosicaoUsuario(pos) {
        const agora = Date.now();
        if (agora - ultimaAtualizacao < 2000) return;
        ultimaAtualizacao = agora;

        const { latitude, longitude, heading } = pos.coords;
        localUsuario = [latitude, longitude];
        const latlng = [latitude, longitude];

        if (!mapa) return;

        if (!marcadorUsuario) {
            marcadorUsuario = L.marker(latlng, { icon: iconeUsuario(heading || 0) }).addTo(mapa);
        } else {
            marcadorUsuario.setLatLng(latlng);
            const wrapper = document.getElementById("usuario-icone-wrapper");
            if (wrapper && heading !== null) {
                wrapper.style.transform = `rotate(${heading}deg)`;
            }
        }

        if (seguir && !mapaInteragido) {
            mapa.flyTo(latlng, mapa.getZoom(), { animate: true, duration: 1 });
            seguir = false;
        }
    }

    function esperarMapaVisivel(callback) {
        const check = () => {
            const el = document.getElementById("map");
            if (el && el.offsetHeight > 0 && el.offsetWidth > 0) callback();
            else requestAnimationFrame(check);
        };
        check();
    }

    document.getElementById("seguirUsuario")?.addEventListener("click", () => {
        seguir = true;
        mapaInteragido = false;
        document.getElementById("seguirUsuario")?.classList.add("hidden");
        if (marcadorUsuario) {
            mapa.setView(marcadorUsuario.getLatLng(), 18);
        }
    });

    function iconeUsuario(grau = 0) {
        return L.divIcon({
            className: '',
            html: `
            <div id="usuario-icone-wrapper" style="width: 32px; height: 32px; background-color: rgba(0,123,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; transform: rotate(${grau}deg);">
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
        .then(() => {
            msgStatus.textContent = "✅ Vistoria enviada com sucesso!";
            msgStatus.classList.remove("hidden", "text-red-400");
            msgStatus.classList.add("text-green-400");
            setTimeout(limparFormulario, 1500);
        })
        .catch(() => {
            msgStatus.textContent = "⚠️ Sem internet. Vistoria salva para envio automático.";
            msgStatus.classList.remove("hidden", "text-green-400");
            msgStatus.classList.add("text-yellow-400");
            salvarVistoriaOffline(formData);
            setTimeout(limparFormulario, 1500);
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
                    if (arquivosPendentes === 0) enfileirarVistoria(itens);
                };
                reader.readAsDataURL(value);
            } else {
                itens.push({ key, value });
            }
        }
        if (arquivosPendentes === 0) enfileirarVistoria(itens);
    }

    function enfileirarVistoria(itens) {
        const fila = JSON.parse(localStorage.getItem("fila_vistorias") || "[]");
        fila.push({ id: Date.now(), itens });
        localStorage.setItem("fila_vistorias", JSON.stringify(fila));
    }

    function tentarReenviarVistorias() {
        if(!navigator.onLine) return;
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
            method: "POST", body: formData
        })
        .then(res => { if (!res.ok) throw new Error("Erro no reenvio"); return res.json(); })
        .then(data => {
             if(data.success){
                const novaFila = fila.slice(1);
                localStorage.setItem("fila_vistorias", JSON.stringify(novaFila));
                if(novaFila.length > 0) tentarReenviarVistorias();
             }
        })
        .catch(() => {});
    }

    function dataURLToBlob(dataurl, type) {
        const arr = dataurl.split(","), bstr = atob(arr[1]);
        let n = bstr.length;
        const u8arr = new Uint8Array(n);
        while (n--) u8arr[n] = bstr.charCodeAt(n);
        return new Blob([u8arr], { type: type });
    }

    function limparFormulario() {
        // Redireciona para a home para forçar um recarregamento limpo
        window.location.href = 'vistoria_1746.php';
    }

    capturarFoto.addEventListener("change", (e) => {
        tratarArquivos(e.target.files);
        e.target.value = "";
    });

    selecionarFoto.addEventListener("change", (e) => {
        tratarArquivos(e.target.files);
        e.target.value = "";
    });

    function tratarArquivos(arquivos) {
        const limite = 4 - fotosSelecionadas.length;
        if (arquivos.length > limite) {
            alert(`Você só pode adicionar mais ${limite} fotos.`);
        }
        const arquivosSelecionados = Array.from(arquivos).slice(0, limite);
        
        arquivosSelecionados.forEach(async (file) => {
            const comprimida = await compressImage(file);
            fotosSelecionadas.push(comprimida);
            const url = URL.createObjectURL(comprimida);
            const imgContainer = document.createElement('div');
            imgContainer.className = 'relative';
            imgContainer.innerHTML = `
                <img src="${url}" class="w-full h-32 object-cover rounded">
                <button type="button" class="absolute top-1 right-1 bg-red-500 text-white rounded-full h-6 w-6 flex items-center justify-center text-xs font-bold">×</button>
            `;
            previewFotos.appendChild(imgContainer);
            
            imgContainer.querySelector('button').addEventListener('click', () => {
                const index = fotosSelecionadas.findIndex(f => f === comprimida);
                if(index > -1) fotosSelecionadas.splice(index, 1);
                imgContainer.remove();
            });
        });
    }

    setInterval(tentarReenviarVistorias, 30000);
    window.addEventListener("online", tentarReenviarVistorias);

    // Inicia o processo
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(carregarDadosIniciais, (error) => {
            console.warn("Não foi possível obter a localização inicial.", error);
            carregarDadosIniciais(null);
        }, { enableHighAccuracy: true, timeout: 10000 });

        if (watchId) navigator.geolocation.clearWatch(watchId);
        watchId = navigator.geolocation.watchPosition(
            atualizarPosicaoUsuario,
            (erro) => console.warn("Erro no watchPosition:", erro),
            { enableHighAccuracy: true, maximumAge: 0, timeout: 10000 }
        );
    } else {
        carregarDadosIniciais(null);
    }
});

function mostrarPopupImagem(url) {
    const overlay = document.createElement("div");
    overlay.className = "fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50 p-4";
    overlay.innerHTML = `
        <div class="bg-white p-2 rounded shadow-lg max-w-full max-h-full relative">
            <button class="absolute top-2 right-2 text-slate-600 hover:text-red-500 text-3xl transition z-10" onclick="this.closest('.fixed').remove()" title="Fechar">
                <i class="fa-solid fa-circle-xmark"></i>
            </button>
            <div class="max-w-[90vw] max-h-[90vh] flex justify-center items-center overflow-hidden">
                <img src="${url}" class="w-auto h-auto max-w-full max-h-full object-contain rounded" />
            </div>
        </div>
    `;
    document.body.appendChild(overlay);
}