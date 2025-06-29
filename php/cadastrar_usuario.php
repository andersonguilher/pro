<?php
require 'conexao.php';

$usuario = $_POST['usuario'] ?? '';
$senha = $_POST['senha'] ?? '';
$nome_completo = $_POST['nome_completo'] ?? '';
$nivel = $_POST['nivel'] ?? 'fiscal'; // padrão
$ativo = 1;

if (empty($usuario) || empty($senha) || empty($nome_completo)) {
  echo "<script>alert('Por favor, preencha todos os campos obrigatórios.'); window.history.back();</script>";
  exit;
}

$hash = password_hash($senha, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO usr_conservapp (usuario, senha, nome_completo, nivel, ativo, criado_em) VALUES (?, ?, ?, ?, ?, NOW())");
$stmt->bind_param("ssssi", $usuario, $hash, $nome_completo, $nivel, $ativo);

if ($stmt->execute()) {
  echo "<script>alert('Usuário cadastrado com sucesso!'); window.location.href='../pages/admin_usuarios.php';</script>";
} else {
  echo "<script>alert('Erro: usuário já existe ou falha no cadastro.'); window.history.back();</script>";
}

$stmt->close();
$conn->close();
