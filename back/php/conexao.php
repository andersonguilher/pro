<?php
// Configurações do banco de dados
$host = 'localhost';       // ou o IP do servidor MySQL
$usuario = 'root';         // substitua conforme necessário
$senha = 'Ptacaapt@190667';               // sua senha MySQL
$banco = '22gc_demandas1746';   // nome real do banco de dados

// Cria a conexão
$conn = new mysqli($host, $usuario, $senha, $banco);

// Verifica se houve erro
if ($conn->connect_error) {
  die("Erro na conexão: " . $conn->connect_error);
}

// Define charset para evitar problemas com acentuação
$conn->set_charset("utf8");
