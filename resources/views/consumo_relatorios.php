<?php
declare(strict_types=1);
ob_start();
/** @var array $historico */
/** @var int $total_transicoes */
/** @var int $total_critico */
/** @var int $total_alerta */
/** @var int $total_normal */
/** @var int $total_sem_giro */
/** @var int $total_inativo */
/** @var string $data_inicio */
/** @var string $data_fim */
/** @var string $status_filtro */
/** @var int $id_especialidade */
/** @var int $id_fornecedor */
/** @var string $busca */
/** @var string $filtro_vinculo */
/** @var string $filtro_uso */
/** @var string $sort */
/** @var array $especialidades */
/** @var array $fornecedores */
/** @var string $csrf_token */
?>
<h1>Histórico e Relatórios de Consumo OPME</h1>
<p class="muted">Acompanhe as mudanças de status dos materiais registradas pelo robô de ingestão. Exporte para CSV compatível com Microsoft Excel.</p>

<div class="nav nav-top">
  <a class="btn" href="/especialidades">Especialidades</a>
  <a class="btn" href="/consumo">Consumo</a>
  <a class="btn" href="/consumo/timeline">Timeline</a>
  <a class="btn btn-secondary" href="/dashboard">Voltar ao painel</a>
</div>

<!-- Cards de estatísticas resumidas -->
<div class="grid" style="margin-bottom:24px;">
  <div class="dashboard-card" style="flex:1; min-width:180px; border-left: 4px solid #3b82f6; padding: 16px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 6px;">
    <span style="font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase;">Total Transições</span>
    <h2 style="margin:8px 0 0; font-size:28px; color:#3b82f6;"><?= $total_transicoes ?></h2>
    <p class="muted" style="margin:4px 0 0; font-size:12px;">Período filtrado</p>
  </div>
  <div class="dashboard-card" style="flex:1; min-width:180px; border-left: 4px solid #ef4444; padding: 16px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 6px;">
    <span style="font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase;">Crítico</span>
    <h2 style="margin:8px 0 0; font-size:28px; color:#ef4444;"><?= $total_critico ?></h2>
    <p class="muted" style="margin:4px 0 0; font-size:12px;">Mudanças para status crítico</p>
  </div>
  <div class="dashboard-card" style="flex:1; min-width:180px; border-left: 4px solid #f59e0b; padding: 16px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 6px;">
    <span style="font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase;">Alerta</span>
    <h2 style="margin:8px 0 0; font-size:28px; color:#f59e0b;"><?= $total_alerta ?></h2>
    <p class="muted" style="margin:4px 0 0; font-size:12px;">Mudanças para status alerta</p>
  </div>
  <div class="dashboard-card" style="flex:1; min-width:180px; border-left: 4px solid #10b981; padding: 16px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 6px;">
    <span style="font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase;">Normal</span>
    <h2 style="margin:8px 0 0; font-size:28px; color:#10b981;"><?= $total_normal ?></h2>
    <p class="muted" style="margin:4px 0 0; font-size:12px;">Mudan&#231;as para status normal</p>
  </div>
  <?php if ($total_sem_giro > 0): ?>
  <div class="dashboard-card" style="flex:1; min-width:180px; border-left: 4px solid #8b5cf6; padding: 16px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 6px;">
    <span style="font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase;">Sem Giro</span>
    <h2 style="margin:8px 0 0; font-size:28px; color:#8b5cf6;"><?= $total_sem_giro ?></h2>
    <p class="muted" style="margin:4px 0 0; font-size:12px;">Estoque parado sem consumo</p>
  </div>
  <?php endif; ?>
  <?php if ($total_inativo > 0): ?>
  <div class="dashboard-card" style="flex:1; min-width:180px; border-left: 4px solid #9ca3af; padding: 16px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 6px;">
    <span style="font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase;">Inativo</span>
    <h2 style="margin:8px 0 0; font-size:28px; color:#9ca3af;"><?= $total_inativo ?></h2>
    <p class="muted" style="margin:4px 0 0; font-size:12px;">Sem estoque e sem consumo</p>
  </div>
  <?php endif; ?>
</div>

<!-- Filtros -->
<section class="panel list-panel">
  <div class="panel-head">
    <div>
      <h2>Histórico de Transições em Massa</h2>
      <p class="muted">Listagem das últimas mudanças de status detectadas (Ruptura, Prevenção e Estabilidade).</p>
    </div>
    <div>
      <a class="btn btn-pending" href="/api/consumo/export-csv?data_inicio=<?= urlencode($data_inicio) ?>&data_fim=<?= urlencode($data_fim) ?>&status=<?= urlencode($status_filtro) ?>&id_especialidade=<?= $id_especialidade ?>&id_fornecedor=<?= $id_fornecedor ?>&q=<?= urlencode($busca) ?>&vinculo=<?= urlencode($filtro_vinculo) ?>&uso=<?= urlencode($filtro_uso) ?>&sort=<?= urlencode($sort) ?>">
        📥 Exportar CSV (Excel BR)
      </a>
    </div>
  </div>

  <form method="get" action="/consumo/relatorios" class="filter-bar">
    <div class="filter-row">
      <div class="filter-field">
        <label class="filter-label">Data Início</label>
        <input type="date" name="data_inicio" value="<?= htmlspecialchars($data_inicio, ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="filter-field">
        <label class="filter-label">Data Fim</label>
        <input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim, ENT_QUOTES, 'UTF-8') ?>">
      </div>
      <div class="filter-field">
        <label class="filter-label">Buscar</label>
        <input type="text" name="q" value="<?= htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') ?>" placeholder="Material, código ou fornecedor" style="min-width:310px;">
      </div>
      <div class="filter-field">
        <label class="filter-label">Uso</label>
        <select name="uso">
          <option value="utilizados" <?= $filtro_uso === 'utilizados' ? 'selected' : '' ?>>⚡ Utilizados</option>
          <option value="nao_utilizados" <?= $filtro_uso === 'nao_utilizados' ? 'selected' : '' ?>>📄 Não Utilizados</option>
          <option value="todos" <?= $filtro_uso === 'todos' ? 'selected' : '' ?>>📁 Todos</option>
        </select>
      </div>
      <div class="filter-field">
        <label class="filter-label">Vínculo</label>
        <select name="vinculo">
          <option value="ativos" <?= $filtro_vinculo === 'ativos' ? 'selected' : '' ?>>🔗 Ativos</option>
          <option value="inativos" <?= $filtro_vinculo === 'inativos' ? 'selected' : '' ?>>🚫 Inativos</option>
          <option value="todos" <?= $filtro_vinculo === 'todos' ? 'selected' : '' ?>>📁 Todos</option>
        </select>
      </div>
      <div class="filter-field">
        <label class="filter-label">Status</label>
        <select name="status">
          <option value="">Todos</option>
          <option value="critico" <?= $status_filtro === 'critico' ? 'selected' : '' ?>>🔴 Crítico</option>
          <option value="alerta" <?= $status_filtro === 'alerta' ? 'selected' : '' ?>>🟠 Alerta</option>
          <option value="normal" <?= $status_filtro === 'normal' ? 'selected' : '' ?>>🟢 Saudável</option>
          <option value="sem_giro" <?= $status_filtro === 'sem_giro' ? 'selected' : '' ?>>🟣 Sem Giro</option>
          <option value="inativo" <?= $status_filtro === 'inativo' ? 'selected' : '' ?>>⚪ Inativo</option>
        </select>
      </div>
      <div class="filter-field">
        <label class="filter-label">Ordenação</label>
        <select name="sort">
          <option value="data_desc" <?= $sort === 'data_desc' ? 'selected' : '' ?>>Data (Recente)</option>
          <option value="data_asc" <?= $sort === 'data_asc' ? 'selected' : '' ?>>Data (Antiga)</option>
          <option value="codigo_asc" <?= $sort === 'codigo_asc' ? 'selected' : '' ?>>Código ↑</option>
          <option value="codigo_desc" <?= $sort === 'codigo_desc' ? 'selected' : '' ?>>Código ↓</option>
          <option value="material_asc" <?= $sort === 'material_asc' ? 'selected' : '' ?>>Material (A→Z)</option>
          <option value="material_desc" <?= $sort === 'material_desc' ? 'selected' : '' ?>>Material (Z→A)</option>
          <option value="fornecedor_asc" <?= $sort === 'fornecedor_asc' ? 'selected' : '' ?>>Fornecedor (A→Z)</option>
          <option value="fornecedor_desc" <?= $sort === 'fornecedor_desc' ? 'selected' : '' ?>>Fornecedor (Z→A)</option>
          <option value="saldo_asc" <?= $sort === 'saldo_asc' ? 'selected' : '' ?>>Saldo ↑</option>
          <option value="saldo_desc" <?= $sort === 'saldo_desc' ? 'selected' : '' ?>>Saldo ↓</option>
        </select>
      </div>
      <div class="filter-field">
        <label class="filter-label" style="visibility:hidden;">Ações</label>
        <div style="display:flex; gap:6px;">
          <button type="submit" class="btn" style="margin:0;">Filtrar</button>
          <?php if ($data_inicio !== '' || $data_fim !== '' || $status_filtro !== '' || $id_especialidade !== 0 || $id_fornecedor !== 0 || $busca !== '' || $filtro_vinculo !== 'ativos' || $filtro_uso !== 'utilizados' || $sort !== 'data_desc'): ?>
            <a class="btn btn-secondary" href="/consumo/relatorios">Limpar</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <div class="filter-row" style="display:none;">
      <div class="filter-field">
        <label class="filter-label">Especialidade</label>
        <select name="id_especialidade">
          <option value="0">Todas</option>
          <?php foreach ($especialidades as $esp): ?>
            <option value="<?= $esp['id'] ?>" <?= $id_especialidade === (int)$esp['id'] ? 'selected' : '' ?>><?= htmlspecialchars($esp['nome'], ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-field">
        <label class="filter-label">Fornecedor</label>
        <select name="id_fornecedor">
          <option value="0">Todos</option>
          <?php foreach ($fornecedores as $forn): ?>
            <option value="<?= $forn['id'] ?>" <?= $id_fornecedor === (int)$forn['id'] ? 'selected' : '' ?>><?= htmlspecialchars($forn['name'], ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </form>

  <?php if (empty($historico)): ?>
    <p class="muted" style="padding: 24px 0; text-align: center;">Nenhuma transição de status registrada para os filtros aplicados.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table class="list-table">
      <colgroup>
        <col style="width: 5%;">
        <col style="width: 35%;">
        <col style="width: 15%;">
        <col style="width: 10%;">
        <col style="width: 10%;">
        <col style="width: 8%;">
        <col style="width: 8%;">
        <col style="width: 9%;">
      </colgroup>
      <thead>
        <tr>
          <th style="text-align:center;">Código</th>
          <th>Material</th>
          <th>Fornecedor</th>
          <th style="text-align:center;">Status Anterior</th>
          <th style="text-align:center;">Status Novo</th>
          <th style="text-align:center;">Saldo</th>
          <th style="text-align:center;">Méd. Transição</th>
          <th style="text-align:center;">Data</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($historico as $row): ?>
          <tr class="row-hover">
            <td style="text-align:center;"><strong><?= htmlspecialchars((string)$row['cd_material']) ?></strong></td>
            <td><?= htmlspecialchars($row['descricao']) ?></td>
            <td>
              <div style="font-weight:500; font-size:13px; color:#374151;"><?= htmlspecialchars($row['fornecedor']) ?></div>
              <div class="muted" style="font-size:11px;">CNPJ: <?= \MateriaisOpme\App\Support\Cnpj::format($row['cnpj_fornecedor']) ?></div>
            </td>
            <td style="text-align:center;">
              <?php if ($row['status_anterior'] === null || $row['status_anterior'] === ''): ?>
                <span style="color:#9ca3af;font-size:12px;">—</span>
              <?php elseif ($row['status_anterior'] === 'critico'): ?>
                <span class="status-tag" style="background:#fee2e2;color:#ef4444;border:1px solid #fca5a5;padding:2px 6px;font-size:11px;font-weight:700;border-radius:4px;">🔴 CRÍTICO</span>
              <?php elseif ($row['status_anterior'] === 'alerta'): ?>
                <span class="status-tag" style="background:#fef3c7;color:#f59e0b;border:1px solid #fcd34d;padding:2px 6px;font-size:11px;font-weight:700;border-radius:4px;">🟠 ALERTA</span>
              <?php elseif ($row['status_anterior'] === 'sem_giro'): ?>
                <span class="status-tag" style="background:#f3e8ff;color:#8b5cf6;border:1px solid #c4b5fd;padding:2px 6px;font-size:11px;font-weight:700;border-radius:4px;">🟣 SEM GIRO</span>
              <?php elseif ($row['status_anterior'] === 'inativo'): ?>
                <span class="status-tag" style="background:#f3f4f6;color:#6b7280;border:1px solid #d1d5db;padding:2px 6px;font-size:11px;font-weight:700;border-radius:4px;">⚪ INATIVO</span>
              <?php else: ?>
                <span class="status-tag" style="background:#ecfdf5;color:#10b981;border:1px solid #a7f3d0;padding:2px 6px;font-size:11px;font-weight:700;border-radius:4px;">🟢 NORMAL</span>
              <?php endif; ?>
            </td>
            <td style="text-align:center;">
              <?php if ($row['status_novo'] === 'critico'): ?>
                <span class="status-tag" style="background:#fee2e2;color:#ef4444;border:1px solid #fca5a5;padding:2px 6px;font-size:11px;font-weight:700;border-radius:4px;">🔴 CRÍTICO</span>
              <?php elseif ($row['status_novo'] === 'alerta'): ?>
                <span class="status-tag" style="background:#fef3c7;color:#f59e0b;border:1px solid #fcd34d;padding:2px 6px;font-size:11px;font-weight:700;border-radius:4px;">🟠 ALERTA</span>
              <?php elseif ($row['status_novo'] === 'sem_giro'): ?>
                <span class="status-tag" style="background:#f3e8ff;color:#8b5cf6;border:1px solid #c4b5fd;padding:2px 6px;font-size:11px;font-weight:700;border-radius:4px;">🟣 SEM GIRO</span>
              <?php elseif ($row['status_novo'] === 'inativo'): ?>
                <span class="status-tag" style="background:#f3f4f6;color:#6b7280;border:1px solid #d1d5db;padding:2px 6px;font-size:11px;font-weight:700;border-radius:4px;">⚪ INATIVO</span>
              <?php else: ?>
                <span class="status-tag" style="background:#ecfdf5;color:#10b981;border:1px solid #a7f3d0;padding:2px 6px;font-size:11px;font-weight:700;border-radius:4px;">🟢 NORMAL</span>
              <?php endif; ?>
            </td>
            <td style="text-align:center;font-weight:700;"><?= number_format((float)$row['saldo_momento'], 0, ',', '.') ?></td>
            <td style="text-align:center;"><?= number_format((float)$row['media_momento'], 0, ',', '.') ?></td>
            <td style="text-align:center;white-space:nowrap;"><?= htmlspecialchars($row['data_formatada']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>

<?php
$content = ob_get_clean();
$title = 'Relatórios de Consumo';
require __DIR__ . '/layout.php';
