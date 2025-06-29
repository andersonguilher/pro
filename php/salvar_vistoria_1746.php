<?php
require 'conexao.php';
require 'upload_fotos.php';

$id_chamado  = $_POST['id_chamado']  ?? '';
$tipo        = $_POST['tipo_servico'] ?? ($_POST['tipo'] ?? '');
$descricao   = $_POST['descricao']   ?? '';
$latitude    = $_POST['latitude']    ?? '';
$longitude   = $_POST['longitude']   ?? '';
$data        = date('Y-m-d H:i:s');

// Upload das imagens
$imagens = uploadFotos($_FILES['fotos'] ?? null);

// Endereço: só é construído em vistoria local
$endereco = '';
if (empty($id_chamado)) {
  $logradouro  = $_POST['logradouro']  ?? '';
  $numero      = $_POST['numero']      ?? '';
  $bairro      = $_POST['bairro']      ?? '';
  $referencia  = $_POST['referencia']  ?? '';

  $endereco = "$logradouro, $numero - $bairro";
  if (!empty($referencia)) {
    $endereco .= " (Ref: $referencia)";
  }
}

// Insere na tabela demandas
$stmt = $conn->prepare("INSERT INTO demandas (id_chamado, tipo, descricao, endereco, latitude, longitude, data, foto1, foto2, foto3, foto4)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("sssssssssss", $id_chamado, $tipo, $descricao, $endereco, $latitude, $longitude, $data,
    $imagens[0], $imagens[1], $imagens[2], $imagens[3]);

$ok = $stmt->execute();
$stmt->close();

// Atualiza status do chamado, se aplicável
if (!empty($id_chamado)) {
    $stmt2 = $conn->prepare("UPDATE dados1746 SET status = 'Vistoriado' WHERE id_chamado = ?");
    $stmt2->bind_param("s", $id_chamado);
    $stmt2->execute();
    $stmt2->close();
}

$conn->close();

echo json_encode(['success' => $ok]);
