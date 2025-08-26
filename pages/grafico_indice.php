<?php
session_start();
$nivel = $_SESSION['nivel'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Gráfico FNP x FFP</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body class="bg-slate-900 text-white min-h-screen p-4 pb-[100px]">

<header class="mb-6 text-center">
  <h1 class="text-xl font-bold text-cyan-300">📈 Gráfico de FNP / FFP</h1>
</header>

<div class="flex flex-wrap gap-4 justify-center mb-6">
  <select id="mesSelect" class="p-2 rounded bg-slate-800 text-white border border-slate-600">
    <option value="geral">📊 Acumulado geral (mensal)</option>
  </select>

  <select id="subtipoSelect" class="p-2 rounded bg-slate-800 text-white border border-slate-600">
    <option value="todos">🔄 Ambos os subtipos</option>
    <option value="manutencao">🛠️ Manutenção/Desobstrução</option>
    <option value="reparo">🚧 Reparo de buraco ou deformação</option>
  </select>
</div>

<div class="bg-slate-800 p-4 rounded-xl shadow-lg">
  <canvas id="grafico" class="w-full max-w-full md:w-[720px]"></canvas>
</div>

<script>
  let chart;

  async function carregarMeses() {
    const hoje = new Date();
    const select = document.getElementById('mesSelect');

    let ultimoMes = hoje.getMonth();
    for (let m = 1; m <= ultimoMes; m++) {
      const mesFormatado = m.toString().padStart(2, '0');
      const nomeMes = new Date(2025, m - 1).toLocaleString('pt-BR', { month: 'long' });
      select.innerHTML += `<option value="${mesFormatado}">${nomeMes}</option>`;
    }

    select.value = ultimoMes.toString().padStart(2, '0');
    carregarDados();
  }

  async function carregarDados() {
    const mes = document.getElementById('mesSelect').value;
    const subtipo = document.getElementById('subtipoSelect').value;
    const modo = (mes === 'geral') ? 'mensal' : 'diario';
    const url = `../php/filtrar_indice.php?modo=${modo}&mes=${mes}&subtipo=${encodeURIComponent(subtipo)}`;

    const resposta = await fetch(url);
    const dados = await resposta.json();

    let dias = [], fnpAcumulado = [], ffpAcumulado = [];
    let diasAnterior = [], fnpAnterior = [], ffpAnterior = [];

    if (Array.isArray(dados)) {
      dados.forEach(l => {
        dias.push(l.dia);
        fnpAcumulado.push(parseInt(l.fnp));
        ffpAcumulado.push(parseInt(l.ffp));
      });
    } else {
      let totalFnp = 0, totalFfp = 0;
      dados.atual.forEach(l => {
        const fnp = parseInt(l.fnp);
        const ffp = parseInt(l.ffp);
        totalFnp += fnp;
        totalFfp += ffp;
        dias.push(l.dia);
        fnpAcumulado.push(totalFnp);
        ffpAcumulado.push(totalFfp);
      });

      let fnpAnt = 0, ffpAnt = 0;
      dados.anterior.forEach(l => {
        const fnp = parseInt(l.fnp);
        const ffp = parseInt(l.ffp);
        fnpAnt += fnp;
        ffpAnt += ffp;
        diasAnterior.push(l.dia);
        fnpAnterior.push(fnpAnt);
        ffpAnterior.push(ffpAnt);
      });
    }

    if (chart) chart.destroy();
    const ctx = document.getElementById('grafico').getContext('2d');
    chart = new Chart(ctx, {
      type: 'line',
      data: {
        labels: dias,
        datasets: [
          {
            label: 'FNP acumulado',
            data: fnpAcumulado,
            borderColor: '#22c55e',
            backgroundColor: 'transparent',
            tension: 0.3
          },
          {
            label: 'FFP acumulado',
            data: ffpAcumulado,
            borderColor: '#ef4444',
            backgroundColor: 'transparent',
            tension: 0.3
          },
          ...(fnpAnterior.length > 0 ? [
            {
              label: 'FNP mês anterior',
              data: fnpAnterior,
              borderColor: '#22c55e',
              borderDash: [5, 5],
              backgroundColor: 'transparent',
              tension: 0.3
            },
            {
              label: 'FFP mês anterior',
              data: ffpAnterior,
              borderColor: '#ef4444',
              borderDash: [5, 5],
              backgroundColor: 'transparent',
              tension: 0.3
            }
          ] : [])
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            labels: { color: 'white' }
          },
          tooltip: {
            callbacks: {
              label: (ctx) => `${ctx.dataset.label}: ${ctx.raw}`
            }
          }
        },
        scales: {
          x: {
            ticks: { color: 'white' },
            grid: { color: '#334155' }
          },
          y: {
            beginAtZero: true,
            ticks: { color: 'white' },
            grid: { color: '#334155' }
          }
        }
      }
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    carregarMeses();
    document.getElementById('mesSelect').addEventListener('change', carregarDados);
    document.getElementById('subtipoSelect').addEventListener('change', carregarDados);
  });
</script>

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
    handleSwipe();
  });

  function handleSwipe() {
    const delta = touchStartX - touchEndX;
    const threshold = 50;

    if (delta < -threshold) {
      window.location.href = 'ranking_unidades.php';
    }
  }
</script>

</body>
</html>
