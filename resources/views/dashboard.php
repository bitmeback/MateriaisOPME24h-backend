<?php
declare(strict_types=1);
ob_start();
?>
<h1>Dashboard</h1>
<div class="nav nav-top">
  <a class="btn" href="/fornecedores">Fornecedores</a>
  <a class="btn" href="/enviados">Enviados</a>
  <?php if (in_array(($role ?? 'guest'), ['admin', 'desenv'], true)): ?>
    <a class="btn" href="/usuarios">Usuários</a>
  <?php endif; ?>
  <?php if (in_array(($role ?? 'guest'), ['admin', 'desenv'], true)): ?>
    <a class="btn" href="/configuracoes/sistema">Configurações</a>
  <?php endif; ?>
</div>
<h2>Pagina de administração do sistema notificações de Materiais OPME</h2>
<ul>
  <li>Usuários: Administração de fornecedores.</li>
  <li>Administradores: Administração de fornecedores, usuários e configurações de sistema.</li>
</ul>
<?php
$content = ob_get_clean();
$title = 'Dashboard';
require __DIR__ . '/layout.php';
