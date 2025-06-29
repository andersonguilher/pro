<?php
require 'conexao.php';

$id = $_POST['id'] ?? 0;
$id = intval($id);

if ($id <= 0) {
  echo json_encode(['sucesso' => false, 'erro' => 'ID inválido']);
  exit;
}

// Evita excluir o usuário principal
$stmt = $conn->prepare("DELETE FROM usr_conservapp WHERE id = ? AND usuario != 'admin'");
$stmt->bind_param("i", $id);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['sucesso' => $ok]);
$conn->close();
