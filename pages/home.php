<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="theme-color" content="#0f172a" />
  <link rel="manifest" href="../manifest.json" />
  <link rel="icon" href="../assets/icons/icon-192.png" />
  <title>Fiscaliza 23ª GC</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white min-h-screen flex flex-col items-center justify-center px-4">

  <header class="text-center mb-8">
    <img src="../assets/icons/icon-192.png" alt="Logo Fiscaliza" class="w-20 h-20 mx-auto rounded-xl mb-2">
    <h1 class="text-2xl font-bold">Fiscaliza 23ª GC</h1>
    <p class="text-slate-300 text-sm">Santa Cruz e Paciência</p>
    <p id="nomeUsuario" class="text-cyan-400 text-sm mt-1 font-semibold"></p>
  </header>

  <main class="w-full max-w-sm space-y-6">
    <button onclick="location.href='vistoria_1746.php'"
      class="w-full flex items-center justify-center gap-2 bg-cyan-600 hover:bg-cyan-700 text-white text-lg py-4 rounded-2xl shadow-lg transition-all">
      📄 Vistoria 1746
    </button>

    <button onclick="location.href='vistoria_local.php'"
      class="w-full flex items-center justify-center gap-2 bg-green-600 hover:bg-green-700 text-white text-lg py-4 rounded-2xl shadow-lg transition-all">
      🛠️ Vistoria Local
    </button>

    <button id="btnAdmin"
      class="w-full flex items-center justify-center gap-2 bg-amber-500 hover:bg-amber-400 text-black text-lg py-4 rounded-2xl shadow-lg transition-all hidden">
      🔐 Administração
    </button>
  </main>

  <footer class="mt-12 text-center text-xs text-slate-400 leading-snug">
    <p>Desenvolvido para a 23ª Gerência de Conservação<br>Prefeitura do Rio</p>
    <p class="text-slate-500 italic mt-1">por Anderson Guilherme</p>
  </footer>


  <script>
    document.addEventListener("DOMContentLoaded", () => {
      fetch("../php/verifica_sessao.php")
        .then(res => res.json())
        .then(data => {
          if (data.logado) {
            document.getElementById("nomeUsuario").textContent = `👤 ${data.nome_completo}`;
          }

          if (data.nivel === 'admin') {
            const btn = document.getElementById("btnAdmin");
            btn.classList.remove("hidden");
            btn.addEventListener("click", () => {
              window.location.href = "admin_dashboard.php";
            });
          }
        });
    });
  </script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js" defer></script>
<?php include __DIR__ . '/../includes/menu_inferior.php'; ?>  
</body>
</html>