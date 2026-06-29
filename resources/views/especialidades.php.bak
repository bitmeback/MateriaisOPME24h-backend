<?php
declare(strict_types=1);
ob_start();
/** @var array $especialidades */
/** @var array $fornecedores */
/** @var string $csrf_token */
?>
<h1>Especialidades por Fornecedor</h1>
<p class="muted">Cadastre as especialidades e associe os fornecedores correspondentes.</p>
<div class="nav nav-top">
  <a class="btn" href="/consumo" style="background: #3b82f6; color:#fff;">Consumo</a>
  <a class="btn btn-secondary" href="/dashboard">Voltar</a>
</div>

<?php if (!empty($_SESSION['success'])): ?>
  <p class="success"><?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') ?></p>
  <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
  <p class="error"><?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?></p>
  <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- Modal Editar Especialidade -->
<div id="formEditar" style="display:none; margin-bottom:20px; padding:12px; background:#fafafa; border:1px solid #e5e7eb; border-radius:8px;">
  <h4 style="margin-top:0;">Editar Especialidade</h4>
  <form method="post" action="/especialidades/editar">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="id" id="editId">
    <label style="display:block; font-size:12px; font-weight:700; color:#374151; margin:0 0 4px;">Nome</label>
    <input type="text" name="nome" id="editNome" required maxlength="200" style="width:100%; box-sizing:border-box; padding:8px; margin:0 0 10px;">
    <label style="display:flex; align-items:center; gap:8px; margin:0 0 10px;">
      <input type="checkbox" name="ativo" id="editAtivo" value="1">
      <span style="font-weight:600;">Ativo</span>
    </label>
    <button type="submit" class="btn">Salvar</button>
    <button type="button" class="btn btn-secondary" onclick="document.getElementById('formEditar').style.display='none'">Cancelar</button>
  </form>
</div>

<!-- Lista de Especialidades -->
<section class="panel list-panel" style="margin-bottom:20px;">
  <div class="panel-head">
    <div>
      <h2>Especialidades Cadastradas</h2>
      <p class="muted">Total: <?= count($especialidades) ?> especialidade(s).</p>
    </div>
    <button class="btn" onclick="document.getElementById('formNova').style.display='block'; return false;">+ Nova Especialidade</button>
  </div>

  <div id="formNova" style="display:none; margin-bottom:16px; padding:12px; background:#fafafa; border:1px solid #e5e7eb; border-radius:8px;">
    <form method="post" action="/especialidades/novo">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
      <label style="display:block; font-size:12px; font-weight:700; color:#374151; margin:0 0 4px;">Nome</label>
      <input type="text" name="nome" required maxlength="200" style="width:100%; box-sizing:border-box; padding:8px; margin:0 0 10px;">
      <button type="submit" class="btn">Salvar</button>
      <button type="button" class="btn btn-secondary" onclick="document.getElementById('formNova').style.display='none'">Cancelar</button>
    </form>
  </div>

  <!-- Barra de busca, filtro e ordenação -->
  <form method="get" action="/especialidades" class="search-bar">
    <input type="text" name="q" value="<?= htmlspecialchars($busca, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por nome da especialidade">
    <select name="status">
      <option value="">Todos os status</option>
      <option value="1" <?= ($filtro_status === '1') ? 'selected' : '' ?>>Ativo</option>
      <option value="0" <?= ($filtro_status === '0') ? 'selected' : '' ?>>Inativo</option>
    </select>
    <select name="sort">
      <option value="nome_asc" <?= ($sort === 'nome_asc') ? 'selected' : '' ?>>Nome (A→Z)</option>
      <option value="nome_desc" <?= ($sort === 'nome_desc') ? 'selected' : '' ?>>Nome (Z→A)</option>
      <option value="id_asc" <?= ($sort === 'id_asc') ? 'selected' : '' ?>>ID (Menor→Maior)</option>
      <option value="id_desc" <?= ($sort === 'id_desc') ? 'selected' : '' ?>>ID (Maior→Menor)</option>
    </select>
    <select name="per_page">
      <?php foreach ([10, 20, 50, 100] as $n): ?>
        <option value="<?= $n ?>" <?= (int)($pagination['per_page'] ?? 10) === $n ? 'selected' : '' ?>><?= $n ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn" type="submit">Filtrar</button>
    <?php if ($busca !== '' || $filtro_status !== '' || $sort !== 'nome_asc'): ?>
      <a class="btn btn-secondary" href="/especialidades">Limpar</a>
    <?php endif; ?>
  </form>

  <?php if (!empty($pagination) && ($pagination['total'] ?? 0) > 0): ?>
    <p class="muted">
      <?= (int)$pagination['total'] ?> resultado(s)
      · página <?= (int)$pagination['page'] ?> de <?= (int)$pagination['total_pages'] ?>
    </p>
  <?php endif; ?>

  <?php if (empty($especialidades)): ?>
    <p class="muted">Nenhuma especialidade encontrada.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table class="list-table">
      <colgroup>
        <col style="width: 10%;">
        <col style="width: 50%;">
        <col style="width: 10%;">
        <col style="width: 30%;">
      </colgroup>
      <thead>
        <tr>
          <th>ID</th>
          <th>Nome</th>
          <th>Status</th>
          <th class="actions-col">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($especialidades as $esp): ?>
        <tr>
          <td><?= (int)$esp['id'] ?></td>
          <td><?= htmlspecialchars($esp['nome'], ENT_QUOTES, 'UTF-8') ?></td>
          <td style="text-align:center;">
            <?php if ($esp['ativo']): ?>
              <span style="color:#0a7a2f; font-weight:700;">Ativo</span>
            <?php else: ?>
              <span style="color:#666;">Inativo</span>
            <?php endif; ?>
          </td>
          <td class="actions">
            <div class="table-actions">
              <button class="btn btn-sm btn-edit" onclick="editarEsp(<?= (int)$esp['id'] ?>, '<?= htmlspecialchars(addslashes($esp['nome']), ENT_QUOTES, 'UTF-8') ?>', <?= (int)$esp['ativo'] ?>); return false;">Editar</button>
              <form method="post" action="/especialidades/excluir" onsubmit="return confirm('Desativar esta especialidade?');" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" value="<?= (int)$esp['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">Desativar</button>
              </form>
            </div>
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
        'status' => (string)$filtro_status,
        'sort' => (string)$sort,
        'per_page' => (int)$pagination['per_page'],
      ];
      $currentPage = (int)$pagination['page'];
      $totalPages = (int)$pagination['total_pages'];
    ?>
    <div class="pagination">
      <?php if ($currentPage > 1): ?>
        <a class="btn btn-secondary" href="/especialidades?<?= htmlspecialchars(http_build_query($baseParams + ['page' => $currentPage - 1]), ENT_QUOTES, 'UTF-8') ?>">Anterior</a>
      <?php endif; ?>
      <span class="muted">Página <?= $currentPage ?> de <?= $totalPages ?></span>
      <?php if ($currentPage < $totalPages): ?>
        <a class="btn btn-secondary" href="/especialidades?<?= htmlspecialchars(http_build_query($baseParams + ['page' => $currentPage + 1]), ENT_QUOTES, 'UTF-8') ?>">Próxima</a>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <?php endif; ?>
</section>

<!-- Seção: Associar Especialidade → Fornecedores -->
<section class="panel">
  <div class="panel-head">
    <div>
      <h2>Associar Fornecedores a Especialidades</h2>
      <p class="muted">Selecione uma especialidade e associe os fornecedores correspondentes.</p>
    </div>
  </div>

  <div class="search-bar">
    <select id="selectEspecialidade" onchange="carregarFornecedores(this.value)" style="min-width:250px;">
      <option value="">Selecione uma especialidade...</option>
      <?php foreach ($especialidades as $esp): ?>
        <option value="<?= (int)$esp['id'] ?>"><?= htmlspecialchars($esp['nome'], ENT_QUOTES, 'UTF-8') ?></option>
      <?php endforeach; ?>
    </select>
    <input type="text" id="buscaFornecedor" placeholder="Buscar fornecedor por nome ou CNPJ..." style="flex:1; min-width:200px;" oninput="filtrarSelect('selectNaoAssociados', this.value); filtrarSelect('selectAssociados', this.value);">
  </div>

  <div id="associacoes-container" style="display:none; margin-top:16px;">
    <p style="font-weight:600;">Associe fornecedores à especialidade <strong id="espNome">—</strong>:</p>

    <!-- Botões de ação em massa -->
    <div style="display:flex; gap:8px; margin-bottom:12px; flex-wrap:wrap;">
      <button class="btn btn-sm" onclick="todosParaAssociados()">Marcar todos →</button>
      <button class="btn btn-secondary btn-sm" onclick="todosParaNaoAssociados()">← Desmarcar todos</button>
      <span id="contador" class="muted" style="align-self:center; margin-left:8px;"></span>
    </div>

    <div style="display:flex; gap:12px; align-items:stretch;">
      <!-- Não associados -->
      <div style="flex:1;">
        <p style="font-size:12px; font-weight:700; color:#374151; margin:0 0 6px;">Não associados</p>
        <select id="selectNaoAssociados" multiple size="20" class="multiselect-forn"></select>
      </div>

      <!-- Botões centrais -->
      <div style="display:flex; flex-direction:column; justify-content:center; gap:8px;">
        <button class="btn btn-sm" onclick="moverSelecionados('selectNaoAssociados','selectAssociados')" title="Mover selecionados para associados">→</button>
        <button class="btn btn-secondary btn-sm" onclick="moverSelecionados('selectAssociados','selectNaoAssociados')" title="Mover selecionados para não associados">←</button>
      </div>

      <!-- Associados -->
      <div style="flex:1;">
        <p style="font-size:12px; font-weight:700; color:#374151; margin:0 0 6px;">Associados</p>
        <select id="selectAssociados" multiple size="20" class="multiselect-forn"></select>
      </div>
    </div>

    <div style="margin-top:12px;">
      <button class="btn" onclick="salvarAssociacoes()">Salvar Associações</button>
    </div>
  </div>
</section>

<style>
.multiselect-forn {
  width: 100%;
  box-sizing: border-box;
  border: 1px solid #ccc;
  border-radius: 4px;
  padding: 6px;
  font-size: 13px;
  font-family: Arial, sans-serif;
  background: #fff;
  color: #222;
  outline: none;
}
.multiselect-forn:focus {
  border-color: #0b5fff;
  box-shadow: 0 0 0 2px rgba(11,95,255,.15);
}
.multiselect-forn option {
  padding: 6px 8px;
  border-bottom: 1px solid #f0f0f0;
  cursor: pointer;
}
.multiselect-forn option:last-child {
  border-bottom: none;
}
.multiselect-forn option:hover {
  background: #e8f0fe;
}
.multiselect-forn option:checked {
  background: #0b5fff;
  color: #fff;
}
</style>

<script>
let csrfToken = '<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>';

function editarEsp(id, nome, ativo) {
    document.getElementById('editId').value = id;
    document.getElementById('editNome').value = nome;
    document.getElementById('editAtivo').checked = ativo === 1;
    document.getElementById('formEditar').style.display = 'block';
}

function carregarFornecedores(espId) {
    if (!espId) {
        document.getElementById('associacoes-container').style.display = 'none';
        return;
    }

    const espNome = document.getElementById('selectEspecialidade').options[document.getElementById('selectEspecialidade').selectedIndex].text;
    document.getElementById('espNome').textContent = espNome;

    fetch('/api/especialidade-fornecedores?id=' + espId)
        .then(r => r.json())
        .then(data => {
            popularSelect('selectNaoAssociados', data.nao_associados || []);
            popularSelect('selectAssociados', data.associados || []);
            atualizarContador();
            document.getElementById('associacoes-container').style.display = 'block';
        });
}

function popularSelect(selectId, itens) {
    const sel = document.getElementById(selectId);
    sel.innerHTML = '';
    itens.forEach(function(f) {
        const opt = document.createElement('option');
        opt.value = f.cnpj;
        opt.textContent = f.name + ' (' + (f.cnpj_formatted || f.cnpj) + ')';
        sel.appendChild(opt);
    });
}

function moverSelecionados(origemId, destinoId) {
    const origem = document.getElementById(origemId);
    const destino = document.getElementById(destinoId);
    const selecionados = Array.from(origem.selectedOptions);
    selecionados.forEach(function(opt) {
        destino.appendChild(opt);
    });
    atualizarContador();
}

function todosParaAssociados() {
    const origem = document.getElementById('selectNaoAssociados');
    const destino = document.getElementById('selectAssociados');
    Array.from(origem.options).forEach(function(opt) {
        destino.appendChild(opt);
    });
    atualizarContador();
}

function todosParaNaoAssociados() {
    const origem = document.getElementById('selectAssociados');
    const destino = document.getElementById('selectNaoAssociados');
    Array.from(origem.options).forEach(function(opt) {
        destino.appendChild(opt);
    });
    atualizarContador();
}

function filtrarSelect(selectId, busca) {
    const sel = document.getElementById(selectId);
    const termo = busca.toLowerCase();
    Array.from(sel.options).forEach(function(opt) {
        const texto = opt.textContent.toLowerCase();
        opt.style.display = (texto.includes(termo) || termo === '') ? '' : 'none';
    });
}

function atualizarContador() {
    const na = document.getElementById('selectNaoAssociados').options.length;
    const a = document.getElementById('selectAssociados').options.length;
    document.getElementById('contador').textContent = a + ' associado(s) · ' + na + ' disponível(is)';
}

function salvarAssociacoes() {
    const espId = document.getElementById('selectEspecialidade').value;
    const selAssociados = document.getElementById('selectAssociados');
    const cnpjs = Array.from(selAssociados.options).map(o => o.value);

    if (!espId) {
        alert('Selecione uma especialidade primeiro.');
        return;
    }

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('especialidade_id', espId);
    cnpjs.forEach(function(cnpj) {
        formData.append('fornecedores[]', cnpj);
    });

    fetch('/especialidades/salvar-fornecedor', {
        method: 'POST',
        body: formData
    }).then(function(r) {
        if (r.ok) {
            alert('Associações salvas com sucesso (' + cnpjs.length + ' fornecedor(es).');
        } else {
            alert('Erro ao salvar. Tente novamente.');
        }
    });
}
</script>
<?php
$content = ob_get_clean();
$title = 'Especialidades';
require __DIR__ . '/layout.php';
