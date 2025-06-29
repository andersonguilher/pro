<?php
function uploadFotos($arquivos) {
    $urls = [];
    $diretorio = '../uploads/';
    if (!file_exists($diretorio)) mkdir($diretorio, 0755, true);

    if (!$arquivos || !isset($arquivos['tmp_name'])) return ['', '', '', ''];

    foreach ($arquivos['tmp_name'] as $i => $tmp) {
        if ($tmp && is_uploaded_file($tmp)) {
            $ext = pathinfo($arquivos['name'][$i], PATHINFO_EXTENSION);
            $nome = uniqid("foto_", true) . '.' . $ext;
            $destino = $diretorio . $nome;
            if (move_uploaded_file($tmp, $destino)) {
                $urls[] = 'uploads/' . $nome;
            } else {
                $urls[] = '';
            }
        }
    }

    // Garante 4 posições
    while (count($urls) < 4) $urls[] = '';
    return $urls;
}
