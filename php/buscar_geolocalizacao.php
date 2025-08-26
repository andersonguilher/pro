<?php
require_once("conexao.php");

$logradouro = $_GET['logradouro'] ?? '';
$numero = intval($_GET['numero'] ?? 0);
$bairro = $_GET['bairro'] ?? '';

// Ruas que precisam do número
$usarNumero = in_array($logradouro, [
        "R. Felipe Cardoso",
        "Av. João XXIII",
        "Av. Areia Branca",
        "Av. Cesário de Melo",
        "Av. Antares",
        "Etr. Urucânia",
        "Etr. da Paciência",
        "Etr. de Santa Eugênia",
        "R. do Império",
        "Av. Isabel",
        "Etr. Cruz das Almas",
        "R. Fernanda",
        "Etr. Vitor Dumas",
        "Etr. Aterrado do Leme",
        "R. Cilon Cunha Brum",
        "Etr. Jose Cid Fernandes",
        "Etr. dos Palmares",
        "Etr. do Cortume"
]);

if ($usarNumero) {
    $stmt = $conn->prepare("SELECT latitude, longitude FROM geolocalizacao WHERE logradouro = ? AND numero = ? AND bairro = ? AND status = 'ok'");
    $stmt->bind_param("sis", $logradouro, $numero, $bairro);
} else {
    $stmt = $conn->prepare("SELECT latitude, longitude FROM geolocalizacao WHERE logradouro = ? AND bairro = ? AND status = 'ok'");
    $stmt->bind_param("ss", $logradouro, $bairro);
}

$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    echo json_encode($res->fetch_assoc());
} else {
    echo json_encode(["latitude" => null, "longitude" => null]);
}
