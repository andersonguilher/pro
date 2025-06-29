<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current = $_SERVER['PHP_SELF'];
$base = dirname($current); // ex: /pro/pages
?>
<style>
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 70px;
  background: #0f172a;
  border-top: 1px solid #1e293b;
  display: flex;
  justify-content: space-around;
  align-items: center;
  z-index: 50;
  font-family: sans-serif;
}
.bottom-nav a {
  flex-grow: 1;
  text-align: center;
  font-size: 12px;
  color: #ccc;
  text-decoration: none;
  padding: 6px 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}
.bottom-nav a.ativo {
  color: #38bdf8;
  font-weight: bold;
}
.bottom-nav a.ativo i {
  transform: scale(1.2);
}
</style>

<div class="bottom-nav">
  <a href="<?= $base ?>/vistoria_1746.php" class="<?= strpos($current, 'vistoria_1746') !== false ? 'ativo' : '' ?>">
    <i class="fas fa-file-alt"></i>
    1746
  </a>
  <a href="<?= $base ?>/vistoria_local.php" class="<?= strpos($current, 'vistoria_local') !== false ? 'ativo' : '' ?>">
    <i class="fas fa-tools"></i>
    Local
  </a>
  <a href="#" class="opacity-50 pointer-events-none">
    <i class="fas fa-clock"></i>
    Histórico
  </a>
  <a href="<?= dirname($base) ?>/logout.php">
    <i class="fas fa-sign-out-alt"></i>
    Sair
  </a>
</div>
