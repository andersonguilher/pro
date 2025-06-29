<?php
require 'conexao.php';

$id = $_POST['id'] ?? null;
$novaSenha = $_POST['senha'] ?? '';

if (!$id || empty($novaSenha) || strlen($novaSenha) < 4) {
  echo json_encode(['sucesso' => false, 'erro' => 'Dados inválidos']);
  exit;
}

$hash = password_hash($novaSenha, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE usr_conservapp SET senha = ? WHERE id = ?");
$stmt->bind_param("si", $hash, $id);

if ($stmt->execute()) {
  echo json_encode(['sucesso' => true]);
} else {
  echo json_encode(['sucesso' => false, 'erro' => 'Erro ao atualizar senha']);
}

$stmt->close();
$conn->close();
