<?php
session_start();
require_once __DIR__ . '/../php/conexao.php';

if (!isset($_SESSION['usuario']) || $_SESSION['nivel'] !== 'admin') {
  header("Location: ../index.html");
  exit;
}

// Buscar os usuários existentes
$usuarios = [];
$res = $conn->query("SELECT * FROM usr_conservapp ORDER BY nome_completo ASC");
while ($row = $res->fetch_assoc()) {
  $usuarios[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="ConserVapp">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gerenciar Usuários</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white min-h-screen p-4 pb-24">
  <h1 class="text-2xl font-bold mb-4 text-center">Usuários Cadastrados</h1>

  <form action="../php/cadastrar_usuario.php" method="POST" class="grid grid-cols-1 md:grid-cols-5 gap-2 mb-6">
    <input type="text" name="nome_completo" placeholder="Nome completo" required class="p-2 rounded bg-slate-800 border border-slate-700 md:col-span-1">
    <input type="text" name="usuario" placeholder="Usuário" required class="p-2 rounded bg-slate-800 border border-slate-700 md:col-span-1">
    <input type="password" name="senha" placeholder="Senha" required class="p-2 rounded bg-slate-800 border border-slate-700 md:col-span-1">
    <select name="nivel" class="p-2 rounded bg-slate-800 border border-slate-700 md:col-span-1">
      <option value="fiscal">Fiscal</option>
      <option value="despachador">Despachador</option>
      <option value="concessionaria">Concessionária</option>
      <option value="gerente">Gerente</option>
      <option value="admin">Admin</option>
    </select>
    <button type="submit" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded font-bold md:col-span-1">Cadastrar</button>
  </form>

  <div class="overflow-auto">
    <table class="w-full text-sm bg-slate-800 rounded min-w-[600px]">
      <thead>
        <tr class="bg-slate-700 text-left">
          <th class="p-2">Nome</th>
          <th class="p-2">Usuário</th>
          <th class="p-2">Nível</th>
          <th class="p-2 text-right">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
          <tr id="usuario-<?= $u['id'] ?>" class="border-t border-slate-700">
            <form action="../php/editar_usuario.php" method="POST" class="contents">
              <input type="hidden" name="id" value="<?= $u['id'] ?>">
              <td class="p-2">
                <input name="nome_completo" value="<?= htmlspecialchars($u['nome_completo']) ?>" class="w-full bg-slate-900 p-1 rounded border border-slate-600">
              </td>
              <td class="p-2">
                <input value="<?= htmlspecialchars($u['usuario']) ?>" disabled class="w-full bg-slate-900 p-1 rounded border border-slate-600 text-slate-400">
              </td>
              <td class="p-2">
              <select name="nivel" class="w-full bg-slate-900 p-1 rounded border border-slate-600">
                <option value="fiscal" <?= $u['nivel'] === 'fiscal' ? 'selected' : '' ?>>Fiscal</option>
                <option value="despachador" <?= $u['nivel'] === 'despachador' ? 'selected' : '' ?>>Despachador</option>
                <option value="concessionaria" <?= $u['nivel'] === 'concessionaria' ? 'selected' : '' ?>>Concessionária</option>
                <option value="gerente" <?= $u['nivel'] === 'gerente' ? 'selected' : '' ?>>Gerente</option>
                <option value="admin" <?= $u['nivel'] === 'admin' ? 'selected' : '' ?>>Admin</option>
              </select>
              </td>
              <td class="p-2 text-right space-x-2">
                <?php if ($u['usuario'] !== 'admin'): ?>
                  <button type="submit" class="text-green-400 hover:underline">Salvar</button>
                  <button type="button" onclick="redefinirSenha(<?= $u['id'] ?>)" class="text-yellow-400 hover:underline">Senha</button>
                  <button type="button" onclick="excluirUsuario(<?= $u['id'] ?>)" class="text-red-400 hover:underline">Excluir</button>
                <?php else: ?>
                  <span class="text-slate-500">Admin padrão</span>
                <?php endif; ?>
              </td>
            </form>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- Font Awesome -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js" defer></script>
  <?php include __DIR__ . '/../includes/menu_inferior.php'; ?>

  <script>
    function excluirUsuario(id) {
      if (!confirm("Deseja realmente excluir este usuário?")) return;
      fetch("../php/excluir_usuario.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id=" + encodeURIComponent(id)
      })
      .then(res => res.json())
      .then(data => {
        if (data.sucesso) {
          document.getElementById("usuario-" + id).remove();
        } else {
          alert("Erro ao excluir: " + (data.erro || "Tente novamente."));
        }
      });
    }

    function redefinirSenha(id) {
      const nova = prompt("Nova senha para este usuário:");
      if (!nova || nova.length < 4) {
        alert("Senha muito curta.");
        return;
      }

      fetch("../php/redefinir_senha.php", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "id=" + id + "&senha=" + encodeURIComponent(nova)
      })
      .then(res => res.json())
      .then(data => {
        if (data.sucesso) {
          alert("Senha redefinida com sucesso.");
        } else {
          alert("Erro ao redefinir senha.");
        }
      });
    }
  </script>
  <script src="../js/logout.js"></script>
</body>
</html>
