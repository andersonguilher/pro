<?php
require 'conexao.php';

$id = intval($_POST['id'] ?? 0);
$nome = trim($_POST['nome_completo'] ?? '');
$nivel = $_POST['nivel'] ?? '';

if ($id <= 0 || !$nome || !$nivel) {
  header("Location: ../pages/admin_usuarios.php");
  exit;
}

$stmt = $conn->prepare("UPDATE usr_conservapp SET nome_completo = ?, nivel = ? WHERE id = ?");
$stmt->bind_param("ssi", $nome, $nivel, $id);
$stmt->execute();
$stmt->close();
$conn->close();

header("Location: ../pages/admin_usuarios.php");
exit;
