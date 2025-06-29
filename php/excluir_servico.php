<?php
require_once 'conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);

    // Verifica se o ID realmente existe
    $check = $conn->prepare("SELECT id FROM tipos_servico WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $stmt = $conn->prepare("DELETE FROM tipos_servico WHERE id = ?");
        $stmt->bind_param("i", $id);
        $ok = $stmt->execute();
        $stmt->close();

        echo json_encode(['sucesso' => $ok]);
    } else {
        echo json_encode(['sucesso' => false, 'erro' => 'Serviço não encontrado.']);
    }

    $check->close();
} else {
    echo json_encode(['sucesso' => false, 'erro' => 'Requisição inválida.']);
}

$conn->close();
?>
