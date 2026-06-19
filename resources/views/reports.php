<?php
declare(strict_types=1);
ob_start();

$isEdit = isset($edit) && is_array($edit);
$formAction = $isEdit ? "/reports/editar/{$edit['id']}" : "/reports";
$formTitle = $isEdit ? 'Editar destinatário' : 'Adicionar destinatário';
$submitLabel = $isEdit ? 'Salvar alterações' : 'Adicionar destinatário';
?>
<h1>Relatórios — Destinatários</h1>
<p class="muted">Gerencie os destinatários que recebem os relatórios por e-mail e/ou WhatsApp.</p>
<div class="nav nav-top">
  <a class="btn btn-secondary" href="/dashboard">Voltar</a>
  <a class="btn" href="/fornecedores">Fornecedores</a>
  <a class="btn" href="/enviados">Enviados</a>
</div>

<?php if (!empty($success)): ?>
  <p class="success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<section class="panel list-panel">
  <div class="panel-head">
    <div>
      <h2>Destinatários cadastrados</h2>
      <p class="muted">Total: <?= is_array($items ?? null) ? count($items) : 0 ?> registro(s).</p>
    </div>
  </div>

  <div class="table-wrap">
    <table class="list-table">
      <colgroup>
        <col style="width: 60px;">
        <col style="width: 180px;">
        <col style="width: 260px;">
        <col style="width: 140px;">
        <col style="width: 100px;">
        <col style="width: 80px;">
        <col style="width: 120px;">
      </colgroup>
      <thead>
        <tr>
          <th>#</th>
          <th>Nome</th>
          <th>E-mail</th>
          <th>Telefone</th>
          <th>Tipo</th>
          <th>Ativo</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (($items ?? []) as $item): ?>
          <tr>
            <td><?= (int)$item['id'] ?></td>
            <td><?= htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td class="contacts"><?= htmlspecialchars((string)($item['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($item['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($item['type'] ?? 'email'), ENT_QUOTES, 'UTF-8') ?></td>
            <td style="text-align: center;">
              <?php if (!empty($item['active'])): ?>
                <span class="success">✓ Sim</span>
              <?php else: ?>
                <span class="muted">✗ Não</span>
              <?php endif; ?>
            </td>
            <td class="actions">
              <div class="table-actions">
                <a class="btn btn-sm btn-edit" href="/reports/editar/<?= (int)$item['id'] ?>">Editar</a>
                <form method="post" action="/reports/excluir/<?= (int)$item['id'] ?>" onsubmit="return confirm('Remover este destinatário?');">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                  <button class="btn btn-sm btn-danger" type="submit">Excluir</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($items)): ?>
          <tr>
            <td colspan="7" class="muted" style="text-align: center; padding: 24px;">Nenhum destinatário cadastrado.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<section class="panel" style="margin-top: 24px;">
  <h2><?= $formTitle ?></h2>
  <form method="post" action="<?= $formAction ?>" class="form-standard form-compact">
    <div class="form-grid">
      <div>
        <label for="name">Nome *</label>
        <input type="text" id="name" name="name" required placeholder="Ex: João" value="<?= htmlspecialchars($isEdit ? ($edit['name'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div>
        <label for="email">E-mail</label>
        <input type="email" id="email" name="email" placeholder="Ex: joao@email.com" value="<?= htmlspecialchars($isEdit ? ($edit['email'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div>
        <label for="phone">Telefone / WhatsApp</label>
        <input type="text" id="phone" name="phone" placeholder="Ex: 554797618699" value="<?= htmlspecialchars($isEdit ? ($edit['phone'] ?? '') : '', ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div>
        <label for="type">Tipo</label>
        <select id="type" name="type">
          <option value="email" <?= ($isEdit && ($edit['type'] ?? '') === 'email') ? 'selected' : '' ?>>E-mail</option>
          <option value="whatsapp" <?= ($isEdit && ($edit['type'] ?? '') === 'whatsapp') ? 'selected' : '' ?>>WhatsApp</option>
          <option value="both" <?= ($isEdit && ($edit['type'] ?? '') === 'both') ? 'selected' : '' ?>>E-mail + WhatsApp</option>
        </select>
      </div>
      <div class="checkbox-line" style="margin-top: 24px;">
        <input type="checkbox" id="active" name="active" value="1" <?= (!$isEdit || !empty($edit['active'])) ? 'checked' : '' ?>>
        <label for="active" style="margin: 0; font-weight: normal;">Ativo</label>
      </div>
    </div>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
    <div class="form-actions" style="margin-top: 16px;">
      <button class="btn" type="submit"><?= $submitLabel ?></button>
      <?php if ($isEdit): ?>
        <a class="btn btn-secondary" href="/reports">Cancelar</a>
      <?php endif; ?>
    </div>
  </form>
  <p class="help" style="margin-top: 8px;">* Informe pelo menos um e-mail ou telefone.</p>
</section>

<?php
$content = ob_get_clean();
$title = 'Relatórios — Destinatários';
require __DIR__ . '/layout.php';
