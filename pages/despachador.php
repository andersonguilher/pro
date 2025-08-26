<?php
session_start();
$nivel = $_SESSION["nivel"] ?? "";
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Registro de chamados</title>

  <link rel="manifest" href="../manifest.json" />
  <link rel="icon" href="../assets/icons/icon-192.png" />
  <meta name="mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-capable" content="yes" />
  <meta name="apple-mobile-web-app-title" content="ConserVapp" />
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" />

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body class="bg-slate-900 text-white min-h-screen p-4 pb-[100px]" data-nivel="<?php echo $nivel; ?>">
  <div id="barraProgresso" class="fixed top-0 left-0 w-0 h-1 bg-blue-500 z-50 transition-all duration-300"></div>
  <h1 class="text-xl font-bold text-center mb-4">Registro de chamados</h1>

  <div class="sticky top-0 bg-slate-900 pb-3 z-50">
    <input type="text" id="buscaChamado" placeholder="Buscar chamado..." class="w-full mb-2 px-3 py-2 rounded bg-slate-800 border border-slate-600 placeholder-gray-400" />
    <div class="grid grid-cols-6 gap-1 mb-2 text-[0.8rem] leading-none">
      <button class="filtro-btn bg-gray-700 w-full px-1 py-1 rounded text-center" data-setor="">Sem setor</button>
      <button class="filtro-btn bg-green-600 w-full px-1 py-1 rounded text-center" data-setor="todos">Todos</button>
      <button class="filtro-btn bg-yellow-600 w-full px-1 py-1 rounded text-center" data-setor="GERENTE">Gerente</button>
      <button class="filtro-btn bg-red-600 w-full px-1 py-1 rounded text-center" data-setor="FISCAL">Fiscal</button>
      <button class="filtro-btn bg-blue-800 w-full px-1 py-1 rounded text-center" data-setor="DESPACHADOR">Desp.</button>
      <button class="filtro-btn bg-pink-600 text-white w-full px-1 py-1 rounded text-center" data-setor="CONC.">Conc.</button>
    </div>
  </div>

  <div id="listaChamados" class="space-y-3 mb-24"></div>

  <script>
    function setorKey(s) {
      const valor = (s || "")
        .toString()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .trim()
        .toUpperCase();

      if (valor.includes("GERENTE") || valor.includes("PROGRAMAR")) return "GERENTE";
      if (valor.includes("FISCAL")) return "FISCAL";
      if (valor.includes("DESPACHADOR")) return "DESPACHADOR";
      if (valor.includes("CONCESSIONARIA")) return "CONC.";
      return "SEM SETOR";
    }

    document.addEventListener("DOMContentLoaded", () => {
      const listaChamados = document.getElementById("listaChamados");
      const buscaInput = document.getElementById("buscaChamado");
      let chamados = [], aberto = null;
      let cardDeOrigem = null;
      let filtroSetor = "";

      const barra = document.getElementById("barraProgresso");
      barra.classList.remove("w-0");
      barra.classList.add("w-1/2"); // Começa a carregar

      fetch("../php/listar_chamados_despachador.php")
        .then(res => res.json())
        .then(data => {
          chamados = data;

          const nivelUsuario = document.body.dataset.nivel;
          const botoesFiltro = document.querySelectorAll(".filtro-btn");
          const nivel = nivelUsuario.toLowerCase();

          if (nivel === "despachador") {
            filtroSetor = "DESPACHADOR";
            botoesFiltro.forEach(btn => {
              if (btn.dataset.setor !== "DESPACHADOR") {
                btn.style.display = "none";
              }
            });
          } else if (nivel === "concessionaria") {
            filtroSetor = "CONC.";
            botoesFiltro.forEach(btn => {
              if (btn.dataset.setor !== "CONC.") {
                btn.style.display = "none";
              }
            });
          } else if (nivel === "gerente") {
            filtroSetor = "GERENTE";
            botoesFiltro.forEach(btn => {
              if (btn.dataset.setor !== "GERENTE") {
                btn.style.display = "none";
              }
            });
          } else {
            filtroSetor = "";
          }

          aplicarFiltros();

          barra.classList.remove("w-1/2");
          barra.classList.add("w-full");

          setTimeout(() => {
            barra.classList.add("opacity-0");
          }, 500);
        });

      const nivelUsuario = document.body.dataset.nivel;
      const botoesFiltro = document.querySelectorAll(".filtro-btn");

      // Define filtro inicial e oculta botões se for despachador
      if (nivelUsuario.toLowerCase() === "despachador") {
        filtroSetor = "DESPACHADOR";
        // Oculta todos os botões exceto "Despachador"
        botoesFiltro.forEach(btn => {
          if (btn.dataset.setor !== "DESPACHADOR") {
            btn.style.display = "none";
          }
        });
      } else {
        filtroSetor = ""; // admin começa com "Sem setor"
      }

      function aplicarFiltros() {
        const termo = buscaInput.value.trim().toLowerCase();

        const textoCompleto = (c) =>
          (
            c.id_chamado +
            " " + c.logradouro +
            " " + c.bairro +
            " " + c.subtipo
          ).toLowerCase();

        // 🔁 Agrupar TODOS os chamados por chave e setor
        const mapaSimilares = {};
        chamados.forEach(c => {
          const chave = `${c.logradouro}||${c.bairro}||${c.subtipo}`;
          const setor = setorKey(c.setor);
          if (!mapaSimilares[chave]) mapaSimilares[chave] = {};
          mapaSimilares[chave][setor] = (mapaSimilares[chave][setor] || 0) + 1;
        });

        // 🎯 Agora filtramos os chamados conforme busca + filtro atual
        let filtrados = chamados.filter(c => {
          const setor = setorKey(c.setor);
          const matchBusca = textoCompleto(c).includes(termo);

          if (filtroSetor === "todos") return matchBusca;
          if (filtroSetor === "") return matchBusca && setor === "SEM SETOR";
          return matchBusca && setor === filtroSetor.toUpperCase();
        });

        // 📦 Para cada chamado filtrado, anexa a lista de similares por setor
        filtrados = filtrados.map(c => {
          const chave = `${c.logradouro}||${c.bairro}||${c.subtipo}`;
          return {
            ...c,
            similares_por_setor: mapaSimilares[chave] || {}
          };
        });

        exibirChamados(filtrados);
      }

      buscaInput.addEventListener("input", aplicarFiltros);

      document.querySelectorAll(".filtro-btn").forEach(btn => {
        btn.addEventListener("click", () => {
          filtroSetor = btn.dataset.setor;
          aplicarFiltros();
        });
      });

      function extrairImagens(texto) {
        const links = texto.match(/(https?:\/\/[^\s]+)/g) || [];
        return links.filter(link => link.match(/\.(jpg|jpeg|png|gif|webp)/i));
      }

      function exibirChamados(lista) {
        listaChamados.innerHTML = "";
        const hoje = new Date(); hoje.setHours(0, 0, 0, 0);

        // Configurações de tamanho fixo para os botões
        const buttonWidth = '85px';
        const buttonHeight = '100%';
        const buttonFontSize = '0.65rem';

        lista.forEach((chamado, index) => {
          const slaDate = new Date(chamado.data_sla);
          slaDate.setHours(0, 0, 0, 0);
          const diffDias = Math.floor((slaDate - hoje) / (1000 * 60 * 60 * 24));
          const dia = String(slaDate.getDate()).padStart(2, '0');
          const mes = String(slaDate.getMonth() + 1).padStart(2, '0');
          const ano = String(slaDate.getFullYear()).slice(-2);
          const slaFormatada = `${dia}-${mes}-${ano}`;

          let corSLA = "bg-green-400 text-black";
          if (diffDias < 0) corSLA = "bg-red-500 text-white";
          else if (diffDias <= 3) corSLA = "bg-orange-400 text-black";

          const container = document.createElement("div");
          container.className = "relative overflow-hidden transition-all duration-300";
          container.dataset.id = chamado.id_chamado;

          // Botões da esquerda - tamanho fixo
          const botoesEsquerda = document.createElement("div");
          botoesEsquerda.className = "absolute left-0 top-0 flex flex-col z-0";
          botoesEsquerda.style.width = buttonWidth;
          botoesEsquerda.style.display = "none";
          botoesEsquerda.innerHTML = `
            <button class="btnSetor bg-red-600 flex items-center justify-center rounded-tl-lg shadow"
                data-id="${chamado.id_chamado}" data-setor="Fiscal"
                style="font-size: ${buttonFontSize}; width: ${buttonWidth}; height: 42px;">
              <span class="font-bold text-white drop-shadow">FISCAL</span>
            </button>
            <div class="bg-yellow-500 flex items-center justify-center rounded-bl-lg shadow"
                style="font-size: ${buttonFontSize}; width: ${buttonWidth}; height: 42px;">
              <span class="font-bold text-black drop-shadow">GERENTE</span>
            </div>
          `;

          // Botões da direita - tamanho fixo
          const btnDespachador = document.createElement("div");
          btnDespachador.className = "absolute right-0 top-0 flex flex-col z-0";
          btnDespachador.style.width = buttonWidth;
          btnDespachador.style.display = "none";
          btnDespachador.innerHTML = `
            <button class="btnDespachador bg-blue-600 flex items-center justify-center rounded-tr-lg shadow"
                data-id="${chamado.id_chamado}"
                style="font-size: ${buttonFontSize}; width: ${buttonWidth}; height: 42px;">
              <span class="font-bold text-white drop-shadow">DESPACHADOR</span>
            </button>
            <div class="bg-blue-800 flex items-center justify-center rounded-br-lg shadow"
                style="font-size: ${buttonFontSize}; width: ${buttonWidth}; height: 42px;">
              <span class="font-bold text-white drop-shadow">...</span>
            </div>
          `;

          const card = document.createElement("div");

          const obsTemTexto = chamado.obs?.trim().length > 0;
          const ehDespachador = setorKey(chamado.setor) === "DESPACHADOR";

          const fundoVistoriado = (obsTemTexto && ehDespachador)
            ? "bg-emerald-900/80 ring-2 ring-emerald-400"
            : "bg-slate-800";

          card.className = `${fundoVistoriado} border border-slate-600 rounded-t-lg p-3 z-10 relative transition-transform duration-300 ease-in-out card-hover-left card-hover-right`;

          card.addEventListener("mousemove", (e) => {
            const bounds = card.getBoundingClientRect();
            const x = e.clientX - bounds.left;
            const limite = bounds.width * 0.15;

            if (x < limite) {
              card.classList.add("hover-left");
              card.classList.remove("hover-right");
            } else if (x > bounds.width - limite) {
              card.classList.add("hover-right");
              card.classList.remove("hover-left");
            } else {
              card.classList.remove("hover-left", "hover-right");
            }
          });

          card.addEventListener("mouseleave", () => {
            card.classList.remove("hover-left", "hover-right");
          });

          const setor = setorKey(chamado.setor);
          const corSetor = {
            "FISCAL": "bg-red-600",
            "GERENTE": "bg-yellow-400 text-black",
            "DESPACHADOR": "bg-blue-600",
            "CONC.": "bg-pink-600 text-white",
            "SEM SETOR": "bg-gray-700"
          }[setor] || "bg-slate-700";

          card.innerHTML = `
            <div class="text-sm font-semibold flex items-center gap-2 justify-between">
              <div class="flex items-center gap-2">
                Chamado ${chamado.id_chamado}
                ${ehDespachador && obsTemTexto ? '<i class="fas fa-check-circle text-emerald-400 text-xs" title="Vistoriado"></i>' : ''}
                <span class="icone-foto hidden text-yellow-400"><i class="fas fa-camera" title="Foto do usuário"></i></span>
                <span class="icone-foto-fiscal hidden text-red-500"><i class="fas fa-camera" title="Foto do fiscal"></i></span>
              </div>
              <span class="flex gap-1">
                ${Object.entries(chamado.similares_por_setor || {}).map(([setor, qtd]) => {
                  const atualMesmoSetor = setorKey(chamado.setor) === setorKey(setor);
                  const extras = atualMesmoSetor ? qtd - 1 : qtd;
                  if (extras <= 0) return "";

                  const cor = {
                    "FISCAL": "bg-red-600",
                    "GERENTE": "bg-yellow-600 text-black",
                    "DESPACHADOR": "bg-blue-800",
                    "CONC.": "bg-pink-600 text-white",
                    "SEM SETOR": "bg-gray-700"
                  }[setorKey(setor)] || "bg-gray-700";

                  const chave = setorKey(setor);

                  return `<span 
                    class="text-xs font-bold px-2 py-0.5 rounded-full text-white drop-shadow cursor-pointer similar-badge ${cor}" 
                    data-logradouro="${chamado.logradouro}" 
                    data-bairro="${chamado.bairro}" 
                    data-subtipo="${chamado.subtipo}" 
                    data-setor="${chave}"
                  >+${extras}</span>`;
                }).join("")}
              </span>
            </div>
            <div class="text-xs mt-1 flex items-start justify-between gap-2">
              <div class="text-cyan-400 font-semibold leading-snug">
                ${chamado.subtipo}
              </div>
              <div class="shrink-0">
                <span class="inline-block px-2 py-0.5 text-center rounded text-xs font-bold whitespace-nowrap ${corSLA}">
                  SLA: ${slaFormatada}
                </span>
              </div>
            </div>
            <div class="text-xs mt-1 mb-1">${chamado.endereco}</div>
            <div class="${corSetor} text-xs font-bold text-center rounded-b-md py-0.5">
              ${setor || "Sem setor"}
            </div>
          `;

          // Aguarda renderização do DOM para ajustar altura dos botões
          setTimeout(() => {
            const alturaCard = card.clientHeight + "px";
            botoesEsquerda.style.height = alturaCard;
            btnDespachador.style.height = alturaCard;

            // Divide em 2 partes (50% cada)
            botoesEsquerda.querySelectorAll("div, button").forEach(el => {
              el.style.height = "50%";
            });
            btnDespachador.querySelectorAll("div").forEach(el => {
              el.style.height = "50%";
            });
          }, 0);

          const cardExtra = document.createElement("div");
          cardExtra.className = "hidden bg-slate-800 px-3 pt-2 pb-4 animate-slideUpFromCard card-extra";
          cardExtra.innerHTML = `<div class="text-xs font-semibold text-gray-400 mb-1">Descrição:</div>`;
          const desc = document.createElement('div');
          const textoLimpo = (chamado.ds_chamado || 'Sem descrição.').replace(/https?:\/\/[\S]+/g, '').trim();
          desc.textContent = textoLimpo;
          desc.className = 'text-sm mb-2 text-gray-200';
          cardExtra.appendChild(desc);
          if (chamado.complemento && chamado.complemento.trim() !== "") {
            const comp = document.createElement("div");
            comp.className = "text-xs text-gray-400 mt-1";
            comp.innerHTML = `<strong>Complemento:</strong> ${chamado.complemento}`;
            cardExtra.appendChild(comp);
          }

          if (chamado.referencia && chamado.referencia.trim() !== "") {
            const ref = document.createElement("div");
            ref.className = "text-xs text-gray-400 mt-1";
            ref.innerHTML = `<strong>Referência:</strong> ${chamado.referencia}`;
            cardExtra.appendChild(ref);
          }

          // Observação (se houver)
          const obs = chamado.obs?.trim();
          if (obs && obs !== "") {
            const rotulo = document.createElement('div');
            rotulo.className = 'text-xs text-gray-400 mt-3 mb-1';
            rotulo.textContent = 'Detalhes adicionais:';
            cardExtra.appendChild(rotulo);

            const obsTexto = document.createElement('div');
            obsTexto.className = `
              text-sm text-white bg-slate-800/80 border-l-4 border-yellow-400
              px-3 py-2 rounded shadow-inner whitespace-pre-line animate-pulseObs font-medium
            `;
            obsTexto.textContent = obs;
            cardExtra.appendChild(obsTexto);
          }

          const separador = document.createElement('div');
          separador.className = 'h-px bg-slate-600 my-2';
          cardExtra.appendChild(separador);

          const mapaId = `mapa-${index}`;
          const mapaContainer = document.createElement("div");
          mapaContainer.id = mapaId;
          mapaContainer.className = "mt-2 rounded shadow";
          mapaContainer.style = "height: 180px; width: 100%;";
          cardExtra.appendChild(mapaContainer);

          const imagens = extrairImagens(chamado.ds_chamado || "");
          const fotosFiscais = [chamado.foto1, chamado.foto2, chamado.foto3, chamado.foto4].filter(f => f);

          if (imagens.length > 0) {
            card.querySelector('.icone-foto')?.classList.remove('hidden');
          }
          if (fotosFiscais.length > 0) {
            card.querySelector('.icone-foto-fiscal')?.classList.remove('hidden');
          }

          let imagensCarregadas = false;

          let startX = 0;
          let isSwiping = false;
          let estadoAtual = "centro"; // centro, esquerda, direita

          card.addEventListener("touchstart", e => {
            startX = e.touches[0].clientX;
            isSwiping = true;
          });

          card.addEventListener("touchmove", e => {
            if (!isSwiping) return;
            const currentX = e.touches[0].clientX;
            const diffX = currentX - startX;

            if (diffX > 0) {
              card.style.transform = `translateX(${Math.min(diffX, 85)}px)`;
              botoesEsquerda.style.display = "flex";
              btnDespachador.style.display = "none";
            } else {
              card.style.transform = `translateX(${Math.max(diffX, -85)}px)`;
              btnDespachador.style.display = "flex";
              botoesEsquerda.style.display = "none";
            }
          });

          card.addEventListener("touchend", e => {
            if (!isSwiping) return;
            isSwiping = false;

            const endX = e.changedTouches[0].clientX;
            const diffX = endX - startX;

            if (diffX > 60) {
              // Deslize para direita
              if (estadoAtual === "esquerda") {
                // Centraliza se deslizar na direção oposta
                card.style.transform = "translateX(0)";
                btnDespachador.style.display = "none";
                botoesEsquerda.style.display = "none";
                estadoAtual = "centro";
              } else {
                // Mostra botões da esquerda
                card.style.transform = "translateX(85px)";
                botoesEsquerda.style.display = "flex";
                btnDespachador.style.display = "none";
                estadoAtual = "direita";
              }
            } else if (diffX < -60) {
              // Deslize para esquerda
              if (estadoAtual === "direita") {
                // Centraliza se deslizar na direção oposta
                card.style.transform = "translateX(0)";
                botoesEsquerda.style.display = "none";
                btnDespachador.style.display = "none";
                estadoAtual = "centro";
              } else {
                // Mostra botões da direita
                card.style.transform = "translateX(-85px)";
                btnDespachador.style.display = "flex";
                botoesEsquerda.style.display = "none";
                estadoAtual = "esquerda";
              }
            } else {
              // Retorna ao centro se o deslize for pequeno
              card.style.transform = "translateX(0)";
              botoesEsquerda.style.display = "none";
              btnDespachador.style.display = "none";
              estadoAtual = "centro";
            }
          });

          card.addEventListener("click", (e) => {
            if (isSwiping) {
              isSwiping = false;
              return;
            }

            const posX = e.offsetX;
            const largura = card.clientWidth;

            // Se já está deslocado e clicou em qualquer parte → centraliza
            if (estadoAtual !== "centro") {
              card.style.transform = "translateX(0)";
              botoesEsquerda.style.display = "none";
              btnDespachador.style.display = "none";
              estadoAtual = "centro";
              return;
            }

            // Clique na lateral esquerda → desliza para a direita
            if (posX < largura * 0.2) {
              card.style.transform = "translateX(85px)";
              botoesEsquerda.style.display = "flex";
              btnDespachador.style.display = "none";
              estadoAtual = "direita";
              return;
            }

            // Clique na lateral direita → desliza para a esquerda
            if (posX > largura * 0.8) {
              card.style.transform = "translateX(-85px)";
              btnDespachador.style.display = "flex";
              botoesEsquerda.style.display = "none";
              estadoAtual = "esquerda";
              return;
            }

            // Clique na parte central → alterna card-extra (detalhes)
            if (aberto !== null && aberto !== index) {
              document.querySelectorAll(".card-extra").forEach(el => el.classList.add("hidden"));
            }

            const jaEstavaAberto = aberto === index;

            if (jaEstavaAberto) {
              document.querySelectorAll(".card-extra").forEach(el => el.classList.add("hidden"));
              aberto = null;
              return;
            }

            document.querySelectorAll(".card-extra").forEach(el => el.classList.add("hidden"));
            cardExtra.classList.remove("hidden");

            const [latG, lngG] = (chamado.geoloc || "").split(",").map(coord => parseFloat(coord));

            if (!isNaN(latG) && !isNaN(lngG)) {
              carregarMapa(mapaId, latG, lngG);
            } else {
              fetch(`../php/buscar_geolocalizacao.php?logradouro=${encodeURIComponent(chamado.logradouro)}&bairro=${encodeURIComponent(chamado.bairro)}`)
                .then(res => res.json())
                .then(data => {
                  if (data.latitude && data.longitude) {
                    carregarMapa(mapaId, data.latitude, data.longitude);
                  } else {
                    fetch(`../php/obter_distancias.php?logradouro=${encodeURIComponent(chamado.logradouro)}&bairro=${encodeURIComponent(chamado.bairro)}`)
                      .then(res => res.json())
                      .then(final => {
                        if (final.latitude && final.longitude) {
                          carregarMapa(mapaId, final.latitude, final.longitude);
                        } else {
                          document.getElementById(mapaId).innerHTML = "<div class='text-sm text-center mt-6 text-red-400'>Localização não encontrada.</div>";
                        }
                      });
                  }
                });
            }

            if (!imagensCarregadas) {
              const fotosFiscais = [chamado.foto1, chamado.foto2, chamado.foto3, chamado.foto4].filter(f => f);
              if (fotosFiscais.length > 0) {
                card.querySelector('.icone-foto-fiscal')?.classList.remove('hidden');

                const rotuloFiscal = document.createElement("div");
                rotuloFiscal.textContent = "Fotos do fiscal:";
                rotuloFiscal.className = "text-xs font-bold text-red-400 mt-2";
                cardExtra.appendChild(rotuloFiscal);

                const galeriaFiscal = document.createElement("div");
                galeriaFiscal.className = "grid grid-cols-2 gap-2 mt-2";

                fotosFiscais.forEach(src => {
                  const img = document.createElement("img");
                  img.src = `../${src}`;
                  img.className = "rounded shadow object-cover w-full h-32 cursor-pointer transition hover:scale-105";
                  img.onclick = () => window.open(img.src, '_blank');
                  galeriaFiscal.appendChild(img);
                });

                cardExtra.appendChild(galeriaFiscal);
              }

              if (imagens.length > 0) {
                card.querySelector('.icone-foto')?.classList.remove('hidden');

                const rotuloUsuario = document.createElement("div");
                rotuloUsuario.textContent = "Fotos do usuário:";
                rotuloUsuario.className = "text-xs font-bold text-yellow-400 mt-4";
                cardExtra.appendChild(rotuloUsuario);

                const galeriaUsuario = document.createElement("div");
                galeriaUsuario.className = "grid grid-cols-2 gap-2 mt-2";

                imagens.forEach(src => {
                  const img = document.createElement("img");
                  img.src = src.startsWith("uploads/") ? `../${src}` : src;
                  img.className = "rounded shadow object-cover w-full h-32 cursor-pointer transition hover:scale-105";
                  img.onclick = () => window.open(img.src, '_blank');
                  galeriaUsuario.appendChild(img);
                });

                cardExtra.appendChild(galeriaUsuario);
              }

              imagensCarregadas = true;
            }

            aberto = index;

            card.style.transform = "translateX(0)";
            botoesEsquerda.style.display = "none";
            btnDespachador.style.display = "none";
            estadoAtual = "centro";

            const alturaTopo = document.querySelector('.sticky')?.offsetHeight || 0;
            const ajuste = alturaTopo + 8;

            window.scrollTo({
              top: container.offsetTop - ajuste,
              behavior: "smooth"
            });
          });


          cardExtra.classList.add("card-extra");

          container.appendChild(botoesEsquerda);
          container.appendChild(btnDespachador);
          container.appendChild(card);
          container.appendChild(cardExtra);
          listaChamados.appendChild(container);
        });

        document.querySelectorAll(".similar-badge").forEach(el => {
          el.addEventListener("click", (e) => {
            const logradouro = el.dataset.logradouro;
            const bairro = el.dataset.bairro;
            const subtipo = el.dataset.subtipo;
            const setor = el.dataset.setor;

            // Salva o elemento do card original
            cardDeOrigem = el.closest("[data-id]")?.dataset.id;

            const similares = chamados.filter(c => {
              return c.logradouro === logradouro &&
                c.bairro === bairro &&
                c.subtipo === subtipo &&
                setorKey(c.setor) === setor;
            });

            exibirChamados(similares);
            buscaInput.value = "";

            // Cria botão voltar
            const voltarBtn = document.createElement("button");
            voltarBtn.textContent = "⬅️ Voltar";
            voltarBtn.className = "fixed bottom-24 right-4 bg-yellow-500 hover:bg-yellow-600 text-black font-bold px-4 py-2 rounded shadow z-[9999]";
            voltarBtn.id = "btn-voltar";

            voltarBtn.onclick = () => {
              aplicarFiltros();

              // Espera a chamada `exibirChamados()` terminar de desenhar os elementos
              requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                  setTimeout(() => {
                    if (cardDeOrigem) {
                      const container = document.querySelector(`[data-id='${cardDeOrigem}']`);
                      const cardRef = container?.querySelector("div.z-10");
                      if (cardRef) {
                        const ajuste = document.querySelector('.sticky')?.offsetHeight || 0;
                        window.scrollTo({
                          top: container.offsetTop - ajuste - 8,
                          behavior: "smooth"
                        });

                        // Força o reflow antes de aplicar a animação
                        void cardRef.offsetWidth;
                        cardRef.classList.add("flash-card");

                        setTimeout(() => cardRef.classList.remove("flash-card"), 3000);
                      }
                      cardDeOrigem = null;
                    }
                    voltarBtn.remove();
                  }, 100); // pequeno atraso garante que o DOM foi renderizado
                });
              });
            };

            document.body.appendChild(voltarBtn);
            window.scrollTo({ top: 0, behavior: "smooth" });
          });
        });

      }
    });

    listaChamados.addEventListener("click", function (e) {
      // ✅ BOTÃO FISCAL
      if (e.target.closest(".btnSetor")) {
        const btn = e.target.closest(".btnSetor");
        const id = btn.dataset.id;
        const setor = btn.dataset.setor;

        fetch("../php/atualizar_setor.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          body: `id=${encodeURIComponent(id)}&setor=${encodeURIComponent(setor)}`
        })
          .then(res => res.json())
          .then(data => {
            if (data.sucesso) {
              const card = btn.closest(".relative");
              if (card) {
                const msg = document.createElement("div");
                msg.innerHTML = `<i class="fas fa-check-circle mr-1 text-white/80"></i> Encaminhado para <strong>Fiscal</strong>`;
                msg.className = `
                  absolute top-2 left-1/2 -translate-x-1/2 z-50
                  bg-emerald-600/90 text-white text-sm px-4 py-2
                  rounded-lg shadow-lg border border-white/20
                  backdrop-blur-sm transition-opacity duration-500
                  flex items-center gap-2 animate-fadeInUp
                `;
                card.appendChild(msg);

                setTimeout(() => {
                  msg.classList.add("opacity-0");
                  setTimeout(() => msg.remove(), 500);
                }, 2000);

                setTimeout(() => {
                  card.classList.add("opacity-0", "scale-95", "transition-all", "duration-300");
                  setTimeout(() => card.remove(), 300);
                }, 2000);
              }
            }
          })
          .catch(err => {
            console.error(err);
            alert("Erro de conexão");
          });
      }

      // ✅ BOTÃO DESPACHADOR
      if (e.target.closest(".btnDespachador")) {
        const btn = e.target.closest(".btnDespachador");
        const id = btn.dataset.id;

        const textoObs = prompt("Digite a observação para encaminhar ao Despachador:");
        if (!textoObs || textoObs.trim() === "") return;

        const dados = new URLSearchParams();
        dados.append("id", id);
        dados.append("setor", "Despachador");
        dados.append("obs", textoObs.trim());

        fetch("../php/atualizar_setor_obs.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          body: dados.toString()
        })
          .then(res => res.json())
          .then(data => {
            if (data.sucesso) {
              const card = btn.closest(".relative");
              if (card) {
                const msg = document.createElement("div");
                msg.innerHTML = `<i class="fas fa-check-circle mr-1 text-white/80"></i> Encaminhado para <strong>Despachador</strong>`;
                msg.className = `
                  absolute top-2 left-1/2 -translate-x-1/2 z-50
                  bg-blue-600/90 text-white text-sm px-4 py-2
                  rounded-lg shadow-lg border border-white/20
                  backdrop-blur-sm transition-opacity duration-500
                  flex items-center gap-2 animate-fadeInUp
                `;
                card.appendChild(msg);

                setTimeout(() => {
                  msg.classList.add("opacity-0");
                  setTimeout(() => msg.remove(), 500);
                }, 2000);

                setTimeout(() => {
                  card.classList.add("opacity-0", "scale-95", "transition-all", "duration-300");
                  setTimeout(() => card.remove(), 300);
                }, 2000);
              }
            }
          })
          .catch(err => {
            console.error(err);
            alert("Erro ao encaminhar para Despachador.");
          });
      }
    });

    function carregarMapa(mapaId, lat, lng) {
      setTimeout(() => {
        const container = document.getElementById(mapaId);
        if (!container) return; // ← Evita erro se container ainda não existe

        if (container._leaflet_id) {
          container._leaflet_id = null;
          container.innerHTML = "";
        }

        const mapa = L.map(mapaId, {
          zoomControl: false,
          attributionControl: false,
        }).setView([lat, lng], 17);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
          maxZoom: 19
        }).addTo(mapa);

        L.marker([lat, lng]).addTo(mapa);

        setTimeout(() => mapa.invalidateSize(), 100);
      }, 300);
    }
  </script>

  <style>
    .card-hover-left::before,
    .card-hover-right::after {
      content: "";
      position: absolute;
      top: 0;
      width: 20%;
      height: 100%;
      pointer-events: none;
      z-index: 10;
      transition: background 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
    }

    .card-hover-left::before {
      left: 0;
    }

    .card-hover-right::after {
      right: 0;
    }

    .card-hover-left.hover-left::before {
      background: linear-gradient(to right, rgba(0, 200, 255, 0.12), transparent);
      box-shadow: 4px 0 12px rgba(0, 200, 255, 0.4);
    }

    .card-hover-right.hover-right::after {
      background: linear-gradient(to left, rgba(255, 140, 0, 0.12), transparent);
      box-shadow: -4px 0 12px rgba(255, 140, 0, 0.4);
    }
    
    .animate-slideUpFromCard {
      animation: slideDown 0.3s ease-out;
    }
    @keyframes slideUpFromCard {
      from { transform: translateY(20px); opacity: 0; }
      to { transform: translateY(0); opacity: 1; }
    }
    #barraProgresso.opacity-0 {
      transition: opacity 0.5s ease;
      opacity: 0;
    }
    @keyframes fadeInUp {
      from {
        transform: translateY(8px);
        opacity: 0;
      }
      to {
        transform: translateY(0);
        opacity: 1;
      }
    }
    .animate-fadeInUp {
      animation: fadeInUp 0.4s ease-out;
    }
    @keyframes pulseObs {
      0%, 100% {
        box-shadow: 0 0 6px #facc15aa;
        border-color: #facc15;
      }
      50% {
        box-shadow: 0 0 14px #fde047cc;
        border-color: #fde047;
      }
    }
    .animate-pulseObs {
      animation: pulseObs 1.5s ease-in-out infinite;
    }
    .overflow-x-auto::-webkit-scrollbar {
      display: none;
    }
    .no-scrollbar::-webkit-scrollbar {
      display: none;
    }
    .no-scrollbar {
      -ms-overflow-style: none;
      scrollbar-width: none;
    }
    .card-extra {
      box-shadow: inset 0 3px 4px rgba(0, 0, 0, 0.3);
    }
    .filtro-btn {}
    @keyframes flashCardBorder {
      0%, 100% {
        border-color: transparent;
        box-shadow: none;
      }
      50% {
        border-color: #facc15;
        box-shadow: 0 0 10px #facc15;
      }
    }
    .flash-card {
      animation: flashCardBorder 1s ease-in-out 3;
      border-width: 2px !important;
      border-style: solid !important;
    }
  </style>

  <?php include __DIR__ . '/../includes/menu_inferior.php'; ?>
  <script src="../js/logout.js"></script>
</body>
</html>
