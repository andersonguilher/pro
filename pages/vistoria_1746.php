<?php
session_set_cookie_params([
  'httponly' => true,
  'secure'   => !empty($_SERVER['HTTPS']),
  'samesite' => 'Lax',
]);
session_start();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="ConserVapp">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Vistoria 1746</title>

  <!-- Manifesto PWA e ícone -->
  <link rel="manifest" href="../manifest.json" />
  <link rel="icon" href="../assets/icons/icon-192.png" />

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Leaflet CSS -->
<link
  rel="stylesheet"
  href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
/>

<script
  src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
></script>


  <!-- Script da página -->
  <script type="module" src="../js/vistoria1746.js" defer></script>
</head>
<body class="bg-slate-900 text-white min-h-screen p-4 pb-[100px]">
  <header class="mb-4">
    <h1 class="text-2xl font-bold text-center mb-2">📄 Vistoria 1746</h1>
    <input type="text" id="buscaChamado" placeholder="Buscar por número..."
      class="w-full p-2 rounded-lg bg-slate-800 border border-slate-700 text-white focus:ring-2 focus:ring-cyan-500" />
  </header>

  <section id="listaChamados" class="space-y-3 overflow-y-auto max-h-[calc(100vh-300px)] mb-6">
    <!-- Lista será carregada via JS -->
  </section>

  <section id="formVistoria" class="hidden">
    <form id="formularioVistoria" class="space-y-4" method="POST" enctype="multipart/form-data">
      <div id="dadosChamado" class="text-sm bg-slate-800 p-3 rounded-lg space-y-1 border border-slate-700">
        <!-- Dados do chamado serão carregados aqui -->
      </div>

      <div>
        <?php
        require_once '../php/conexao.php';
        $tipos = $conn->query("SELECT nome FROM tipos_servico ORDER BY nome ASC");
        ?>
        <label class="block mb-1 text-sm font-medium">Tipo de serviço</label>
        <select name="tipo_servico" required
          class="w-full bg-slate-800 p-2 rounded-lg border border-slate-600">
          <option value="">Selecione...</option>
          <?php while ($row = $tipos->fetch_assoc()): ?>
            <option value="<?= htmlspecialchars($row['nome']) ?>">
              <?= htmlspecialchars($row['nome']) ?>
            </option>
          <?php endwhile; ?>
        </select>

      </div>

      <div>
        <label class="block mb-1 text-sm font-medium">Descrição da vistoria</label>
        <textarea name="descricao" required rows="4"
          class="w-full bg-slate-800 p-2 rounded-lg border border-slate-600 resize-none"></textarea>
      </div>

      <div>
        <label class="block mb-1 text-sm font-medium">Fotos (máx. 4)</label>

        <!-- Preview primeiro -->
        <div id="previewFotos" class="grid grid-cols-2 gap-2 mb-3"></div>

        <!-- Botões depois -->
        <div class="flex gap-2">
          <input type="file" id="capturarFoto" accept="image/*" capture="environment" hidden>
          <button type="button" onclick="document.getElementById('capturarFoto').click()"
            class="flex-1 bg-cyan-600 hover:bg-cyan-700 text-white text-sm py-2 rounded-lg font-semibold">
            📷 Capturar
          </button>
          <input type="file" id="selecionarFoto" accept="image/*" multiple hidden>
          <button type="button" onclick="document.getElementById('selecionarFoto').click()"
            class="flex-1 bg-sky-600 hover:bg-sky-700 text-white text-sm py-2 rounded-lg font-semibold">
            📁 Selecionar
          </button>
        </div>
      </div>

      <div>
        <label class="block mb-1 text-sm font-medium">Localização no mapa</label>
        <div class="flex flex-col sm:flex-row gap-2 mt-2">
          <input type="text" id="inputEnderecoCompleto" placeholder="Ex: Av. João XXIII 123 Santa Cruz"
            class="flex-1 p-2 rounded bg-slate-800 border border-slate-600 w-full" />
          <button type="button" id="btnBuscarEndereco"
            class="px-4 py-2 rounded bg-cyan-600 text-white hover:bg-cyan-500 transition whitespace-nowrap">
            Buscar Endereço
          </button>
        </div>
        <div id="map" class="relative w-full h-64 rounded-lg border border-slate-700">
          <button type="button" id="seguirUsuario" title="Seguir posição"
            class="absolute z-[999] top-2 right-2 bg-white text-black px-3 py-1 rounded-full shadow-md hover:bg-gray-200 hidden">
            <i class="fas fa-location-arrow"></i>
          </button>
          <button type="button" id="abrirRota" title="Abrir rota no Google Maps"
            class="absolute z-[999] top-12 right-2 bg-white text-black px-3 py-1 rounded-full shadow-md hover:bg-gray-200 hidden">

            <i class="fas fa-route"></i>
          </button>
        </div>

      </div>

      <button type="submit"
        class="w-full bg-green-600 hover:bg-green-700 transition py-3 rounded-lg font-bold">
        Enviar Vistoria
      </button>

      <p id="msgStatus" class="text-sm text-center mt-2 hidden"></p>
    </form>
  </section>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js" defer></script>
<?php include __DIR__ . '/../includes/menu_inferior.php'; ?>
<script src="../js/logout.js"></script>
</body>
</html>