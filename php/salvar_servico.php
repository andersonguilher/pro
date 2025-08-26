<?php
require_once 'conexao.php';

$nome = trim($_POST['nome'] ?? '');
if ($nome === '') {
  header('Location: ../pages/admin_servicos.php');
  exit;
}

$stmt = $conn->prepare("INSERT IGNORE INTO tipos_servico (nome) VALUES (?)");
$stmt->bind_param("s", $nome);
$stmt->execute();
$stmt->close();
$conn->close();

header('Location: ../pages/admin_servicos.php');
