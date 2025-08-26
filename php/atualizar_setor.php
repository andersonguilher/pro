<?php
require_once("conexao.php");
header('Content-Type: application/json');

$id = $_POST['id'] ?? null;
$setor = $_POST['setor'] ?? null;

if (!$id || !$setor) {
  echo json_encode(["sucesso" => false, "mensagem" => "Dados incompletos."]);
  exit;
}

try {
  $stmt = $conn->prepare("UPDATE dados1746 SET setor = ? WHERE id_chamado = ?");
  $stmt->execute([$setor, $id]);
  echo json_encode(["sucesso" => true]);
} catch (Exception $e) {
  echo json_encode(["sucesso" => false, "mensagem" => $e->getMessage()]);
}
?>