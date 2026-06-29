<?php
declare(strict_types=1);
ob_start();
?>
<h1>Fornecedores</h1>
<p class="muted">Cadastro de fornecedores para envio de notificações de informações sobre materiais usados em saída de sala.</p>
<div class="nav nav-top">
  <a class="btn" href="/fornecedores/novo">Incluir fornecedor</a>
  <a class="btn btn-secondary" href="/dashboard">Voltar</a>
</div>

<?php if (!empty($success)): ?>
  <p class="success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<?php if (!empty($error)): ?>
  <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>

<section class="panel list-panel suppliers-panel">
  <div class="panel-head">
    <div>
      <h2>Lista de fornecedores</h2>
      <p class="muted">Total: <?= (int)($pagination['total'] ?? count($vendors ?? [])) ?> fornecedor(es) cadastrados.</p>
    </div>
  </div>

  <form method="get" action="/fornecedores" class="search-bar">
    <input type="text" name="q" value="<?= htmlspecialchars((string)($query ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por CNPJ, status, contato, nome ou observação">
    <select name="status">
      <option value="">Todas as etiquetas</option>
      <?php foreach (($status_options ?? []) as $option): ?>
        <option value="<?= htmlspecialchars((string)$option, ENT_QUOTES, 'UTF-8') ?>" <?= (($status ?? '') === $option) ? 'selected' : '' ?>>
          <?= htmlspecialchars((string)$option, ENT_QUOTES, 'UTF-8') ?>
        </option>
      <?php endforeach; ?>
    </select>
    <select name="sort">
      <option value="cnpj_asc" <?= (($sort ?? 'cnpj_asc') === 'cnpj_asc') ? 'selected' : '' ?>>Ordem CNPJ (A→Z)</option>
      <option value="cnpj_desc" <?= (($sort ?? '') === 'cnpj_desc') ? 'selected' : '' ?>>Ordem CNPJ (Z→A)</option>
      <option value="name_asc" <?= (($sort ?? '') === 'name_asc') ? 'selected' : '' ?>>Ordem Nome (A→Z)</option>
      <option value="name_desc" <?= (($sort ?? '') === 'name_desc') ? 'selected' : '' ?>>Ordem Nome (Z→A)</option>
      <option value="pendencias_asc" <?= (($sort ?? '') === 'pendencias_asc') ? 'selected' : '' ?>>Ordem Pendentes (Menor→Maior)</option>
      <option value="pendencias_desc" <?= (($sort ?? '') === 'pendencias_desc') ? 'selected' : '' ?>>Ordem Pendentes (Maior→Menor)</option>
    </select>
    <select name="per_page" aria-label="Registros por página">
      <?php for ($i = 10; $i <= 100; $i += 10): ?>
        <option value="<?= $i ?>" <?= ((int)($pagination['per_page'] ?? 10) === $i) ? 'selected' : '' ?>><?= $i ?></option>
      <?php endfor; ?>
    </select>
    <button class="btn" type="submit">Filtrar</button>
    <?php if (!empty($query) || !empty($status) || ($sort ?? 'cnpj_asc') !== 'cnpj_asc' || (int)($pagination['per_page'] ?? 10) !== 10): ?>
      <a class="btn btn-secondary" href="/fornecedores?<?= htmlspecialchars(http_build_query(array_filter(['per_page' => (int)($pagination['per_page'] ?? 10)], static fn($value) => $value !== '' && $value !== null)), ENT_QUOTES, 'UTF-8') ?>">Limpar</a>
    <?php endif; ?>
  </form>

  <?php if (!empty($pagination)): ?>
    <p class="muted">
      <?= (int)($pagination['total'] ?? 0) ?> resultado(s)
      · página <?= (int)($pagination['page'] ?? 1) ?> de <?= (int)($pagination['total_pages'] ?? 1) ?>
    </p>
  <?php endif; ?>

  <div class="table-wrap">
    <table class="list-table fornecedores-table">
      <colgroup>
        <col style="width: 140px;">
        <col style="width: 65px;">
        <col style="width: 90px;">
        <col style="width: 80px;">
        <col style="width: 170px;">
        <col style="width: 550px;">
        <col style="width: 190px;">
      </colgroup>
      <thead>
        <tr>
          <th class="cnpj-cell">CNPJ</th>
          <th class="status-cell">Enviar</th>
          <th class="status-cell">Etiqueta</th>
          <th class="status-cell">Pendentes</th>
          <th class="contacts">Contatos</th>
          <th>Nome</th>
          <th class="actions-col">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (($vendors ?? []) as $vendor): ?>
          <?php $canSendPending = !empty($vendor['can_send_pending']); ?>
          <tr>
            <td class="cnpj-cell"><?= htmlspecialchars((string)($vendor['cnpj_formatted'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td class="status-cell"><?= !empty($vendor['send_report']) ? 'sim' : 'não' ?></td>
            <td class="status-cell"><?= htmlspecialchars((string)($vendor['status'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td class="pending-count<?= ((int)($vendor['pending_display_count'] ?? $vendor['pendencias'] ?? 0) === 0) ? ' zero' : '' ?>"><?= (int)($vendor['pending_display_count'] ?? $vendor['pendencias'] ?? 0) ?></td>
            <td class="contacts"><?= htmlspecialchars((string)($vendor['contacts'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($vendor['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td class="actions">
              <div class="table-actions">
                <a class="btn btn-sm btn-edit" href="/fornecedores/editar/<?= urlencode((string)($vendor['cnpj'] ?? '')) ?>">Editar</a>
                <form method="post" action="/fornecedores/enviar-pendentes/<?= urlencode((string)($vendor['cnpj'] ?? '')) ?>" onsubmit="return confirm('Enviar pendências deste fornecedor?');">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                  <button class="btn btn-sm btn-pending" type="submit" <?= $canSendPending ? '' : 'disabled' ?>>Enviar pendentes</button>
                </form>
                <form method="post" action="/fornecedores/excluir/<?= urlencode((string)($vendor['cnpj'] ?? '')) ?>" onsubmit="return confirm('Remover este fornecedor?');">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                  <button class="btn btn-sm btn-danger" type="submit">Excluir</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if (!empty($pagination) && (int)($pagination['total_pages'] ?? 1) > 1): ?>
    <?php
      $baseParams = [
        'q' => (string)($query ?? ''),
        'status' => (string)($status ?? ''),
        'sort' => (string)($sort ?? 'cnpj_asc'),
        'per_page' => (int)($pagination['per_page'] ?? 10),
      ];
      $currentPage = (int)($pagination['page'] ?? 1);
      $totalPages = (int)($pagination['total_pages'] ?? 1);
    ?>
    <div class="pagination">
      <?php if ($currentPage > 1): ?>
        <a class="btn btn-secondary" href="/fornecedores?<?= htmlspecialchars(http_build_query($baseParams + ['page' => $currentPage - 1]), ENT_QUOTES, 'UTF-8') ?>">Anterior</a>
      <?php endif; ?>
      <span class="muted">Página <?= $currentPage ?> de <?= $totalPages ?></span>
      <?php if ($currentPage < $totalPages): ?>
        <a class="btn btn-secondary" href="/fornecedores?<?= htmlspecialchars(http_build_query($baseParams + ['page' => $currentPage + 1]), ENT_QUOTES, 'UTF-8') ?>">Próxima</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
$title = 'Fornecedores';
require __DIR__ . '/layout.php';
