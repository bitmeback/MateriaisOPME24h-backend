<?php
declare(strict_types=1);
ob_start();
$user = $user ?? [];
?>
<h1>Perfil</h1>
<div class="nav nav-top">
  <?php if (in_array((string)($user['role'] ?? ''), ['admin', 'desenv'], true)): ?>
    <a class="btn" href="/configuracoes/sistema">Configurações</a>
  <?php endif; ?>
  <a class="btn btn-secondary" href="/dashboard">Voltar</a>
</div>

<?php if (!empty($error)): ?>
  <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<?php if (!empty($success)): ?>
  <p class="success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<div class="profile-layout">
  <section class="panel">
    <h2>Informações do usuário</h2>
    <ul class="profile-list">
      <li>
        <span class="profile-label">Nome completo</span>
        <span class="profile-value"><?= htmlspecialchars((string)($user['full_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
      </li>
      <li>
        <span class="profile-label">Usuário</span>
        <span class="profile-value"><?= htmlspecialchars((string)($user['username'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
      </li>
      <li>
        <span class="profile-label">Perfil</span>
        <span class="profile-value"><?= htmlspecialchars((string)($user['role_label'] ?? $user['role'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
      </li>
      <li>
        <span class="profile-label">Status</span>
        <span class="profile-value"><?= !empty($user['active']) ? 'Ativo' : 'Inativo' ?></span>
      </li>
      <li>
        <span class="profile-label">Último login</span>
        <span class="profile-value"><?= htmlspecialchars((string)($user['last_login_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
      </li>
      <li>
        <span class="profile-label">Criado em</span>
        <span class="profile-value"><?= htmlspecialchars((string)($user['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
      </li>
      <li>
        <span class="profile-label">Atualizado em</span>
        <span class="profile-value"><?= htmlspecialchars((string)($user['updated_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span>
      </li>
    </ul>
  </section>

  <aside class="panel">
    <h2>Trocar senha</h2>
    <p class="help">Informe a senha atual e digite a nova senha duas vezes.</p>
    <form method="post" action="/perfil" class="form-standard form-compact">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

      <label for="current_password">Senha atual</label>
      <input id="current_password" type="password" name="current_password" autocomplete="current-password" required>

      <label for="new_password">Nova senha</label>
      <input id="new_password" type="password" name="new_password" autocomplete="new-password" minlength="8" required>
      <div class="help">Use uma senha com pelo menos 8 caracteres.</div>

      <label for="new_password_confirm">Confirme a nova senha</label>
      <input id="new_password_confirm" type="password" name="new_password_confirm" autocomplete="new-password" minlength="8" required>

      <div class="form-actions">
        <button type="submit">Atualizar senha</button>
      </div>
    </form>
  </aside>
</div>
<?php
$content = ob_get_clean();
$title = 'Perfil';
require __DIR__ . '/layout.php';
