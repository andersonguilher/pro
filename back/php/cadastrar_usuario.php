<?php
require 'conexao.php';

$usuario = $_POST['usuario'] ?? '';
$senha = $_POST['senha'] ?? '';

if (empty($usuario) || empty($senha)) {
  echo "Dados incompletos.";
  exit;
}

$hash = password_hash($senha, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO usuarios (usuario, senha) VALUES (?, ?)");
$stmt->bind_param("ss", $usuario, $hash);

if ($stmt->execute()) {
  echo "<script>alert('Usuário cadastrado com sucesso!'); window.location.href='../pages/admin_usuarios.html';</script>";
} else {
  echo "<script>alert('Erro: usuário já existe ou falha no cadastro.'); window.history.back();</script>";
}

$stmt->close();
$conn->close();
