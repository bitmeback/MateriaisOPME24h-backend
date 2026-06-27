<?php
declare(strict_types=1);
ob_start();
/** @var array $historico */
/** @var int $total_transicoes */
/** @var int $total_critico */
/** @var int $total_alerta */
/** @var int $total_normal */
/** @var string $data_inicio */
/** @var string $data_fim */
/** @var string $status_filtro */
/** @var int $id_especialidade */
/** @var int $id_fornecedor */
/** @var array $especialidades */
/** @var array $fornecedores */
/** @var string $csrf_token */
?>
<h1>Histórico e Relatórios de Consumo OPME</h1>
<p class="muted">Acompanhe as mudanças de status dos materiais registradas pelo robô de ingestão. Exporte para CSV compatível com Microsoft Excel.</p>

<div class="nav nav-top">
  <a class="btn" href="/especialidades">Especialidades</a>
  <a class="btn" href="/consumo" style="background:#3b82f6; color:#fff;">Monitoramento</a>
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
    <p class="muted" style="margin:4px 0 0; font-size:12px;">Mudanças para status normal</p>
  </div>
</div>

<!-- Filtros -->
<section class="panel list-panel">
  <div class="panel-head">
    <div>
      <h2>Histórico de Transições em Massa</h2>
      <p class="muted">Listagem das últimas mudanças de status detectadas (Ruptura, Prevenção e Estabilidade).</p>
    </div>
    <div>
      <a class="btn btn-pending" href="/api/consumo/export-csv?data_inicio=<?= urlencode($data_inicio) ?>&data_fim=<?= urlencode($data_fim) ?>&status=<?= urlencode($status_filtro) ?>&id_especialidade=<?= $id_especialidade ?>&id_fornecedor=<?= $id_fornecedor ?>">
        📥 Exportar CSV (Excel BR)
      </a>
    </div>
  </div>

  <form method="get" action="/consumo/relatorios" class="search-bar">
    <input type="date" name="data_inicio" value="<?= htmlspecialchars($data_inicio, ENT_QUOTES, 'UTF-8') ?>" title="Data Início">
    <input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim, ENT_QUOTES, 'UTF-8') ?>" title="Data Fim">
    
    <select name="status">
      <option value="">Todos os status</option>
      <option value="critico" <?= $status_filtro === 'critico' ? 'selected' : '' ?>>🔴 Crítico</option>
      <option value="alerta" <?= $status_filtro === 'alerta' ? 'selected' : '' ?>>🟠 Alerta</option>
      <option value="normal" <?= $status_filtro === 'normal' ? 'selected' : '' ?>>🟢 Saudável</option>
    </select>

    <select name="id_especialidade">
      <option value="0">Todas Especialidades</option>
      <?php foreach ($especialidades as $esp): ?>
        <option value="<?= $esp['id'] ?>" <?= $id_especialidade === (int)$esp['id'] ? 'selected' : '' ?>><?= htmlspecialchars($esp['nome'], ENT_QUOTES, 'UTF-8') ?></option>
      <?php endforeach; ?>
    </select>

    <select name="id_fornecedor">
      <option value="0">Todos Fornecedores</option>
      <?php foreach ($fornecedores as $forn): ?>
        <option value="<?= $forn['id'] ?>" <?= $id_fornecedor === (int)$forn['id'] ? 'selected' : '' ?>><?= htmlspecialchars($forn['name'], ENT_QUOTES, 'UTF-8') ?></option>
      <?php endforeach; ?>
    </select>

    <button type="submit" class="btn">Filtrar</button>
    
    <?php if ($data_inicio !== '' || $data_fim !== '' || $status_filtro !== '' || $id_especialidade !== 0 || $id_fornecedor !== 0): ?>
      <a class="btn btn-secondary" href="/consumo/relatorios">Limpar</a>
    <?php endif; ?>
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
          <th style="text-align:center;">Média</th>
          <th style="text-align:center;">Data</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($historico as $row): ?>
          <tr class="row-hover">
            <td style="text-align:center;"><strong><?= htmlspecialchars($row['cd_material']) ?></strong></td>
            <td><?= htmlspecialchars($row['descricao']) ?></td>
            <td>
              <div style="font-weight:500; font-size:13px; color:#374151;"><?= htmlspecialchars($row['fornecedor']) ?></div>
              <div class="muted" style="font-size:11px;">CNPJ: <?= \MateriaisOpme\App\Support\Cnpj::format($row['cnpj_fornecedor']) ?></div>
            </td>
            <td style="text-align:center;">
              <?php if ($row['status_anterior'] === 'critico'): ?>
                <span class="status-tag" style="background:#fee2e2;color:#ef4444;border:1px solid #fca5a5;padding:2px 6px;font-size:11px;font-weight:700;border-radius:4px;">🔴 CRÍTICO</span>
              <?php elseif ($row['status_anterior'] === 'alerta'): ?>
                <span class="status-tag" style="background:#fef3c7;color:#f59e0b;border:1px solid #fcd34d;padding:2px 6px;font-size:11px;font-weight:700;border-radius:4px;">🟠 ALERTA</span>
              <?php else: ?>
                <span class="status-tag" style="background:#ecfdf5;color:#10b981;border:1px solid #a7f3d0;padding:2px 6px;font-size:11px;font-weight:700;border-radius:4px;">🟢 NORMAL</span>
              <?php endif; ?>
            </td>
            <td style="text-align:center;">
              <?php if ($row['status_novo'] === 'critico'): ?>
                <span class="status-tag" style="background:#fee2e2;color:#ef4444;border:1px solid #fca5a5;padding:2px 6px;font-size:11px;font-weight:700;border-radius:4px;">🔴 CRÍTICO</span>
              <?php elseif ($row['status_novo'] === 'alerta'): ?>
                <span class="status-tag" style="background:#fef3c7;color:#f59e0b;border:1px solid #fcd34d;padding:2px 6px;font-size:11px;font-weight:700;border-radius:4px;">🟠 ALERTA</span>
              <?php else: ?>
                <span class="status-tag" style="background:#ecfdf5;color:#10b981;border:1px solid #a7f3d0;padding:2px 6px;font-size:11px;font-weight:700;border-radius:4px;">🟢 NORMAL</span>
              <?php endif; ?>
            </td>
            <td style="text-align:center;font-weight:700;"><?= number_format((int)$row['saldo_momento'], 0, ',', '.') ?></td>
            <td style="text-align:center;"><?= number_format((int)$row['media_momento'], 0, ',', '.') ?></td>
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
