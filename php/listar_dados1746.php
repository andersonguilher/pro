<?php
require_once("conexao.php");

$sql = "SELECT id_chamado, data_sla, subtipo, logradouro, numero, bairro, complemento, referencia, ds_chamado 
        FROM dados1746 
        WHERE status IN ('Aberto', 'Em andamento') AND setor LIKE '%Fiscal%'
        ORDER BY data_sla ASC";

$result = $conn->query($sql);
$chamados = [];

while ($row = $result->fetch_assoc()) {
    $row["endereco"] = $row["logradouro"] . ", " . $row["numero"] . " - " . $row["bairro"];
    $chamados[] = $row;
}

echo json_encode($chamados, JSON_UNESCAPED_UNICODE);
?>
