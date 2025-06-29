import { compressImage } from "./compressImage.js";

let fotosSelecionadas = [];

// Script principal
document.addEventListener("DOMContentLoaded", () => {
  const listaChamados = document.getElementById("listaChamados");
  const formVistoria = document.getElementById("formVistoria");
  const dadosChamado = document.getElementById("dadosChamado");
  const formulario = document.getElementById("formularioVistoria");
  const buscaInput = document.getElementById("buscaChamado");
  const msgStatus = document.getElementById("msgStatus");
  const previewFotos = document.getElementById("previewFotos");
  const inputFotos = document.getElementById("fotos");

  let chamados = [];
  let marcador = null;
  let mapa = null;

  fetch("../php/listar_dados1746.php")
    .then((res) => res.json())
    .then((data) => {
      chamados = data;
      exibirChamados(chamados);
    });

  function exibirChamados(lista) {
    listaChamados.innerHTML = "";
    lista.forEach((chamado) => {
      const btn = document.createElement("button");
      btn.className =
        "block w-full text-left bg-slate-800 border border-slate-600 rounded-lg p-3 hover:bg-slate-700 transition";
      btn.innerHTML = `
        <div class="text-sm font-semibold">Chamado ${chamado.id_chamado}</div>
        <div class="text-xs text-slate-400">${chamado.subtipo} - SLA: ${chamado.data_sla}</div>
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

    if (mapa) {
      mapa.invalidateSize();
      return;
    }

    if (!mapDiv || mapDiv.offsetHeight === 0) {
      console.warn("Div #map ainda não visível. Tentando novamente em 200ms...");
      return setTimeout(inicializarMapa, 200);
    }

    mapa = L.map(mapDiv).setView([-22.9307, -43.6815], 15);

    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "© OpenStreetMap",
    }).addTo(mapa);

    mapa.on("click", function (e) {
      if (marcador) {
        marcador.setLatLng(e.latlng);
      } else {
        marcador = L.marker(e.latlng, { draggable: true }).addTo(mapa);
      }
    });

    if ("geolocation" in navigator) {
      navigator.geolocation.getCurrentPosition((pos) => {
        const latlng = [pos.coords.latitude, pos.coords.longitude];
        mapa.setView(latlng, 18);

        if (marcador) {
          marcador.setLatLng(latlng);
        } else {
          marcador = L.marker(latlng, { draggable: true }).addTo(mapa);
        }

        mapa.invalidateSize();
      }, (err) => {
        console.warn("Erro ao obter localização:", err.message);
      });
    }
  }

  inputFotos.addEventListener("change", async (e) => {
    previewFotos.innerHTML = "";
    fotosSelecionadas = [];
    const arquivos = Array.from(e.target.files).slice(0, 4);
    for (const file of arquivos) {
      const comprimida = await compressImage(file);
      fotosSelecionadas.push(comprimida);
      const url = URL.createObjectURL(comprimida);
      const img = document.createElement("img");
      img.src = url;
      img.className = "w-full h-32 object-cover rounded";
      previewFotos.appendChild(img);
    }
  });

  function esperarMapaVisivel(callback) {
    const check = () => {
      const el = document.getElementById("map");
      if (el && el.offsetHeight > 0 && el.offsetWidth > 0) {
        callback();
      } else {
        requestAnimationFrame(check);
      }
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
      .catch((err) => {
        msgStatus.classList.remove("hidden");
        msgStatus.textContent = "Erro ao enviar a vistoria.";
        msgStatus.classList.add("text-red-400");
      });
  });
});
