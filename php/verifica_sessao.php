<?php
session_start();

header('Content-Type: application/json');

if (isset($_SESSION['usuario'])) {
    echo json_encode([
        'logado' => true,
        'usuario' => $_SESSION['usuario'],
        'nome_completo' => $_SESSION['nome_completo'] ?? $_SESSION['usuario'],
        'nivel' => $_SESSION['nivel'] ?? ''
    ]);
} else {
    echo json_encode(['logado' => false]);
}
