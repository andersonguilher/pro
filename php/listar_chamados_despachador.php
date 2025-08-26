<?php
require_once("conexao.php");

$sql = "
SELECT 
  d1.id_chamado,
  d1.data_sla,
  d1.subtipo,
  d1.logradouro,
  d1.numero,
  d1.bairro,
  d1.complemento,
  d1.referencia,
  d1.ds_chamado,
  d1.setor,
  d1.obs,
  d2.foto1,
  d2.foto2,
  d2.foto3,
  d2.foto4
FROM dados1746 d1
LEFT JOIN demandas d2 ON d1.id_chamado = d2.protocolo
WHERE d1.status IN ('Aberto', 'Em andamento')
ORDER BY 
  CASE 
    WHEN DATE(d1.data_sla) >= CURDATE() THEN 0
    ELSE 1
  END,
  DATE(d1.data_sla) ASC
";

$result = $conn->query($sql);
$chamados = [];

while ($row = $result->fetch_assoc()) {
    $row["endereco"] = $row["logradouro"] . ", " . $row["numero"] . " - " . $row["bairro"];
    $chamados[] = $row;
}

echo json_encode($chamados, JSON_UNESCAPED_UNICODE);
?>
