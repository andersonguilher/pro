<?php
session_start();
require_once(__DIR__ . '/../php/conexao.php');

date_default_timezone_set('America/Sao_Paulo');

$ano = date('Y');
$mesAtual = (int)date('n');

// Mês de referência = até mês anterior ao atual (ex: agosto => jan–jul)
$mesLimite = $mesAtual - 1;
$anoReferencia = $ano;

// Mês de comparação = até dois meses antes do atual (ex: agosto => jan–jun)
$mesComparacao = $mesAtual - 2;
$anoComparacao = $ano;
if ($mesComparacao < 1) {
    $mesComparacao += 12;
    $anoComparacao--;
}

$dataHoje = date('Y-m-d');

// Nome do intervalo
setlocale(LC_TIME, 'pt_BR.UTF-8');
$nomeInicio = ucfirst(strftime('%B', mktime(0, 0, 0, 1, 1))); // Janeiro
$nomeFim = ucfirst(strftime('%B', mktime(0, 0, 0, $mesLimite, 1)));

// === Ranking acumulado até mês anterior ===
$sql = "
SELECT
  no_unidade_organizacional AS Unidade,

  -- Finalizados no prazo
  SUM(CASE
    WHEN data_fim IS NOT NULL AND DATE(data_sla) >= DATE(data_fim)
  THEN 1 ELSE 0 END) AS FNP,

  -- Finalizados fora do prazo
  SUM(CASE
    WHEN data_fim IS NOT NULL AND DATE(data_fim) > DATE(data_sla)
  THEN 1 ELSE 0 END) AS FechadosFora,

  -- Abertos fora do prazo
  SUM(CASE
    WHEN data_fim IS NULL AND DATE(data_sla) < CURDATE()
  THEN 1 ELSE 0 END) AS AbertosFora,

  -- Total: todos os chamados abertos no período
  COUNT(*) AS Total,

  -- % de cumprimento no prazo
  IFNULL(ROUND(
    100.0 *
    SUM(CASE
      WHEN data_fim IS NOT NULL AND DATE(data_sla) >= DATE(data_fim)
    THEN 1 ELSE 0 END)
    /
    NULLIF(COUNT(*), 0)
  , 2), 0) AS perc

FROM extracted_1746_geral

WHERE
  YEAR(data_inicio) = $anoReferencia
  AND MONTH(data_inicio) BETWEEN 1 AND $mesLimite
  AND subtipo IN (
    'Manutenção/Desobstrução de ramais de águas pluviais e ralos',
    'Reparo de buraco, deformação ou afundamento na pista'
  )
  AND (
    no_unidade_organizacional != '22aGC' OR
    (no_unidade_organizacional = '22aGC' AND bairro NOT LIKE 'SEPETIBA')
  )

GROUP BY no_unidade_organizacional
ORDER BY perc DESC, FNP DESC
";

$result = $conn->query($sql);

// === Ranking de COMPARAÇÃO acumulado até mês antecessor ===
$sql_anterior = "
SELECT
  no_unidade_organizacional AS Unidade,

  -- Finalizados no prazo
  SUM(CASE
    WHEN data_fim IS NOT NULL AND DATE(data_sla) >= DATE(data_fim)
  THEN 1 ELSE 0 END) AS FNP,

  -- Total: todos os chamados abertos no período
  COUNT(*) AS Total,

  -- % de cumprimento no prazo
  IFNULL(ROUND(
    100.0 *
    SUM(CASE
      WHEN data_fim IS NOT NULL AND DATE(data_sla) >= DATE(data_fim)
    THEN 1 ELSE 0 END)
    /
    NULLIF(COUNT(*), 0)
  , 2), 0) AS perc

FROM extracted_1746_geral

WHERE
  YEAR(data_inicio) = $anoComparacao
  AND MONTH(data_inicio) BETWEEN 1 AND $mesComparacao
  AND subtipo IN (
    'Manutenção/Desobstrução de ramais de águas pluviais e ralos',
    'Reparo de buraco, deformação ou afundamento na pista'
  )
  AND (
    no_unidade_organizacional != '22aGC' OR
    (no_unidade_organizacional = '22aGC' AND bairro NOT LIKE 'SEPETIBA')
  )

GROUP BY no_unidade_organizacional
ORDER BY perc DESC, FNP DESC
";

$result_anterior = $conn->query($sql_anterior);

// Armazenar posições anteriores
$rankingAnterior = 1;
$posicoes_anteriores = [];

while ($row = $result_anterior->fetch_assoc()) {
    $posicoes_anteriores[$row['Unidade']] = $rankingAnterior;
    $rankingAnterior++;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Ranking por Unidade - ConserVapp</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body class="bg-slate-900 text-white min-h-screen p-4 pb-[100px]">

<header class="mb-6 text-center">
  <h1 class="text-xl font-bold text-cyan-300">
    📊 Ranking por Unidade (Janeiro a <?= $nomeFim ?>/<?= $anoReferencia ?>)
  </h1>
</header>

<div class="overflow-x-auto bg-slate-800 rounded-lg shadow-lg">
  <table class="min-w-full text-sm border border-slate-700 text-white">
    <thead class="bg-slate-700 text-cyan-200 text-left">
      <tr>
        <th class="px-2 py-2 border border-slate-600 w-12 text-center">#</th>
        <th class="px-2 py-2 border border-slate-600 w-28 truncate">Unidade</th>
        <th class="px-4 py-2 border border-slate-600">%</th>
        <th class="px-4 py-2 border border-slate-600">FNP</th>
        <th class="px-4 py-2 border border-slate-600">FFP</th>
        <th class="px-4 py-2 border border-slate-600">AFP</th>
      </tr>
    </thead>
    <tbody>
      <?php
        $ranking = 1;

        while ($row = $result->fetch_assoc()):
          $unidade = $row['Unidade'];
          $classe = ($unidade === '22aGC') ? 'bg-yellow-700 text-white font-bold' : 'hover:bg-slate-600';

          $FNP = (int)$row['FNP'];
          $FFP = (int)$row['FechadosFora'] + (int)$row['AbertosFora'];
          $AFP = (int)$row['AbertosFora'];
          $perc = $row['perc'];
      ?>
        <tr class="<?= $classe ?>">
          <td class="px-2 py-2 border border-slate-700 font-bold text-cyan-300 text-center w-12">
            <div class="flex items-center justify-center gap-1">
              <span><?= $ranking ?></span>
              <?php
                if (isset($posicoes_anteriores[$unidade])) {
                  $delta = $posicoes_anteriores[$unidade] - $ranking;
                  if ($delta > 0) {
                    echo "<span class='text-green-400 text-xs flex items-center'><i class='fas fa-caret-up'></i>+$delta</span>";
                  } elseif ($delta < 0) {
                    echo "<span class='text-red-400 text-xs flex items-center'><i class='fas fa-caret-down'></i>" . abs($delta) . "</span>";
                  }
                  // delta == 0 não exibe nada
                }
              ?>
            </div>
          </td>
          <td class="px-2 py-2 border border-slate-700 truncate w-28" title="<?= htmlspecialchars($unidade) ?>">
            <?= ($unidade === 'TRANSCARIOCA') ? 'TRANSC.' : htmlspecialchars($unidade) ?>
          </td>
          <td class="px-4 py-2 border border-slate-700 text-green-400"><?= $perc ?>%</td>
          <td class="px-4 py-2 border border-slate-700"><?= $FNP ?></td>
          <td class="px-4 py-2 border border-slate-700"><?= $FFP ?></td>
          <td class="px-4 py-2 border border-slate-700 text-red-300"><?= $AFP ?></td>
        </tr>
      <?php
        $ranking++;
        endwhile;
      ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../includes/menu_inferior.php'; ?>
<script src="../js/logout.js"></script>

<script>
let touchStartX = 0;
let touchEndX = 0;

document.addEventListener('touchstart', e => {
  touchStartX = e.changedTouches[0].screenX;
});

document.addEventListener('touchend', e => {
  touchEndX = e.changedTouches[0].screenX;
  handleGesture();
});

function handleGesture() {
  const delta = touchStartX - touchEndX;
  const limiar = 50; // mínimo de 50px para considerar swipe

  if (delta > limiar) {
    // Swipe para a esquerda: ir para o gráfico
    window.location.href = 'grafico_indice.php';
  }
}
</script>


</body>
</html>
