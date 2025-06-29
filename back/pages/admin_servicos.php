<?php
require_once __DIR__ . '/../php/conexao.php';

// Buscar os tipos existentes
$tipos = [];
$res = $conn->query("SELECT * FROM tipos_servico ORDER BY nome ASC");
while ($row = $res->fetch_assoc()) {
  $tipos[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gerenciar Tipos de Serviço</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-white min-h-screen p-4 pb-24">
  <h1 class="text-2xl font-bold mb-4 text-center">Tipos de Serviço</h1>

  <form action="../php/salvar_servico.php" method="POST" class="flex gap-2 mb-6">
    <input type="text" name="nome" required placeholder="Novo tipo de serviço"
      class="flex-1 p-2 rounded bg-slate-800 border border-slate-700">
    <button type="submit" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded">Adicionar</button>
  </form>

  <table class="w-full text-sm bg-slate-800 rounded">
    <thead>
      <tr class="bg-slate-700 text-left">
        <th class="p-2">Nome</th>
        <th class="p-2 text-right">Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($tipos as $tipo): ?>
      <tr class="border-t border-slate-700">
        <td class="p-2"><?php echo htmlspecialchars($tipo['nome']); ?></td>
        <td class="p-2 text-right">
          <form action="../php/excluir_servico.php" method="POST" onsubmit="return confirm('Excluir este tipo?');">
            <input type="hidden" name="id" value="<?php echo $tipo['id']; ?>">
            <button type="submit" class="text-red-400 hover:underline">Excluir</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php include $_SERVER['DOCUMENT_ROOT'] . '/pro/includes/menu_inferior.php'; ?>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js" defer></script>
</body>
</html>
