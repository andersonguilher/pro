<?php
session_start();

// Verifica se está logado e se o nível é admin
if (!isset($_SESSION['usuario']) || ($_SESSION['nivel'] ?? '') !== 'admin') {
  header('Location: ../index.html');
  exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="ConserVapp">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Painel Administrativo</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white min-h-screen flex flex-col">

<main class="flex-grow p-4 pb-24">
  <h1 class="text-2xl font-bold mb-6 text-center">🔐 Painel Administrativo</h1>

  <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
    <!-- Tipos de Serviço -->
    <a href="admin_servicos.php"
       class="bg-cyan-700 hover:bg-cyan-600 text-white p-4 rounded-xl shadow transition flex flex-col items-center">
      <i class="fas fa-tools text-3xl mb-2"></i>
      <span class="text-lg font-semibold">Tipos de Serviço</span>
    </a>

    <!-- Cadastrar Usuário -->
    <a href="admin_usuarios.php"
       class="bg-green-700 hover:bg-green-600 text-white p-4 rounded-xl shadow transition flex flex-col items-center">
      <i class="fas fa-users-cog text-3xl mb-2"></i>
      <span class="text-lg font-semibold">Gerenciar Usuários</span>
    </a>
  </div>
</main>


  <?php include __DIR__ . '/../includes/menu_inferior.php'; ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js" defer></script>
  <script src="../js/logout.js"></script>
</body>
</html>
