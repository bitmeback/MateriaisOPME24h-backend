<?php
declare(strict_types=1);
ob_start();
/** @var array $items */
/** @var array $aggregates */
/** @var array $filtros */
/** @var array $listas */
/** @var int $total */
/** @var int $page */
/** @var int $per_page */
/** @var int $total_pages */
/** @var string $csrf_token */
/** @var string $role */
?>
<h1>Resultado Analítico OPME</h1>
<p class="muted">Visão consolidada do Analítico do ReportLoad: quantidade, valor em conta e lucro líquido por cirurgia/material.</p>

<div class="nav nav-top">
  <a class="btn" href="/especialidades">Especialidades</a>
  <a class="btn" href="/consumo">Consumo</a>
  <a class="btn" href="/consumo/relatorios">Relatórios</a>
  <a class="btn" href="/consumo/timeline">Timeline</a>
  <a class="btn btn-secondary" href="/dashboard">Voltar</a>
</div>

<!-- Cards de resumo -->
<div class="grid" style="margin-bottom:24px;">
  <div class="dashboard-card" style="flex:1; min-width:200px; border-left: 4px solid #10b981; padding: 16px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 6px;">
    <span style="font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase;">Cirurgias</span>
    <h2 style="margin:8px 0 0; font-size:28px; color:#10b981;"><?= number_format((int)($aggregates['cirurgias'] ?? 0), 0, ',', '.') ?></h2>
    <p class="muted" style="margin:4px 0 0; font-size:12px;">Cirurgias únicas no período</p>
  </div>
  <div class="dashboard-card" style="flex:1; min-width:200px; border-left: 4px solid #3b82f6; padding: 16px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 6px;">
    <span style="font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase;">Itens</span>
    <h2 style="margin:8px 0 0; font-size:28px; color:#3b82f6;"><?= number_format((int)($aggregates['itens'] ?? 0), 0, ',', '.') ?></h2>
    <p class="muted" style="margin:4px 0 0; font-size:12px;">Registros de material</p>
  </div>
  <div class="dashboard-card" style="flex:1; min-width:200px; border-left: 4px solid #f59e0b; padding: 16px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 6px;">
    <span style="font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase;">Valor em Conta</span>
    <h2 style="margin:8px 0 0; font-size:28px; color:#f59e0b;">R$ <?= number_format((float)($aggregates['vl_conta'] ?? 0), 2, ',', '.') ?></h2>
    <p class="muted" style="margin:4px 0 0; font-size:12px;">Soma do período filtrado</p>
  </div>
  <div class="dashboard-card" style="flex:1; min-width:200px; border-left: 4px solid #10b981; padding: 16px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 6px;">
    <span style="font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase;">Lucro Líquido</span>
    <h2 style="margin:8px 0 0; font-size:28px; color:#10b981;">R$ <?= number_format((float)($aggregates['lucro'] ?? 0), 2, ',', '.') ?></h2>
    <p class="muted" style="margin:4px 0 0; font-size:12px;">Soma do período filtrado</p>
  </div>
</div>

<section class="panel list-panel">
  <div class="panel-head">
    <h2>Itens Analíticos</h2>
    <p class="muted">Dados carregados do cache local atualizado diariamente às 05:00.</p>
  </div>

  <form method="get" action="/resultado" class="filter-bar">
    <div class="filter-row">
      <div class="filter-field">
        <label class="filter-label">Data início</label>
        <input type="date" name="data_inicio" value="<?= htmlspecialchars($filtros['data_inicio'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="filter-field">
        <label class="filter-label">Data fim</label>
        <input type="date" name="data_fim" value="<?= htmlspecialchars($filtros['data_fim'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="filter-field">
        <label class="filter-label">Convênio</label>
        <input type="text" name="convenio" value="<?= htmlspecialchars($filtros['convenio'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Convênio" list="list-convenios">
        <datalist id="list-convenios">
          <?php foreach (($listas['convenios'] ?? []) as $c): ?>
            <option value="<?= htmlspecialchars($c, ENT_QUOTES, 'UTF-8') ?>">
          <?php endforeach; ?>
        </datalist>
      </div>
      <div class="filter-field">
        <label class="filter-label">Setor</label>
        <input type="text" name="setor" value="<?= htmlspecialchars($filtros['setor'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Setor" list="list-setores">
        <datalist id="list-setores">
          <?php foreach (($listas['setores'] ?? []) as $s): ?>
            <option value="<?= htmlspecialchars($s, ENT_QUOTES, 'UTF-8') ?>">
          <?php endforeach; ?>
        </datalist>
      </div>
    </div>
    <div class="filter-row">
      <div class="filter-field">
        <label class="filter-label">Fornecedor</label>
        <input type="text" name="fornecedor" value="<?= htmlspecialchars($filtros['fornecedor'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Fornecedor">
      </div>
      <div class="filter-field">
        <label class="filter-label">Material</label>
        <input type="text" name="material" value="<?= htmlspecialchars($filtros['material'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Código ou descrição">
      </div>
      <div class="filter-field">
        <label class="filter-label">Página</label>
        <select name="per_page">
          <?php foreach ([20, 50, 100, 200] as $n): ?>
            <option value="<?= $n ?>" <?= (int)$per_page === $n ? 'selected' : '' ?>><?= $n ?>/pág</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-field">
        <label class="filter-label" style="visibility:hidden;">Ações</label>
        <div style="display:flex; gap:6px;">
          <button type="submit" class="btn" style="margin:0;">Filtrar</button>
          <?php if ($filtros['data_inicio'] !== '' || $filtros['data_fim'] !== '' || $filtros['convenio'] !== '' || $filtros['setor'] !== '' || $filtros['fornecedor'] !== '' || $filtros['material'] !== ''): ?>
            <a class="btn btn-secondary" href="/resultado">Limpar</a>
          <?php endif; ?>
          <a class="btn" href="/api/resultado/export-csv?<?= htmlspecialchars(http_build_query($filtros), ENT_QUOTES, 'UTF-8') ?>" style="margin:0;">Exportar CSV</a>
        </div>
      </div>
    </div>
  </form>

  <?php if ($total > 0): ?>
    <p class="muted" style="margin-bottom: 12px;">
      Exibindo <?= count($items) ?> de <?= (int)$total ?> registros.
    </p>
  <?php endif; ?>

  <?php if (empty($items)): ?>
    <p class="muted" style="padding: 24px 0; text-align: center;">Nenhum registro encontrado para estes filtros.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table class="list-table">
      <colgroup>
        <col style="width: 6%;">
        <col style="width: 6%;">
        <col style="width: 14%;">
        <col style="width: 28%;">
        <col style="width: 5%;">
        <col style="width: 8%;">
        <col style="width: 8%;">
        <col style="width: 8%;">
        <col style="width: 10%;">
        <col style="width: 10%;">
      </colgroup>
      <thead>
        <tr>
          <th>Atendimento</th>
          <th>Cirurgia</th>
          <th>Material</th>
          <th>Descrição</th>
          <th style="text-align: center;">Qtde</th>
          <th style="text-align: right;">Vl Conta</th>
          <th style="text-align: right;">Lucro Líq.</th>
          <th>Fornecedor</th>
          <th>Convenio</th>
          <th>Setor</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
          <tr class="row-hover">
            <td style="text-align: center;"><?= htmlspecialchars((string)($item['nr_atendimento'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td style="text-align: center;"><?= htmlspecialchars((string)($item['nr_cirurgia'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td style="text-align: center;"><?= htmlspecialchars((string)($item['cd_material'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($item['ds_material'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td style="text-align: center;"><?= $item['qtde'] !== null ? number_format((float)$item['qtde'], 2, ',', '.') : '-' ?></td>
            <td style="text-align: right;"><?= $item['vl_conta'] !== null ? 'R$ ' . number_format((float)$item['vl_conta'], 2, ',', '.') : '-' ?></td>
            <td style="text-align: right; color: <?= ($item['vl_lucro_liq'] ?? 0) >= 0 ? '#10b981' : '#ef4444' ?>; font-weight: 600;">
              <?= $item['vl_lucro_liq'] !== null ? 'R$ ' . number_format((float)$item['vl_lucro_liq'], 2, ',', '.') : '-' ?>
            </td>
            <td><?= htmlspecialchars((string)($item['ds_fornecedor'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($item['ds_convenio'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= htmlspecialchars((string)($item['ds_setor'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($total_pages > 1): ?>
    <?php
      $baseParams = [
        'data_inicio' => (string)($filtros['data_inicio'] ?? ''),
        'data_fim' => (string)($filtros['data_fim'] ?? ''),
        'convenio' => (string)($filtros['convenio'] ?? ''),
        'setor' => (string)($filtros['setor'] ?? ''),
        'fornecedor' => (string)($filtros['fornecedor'] ?? ''),
        'material' => (string)($filtros['material'] ?? ''),
        'per_page' => (int)$per_page,
      ];
    ?>
    <div class="pagination" style="margin-top:20px; display:flex; justify-content:center; align-items:center; gap:12px;">
      <?php if ($page > 1): ?>
        <a class="btn btn-secondary" href="/resultado?<?= htmlspecialchars(http_build_query($baseParams + ['page' => $page - 1]), ENT_QUOTES, 'UTF-8') ?>">Anterior</a>
      <?php endif; ?>
      <span class="muted">Página <?= $page ?> de <?= $total_pages ?></span>
      <?php if ($page < $total_pages): ?>
        <a class="btn btn-secondary" href="/resultado?<?= htmlspecialchars(http_build_query($baseParams + ['page' => $page + 1]), ENT_QUOTES, 'UTF-8') ?>">Próxima</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php endif; ?>
</section>

<style>
.dashboard-card {
  transition: transform 0.2s, box-shadow 0.2s;
}
.dashboard-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
}
.row-hover {
  transition: background-color 0.15s;
}
.row-hover:hover {
  background-color: #f9fafb;
}
</style>
<?php
$content = ob_get_clean();
$title = 'Resultado Analítico OPME';
require __DIR__ . '/layout.php';
