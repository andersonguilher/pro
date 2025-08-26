<?php
session_start();
require_once(__DIR__ . '/../php/conexao.php');

$anoAtual    = date('Y');
$anoAnterior = $anoAtual - 1;

$placeholders = implode("','", [
  'Manutenção/Desobstrução de ramais de águas pluviais e ralos',
  'Reparo de buraco, deformação ou afundamento na pista'
]);

// === 1) No prazo (2025) ===
$ano_no = $conn->query("
  SELECT COUNT(*) AS qtd FROM dados1746
  WHERE data_inicio IS NOT NULL AND YEAR(data_inicio) = $anoAtual
    AND data_sla IS NOT NULL AND (
      (data_fim IS NOT NULL AND DATE(data_sla) >= DATE(data_fim)) OR
      (data_fim IS NULL     AND DATE(data_sla) >= CURDATE())
    )
")->fetch_assoc()['qtd'];

$ano_total = $conn->query("
  SELECT COUNT(*) AS tot FROM dados1746
  WHERE data_inicio IS NOT NULL AND YEAR(data_inicio) = $anoAtual
")->fetch_assoc()['tot'];

$perc_ano_no = $ano_total ? round($ano_no / $ano_total * 100, 1) : 0;

// Delta anual
$ant_no = $conn->query("
  SELECT COUNT(*) AS qtd FROM dados1746
  WHERE data_inicio IS NOT NULL AND YEAR(data_inicio) = $anoAnterior
    AND data_sla IS NOT NULL AND (
      (data_fim IS NOT NULL AND DATE(data_sla) >= DATE(data_fim)) OR
      (data_fim IS NULL     AND DATE(data_sla) >= CURDATE())
    )
")->fetch_assoc()['qtd'];

$ant_tot = $conn->query("
  SELECT COUNT(*) AS tot FROM dados1746
  WHERE data_inicio IS NOT NULL AND YEAR(data_inicio) = $anoAnterior
")->fetch_assoc()['tot'];

$perc_ant = $ant_tot ? round($ant_no / $ant_tot * 100, 1) : 0;
$delta_ano_no = round($perc_ano_no - $perc_ant, 1);

// === 2) Fora do prazo (2025) ===
$ano_fora = $conn->query("
  SELECT COUNT(*) AS qtd FROM dados1746
  WHERE data_inicio IS NOT NULL AND YEAR(data_inicio) = $anoAtual
    AND data_sla IS NOT NULL AND (
      (data_fim IS NOT NULL AND DATE(data_sla) < DATE(data_fim)) OR
      (data_fim IS NULL     AND DATE(data_sla) < CURDATE())
    )
")->fetch_assoc()['qtd'];

$perc_ano_fora = $ano_total ? round($ano_fora / $ano_total * 100, 1) : 0;

$ant_fora = $conn->query("
  SELECT COUNT(*) AS qtd FROM dados1746
  WHERE data_inicio IS NOT NULL AND YEAR(data_inicio) = $anoAnterior
    AND data_sla IS NOT NULL AND (
      (data_fim IS NOT NULL AND DATE(data_sla) < DATE(data_fim)) OR
      (data_fim IS NULL     AND DATE(data_sla) < CURDATE())
    )
")->fetch_assoc()['qtd'];

$perc_ant_fora = $ant_tot ? round($ant_fora / $ant_tot * 100, 1) : 0;
$delta_ano_fora = round($perc_ano_fora - $perc_ant_fora, 1);

// === 3) Meta 2025: No prazo (%) ===
$meta_no = $conn->query("
  SELECT COUNT(*) AS qtd FROM dados1746
  WHERE subtipo IN ('$placeholders')
    AND data_inicio IS NOT NULL AND YEAR(data_inicio) = $anoAtual
    AND data_sla IS NOT NULL AND (
      (data_fim IS NOT NULL AND DATE(data_sla) >= DATE(data_fim)) OR
      (data_fim IS NULL     AND DATE(data_sla) >= CURDATE())
    )
")->fetch_assoc()['qtd'];

$meta_tot = $conn->query("
  SELECT COUNT(*) AS tot FROM dados1746
  WHERE subtipo IN ('$placeholders')
    AND data_inicio IS NOT NULL AND YEAR(data_inicio) = $anoAtual
")->fetch_assoc()['tot'];

$perc_meta_no = $meta_tot ? round($meta_no / $meta_tot * 100, 1) : 0;

// Delta meta
$metaAnt_no = $conn->query("
  SELECT COUNT(*) AS qtd FROM dados1746
  WHERE subtipo IN ('$placeholders')
    AND data_inicio IS NOT NULL AND YEAR(data_inicio) = $anoAnterior
    AND data_sla IS NOT NULL AND (
      (data_fim IS NOT NULL AND DATE(data_sla) >= DATE(data_fim)) OR
      (data_fim IS NULL     AND DATE(data_sla) >= CURDATE())
    )
")->fetch_assoc()['qtd'];

$metaAnt_tot = $conn->query("
  SELECT COUNT(*) AS tot FROM dados1746
  WHERE subtipo IN ('$placeholders')
    AND data_inicio IS NOT NULL AND YEAR(data_inicio) = $anoAnterior
")->fetch_assoc()['tot'];

$perc_metaAnt = $metaAnt_tot ? round($metaAnt_no / $metaAnt_tot * 100, 1) : 0;
$delta_meta = round($perc_meta_no - $perc_metaAnt, 1);

// === 4) Meta 2025: Fora do prazo (%) ===
$perc_meta_fora = round(100 - $perc_meta_no, 1);
$delta_meta_fora = round(-$delta_meta, 1);
?>



<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <meta name="theme-color" content="#0f172a"/>
  <meta name="mobile-web-app-capable" content="yes"/>
  <meta name="apple-mobile-web-app-capable" content="yes"/>
  <meta name="apple-mobile-web-app-title" content="ConserVapp"/>
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"/>
  <link rel="manifest" href="../manifest.json"/>
  <link rel="icon" href="../assets/icons/icon-192.png"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <title>ConservApp 23ª GC</title>
  <style>
    body { font-family: 'Inter', sans-serif; }
    .card:hover { transform: scale(1.02) }
  </style>
</head>
<body class="bg-slate-900 text-white min-h-screen px-4 pt-6 pb-[100px] grid grid-rows-[auto_1fr_auto]">

  <header class="text-center mb-4">
    <img src="../assets/icons/icon-192.png" alt="Logo" class="w-20 h-20 mx-auto rounded-xl ring-2 ring-cyan-500 shadow mb-2"/>
    <h1 class="text-xl font-bold text-cyan-400">ConservApp - 23ª GC</h1>
    <p class="text-slate-300 text-sm">Santa Cruz e Paciência</p>
    <p id="nomeUsuario" class="text-cyan-300 text-sm mt-1 font-semibold"></p>
  </header>

  <main class="w-full max-w-2xl mx-auto space-y-6">
    <div class="grid grid-cols-1 gap-4 text-sm">

<!-- 1) No prazo (2025) -->
<div class="bg-green-800 p-4 rounded-lg shadow card relative">
  <p class="text-xs text-green-200">No prazo (<?= $anoAtual ?>)</p>
  <h2 class="text-xl font-bold"><?= $perc_ano_no ?>%</h2>
  <div class="absolute top-3 right-3 flex items-center space-x-1 text-sm">
    <?php if ($delta_ano_no >= 0): ?>
      <i class="fa-solid fa-caret-up text-green-400"></i>
      <span class="text-green-400">+<?= $delta_ano_no ?>%</span>
    <?php else: ?>
      <i class="fa-solid fa-caret-down text-red-400"></i>
      <span class="text-red-400"><?= $delta_ano_no ?>%</span>
    <?php endif; ?>
  </div>
</div>

<!-- 2) Fora do prazo (2025) -->
<div class="bg-red-800 p-4 rounded-lg shadow card relative">
  <p class="text-xs text-red-200">Fora do prazo (<?= $anoAtual ?>)</p>
  <h2 class="text-xl font-bold"><?= $perc_ano_fora ?>%</h2>
  <div class="absolute top-3 right-3 flex items-center space-x-1 text-sm">
    <?php if ($delta_ano_fora >= 0): ?>
      <i class="fa-solid fa-caret-up text-green-400"></i>
      <span class="text-green-400">+<?= $delta_ano_fora ?>%</span>
    <?php else: ?>
      <i class="fa-solid fa-caret-down text-red-400"></i>
      <span class="text-red-400"><?= $delta_ano_fora ?>%</span>
    <?php endif; ?>
  </div>
</div>

<!-- 3) Meta 2025: No prazo (%) -->
<a href="ranking_unidades.php" class="block">
  <div class="bg-green-800 p-4 rounded-lg shadow card relative hover:bg-green-700 transition cursor-pointer">
    <p class="text-xs text-green-100">Meta <?= $anoAtual ?>: No prazo (%)</p>
    <h2 class="text-xl font-bold"><?= $perc_meta_no ?>%</h2>
    <div class="absolute top-3 right-3 flex items-center space-x-1 text-sm">
      <?php if ($delta_meta >= 0): ?>
        <i class="fa-solid fa-caret-up text-green-400 text-sm"></i>
        <span class="text-green-400">+<?= $delta_meta ?>%</span>
      <?php else: ?>
        <i class="fa-solid fa-caret-down text-red-400 text-sm"></i>
        <span class="text-red-400"><?= $delta_meta ?>%</span>
      <?php endif; ?>
    </div>
  </div>
</a>

<!-- 4) Meta 2025: Fora do prazo (%) -->
<div class="bg-red-800 p-4 rounded-lg shadow card relative">
  <p class="text-xs text-red-100">Meta <?= $anoAtual ?>: Fora do prazo (%)</p>
  <h2 class="text-xl font-bold"><?= $perc_meta_fora ?>%</h2>
  <div class="absolute top-3 right-3 flex items-center space-x-1 text-sm">
    <?php if ($delta_meta_fora >= 0): ?>
      <i class="fa-solid fa-caret-up text-green-400 text-sm"></i>
      <span class="text-green-400">+<?= $delta_meta_fora ?>%</span>
    <?php else: ?>
      <i class="fa-solid fa-caret-down text-red-400 text-sm"></i>
      <span class="text-red-400"><?= $delta_meta_fora ?>%</span>
    <?php endif; ?>
  </div>
</div>



    </div>
  </main>
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      fetch("../php/verifica_sessao.php")
        .then(res => res.json())
        .then(data => {
          if (data.logado) {
            document.getElementById("nomeUsuario")
              .textContent = `👤 ${data.nome_completo}`;
          }
        });
    });
  </script>
 <footer class="mt-10 text-center text-xs text-slate-500 border-t border-slate-800 pt-4">
   <p>Desenvolvido para a 23ª Gerência de Conservação<br/>Prefeitura do Rio</p>
   <p class="italic mt-1 text-slate-600">por Anderson Guilherme</p>
 </footer>
  <?php include __DIR__ . '/../includes/menu_inferior.php'; ?>
  <script src="../js/logout.js"></script>
</body>
</html>
