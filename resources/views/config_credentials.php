<?php
declare(strict_types=1);
ob_start();
$values = $values ?? [];
?>
<h1>Credenciais IMAP/SMTP</h1>
<div class="nav nav-top">
  <a class="btn btn-secondary" href="/configuracoes/sistema">Voltar</a>
</div>
<p class="muted">Credenciais da conta de e-mail usadas para coleta (IMAP) e envio (SMTP). Não alterar sem apoio do Desenvolvedor pois pode afetar a operação.</p>
<?php if (!empty($error)): ?>
  <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<?php if (!empty($success)): ?>
  <p class="success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<form method="post" action="/configuracoes/credenciais" class="form-standard form-compact">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
  <div class="form-grid">
    <div>
      <label>Usuário</label>
      <input name="user" value="<?= htmlspecialchars((string)($values['user'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
    </div>
    <div>
      <label>Senha</label>
      <input type="password" name="pass" value="" placeholder="Deixe em branco para manter o valor atual">
    </div>
  </div>
  <div class="form-actions">
    <button type="submit">Salvar credenciais</button>
  </div>
</form>
<?php
$content = ob_get_clean();
$title = 'Credenciais IMAP/SMTP';
require __DIR__ . '/layout.php';
