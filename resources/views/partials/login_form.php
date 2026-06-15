<?php
declare(strict_types=1);
$formAction = (string)($form_action ?? '/login');
$buttonLabel = (string)($button_label ?? 'Entrar');
?>
<?php if (!empty($error)): ?>
  <p class="error"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<form method="post" action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>" class="form-standard login-form">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

  <label for="username">Usuário</label>
  <input id="username" name="username" autocomplete="username" required>

  <label for="password">Senha</label>
  <input id="password" type="password" name="password" autocomplete="current-password" required>

  <div class="form-actions login-form-actions">
    <button type="submit"><?= htmlspecialchars($buttonLabel, ENT_QUOTES, 'UTF-8') ?></button>
  </div>
</form>
