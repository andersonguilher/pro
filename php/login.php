<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');
require 'conexao.php';

$data = json_decode(file_get_contents("php://input"), true);

$usuario = $data['usuario'] ?? '';
$senha = $data['senha'] ?? '';

$stmt = $conn->prepare("SELECT senha, nome_completo, nivel FROM usr_conservapp WHERE usuario = ? AND ativo = 1");
$stmt->bind_param("s", $usuario);
$stmt->execute();

$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $senha_hash = $row['senha'];

    if (password_verify($senha, $senha_hash)) {
        $_SESSION['usuario'] = $usuario;
        $_SESSION['nome_completo'] = $row['nome_completo'] ?? $usuario;
        $_SESSION['nivel'] = $row['nivel'];
        $_SESSION['nome_completo'] = $row['nome_completo'];

        echo json_encode([
            'sucesso' => true,
            'nivel' => $row['nivel'],
            'nome' => $row['nome_completo']
        ]);
    } else {
        echo json_encode(['sucesso' => false]);
    }
} else {
    echo json_encode(['sucesso' => false]);
}

$stmt->close();
$conn->close();
