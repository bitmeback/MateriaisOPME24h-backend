<?php
declare(strict_types=1);
ob_start();
$users = $users ?? [];
$form = $form ?? [];
$filters = $filters ?? [];
$formMode = (string)($form_mode ?? 'create');
$isEditing = $formMode === 'edit';
?>
<h1>Usuários</h1>
<p class="muted">Área exclusiva do administrador para consultar contas e cadastrar novos usuários.</p>
<?php
$currentFilterParams = array_filter([
  'q' => (string)($filters['q'] ?? ''),
  'role' => (string)($filters['role'] ?? 'all'),
  'active' => (string)($filters['active'] ?? 'all'),
  'sort' => (string)($filters['sort'] ?? 'default'),
  'per_page' => (int)($pagination['per_page'] ?? 10),
], static fn ($value) => $value !== '' && $value !== 'all' && $value !== 'default');
$hasFilters = !empty($currentFilterParams);
$currentPage = (int)($pagination['page'] ?? 1);
$totalPages = (int)($pagination['total_pages'] ?? 1);
$clearParams = array_filter([
  'per_page' => (int)($pagination['per_page'] ?? 10),
], static fn ($value) => $value !== '' && $value !== null && $value !== 10);
?>
<div class="nav nav-top">
  <a class="btn btn-secondary" href="/dashboard">Voltar</a>
  <a class="btn" href="/fornecedores">Fornecedores</a>
  <a class="btn" href="/configuracoes/sistema">Configurações</a>
</div>

<form method="get" action="/usuarios" class="search-bar">
  <input type="text" name="q" value="<?= htmlspecialchars((string)($filters['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por usuário, nome completo, perfil ou status">
  <select name="role">
    <option value="all" <?= (($filters['role'] ?? 'all') === 'all') ? 'selected' : '' ?>>Todos os perfis</option>
    <option value="user" <?= (($filters['role'] ?? 'all') === 'user') ? 'selected' : '' ?>>Usuário</option>
    <option value="admin" <?= (($filters['role'] ?? 'all') === 'admin') ? 'selected' : '' ?>>Administrador</option>
    <option value="desenv" <?= (($filters['role'] ?? 'all') === 'desenv') ? 'selected' : '' ?>>Desenvolvedor</option>
  </select>
  <select name="active">
    <option value="all" <?= (($filters['active'] ?? 'all') === 'all') ? 'selected' : '' ?>>Todos os status</option>
    <option value="1" <?= (($filters['active'] ?? 'all') === '1') ? 'selected' : '' ?>>Ativos</option>
    <option value="0" <?= (($filters['active'] ?? 'all') === '0') ? 'selected' : '' ?>>Inativos</option>
  </select>
  <select name="sort">
    <option value="default" <?= (($filters['sort'] ?? 'default') === 'default') ? 'selected' : '' ?>>Ordenação padrão</option>
    <option value="username_asc" <?= (($filters['sort'] ?? 'default') === 'username_asc') ? 'selected' : '' ?>>Usuário (A→Z)</option>
    <option value="username_desc" <?= (($filters['sort'] ?? 'default') === 'username_desc') ? 'selected' : '' ?>>Usuário (Z→A)</option>
    <option value="full_name_asc" <?= (($filters['sort'] ?? 'default') === 'full_name_asc') ? 'selected' : '' ?>>Nome completo (A→Z)</option>
    <option value="full_name_desc" <?= (($filters['sort'] ?? 'default') === 'full_name_desc') ? 'selected' : '' ?>>Nome completo (Z→A)</option>
    <option value="role_asc" <?= (($filters['sort'] ?? 'default') === 'role_asc') ? 'selected' : '' ?>>Perfil (A→Z)</option>
    <option value="role_desc" <?= (($filters['sort'] ?? 'default') === 'role_desc') ? 'selected' : '' ?>>Perfil (Z→A)</option>
    <option value="active_desc" <?= (($filters['sort'] ?? 'default') === 'active_desc') ? 'selected' : '' ?>>Status (Ativos primeiro)</option>
    <option value="active_asc" <?= (($filters['sort'] ?? 'default') === 'active_asc') ? 'selected' : '' ?>>Status (Inativos primeiro)</option>
  </select>
  <select name="per_page" aria-label="Registros por página">
    <?php for ($i = 10; $i <= 100; $i += 10): ?>
      <option value="<?= $i ?>" <?= ((int)($pagination['per_page'] ?? 10) === $i) ? 'selected' : '' ?>><?= $i ?></option>
    <?php endfor; ?>
  </select>
  <button class="btn" type="submit">Filtrar</button>
  <?php if ($hasFilters): ?>
    <a class="btn btn-secondary" href="/usuarios?<?= htmlspecialchars(http_build_query($clearParams), ENT_QUOTES, 'UTF-8') ?>">Limpar</a>
  <?php endif; ?>
</form>
<?php if (!empty($pagination)): ?>
  <p class="muted"><?= (int)($pagination['total'] ?? count($users)) ?> resultado(s) · página <?= $currentPage ?> de <?= $totalPages ?></p>
<?php endif; ?>

<?php if (!empty($error)): ?>
  <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<?php if (!empty($success)): ?>
  <p class="success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<div class="profile-layout users-layout">
  <section class="panel users-panel">
    <div class="panel-head">
      <div>
        <h2>Lista de usuários</h2>
        <p class="muted">Total: <?= (int)($pagination['total'] ?? count($users)) ?> usuário(s) cadastrados.</p>
      </div>
    </div>

    <div class="table-wrap users-table-wrap">
      <table class="users-table">
        <colgroup>
          <col style="width: 42px;">
          <col style="width: 80px;">
          <col style="width: 140px;">
          <col style="width: 100px;">
          <col style="width: 40px;">
          <col style="width: 90px;">
          <col style="width: 90px;">
          <col style="width: 90px;">
          <col style="width: 82px;">
        </colgroup>
        <thead>
          <tr>
            <th>ID</th>
            <th class="user-col">Usuário</th>
            <th class="name-col">Nome completo</th>
            <th>Perfil</th>
            <th>Status</th>
            <th>Último login</th>
            <th>Criado em</th>
            <th>Atualizado em</th>
            <th class="actions-col">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
            <tr>
              <td><?= (int)($user['id'] ?? 0) ?></td>
              <td class="user-col"><?= htmlspecialchars((string)($user['username'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
              <td class="name-col"><?= htmlspecialchars((string)($user['full_name'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string)($user['role_label'] ?? 'Usuário'), ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= !empty($user['active']) ? 'Ativo' : 'Inativo' ?></td>
              <td><?= htmlspecialchars((string)($user['last_login_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string)($user['created_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars((string)($user['updated_at'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
              <td class="actions actions-col">
                <div class="table-actions">
                  <?php if (!empty($user['can_manage'])): ?>
                    <a class="btn btn-sm btn-edit" href="/usuarios?<?= htmlspecialchars(http_build_query($currentFilterParams + ['page' => $currentPage, 'edit' => (int)($user['id'] ?? 0)]), ENT_QUOTES, 'UTF-8') ?>">Editar</a>
                    <form method="post" action="/usuarios/excluir/<?= (int)($user['id'] ?? 0) ?>" onsubmit="return confirm('Confirma excluir este usuário?');">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                      <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
                    </form>
                  <?php else: ?>
                    <button type="button" class="btn btn-sm btn-edit" disabled title="Administradores não podem editar usuários Desenvolvedor.">Editar</button>
                    <button type="button" class="btn btn-sm btn-danger" disabled title="Administradores não podem excluir usuários Desenvolvedor.">Excluir</button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php if ($totalPages > 1): ?>
      <?php
        $baseParams = [
          'q' => (string)($filters['q'] ?? ''),
          'role' => (string)($filters['role'] ?? 'all'),
          'active' => (string)($filters['active'] ?? 'all'),
          'sort' => (string)($filters['sort'] ?? 'default'),
          'per_page' => (int)($pagination['per_page'] ?? 10),
        ];
      ?>
      <div class="pagination">
        <?php if ($currentPage > 1): ?>
          <a class="btn btn-secondary" href="/usuarios?<?= htmlspecialchars(http_build_query($baseParams + ['page' => $currentPage - 1]), ENT_QUOTES, 'UTF-8') ?>">Anterior</a>
        <?php endif; ?>
        <span class="muted">Página <?= $currentPage ?> de <?= $totalPages ?></span>
        <?php if ($currentPage < $totalPages): ?>
          <a class="btn btn-secondary" href="/usuarios?<?= htmlspecialchars(http_build_query($baseParams + ['page' => $currentPage + 1]), ENT_QUOTES, 'UTF-8') ?>">Próxima</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </section>

  <aside class="panel users-panel">
    <h2><?= $isEditing ? 'Editar usuário' : 'Novo usuário' ?></h2>
    <p class="help"><?= $isEditing ? 'Ajuste os dados do usuário selecionado e salve as alterações.' : 'Preencha os dados abaixo para criar uma conta com acesso ao sistema.' ?></p>
    <form class="form-standard" method="post" action="/usuarios">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
      <input type="hidden" name="id" value="<?= (int)($form['id'] ?? 0) ?>">
      <input type="hidden" name="form_mode" value="<?= $isEditing ? 'edit' : 'create' ?>">
      <input type="hidden" name="page" value="<?= (int)($pagination['page'] ?? 1) ?>">
      <input type="hidden" name="per_page" value="<?= (int)($pagination['per_page'] ?? 10) ?>">
      <?php $usernameHint = $username_hint ?? null; ?>
      <?php $passwordHint = $password_hint ?? null; ?>

      <label for="username">Usuário</label>
      <input id="username" type="text" name="username" value="<?= htmlspecialchars((string)($form['username'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="80" autocomplete="off" spellcheck="false" pattern="[A-Za-z0-9._-]+" title="Use apenas letras, números, ponto, underline e hífen." required>
      <?php if (!empty($usernameHint)): ?>
        <div class="username-hint error"><?= htmlspecialchars($usernameHint, ENT_QUOTES, 'UTF-8') ?></div>
      <?php else: ?>
        <div class="username-hint muted">Use apenas letras, números, ponto, underline e hífen.</div>
      <?php endif; ?>

      <label for="full_name">Nome completo</label>
      <input id="full_name" type="text" name="full_name" value="<?= htmlspecialchars((string)($form['full_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" maxlength="150" required>

      <label for="role">Perfil</label>
      <select id="role" name="role" required>
        <option value="user" <?= (($form['role'] ?? 'user') === 'user') ? 'selected' : '' ?>>Usuário</option>
        <option value="admin" <?= (($form['role'] ?? 'user') === 'admin') ? 'selected' : '' ?>>Administrador</option>
        <option value="desenv" <?= (($form['role'] ?? 'user') === 'desenv') ? 'selected' : '' ?>>Desenvolvedor</option>
      </select>

      <label for="password"><?= $isEditing ? 'Nova senha (opcional)' : 'Senha' ?></label>
      <input id="password" type="password" name="password" autocomplete="new-password" <?= $isEditing ? '' : 'minlength="8" required' ?>>
      <?php if (!empty($passwordHint)): ?>
        <div class="password-hint error"><?= htmlspecialchars($passwordHint, ENT_QUOTES, 'UTF-8') ?></div>
      <?php else: ?>
        <div class="password-hint muted"><?= $isEditing ? 'Deixe em branco para manter a senha atual.' : 'Mínimo de 8 caracteres.' ?></div>
      <?php endif; ?>

      <label for="password_confirm"><?= $isEditing ? 'Confirmar nova senha' : 'Confirmar senha' ?></label>
      <input id="password_confirm" type="password" name="password_confirm" autocomplete="new-password" <?= $isEditing ? '' : 'minlength="8" required' ?>>

      <label class="checkbox-line">
        <input type="checkbox" name="active" value="1" <?= !empty($form['active']) ? 'checked' : '' ?>>
        <span>Usuário ativo</span>
      </label>

      <div class="form-actions">
        <button type="submit"><?= $isEditing ? 'Atualizar' : 'Cadastrar' ?></button>
        <?php if ($isEditing): ?>
          <a class="btn btn-secondary" href="/usuarios">Cancelar</a>
        <?php endif; ?>
      </div>
    </form>
  </aside>
</div>
<?php
$content = ob_get_clean();
$title = 'Usuários';
require __DIR__ . '/layout.php';
