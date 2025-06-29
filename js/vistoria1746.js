import { compressImage } from "./compressImage.js";

let fotosSelecionadas = [];
let mostrarVencidos = false;

// Script principal
document.addEventListener("DOMContentLoaded", () => {
  const listaChamados = document.getElementById("listaChamados");
  const formVistoria = document.getElementById("formVistoria");
  const dadosChamado = document.getElementById("dadosChamado");
  const formulario = document.getElementById("formularioVistoria");
  const buscaInput = document.getElementById("buscaChamado");
  const msgStatus = document.getElementById("msgStatus");
  const previewFotos = document.getElementById("previewFotos");
  const capturarFoto = document.getElementById("capturarFoto");
  const selecionarFoto = document.getElementById("selecionarFoto");


  let chamados = [];
  let marcador = null;
  let mapa = null;

  fetch("../php/listar_dados1746.php")
    .then((res) => res.json())
    .then((data) => {
      chamados = data;
      exibirChamados(chamados);
    });

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
      const slaDate = new Date(chamado.data_sla);
      slaDate.setHours(0, 0, 0, 0);

      const diffDias = Math.floor((slaDate - hoje) / (1000 * 60 * 60 * 24));
      const slaFormatada = slaDate.toLocaleDateString("pt-BR").replace(/\//g, "-");

      if (!mostrarVencidos && diffDias < 0) return;

      let corSLA = "bg-green-400 text-black";
      if (diffDias < 0) corSLA = "bg-red-500 text-white";
      else if (diffDias <= 3) corSLA = "bg-orange-400 text-black";

      const btn = document.createElement("button");
      btn.className =
        "block w-full text-left bg-slate-800 border border-slate-600 rounded-lg p-3 hover:bg-slate-700 transition";
      btn.innerHTML = `
        <div class="text-sm font-semibold">Chamado ${chamado.id_chamado}</div>
        <div class="text-xs flex justify-between items-center">
          <span class="text-cyan-400 font-semibold">${chamado.subtipo}</span>
          <span class="px-2 py-0.5 rounded text-xs font-bold whitespace-nowrap ${corSLA}">
            SLA: ${slaFormatada}
          </span>
        </div>
        <div class="text-xs">${chamado.endereco}</div>
      `;
      btn.onclick = () => preencherForm(chamado);
      listaChamados.appendChild(btn);
    });
  }

  buscaInput.addEventListener("input", () => {
    const termo = buscaInput.value.trim();
    const filtrados = chamados.filter((c) => c.id_chamado.includes(termo));
    exibirChamados(filtrados);
  });

  function preencherForm(chamado) {
    formVistoria.classList.remove("hidden");
    listaChamados.classList.add("hidden");
    window.scrollTo({ top: formVistoria.offsetTop, behavior: "smooth" });

    dadosChamado.innerHTML = `
      <div><strong>Chamado:</strong> ${chamado.id_chamado}</div>
      <div><strong>SLA:</strong> ${chamado.data_sla}</div>
      <div><strong>Subtipo:</strong> ${chamado.subtipo}</div>
      <div><strong>Endereço:</strong> ${chamado.endereco}</div>
      ${chamado.complemento ? `<div><strong>Complemento:</strong> ${chamado.complemento}</div>` : ""}
      ${chamado.referencia ? `<div><strong>Referência:</strong> ${chamado.referencia}</div>` : ""}
      <div><strong>Descrição:</strong> ${chamado.ds_chamado}</div>
    `;

    esperarMapaVisivel(() => inicializarMapa());
  }

  function inicializarMapa() {
    const mapDiv = document.getElementById("map");
    if (mapa) return mapa.invalidateSize();
    if (!mapDiv || mapDiv.offsetHeight === 0) return setTimeout(inicializarMapa, 200);

    mapa = L.map(mapDiv).setView([-22.9307, -43.6815], 15);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "© OpenStreetMap",
    }).addTo(mapa);

    mapa.on("click", (e) => {
      if (marcador) marcador.setLatLng(e.latlng);
      else marcador = L.marker(e.latlng, { draggable: true }).addTo(mapa);
    });

    if ("geolocation" in navigator) {
      navigator.geolocation.getCurrentPosition((pos) => {
        const latlng = [pos.coords.latitude, pos.coords.longitude];
        mapa.setView(latlng, 18);
        marcador = L.marker(latlng, { draggable: true }).addTo(mapa);
        mapa.invalidateSize();
      });
    }
  }

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

  capturarFoto.addEventListener("change", (e) => {
    tratarArquivos(e.target.files);
    capturarFoto.value = ""; // limpa para permitir nova captura
  });

  selecionarFoto.addEventListener("change", (e) => {
    tratarArquivos(e.target.files);
    selecionarFoto.value = "";
  });


  function esperarMapaVisivel(callback) {
    const check = () => {
      const el = document.getElementById("map");
      if (el && el.offsetHeight > 0 && el.offsetWidth > 0) callback();
      else requestAnimationFrame(check);
    };
    check();
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

    fotosSelecionadas.forEach((foto, i) => {
      formData.append(`foto${i + 1}`, foto);
    });

    const idChamado = dadosChamado.querySelector("div").innerText.split(" ")[1];
    formData.append("id_chamado", idChamado);

    fetch("../php/salvar_vistoria_1746.php", {
      method: "POST",
      body: formData,
    })
      .then((res) => res.text())
      .then((resposta) => {
        msgStatus.classList.remove("hidden");
        msgStatus.textContent = "Vistoria enviada com sucesso!";
        msgStatus.classList.add("text-green-400");

        formulario.reset();
        previewFotos.innerHTML = "";
        formVistoria.classList.add("hidden");
        marcador = null;
        mapa.remove();
        mapa = null;
      })
      .catch(() => {
        msgStatus.classList.remove("hidden");
        msgStatus.textContent = "Erro ao enviar a vistoria.";
        msgStatus.classList.add("text-red-400");
      });
  });
});
