<?php
declare(strict_types=1);
require_once __DIR__ . '/env.php';

// Produção: não exibir erros no navegador
$showErrors = getenv('APP_ENV') === 'local' ? '1' : '0';
ini_set('display_errors', $showErrors);
ini_set('display_startup_errors', $showErrors);
if (!$showErrors) error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$host = getenv('DB_HOST') ?: 'localhost';
$port = (int)(getenv('DB_PORT') ?: 3306);
$user = getenv('DB_USER') ?: '';
$pass = getenv('DB_PASS') ?: '';
$name = getenv('DB_NAME') ?: '';

$conn = @new mysqli($host, $user, $pass, $name, $port);
if ($conn->connect_error) {
  error_log('DB connection error: '.$conn->connect_error);
  http_response_code(500);
  exit; // não vaze detalhes para o cliente
}
$conn->set_charset('utf8mb4');
?>