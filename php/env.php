<?php
// php/env.php
function loadEnv(string $dir) {
  $file = rtrim($dir, '/').'/../.env';
  if (!is_readable($file)) return;
  foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#')) continue;
    [$k, $v] = array_pad(explode('=', $line, 2), 2, '');
    $k = trim($k); $v = trim($v);
    if ($k !== '' && getenv($k) === false) {
      putenv("$k=$v"); $_ENV[$k] = $v; $_SERVER[$k] = $v;
    }
  }
}
loadEnv(__DIR__); // lida .env um nível acima de /php
