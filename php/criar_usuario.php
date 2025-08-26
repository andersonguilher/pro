<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO("mysql:host=localhost;dbname=22gc_demandas1746;charset=utf8", "22gc_mysql", "wxOoZLex1ary6SX5");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $usuario = 'admin';
    $senha = password_hash('123456', PASSWORD_DEFAULT);

    $sql = "INSERT INTO usr_conservapp (usuario, senha) VALUES (:usuario, :senha)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['usuario' => $usuario, 'senha' => $senha]);

    echo "✅ Usuário criado com sucesso.";
} catch (PDOException $e) {
    echo "❌ Erro: " . $e->getMessage();
}
