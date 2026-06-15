<?php
declare(strict_types=1);
ob_start();
?>
<h1>Enviados</h1>
<p class="muted">Histórico de relatórios enviados aos fornecedores via e-mail.</p>
<div class="nav nav-top">
  <a class="btn btn-secondary" href="/dashboard">Voltar</a>
  <a class="btn" href="/fornecedores">Fornecedores</a>
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
      <h2>Histórico de envios</h2>
      <p class="muted">Total: <?= (int)($total ?? 0) ?> registro(s) encontrado(s).</p>
    </div>
  </div>

  <form method="get" action="/enviados" class="search-bar">
    <input type="date" name="data_de" value="<?= htmlspecialchars((string)($filters['data_de'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" title="Data início">
    <input type="date" name="data_ate" value="<?= htmlspecialchars((string)($filters['data_ate'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" title="Data fim">
    <input type="text" name="fornecedor" value="<?= htmlspecialchars((string)($filters['fornecedor'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por nome ou CNPJ">
    <select name="per_page" aria-label="Registros por página">
      <?for ($i = 10; $i <= 100; $i += 10): ?>
        <option value="<?= $i ?>" <?= ((int)($per_page ?? 50) === $i) ? 'selected' : '' ?>><?= $i ?></option>
      <? endfor; ?>
    </select>
    <button class="btn" type="submit">Filtrar</button>
    <?php if (!empty($filters['data_de']) || !empty($filters['data_ate']) || !empty($filters['fornecedor'])): ?>
      <a class="btn btn-secondary" href="/enviados?<?= htmlspecialchars(http_build_query(['per_page' => (int)($per_page ?? 50)]), ENT_QUOTES, 'UTF-8') ?>">Limpar filtros</a>
    <?php endif; ?>
  </form>

  <?php if (!empty($total_pages) && $total_pages > 1): ?>
    <p class="muted">
      <?= (int)($total ?? 0) ?> resultado(s)
      · página <?= (int)($page ?? 1) ?> de <?= (int)$total_pages ?>
    </p>
  <?php endif; ?>

  <div class="table-wrap">
    <table class="list-table">
      <colgroup>
        <col style="width: 150px;">
        <col style="width: 280px;">
        <col style="width: 120px;">
        <col style="width: 200px;">
        <col style="width: 80px;">
        <col>
      </colgroup>
      <thead>
        <tr>
          <th>Data/Hora</th>
          <th>Fornecedor</th>
          <th>CNPJ</th>
          <th>Destinatários</th>
          <th>Status</th>
          <th>Conteúdo</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (($items ?? []) as $item): ?>
          <tr>
            <td><?= htmlspecialchars((string)($item['data_envio'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($item['fornecedor_nome'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td class="cnpj-cell"><?= htmlspecialchars((string)($item['fornecedor_cnpj'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td class="contacts">
              <?= htmlspecialchars((string)($item['email_destinatarios'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </td>
            <td>
              <?php if (!empty($item['email_enviado'])): ?>
                <span>✓ Enviado</span>
              <?php else: ?>
                <span class="muted">—</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if (!empty($item['email_enviado']) && !empty($item['email_conteudo'])): ?>
                <details>
                  <summary class="btn btn-sm btn-secondary" style="padding: 2px 8px; font-size: 11px;">Ver e-mail</summary>
                  <pre style="white-space: pre-wrap; font-size: 12px; margin-top: 6px; max-width: 420px; overflow-x: auto;"><?= htmlspecialchars((string)$item['email_conteudo'], ENT_QUOTES, 'UTF-8') ?></pre>
                </details>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (empty($items)): ?>
          <tr>
            <td colspan="6" class="muted" style="text-align: center; padding: 24px;">Nenhum envio registrado encontrado.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if (!empty($total_pages) && $total_pages > 1): ?>
    <?php
      $baseParams = [
        'data_de'    => (string)($filters['data_de'] ?? ''),
        'data_ate'   => (string)($filters['data_ate'] ?? ''),
        'fornecedor' => (string)($filters['fornecedor'] ?? ''),
        'per_page'   => (int)($per_page ?? 50),
      ];
      $currentPage = (int)($page ?? 1);
    ?>
    <div class="pagination">
      <?php if ($currentPage > 1): ?>
        <a class="btn btn-secondary" href="/enviados?<?= htmlspecialchars(http_build_query($baseParams + ['page' => $currentPage - 1]), ENT_QUOTES, 'UTF-8') ?>">Anterior</a>
      <?php endif; ?>
      <span class="muted">Página <?= $currentPage ?> de <?= (int)$total_pages ?></span>
      <?php if ($currentPage < $total_pages): ?>
        <a class="btn btn-secondary" href="/enviados?<?= htmlspecialchars(http_build_query($baseParams + ['page' => $currentPage + 1]), ENT_QUOTES, 'UTF-8') ?>">Próxima</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</section>
<?php
$content = ob_get_clean();
$title = 'Enviados';
require __DIR__ . '/layout.php';
