<?php
if (!headers_sent() && session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current = $_SERVER['PHP_SELF'];
$base = dirname($current);
$nivel = $_SESSION['nivel'] ?? '';
?>

<style>
.bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  width: 100%;
  height: 90px;
  background: #0f172a;
  border-top: 1px solid #1e293b;
  display: flex;
  justify-content: space-around;
  align-items: center;
  z-index: 9999;
  font-family: sans-serif;
}
.bottom-nav a {
  flex-grow: 1;
  text-align: center;
  font-size: 16px;
  color: #ccc;
  text-decoration: none;
  padding: 10px 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}
.bottom-nav a i {
  font-size: 20px;
  margin-bottom: 4px;
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
  <a href="<?= dirname($base) ?>/pages/home.php" class="<?= strpos($current, 'home') !== false ? 'ativo' : '' ?>">
    <i class="fas fa-home"></i>
    Início
  </a>

  <?php if (in_array($nivel, ['despachador', 'concessionaria', 'gerente'])): ?>
    <a href="<?= $base ?>/despachador.php" class="<?= strpos($current, 'despachador') !== false ? 'ativo' : '' ?>">
      <i class="fas fa-clipboard-list"></i>
      Chamados
    </a>

  <?php elseif ($nivel === 'admin'): ?>
    <a href="<?= $base ?>/despachador.php" class="<?= strpos($current, 'despachador') !== false ? 'ativo' : '' ?>">
      <i class="fas fa-clipboard-list"></i>
      Chamados
    </a>      
    <a href="<?= $base ?>/vistoria_1746.php" class="<?= strpos($current, 'vistoria_1746') !== false ? 'ativo' : '' ?>">
      <i class="fas fa-file-alt"></i>
      1746
    </a>
    <a href="<?= $base ?>/vistoria_local.php" class="<?= strpos($current, 'vistoria_local') !== false ? 'ativo' : '' ?>">
      <i class="fas fa-tools"></i>
      Rotina
    </a>

  <?php else: ?>
    <a href="<?= $base ?>/vistoria_1746.php" class="<?= strpos($current, 'vistoria_1746') !== false ? 'ativo' : '' ?>">
      <i class="fas fa-file-alt"></i>
      1746
    </a>
    <a href="<?= $base ?>/vistoria_local.php" class="<?= strpos($current, 'vistoria_local') !== false ? 'ativo' : '' ?>">
      <i class="fas fa-tools"></i>
      Rotina
    </a>
  <?php endif; ?>

  <?php if (in_array($nivel, ['admin', 'fiscal', 'gerente'])): ?>
    <a href="<?= $base ?>/mapa_chamados.php" class="<?= strpos($current, 'mapa') !== false ? 'ativo' : '' ?>">
      <i class="fas fa-map-marked-alt"></i>
      Mapa
    </a>
  <?php endif; ?>

  <?php if ($nivel === 'admin'): ?>
    <a href="<?= dirname($base) ?>/pages/admin_dashboard.php" class="<?= strpos($current, 'admin') !== false ? 'ativo' : '' ?>">
      <i class="fas fa-user-cog"></i>
      Admin
    </a>
  <?php endif; ?>

  <a href="<?= dirname($base) ?>/php/logout.php">
    <i class="fas fa-sign-out-alt"></i>
    Sair
  </a>
</div>
