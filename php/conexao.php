<?php
ini_set('display_errors', 1);
// Configurações do banco de dados
$host = 'localhost';       // ou o IP do servidor MySQL
$usuario = '22gc_mysql';         // substitua conforme necessário
$senha = 'wxOoZLex1ary6SX5';               // sua senha MySQL
$banco = '22gc_demandas1746';   // nome real do banco de dados

// Cria a conexão
$conn = new mysqli($host, $usuario, $senha, $banco);

// Verifica se houve erro
if ($conn->connect_error) {
  die("Erro na conexão: " . $conn->connect_error);
}

// Define charset para evitar problemas com acentuação
$conn->set_charset("utf8");
