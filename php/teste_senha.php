<?php
$pdo = new PDO("mysql:host=localhost;dbname=conservapp;charset=utf8", "root", "");

$usuario = 'admin';
$senha = password_hash('123456', PASSWORD_DEFAULT);

$sql = "INSERT INTO users (usuario, senha) VALUES (:usuario, :senha)";
$stmt = $pdo->prepare($sql);
$stmt->execute(['usuario' => $usuario, 'senha' => $senha]);

echo "Usuário criado com sucesso.";
