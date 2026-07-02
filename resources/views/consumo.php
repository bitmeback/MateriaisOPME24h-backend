<?php
declare(strict_types=1);
ob_start();
/** @var array $items */
/** @var string $busca */
/** @var string $filtro_status */
/** @var string $filtro_vinculo */
/** @var string $filtro_uso */
/** @var string $sort */
/** @var array $pagination */
/** @var int $total_count */
/** @var int $critico_count */
/** @var int $alerta_count */
/** @var int $saudavel_count */
/** @var int $sem_giro_count */
/** @var int $inativo_count */
/** @var string $csrf_token */
?>
<h1>Monitor de Estoque de Consumo OPME</h1>
<p class="muted">Acompanhe o saldo físico do hospital monitorado em tempo real com regras de threshold baseadas no histórico analítico (excluindo Ortopedia).</p>

<div class="nav nav-top">
  <a class="btn" href="/especialidades">Especialidades</a>
  <a class="btn" href="/consumo/relatorios">Relatórios</a>
  <a class="btn" href="/consumo/timeline">Timeline</a>
  <a class="btn btn-secondary" href="/dashboard">Voltar ao painel</a>
</div>

<!-- Cards descritivos de status -->
<div class="grid" style="margin-bottom:24px;">
  <div class="dashboard-card" style="flex:1; min-width:200px; border-left: 4px solid #10b981; padding: 16px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 6px;">
    <span style="font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase;">Saudáveis</span>
    <h2 style="margin:8px 0 0; font-size:28px; color:#10b981;"><?= $saudavel_count ?></h2>
    <p class="muted" style="margin:4px 0 0; font-size:12px;">Estoque acima da média trimestral</p>
  </div>
  <div class="dashboard-card" style="flex:1; min-width:200px; border-left: 4px solid #f59e0b; padding: 16px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 6px;">
    <span style="font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase;">Em Alerta</span>
    <h2 style="margin:8px 0 0; font-size:28px; color:#f59e0b;"><?= $alerta_count ?></h2>
    <p class="muted" style="margin:4px 0 0; font-size:12px;">Saldo inferior à média (+5%)</p>
  </div>
  <div class="dashboard-card" style="flex:1; min-width:200px; border-left: 4px solid #ef4444; padding: 16px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 6px;">
    <span style="font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase;">Críticos</span>
    <h2 style="margin:8px 0 0; font-size:28px; color:#ef4444;"><?= $critico_count ?></h2>
    <p class="muted" style="margin:4px 0 0; font-size:12px;">Saldo crítico inferior à média (-5%)</p>
  </div>
  <div class="dashboard-card" style="flex:1; min-width:200px; border-left: 4px solid #3b82f6; padding: 16px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 6px;">
    <span style="font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase;">Sob Giro Visualizado</span>
    <h2 style="margin:8px 0 0; font-size:28px; color:#3b82f6;"><?= $total_count ?></h2>
    <p class="muted" style="margin:4px 0 0; font-size:12px;">Itens ativos em circula&#231;&#227;o no giro</p>
  </div>
  <?php if ($sem_giro_count > 0): ?>
  <div class="dashboard-card" style="flex:1; min-width:200px; border-left: 4px solid #8b5cf6; padding: 16px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 6px;">
    <span style="font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase;">Sem Giro</span>
    <h2 style="margin:8px 0 0; font-size:28px; color:#8b5cf6;"><?= $sem_giro_count ?></h2>
    <p class="muted" style="margin:4px 0 0; font-size:12px;">Estoque parado sem consumo recente</p>
  </div>
  <?php endif; ?>
  <?php if ($inativo_count > 0): ?>
  <div class="dashboard-card" style="flex:1; min-width:200px; border-left: 4px solid #9ca3af; padding: 16px; background: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 6px;">
    <span style="font-size:12px; font-weight:700; color:#6b7280; text-transform:uppercase;">Inativos</span>
    <h2 style="margin:8px 0 0; font-size:28px; color:#9ca3af;"><?= $inativo_count ?></h2>
    <p class="muted" style="margin:4px 0 0; font-size:12px;">Sem estoque e sem consumo</p>
  </div>
  <?php endif; ?>
</div>

<section class="panel list-panel">
  <div class="panel-head">
    <h2>Materiais em Giro de Estoque</h2>
    <p class="muted">Filtrados de acordo com os critérios de consumo das especialidades de faturamento ativas.</p>
  </div>

  <!-- Barra de Busca e Filtro de Threshold -->
  <form method="get" action="/consumo" class="filter-bar">
    <div class="filter-row">
      <div class="filter-field">
        <label class="filter-label">Buscar</label>
        <input type="text" name="q" value="<?= htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') ?>" placeholder="Material, código ou fornecedor" style="min-width:450px;">
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
          <option value="critico" <?= $filtro_status === 'critico' ? 'selected' : '' ?>>🔴 Crítico</option>
          <option value="alerta" <?= $filtro_status === 'alerta' ? 'selected' : '' ?>>🟠 Alerta</option>
          <option value="normal" <?= $filtro_status === 'normal' ? 'selected' : '' ?>>🟢 Saudável</option>
          <option value="sem_giro" <?= $filtro_status === 'sem_giro' ? 'selected' : '' ?>>🟣 Sem Giro</option>
          <option value="inativo" <?= $filtro_status === 'inativo' ? 'selected' : '' ?>>⚪ Inativo</option>
        </select>
      </div>
      <div class="filter-field">
        <label class="filter-label">Ordenação</label>
        <select name="sort">
          <option value="status_ratio" <?= $sort === 'status_ratio' ? 'selected' : '' ?>>Criticidade</option>
          <option value="nome_asc" <?= $sort === 'nome_asc' ? 'selected' : '' ?>>Material (A→Z)</option>
          <option value="nome_desc" <?= $sort === 'nome_desc' ? 'selected' : '' ?>>Material (Z→A)</option>
          <option value="codigo_asc" <?= $sort === 'codigo_asc' ? 'selected' : '' ?>>Código ↑</option>
          <option value="codigo_desc" <?= $sort === 'codigo_desc' ? 'selected' : '' ?>>Código ↓</option>
          <option value="saldo_asc" <?= $sort === 'saldo_asc' ? 'selected' : '' ?>>Saldo ↑</option>
          <option value="saldo_desc" <?= $sort === 'saldo_desc' ? 'selected' : '' ?>>Saldo ↓</option>
          <option value="media_desc" <?= $sort === 'media_desc' ? 'selected' : '' ?>>Média ↓</option>
        </select>
      </div>
      <div class="filter-field">
        <label class="filter-label">Página</label>
        <select name="per_page">
          <?php foreach ([20, 50, 100] as $n): ?>
            <option value="<?= $n ?>" <?= (int)($pagination['per_page'] ?? 20) === $n ? 'selected' : '' ?>><?= $n ?>/pág</option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="filter-field">
        <label class="filter-label" style="visibility:hidden;">Ações</label>
        <div style="display:flex; gap:6px;">
          <button type="submit" class="btn" style="margin:0;">Filtrar</button>
          <?php if ($busca !== '' || $filtro_status !== '' || $filtro_vinculo !== 'ativos' || $filtro_uso !== 'utilizados' || $sort !== 'status_ratio'): ?>
            <a class="btn btn-secondary" href="/consumo">Limpar</a>
          <?php endif; ?>
          <a class="btn" style="margin:0;" href="<?= htmlspecialchars('/api/consumo/export-csv-atual?' . http_build_query(array_merge($_GET, ['page' => 1])), ENT_QUOTES, 'UTF-8') ?>">Exportar CSV</a>
        </div>
      </div>
    </div>
  </form>

  <?php if (!empty($pagination) && ($pagination['total'] ?? 0) > 0): ?>
    <p class="muted" style="margin-bottom: 12px;">
      Exibindo <?= count($items) ?> de <?= (int)$pagination['total'] ?> materiais sob as regras de filtro.
    </p>
  <?php endif; ?>

  <?php if (empty($items)): ?>
    <p class="muted" style="padding: 24px 0; text-align: center;">Nenhum material de estoque correspondente encontrado para estes filtros.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table class="list-table">
      <colgroup>
        <col style="width: 5%;">
        <col style="width: 45%;">
        <col style="width: 25%;">
        <col style="width: 6%;">
        <col style="width: 6%;">
        <col style="width: 5%;">
        <col style="width: 8%;">
      </colgroup>
      <thead>
        <tr>
          <th style="text-align: center;">Código</th>
          <th style="text-align: center;">Material</th>
          <th style="text-align: center;">Fornecedor Ativo</th>
          <th style="text-align: center;">Média 90d</th>
          <th style="text-align: center;">Saldo</th>
          <th style="text-align: center;">Vínculo</th>
          <th style="text-align: center;">Histórico</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $item): ?>
          <?php 
            $opacity = $item['vinculo_ativo'] ? '1' : '0.55';
            $bgStyle = $item['vinculo_ativo'] ? '' : 'background-color: #f3f4f6;';
          ?>
          <tr class="row-hover" style="opacity: <?= $opacity ?>; <?= $bgStyle ?> transition: opacity 0.2s, background-color 0.2s;" id="row_<?= $item['codigo'] ?>_<?= $item['cnpj_fornecedor'] ?>">
            <td style="text-align: center;"><strong><?= $item['codigo'] ?></strong></td>
            <td>
              <div style="font-weight: 600; color: #111827;"><?= htmlspecialchars($item['descricao'], ENT_QUOTES, 'UTF-8') ?></div>
              <div style="margin-top: 4px; display: flex; gap: 8px; align-items: center;">
                <?php if ($item['status'] === 'critico'): ?>
                  <span class="status-tag" style="background: #fee2e2; color: #ef4444; border: 1px solid #fca5a5; padding: 2px 6px; font-size: 11px; font-weight: 700; border-radius: 4px;">🔴 CRÍTICO</span>
                  <span class="muted" style="font-size: 11px;">Ruptura iminente (Limite: <?= $item['threshold_critico'] ?>)</span>
                <?php elseif ($item['status'] === 'alerta'): ?>
                  <span class="status-tag" style="background: #fef3c7; color: #f59e0b; border: 1px solid #fcd34d; padding: 2px 6px; font-size: 11px; font-weight: 700; border-radius: 4px;">🟠 ALERTA</span>
                  <span class="muted" style="font-size: 11px;">Atenção (Margem: <?= $item['threshold_warning'] ?>)</span>
                <?php elseif ($item['status'] === 'sem_giro'): ?>
                  <span class="status-tag" style="background: #f3e8ff; color: #8b5cf6; border: 1px solid #c4b5fd; padding: 2px 6px; font-size: 11px; font-weight: 700; border-radius: 4px;">🟣 SEM GIRO</span>
                  <span class="muted" style="font-size: 11px;">Estoque parado sem consumo recente</span>
                <?php elseif ($item['status'] === 'inativo'): ?>
                  <span class="status-tag" style="background: #f3f4f6; color: #6b7280; border: 1px solid #d1d5db; padding: 2px 6px; font-size: 11px; font-weight: 700; border-radius: 4px;">⚪ INATIVO</span>
                  <span class="muted" style="font-size: 11px;">Sem estoque e sem consumo</span>
                <?php else: ?>
                  <span class="status-tag" style="background: #ecfdf5; color: #10b981; border: 1px solid #a7f3d0; padding: 2px 6px; font-size: 11px; font-weight: 700; border-radius: 4px;">🟢 ESTÁVEL</span>
                  <span class="muted" style="font-size: 11px;">Margem segura</span>
                <?php endif; ?>
              </div>
            </td>
            <td>
              <div style="font-weight: 500; font-size: 13px; color: #374151;"><?= htmlspecialchars($item['fornecedor'], ENT_QUOTES, 'UTF-8') ?></div>
              <div class="muted" style="font-size:11px; margin-top:2px;">CNPJ: <?= \MateriaisOpme\App\Support\Cnpj::format($item['cnpj_fornecedor']) ?></div>
            </td>
            <td style="text-align: center; font-weight: 600; font-size: 14px; color: #4b5563;">
              <?= $item['media'] < 1 ? '<span style="color:#9ca3af;">—</span>' : number_format($item['media'], 0, ',', '.') ?>
            </td>
            <td style="text-align: center; font-weight: 700; font-size: 15px; color: <?= $item['status'] === 'critico' ? '#ef4444' : ($item['status'] === 'alerta' ? '#f59e0b' : ($item['status'] === 'sem_giro' ? '#8b5cf6' : ($item['status'] === 'inativo' ? '#9ca3af' : '#10b981'))) ?>;">
              <?= number_format($item['saldo'], 0, ',', '.') ?>
            </td>
            <td style="text-align: center; vertical-align: middle;">
              <!-- Switch Desativador Estilo Toggle iOS/Moderno -->
              <label class="switch-container">
                <input type="checkbox" class="switch-input" <?= $item['vinculo_ativo'] ? 'checked' : '' ?> 
                       onclick="toggleVinculo(<?= $item['codigo'] ?>, '<?= $item['cnpj_fornecedor'] ?>', this)">
                <span class="switch-slider"></span>
              </label>
            </td>
            <td style="text-align: center; vertical-align: middle;">
              <button class="btn btn-secondary btn-sm" style="padding: 4px 10px; font-size: 12px; margin: 0;" onclick="abrirHistorico(<?= $item['codigo'] ?>, '<?= $item['cnpj_fornecedor'] ?>', '<?= htmlspecialchars(addslashes($item['descricao']), ENT_QUOTES, 'UTF-8') ?>')">
                ⏱️ Logs
              </button>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if (!empty($pagination) && (int)$pagination['total_pages'] > 1): ?>
    <?php
      $baseParams = [
        'q' => (string)$busca,
        'uso' => (string)$filtro_uso,
        'status' => (string)$filtro_status,
        'vinculo' => (string)$filtro_vinculo,
        'sort' => (string)$sort,
        'per_page' => (int)$pagination['per_page'],
      ];
      $currentPage = (int)$pagination['page'];
      $totalPages = (int)$pagination['total_pages'];
    ?>
    <div class="pagination" style="margin-top:20px; display:flex; justify-content:center; align-items:center; gap:12px;">
      <?php if ($currentPage > 1): ?>
        <a class="btn btn-secondary" href="/consumo?<?= htmlspecialchars(http_build_query($baseParams + ['page' => $currentPage - 1]), ENT_QUOTES, 'UTF-8') ?>">Anterior</a>
      <?php endif; ?>
      <span class="muted">Página <?= $currentPage ?> de <?= $totalPages ?></span>
      <?php if ($currentPage < $totalPages): ?>
        <a class="btn btn-secondary" href="/consumo?<?= htmlspecialchars(http_build_query($baseParams + ['page' => $currentPage + 1]), ENT_QUOTES, 'UTF-8') ?>">Próxima</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php endif; ?>
</section>

<!-- Modal de Histórico de Transição de Status (Logs) -->
<div id="modal_historico" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
  <div class="modal-content" style="background:#fff; padding:24px; border-radius:12px; width:100%; max-width:650px; box-shadow:0 10px 25px rgba(0,0,0,0.15); position:relative; max-height:85vh; display:flex; flex-direction:column;">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:1px solid #e5e7eb; padding-bottom:12px; margin-bottom:16px;">
      <div>
        <h3 id="modal_titulo" style="margin:0; font-size:18px; color:#111827;">Histórico de Status do Estoque</h3>
        <p id="modal_subtitulo" class="muted" style="margin:4px 0 0; font-size:12px;"></p>
      </div>
      <button onclick="fecharModal()" style="background:none; border:none; font-size:24px; color:#9ca3af; cursor:pointer; padding:0; line-height:1; width:auto; margin:0;">&times;</button>
    </div>
    
    <div id="modal_body" style="overflow-y:auto; flex:1; padding-right:4px;">
      <!-- Conteúdo dinâmico via AJAX -->
      <div style="display:flex; justify-content:center; padding:32px 0;">
        <span class="muted">Carregando histórico...</span>
      </div>
    </div>
    
    <div style="border-top:1px solid #e5e7eb; padding-top:12px; margin-top:16px; display:flex; justify-content:flex-end;">
      <button class="btn btn-secondary" onclick="fecharModal()" style="margin:0;">Fechar</button>
    </div>
  </div>
</div>

<style>
.dashboard-card {
  transition: transform 0.2s, box-shadow 0.2s;
}
.dashboard-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
}
.row-hover {
  transition: background-color 0.15s, opacity 0.15s;
}
.row-hover:hover {
  background-color: #f9fafb;
}

/* Modal styling */
.modal-overlay {
  display: none;
}

/* Linha do tempo vertical (Timeline) */
.timeline {
  position: relative;
  padding-left: 24px;
  list-style: none;
  margin: 0;
}
.timeline:before {
  content: "";
  position: absolute;
  left: 7px;
  top: 6px;
  bottom: 6px;
  width: 2px;
  background: #e5e7eb;
}
.timeline-item {
  position: relative;
  margin-bottom: 20px;
}
.timeline-item:last-child {
  margin-bottom: 4px;
}
.timeline-marker {
  position: absolute;
  left: -24px;
  top: 4px;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  border: 3px solid #fff;
  box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}
.timeline-marker.critico { background-color: #ef4444; }
.timeline-marker.alerta { background-color: #f59e0b; }
.timeline-marker.normal { background-color: #10b981; }
.timeline-marker.neutro { background-color: #9cb3af; }

.timeline-content {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  padding: 12px;
  border-radius: 8px;
}
.timeline-time {
  font-size: 11px;
  color: #6b7280;
  font-weight: 600;
  margin-bottom: 4px;
}
.timeline-title {
  font-size: 13px;
  font-weight: 700;
  color: #111827;
  margin-bottom: 4px;
}
.timeline-details {
  font-size: 12px;
  color: #4b5563;
}

/* Switch Styling de Alta Performance */
.switch-container {
  display: inline-block;
  position: relative;
  width: 44px;
  height: 22px;
  vertical-align: middle;
}
.switch-input {
  opacity: 0;
  width: 0;
  height: 0;
}
.switch-slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ef4444; /* Vermelho suave indicando desativado */
  transition: .3s;
  border-radius: 34px;
}
.switch-input:checked + .switch-slider {
  background-color: #10b981; /* Verde esmeralda bem definido indicando ativo */
}
.switch-slider:before {
  position: absolute;
  content: "";
  height: 16px;
  width: 16px;
  left: 3px;
  bottom: 3px;
  background-color: white;
  transition: .3s;
  border-radius: 50%;
  box-shadow: 0 1px 3px rgba(0,0,0,0.3);
}
.switch-input:checked + .switch-slider:before {
  transform: translateX(22px);
}
</style>

<script>
const csrfToken = '<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>';

function toggleVinculo(cdMaterial, cnpjFornecedor, checkboxElement) {
    const isAtivo = checkboxElement.checked;
    const rowId = `row_${cdMaterial}_${cnpjFornecedor}`;
    const row = document.getElementById(rowId);
    
    // Aplicar transição de esmaecimento física preventiva na hora do clique
    if (!isAtivo) {
        row.style.opacity = '0.55';
        row.style.backgroundColor = '#f3f4f6';
    } else {
        row.style.opacity = '1';
        row.style.backgroundColor = '';
    }

    // Chamada silenciosa AJAX
    fetch('/consumo/toggle', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
        },
        body: JSON.stringify({
            cd_material: cdMaterial,
            cnpj_fornecedor: cnpjFornecedor,
            ativo: isAtivo
        })
    })
    .then(response => response.json().then(data => ({ ok: response.ok, data })))
    .then(({ ok, data }) => {
        if (!ok) {
            throw new Error(data.error || 'Falha de resposta no servidor');
        }
        if (data.success) {
            console.log(`Vínculo do material ${cdMaterial} com fornecedor ${cnpjFornecedor} ${data.action} com sucesso!`);
        } else {
            throw new Error(data.error || 'Erro desconhecido.');
        }
    })
    .catch(error => {
        console.error('Erro na requisição AJAX:', error);
        reverterCheckbox(checkboxElement, row, !isAtivo);
        alert('Erro: ' + (error.message || 'Erro ao conectar ao servidor. O vínculo não foi salvo.'));
    });
}

function reverterCheckbox(checkboxElement, row, originalState) {
    checkboxElement.checked = originalState;
    if (originalState) {
        row.style.opacity = '1';
        row.style.backgroundColor = '';
    } else {
        row.style.opacity = '0.55';
        row.style.backgroundColor = '#f3f4f6';
    }
}

/* Funções de Controle do Modal de Histórico */
function abrirHistorico(cdMaterial, cnpjFornecedor, descricao) {
    const modal = document.getElementById('modal_historico');
    const subtitulo = document.getElementById('modal_subtitulo');
    const body = document.getElementById('modal_body');
    
    // Configura cabeçalho e limpa listagem de carregar
    subtitulo.innerHTML = `Cod. <strong>${cdMaterial}</strong> &bull; CNPJ <strong>${cnpjFornecedor}</strong><br><span style="font-size: 13px; color: #1f2937;">${descricao}</span>`;
    body.innerHTML = `
      <div style="display:flex; justify-content:center; padding:32px 0;">
        <span class="muted">Buscando histórico na base...</span>
      </div>
    `;
    
    // Abre a overlay
    modal.style.display = 'flex';
    
    // Busca informações de logs do estoque via AJAX
    fetch(`/api/consumo/historico?cd_material=${cdMaterial}&cnpj_fornecedor=${cnpjFornecedor}`, {
        method: 'GET',
        headers: {
            'X-CSRF-Token': csrfToken
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Erro de resposta do servidor');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            exibirHistoricoHtml(data.historico, body);
        } else {
            body.innerHTML = `<p class="error" style="text-align:center; padding:16px;">Erro: ${data.error || 'Não foi possível carregar.'}</p>`;
        }
    })
    .catch(error => {
        console.error('Falha carregando histórico:', error);
        body.innerHTML = `<p class="error" style="text-align:center; padding:16px;">Erro de conexão com o servidor.</p>`;
    });
}

function fecharModal() {
    document.getElementById('modal_historico').style.display = 'none';
}

function exibirHistoricoHtml(historico, containerElement) {
    if (!historico || historico.length === 0) {
        containerElement.innerHTML = `
          <div style="text-align:center; padding:32px 16px; border: 1px dashed #d1d5db; border-radius: 8px;">
            <p style="margin:0; font-weight:600; color:#4b5563;">Nenhum registro histórico de transição</p>
            <p class="muted" style="margin:4px 0 0; font-size:12px;">Se as médias e o estoque do fornecedor continuarem na mesma regra de threshold, nenhuma transição de alteração será gerada no robô diário.</p>
          </div>
        `;
        return;
    }
    
    let html = '<ul class="timeline">';
    
    historico.forEach(log => {
        const estAnterior = log.status_anterior ? log.status_anterior.toUpperCase() : 'NENHUM';
        const estNovo = log.status_novo.toUpperCase();
        
        let markerClass = 'neutro';
        let badgeStyle = '';
        let txtMudanca = '';
        
        // Formata os status em badges legíveis e estilizados
        if (log.status_novo === 'critico') {
            markerClass = 'critico';
            badgeStyle = 'background-color: #fee2e2; color: #ef4444; border: 1px solid #fca5a5;';
        } else if (log.status_novo === 'alerta') {
            markerClass = 'alerta';
            badgeStyle = 'background-color: #fef3c7; color: #f59e0b; border: 1px solid #fcd34d;';
        } else if (log.status_novo === 'normal') {
            markerClass = 'normal';
            badgeStyle = 'background-color: #ecfdf5; color: #10b981; border: 1px solid #a7f3d0;';
        }
        
        const badgeNovo = `<span style="display:inline-block; padding:2px 6px; font-size:11px; font-weight:700; border-radius:4px; ${badgeStyle}">${estNovo}</span>`;
        
        if (log.status_anterior) {
            let badgeAntStyle = 'background-color: #f3f4f6; color: #6b7280; border: 1px solid #d1d5db;';
            if (log.status_anterior === 'critico') badgeAntStyle = 'background-color: #fee2e2; color: #ef4444; border: 1px solid #fca5a5;';
            if (log.status_anterior === 'alerta') badgeAntStyle = 'background-color: #fef3c7; color: #f59e0b; border: 1px solid #fcd34d;';
            if (log.status_anterior === 'normal') badgeAntStyle = 'background-color: #ecfdf5; color: #10b981; border: 1px solid #a7f3d0;';
            
            const badgeAnt = `<span style="display:inline-block; padding:2px 6px; font-size:11px; font-weight:700; border-radius:4px; ${badgeAntStyle}">${estAnterior}</span>`;
            txtMudanca = `Transição de Status: ${badgeAnt} &rarr; ${badgeNovo}`;
        } else {
            txtMudanca = `Status Inicial Detectado: ${badgeNovo}`;
        }
        
        // Converte o saldo e média para números formatados em português brasileiro
        const saldoFmt = parseFloat(log.saldo_momento).toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        const mediaFmt = parseFloat(log.media_momento).toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
        
        html += `
          <li class="timeline-item">
            <span class="timeline-marker ${markerClass}"></span>
            <div class="timeline-content">
              <div class="timeline-time">${log.data_formatada} UTC-3</div>
              <div class="timeline-title">${txtMudanca}</div>
              <div class="timeline-details">
                <div style="margin-top: 4px; display: flex; gap: 16px;">
                  <span>📦 Saldo Físico: <strong>${saldoFmt}</strong></span>
                  <span>📊 Giro Analítico (Média): <strong>${mediaFmt}</strong></span>
                </div>
              </div>
            </div>
          </li>
        `;
    });
    
    html += '</ul>';
    containerElement.innerHTML = html;
}

// Fecha o modal caso clique fora da janela branca
window.onclick = function(event) {
    const modal = document.getElementById('modal_historico');
    if (event.target == modal) {
        fecharModal();
    }
}
</script>
<?php
$content = ob_get_clean();
$title = 'Estoque de Consumo';
require __DIR__ . '/layout.php';
