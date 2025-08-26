<?php
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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Vistoria Local</title>
  <link rel="manifest" href="../manifest.json" />
  <link rel="icon" href="../assets/icons/icon-192.png" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <link href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" rel="stylesheet" />
  <script defer src="../js/vistoriaLocal.js"></script>
</head>
<body class="bg-slate-900 text-white min-h-screen p-4 pt-4 pb-[110px]">


  <header class="mb-4">
    <h1 class="text-2xl font-bold text-center mb-2">🛠️ Vistoria Local</h1>
    <p class="text-center text-slate-400 text-sm">Protocolo gerado automaticamente</p>
  </header>

  <form id="formVistoria" class="space-y-4 max-w-md mx-auto">
    <div>
      <label class="block mb-1 text-sm font-medium">Logradouro</label>
      <input type="text" name="logradouro" required
        class="w-full bg-slate-800 p-2 rounded-lg border border-slate-600" />
    </div>

    <div class="flex gap-4">
      <div class="flex-1">
        <label class="block mb-1 text-sm font-medium">Número</label>
        <input type="text" name="numero" required
          class="w-full bg-slate-800 p-2 rounded-lg border border-slate-600" />
      </div>
      <div class="flex-1">
        <label class="block mb-1 text-sm font-medium">Bairro</label>
        <select name="bairro" required
          class="w-full bg-slate-800 p-2 rounded-lg border border-slate-600">
          <option value="">Selecione</option>
          <option value="Santa Cruz">Santa Cruz</option>
          <option value="Paciência">Paciência</option>
        </select>
      </div>
    </div>

    <div>
      <label class="block mb-1 text-sm font-medium">Referência (opcional)</label>
      <input type="text" name="referencia"
        class="w-full bg-slate-800 p-2 rounded-lg border border-slate-600" />
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
      <textarea name="descricao" rows="4" required
        class="w-full bg-slate-800 p-2 rounded-lg border border-slate-600 resize-none"></textarea>
    </div>

    <div>
      <label class="block mb-1 text-sm font-medium">Fotos (máx. 4)</label>
      <input type="file" name="fotos[]" id="fotos" multiple accept="image/*" capture="environment"
        class="w-full text-sm file:mr-2 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-cyan-600 file:text-white" />
    </div>

    <div>
      <label class="block mb-1 text-sm font-medium">Localização no mapa</label>
      <div id="map" class="w-full h-64 rounded-lg border border-slate-700 z-0"></div>
    </div>

    <button type="submit"
      class="w-full bg-green-600 hover:bg-green-700 transition py-3 rounded-lg font-bold">
      Enviar Vistoria
    </button>

    <p id="msgStatus" class="text-sm text-center mt-2 hidden"></p>
  </form>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js" defer></script>
  <?php include __DIR__ . '/../includes/menu_inferior.php'; ?>
  <script src="../js/logout.js"></script>
</body>
</html>
