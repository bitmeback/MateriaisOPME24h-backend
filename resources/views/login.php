<?php
declare(strict_types=1);
ob_start();
?>
<div class="panel login-panel">
  <h1 style="margin-top: 0;">Materiais Opme Backend</h1>
  <p class="muted">Acesso restrito ao setor de OPME</p>
  <?php require __DIR__ . '/partials/login_form.php'; ?>
</div>
<?php
$content = ob_get_clean();
$title = 'Login';
require __DIR__ . '/layout.php';
