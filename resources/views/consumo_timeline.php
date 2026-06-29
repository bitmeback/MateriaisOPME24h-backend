<?php
declare(strict_types=1);

use MateriaisOpme\App\Support\Cnpj;

/** @var array $snapshots */
/** @var array $stats_por_dia */
/** @var array $dias_disponiveis */
/** @var string $data_inicio */
/** @var string $data_fim */
/** @var string $status_filtro */
/** @var string $busca */
/** @var string $filtro_vinculo */
/** @var array $fornecedores */
/** @var string $csrf_token */
/** @var string $role */

// Defaults seguros para evitar TypeError com null em urlencode/htmlspecialchars
$data_inicio = $data_inicio ?? '';
$data_fim = $data_fim ?? '';
$status_filtro = $status_filtro ?? '';
$busca = $busca ?? '';
$filtro_vinculo = $filtro_vinculo ?? 'ativos';
$snapshots = $snapshots ?? [];
$stats_por_dia = $stats_por_dia ?? [];
$dias_disponiveis = $dias_disponiveis ?? [];
$fornecedores = $fornecedores ?? [];
$csrf_token = $csrf_token ?? '';
$role = $role ?? 'guest';

ob_start();
?>

<h1>Timeline de Consumo OPME</h1>
<p class="muted">Evolução diária do estado de todos os materiais. Cada dia registra um snapshot com o status calculado naquele momento.</p>

<div class="nav nav-top">
  <a class="btn" href="/especialidades">Especialidades</a>
  <a class="btn" href="/consumo">Consumo</a>
  <a class="btn" href="/consumo/relatorios">Relatórios</a>
  <a class="btn btn-secondary" href="/dashboard">Voltar ao painel</a>
</div>

<?php if (!empty($stats_por_dia)): ?>
<!-- Cards de resumo por dia -->
<div class="grid" style="margin-bottom:24px;">
  <?php foreach ($stats_por_dia as $dia => $stats): ?>
  <div class="dashboard-card" style="flex:1; min-width:200px; border-left:4px solid #3b82f6; padding:16px; background:#fff; box-shadow:0 1px 3px rgba(0,0,0,0.1); border-radius:6px;">
    <span style="font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase;"><?= date('d/m/Y', strtotime($dia)) ?></span>
    <div style="display:flex; gap:16px; margin-top:8px;">
      <div>
        <span style="font-size:11px; color:#ef4444; font-weight:700;">🔴 <?= $stats['critico'] ?></span>
      </div>
      <div>
        <span style="font-size:11px; color:#f59e0b; font-weight:700;">🟠 <?= $stats['alerta'] ?></span>
      </div>
      <div>
        <span style="font-size:11px; color:#10b981; font-weight:700;">🟢 <?= $stats['normal'] ?></span>
      </div>
      <div>
        <span style="font-size:11px; color:#6b7280; font-weight:600;">Total: <?= $stats['total'] ?></span>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Timeline visual por dia -->
<?php if (!empty($stats_por_dia)): ?>
<section class="panel" style="margin-bottom:20px;">
  <div class="panel-head">
    <div>
      <h2>Evolução ao Longo do Tempo</h2>
      <p class="muted">Barras proporcionais de status por dia.</p>
    </div>
  </div>
  <div style="padding:16px;">
    <?php foreach ($stats_por_dia as $dia => $stats): ?>
      <?php
        $total = max($stats['total'], 1);
        $pct_critico = round(($stats['critico'] / $total) * 100, 1);
        $pct_alerta = round(($stats['alerta'] / $total) * 100, 1);
        $pct_normal = round(($stats['normal'] / $total) * 100, 1);
      ?>
      <div style="display:flex; align-items:center; gap:12px; margin-bottom:8px;">
        <span style="font-size:12px; font-weight:600; color:#374151; min-width:80px;"><?= date('d/m/Y', strtotime($dia)) ?></span>
        <div style="flex:1; display:flex; height:24px; border-radius:4px; overflow:hidden; background:#f3f4f6;">
          <?php if ($stats['critico'] > 0): ?>
          <div style="width:<?= $pct_critico ?>%; background:#ef4444; min-width:2px;" title="<?= $stats['critico'] ?> críticos"></div>
          <?php endif; ?>
          <?php if ($stats['alerta'] > 0): ?>
          <div style="width:<?= $pct_alerta ?>%; background:#f59e0b; min-width:2px;" title="<?= $stats['alerta'] ?> alertas"></div>
          <?php endif; ?>
          <?php if ($stats['normal'] > 0): ?>
          <div style="width:<?= $pct_normal ?>%; background:#10b981; min-width:2px;" title="<?= $stats['normal'] ?> normais"></div>
          <?php endif; ?>
        </div>
        <span style="font-size:11px; color:#6b7280; min-width:40px; text-align:right;"><?= $stats['total'] ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- Filtros -->
<section class="panel list-panel">
  <div class="panel-head">
    <div>
      <h2>Snapshot Diário por Material</h2>
      <p class="muted">Estado de cada material registrado nos dias monitorados.</p>
    </div>
    <div>
      <a class="btn btn-pending" href="/api/consumo/timeline-csv?data_inicio=<?= urlencode($data_inicio) ?>&data_fim=<?= urlencode($data_fim) ?>&status=<?= urlencode($status_filtro) ?>&q=<?= urlencode($busca) ?>&vinculo=<?= urlencode($filtro_vinculo) ?>">
        📥 Exportar CSV (Excel BR)
      </a>
    </div>
  </div>

  <form method="get" action="/consumo/timeline" class="search-bar">
    <input type="date" name="data_inicio" value="<?= htmlspecialchars($data_inicio, ENT_QUOTES, 'UTF-8') ?>" title="Data Início">
    <input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim, ENT_QUOTES, 'UTF-8') ?>" title="Data Fim">
    <input type="text" name="q" value="<?= htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por material, código ou fornecedor" style="flex:1;">

    <select name="vinculo">
      <option value="ativos" <?= $filtro_vinculo === 'ativos' ? 'selected' : '' ?>>🔗 Vínculos Ativos</option>
      <option value="inativos" <?= $filtro_vinculo === 'inativos' ? 'selected' : '' ?>>🚫 Vínculos Inativos</option>
      <option value="todos" <?= $filtro_vinculo === 'todos' ? 'selected' : '' ?>>📁 Todos os Vínculos</option>
    </select>

    <select name="status">
      <option value="">Todos os status</option>
      <option value="critico" <?= $status_filtro === 'critico' ? 'selected' : '' ?>>🔴 Crítico</option>
      <option value="alerta" <?= $status_filtro === 'alerta' ? 'selected' : '' ?>>🟠 Alerta</option>
      <option value="normal" <?= $status_filtro === 'normal' ? 'selected' : '' ?>>🟢 Normal</option>
    </select>

    <button type="submit" class="btn">Filtrar</button>

    <?php if ($data_inicio !== '' || $data_fim !== '' || $status_filtro !== '' || $busca !== '' || $filtro_vinculo !== 'ativos'): ?>
      <a class="btn btn-secondary" href="/consumo/timeline">Limpar</a>
    <?php endif; ?>
  </form>

  <?php if (empty($snapshots)): ?>
    <p class="muted" style="padding:24px 0; text-align:center;">Nenhum snapshot disponível para os filtros aplicados. O sistema acumula dados a cada execução do job de ingestão.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table class="list-table">
      <colgroup>
        <col style="width:10%;">
        <col style="width:5%;">
        <col style="width:30%;">
        <col style="width:18%;">
        <col style="width:10%;">
        <col style="width:9%;">
        <col style="width:9%;">
      </colgroup>
      <thead>
        <tr>
          <th style="text-align:center;">Data</th>
          <th style="text-align:center;">Código</th>
          <th>Material</th>
          <th>Fornecedor</th>
          <th style="text-align:center;">Status</th>
          <th style="text-align:center;">Saldo</th>
          <th style="text-align:center;">Média</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($snapshots as $row): ?>
          <tr class="row-hover">
            <td style="text-align:center; white-space:nowrap; font-size:12px;"><?= date('d/m/Y', strtotime($row['data_snapshot'])) ?></td>
            <td style="text-align:center;"><strong><?= htmlspecialchars((string)$row['cd_material']) ?></strong></td>
            <td><?= htmlspecialchars($row['descricao']) ?></td>
            <td>
              <div style="font-weight:500; font-size:13px; color:#374151;"><?= htmlspecialchars($row['fornecedor']) ?></div>
              <div class="muted" style="font-size:11px;">CNPJ: <?= Cnpj::format($row['cnpj_fornecedor']) ?></div>
            </td>
            <td style="text-align:center;">
              <?php if ($row['status'] === 'critico'): ?>
                <span class="status-tag" style="background:#fee2e2;color:#ef4444;border:1px solid #fca5a5;padding:2px 6px;font-size:11px;font-weight:700;border-radius:4px;">🔴 CRÍTICO</span>
              <?php elseif ($row['status'] === 'alerta'): ?>
                <span class="status-tag" style="background:#fef3c7;color:#f59e0b;border:1px solid #fcd34d;padding:2px 6px;font-size:11px;font-weight:700;border-radius:4px;">🟠 ALERTA</span>
              <?php else: ?>
                <span class="status-tag" style="background:#ecfdf5;color:#10b981;border:1px solid #a7f3d0;padding:2px 6px;font-size:11px;font-weight:700;border-radius:4px;">🟢 NORMAL</span>
              <?php endif; ?>
            </td>
            <td style="text-align:center;font-weight:700;"><?= number_format((float)$row['saldo'], 1, ',', '.') ?></td>
            <td style="text-align:center;"><?= number_format((float)$row['media_trimestre'], 1, ',', '.') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>

<?php
$content = ob_get_clean();
$title = 'Timeline de Consumo';
require __DIR__ . '/layout.php';
