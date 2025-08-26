<?php
require_once "conexao.php";

try {
  $id = $_POST["id"] ?? null;
  $setor = $_POST["setor"] ?? null;
  $obs = trim($_POST["obs"] ?? "");

  if (!$id || !$setor) {
    throw new Exception("ID ou setor ausente");
  }

  // Atualiza diretamente substituindo a observação
  $stmt = $conn->prepare("UPDATE dados1746 SET setor = ?, obs = ? WHERE id_chamado = ?");
  $stmt->execute([$setor, $obs, $id]);

  echo json_encode(["sucesso" => true]);
} catch (Exception $e) {
  echo json_encode(["sucesso" => false, "erro" => $e->getMessage()]);
}
