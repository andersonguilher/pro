<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
header('Content-Type: application/json');
require 'conexao.php';

$data = json_decode(file_get_contents("php://input"), true);
file_put_contents("debug_login.txt", print_r($data, true));

$usuario = $data['usuario'] ?? '';
$senha = $data['senha'] ?? '';

$stmt = $conn->prepare("SELECT senha FROM users WHERE usuario = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();

$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $senha_hash = $row['senha'];
    file_put_contents("debug_login.txt", "Senha no banco: $senha_hash\n", FILE_APPEND);

    if (password_verify($senha, $senha_hash)) {
        $_SESSION['usuario'] = $usuario;
        echo json_encode(['sucesso' => true]);
    } else {
        echo json_encode(['sucesso' => false]);
    }
} else {
    file_put_contents("debug_login.txt", "Usuário não encontrado\n", FILE_APPEND);
    echo json_encode(['sucesso' => false]);
}

$stmt->close();
$conn->close();
