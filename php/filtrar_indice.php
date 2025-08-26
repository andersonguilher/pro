<?php
require_once("conexao.php");

$modo = $_GET['modo'] ?? 'diario';
$mes = $_GET['mes'] ?? date('m');
$subtipo = $_GET['subtipo'] ?? 'todos';

$filtroSQL = "";
$param = "";

if ($subtipo === 'manutencao') {
    $filtroSQL = "AND subtipo = ?";
    $param = "Manutenção/Desobstrução de ramais de águas pluviais e ralos";
} elseif ($subtipo === 'reparo') {
    $filtroSQL = "AND subtipo = ?";
    $param = "Reparo de buraco, deformação ou afundamento na pista";
}

if ($modo === 'mensal') {
    $sql = "
        SELECT 
            DATE_FORMAT(dia, '%Y-%m') AS mes,
            SUM(fnp) AS fnp,
            SUM(ffp) AS ffp
        FROM indice_geral
        WHERE 1=1 $filtroSQL
        GROUP BY mes
        ORDER BY mes ASC
    ";

    $stmt = $conn->prepare($sql);
    if ($filtroSQL !== "") {
        $stmt->bind_param("s", $param);
    }

    $stmt->execute();
    $res = $stmt->get_result();

    $dados = [];
    $totalFnp = 0;
    $totalFfp = 0;

    while ($row = $res->fetch_assoc()) {
        $totalFnp += (int)$row['fnp'];
        $totalFfp += (int)$row['ffp'];
        $dados[] = [
            'dia' => $row['mes'],
            'fnp' => $totalFnp,
            'ffp' => $totalFfp
        ];
    }

} else {
    $mesAtual = (int)$mes;
    $anoAtual = date('Y');

    $mesAnterior = $mesAtual - 1;
    $anoAnterior = $anoAtual;
    if ($mesAnterior === 0) {
        $mesAnterior = 12;
        $anoAnterior--;
    }

    // Dados mês atual
    $sqlAtual = "
        SELECT DATE_FORMAT(dia, '%Y-%m-%d') AS dia, fnp, ffp
        FROM indice_geral
        WHERE YEAR(dia) = ? AND MONTH(dia) = ? $filtroSQL
        ORDER BY dia ASC
    ";
    $stmtAtual = $conn->prepare($sqlAtual);
    if ($filtroSQL !== "") {
        $stmtAtual->bind_param("iis", $anoAtual, $mesAtual, $param);
    } else {
        $stmtAtual->bind_param("ii", $anoAtual, $mesAtual);
    }
    $stmtAtual->execute();
    $resAtual = $stmtAtual->get_result();
    $dadosAtual = [];
    while ($row = $resAtual->fetch_assoc()) {
        $dadosAtual[] = $row;
    }

    // Dados mês anterior
    $sqlAnterior = "
        SELECT DATE_FORMAT(dia, '%Y-%m-%d') AS dia, fnp, ffp
        FROM indice_geral
        WHERE YEAR(dia) = ? AND MONTH(dia) = ? $filtroSQL
        ORDER BY dia ASC
    ";
    $stmtAnterior = $conn->prepare($sqlAnterior);
    if ($filtroSQL !== "") {
        $stmtAnterior->bind_param("iis", $anoAnterior, $mesAnterior, $param);
    } else {
        $stmtAnterior->bind_param("ii", $anoAnterior, $mesAnterior);
    }
    $stmtAnterior->execute();
    $resAnterior = $stmtAnterior->get_result();
    $dadosAnterior = [];
    while ($row = $resAnterior->fetch_assoc()) {
        $dadosAnterior[] = $row;
    }

    $dados = [
        'atual' => $dadosAtual,
        'anterior' => $dadosAnterior
    ];
}

header('Content-Type: application/json');
echo json_encode($dados);
