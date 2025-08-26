<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'conexao.php';
require 'upload_fotos.php';

$id_chamado  = $_POST['id_chamado'] ?? '';
$tipo        = $_POST['tipo_servico'] ?? ($_POST['tipo'] ?? '');
$descricao   = $_POST['descricao'] ?? '';
$latitude    = $_POST['latitude'] ?? '';
$longitude   = $_POST['longitude'] ?? '';
$latlong     = $latitude . ',' . $longitude;
$data_agora  = date('Y-m-d H:i:s');
$sem_possibilidade = (strtolower(trim($tipo)) === 'sem possibilidade') ? 1 : 0;
$fiscal = $_SESSION['usuario'] ?? 'Fiscal';

// Upload das imagens
$imagens = uploadFotos($_FILES['fotos'] ?? null, $id_chamado);

// Buscar dados do chamado original
$stmt = $conn->prepare("SELECT data_inicio, data_sla, subtipo, logradouro, numero, complemento, referencia, bairro, ds_chamado FROM dados1746 WHERE id_chamado = ?");
$stmt->bind_param("s", $id_chamado);
$stmt->execute();
$res = $stmt->get_result();
$dados = $res->fetch_assoc();
$stmt->close();

if ($dados) {
    $programado = 0;
    $executado = 0;
    $programado_date = null;
    $data_execucao   = null;
    $vistoriado = 1;

    $stmt = $conn->prepare("INSERT INTO demandas (
        protocolo, data_inicio, sla,
        logradouro, numero, complemento, referencia, bairro,
        solicitacao, origem, descricao, vistoria, tipo,
        programado, programado_date, executado, data_execucao,
        foto1, foto2, foto3, foto4,
        latlong, vistoriado, sem_possibilidade, fiscal
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, '1746', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("ssssssssssssisssssssssss",
        $id_chamado, $dados['data_inicio'], $dados['data_sla'],
        $dados['logradouro'], $dados['numero'], $dados['complemento'], $dados['referencia'], $dados['bairro'],
        $dados['subtipo'], $dados['ds_chamado'], $descricao, $tipo,
        $programado, $programado_date, $executado, $data_execucao,
        $imagens[0], $imagens[1], $imagens[2], $imagens[3],
        $latlong, $vistoriado, $sem_possibilidade, $fiscal
    );

    $ok = $stmt->execute();
    $stmt->close();
} else {
    $ok = false;
}

// Atualiza dados1746
if (!empty($id_chamado)) {
    $stmt2 = $conn->prepare("UPDATE dados1746 SET 
        setor = 'Despachador',
        obs = ?,
        foto_url = ?,
        geoloc = ?,
        `serviço` = ?
        WHERE id_chamado = ?");
    $stmt2->bind_param("sssss", $descricao, $imagens[0], $latlong, $tipo, $id_chamado);
    $stmt2->execute();
    $stmt2->close();
}

$conn->close();

echo json_encode(['success' => $ok]);
