<?php
declare(strict_types=1);
ob_start();
$isEdit = str_contains((string)($action ?? ''), '/editar/');
?>
<h1><?= $isEdit ? 'Editar fornecedor' : 'Incluir fornecedor' ?></h1>
<div class="nav nav-top">
  <a class="btn btn-secondary" href="/fornecedores">Voltar</a>
</div>
<?php if (!empty($error)): ?>
  <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<?php if (!empty($warning)): ?>
  <p class="warning"><?= $warning ?></p>
<?php endif; ?>
<?php $existing_vendors = $existing_vendors ?? []; ?>
<?php $current_cnpj = preg_replace('/\D+/', '', (string)($vendor['cnpj_formatted'] ?? '')); ?>
<form method="post" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" class="form-standard form-compact">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

  <div class="form-grid">
    <div>
      <label>CNPJ</label>
      <input id="cnpj-field" name="cnpj" value="<?= htmlspecialchars((string)($vendor['cnpj_formatted'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="00.000.000/0000-00" required>
      <p id="cnpj-duplicate" class="error" style="display:none; margin-top:6px;">Já existe um fornecedor com esse CNPJ.</p>
    </div>

    <div>
      <label>Nome</label>
      <input name="name" value="<?= htmlspecialchars((string)($vendor['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
    </div>

    <div>
      <label>Enviar relatório</label>
      <select name="send_report">
        <option value="1" <?= !empty($vendor['send_report']) ? 'selected' : '' ?>>Sim</option>
        <option value="0" <?= empty($vendor['send_report']) ? 'selected' : '' ?>>Não</option>
      </select>
    </div>

    <div>
      <label>Etiqueta</label>
      <input name="status" value="<?= htmlspecialchars((string)($vendor['status'] ?? ($isEdit ? '' : 'manual')), ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <input type="hidden" name="pendencias" value="<?= (int)($vendor['pending_display_count'] ?? $vendor['pendencias'] ?? 0) ?>">
    <?php if (!$isEdit): ?>
      <p class="help full-width">Pendências manual: o cadastro grava 0.</p>
    <?php endif; ?>

    <div class="full-width">
      <label>E-mails de contato</label>
      <textarea name="contacts" rows="3" placeholder="Exemplo: fornecedor@email.com, outro@email.com"><?= htmlspecialchars((string)($vendor['contacts'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
      <div class="help">Separe múltiplos e-mails com vírgula.</div>
    </div>

    <div class="full-width">
      <label>Obs</label>
      <textarea name="notes" rows="3"><?= htmlspecialchars((string)($vendor['notes'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
    </div>
  </div>

  <div class="form-actions">
    <button type="submit"><?= $isEdit ? 'Salvar alterações' : 'Cadastrar fornecedor' ?></button>
  </div>
</form>
<script>
(() => {
  const input = document.getElementById('cnpj-field');
  const warning = document.getElementById('cnpj-duplicate');
  const existing = new Map(<?= json_encode(array_values(array_filter(array_map(static fn(array $item): array => [
    'cnpj' => preg_replace('/\D+/', '', (string)($item['cnpj'] ?? '')),
    'name' => trim((string)($item['name'] ?? '')),
  ], $existing_vendors ?? []), static fn(array $item): bool => $item['cnpj'] !== '' && $item['cnpj'] !== $current_cnpj)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>.map(item => [item.cnpj, item.name]));

  const onlyDigits = (value) => String(value || '').replace(/\D+/g, '');
  const formatCnpj = (value) => {
    const digits = onlyDigits(value).slice(0, 14);
    if (!digits) return '';
    if (digits.length <= 2) return digits;
    if (digits.length <= 5) return digits.replace(/^(\d{2})(\d+)/, '$1.$2');
    if (digits.length <= 8) return digits.replace(/^(\d{2})(\d{3})(\d+)/, '$1.$2.$3');
    if (digits.length <= 12) return digits.replace(/^(\d{2})(\d{3})(\d{3})(\d+)/, '$1.$2.$3/$4');
    return digits.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{1,2}).*/, '$1.$2.$3/$4-$5');
  };

  const checkDuplicate = () => {
    const digits = onlyDigits(input.value);
    const duplicateName = existing.get(digits) || '';
    const duplicate = digits.length === 14 && existing.has(digits);
    warning.textContent = duplicate
      ? 'Já existe um fornecedor com esse CNPJ.' + (duplicateName ? ' (' + duplicateName + ')' : '')
      : 'Já existe um fornecedor com esse CNPJ.';
    warning.style.display = duplicate ? 'block' : 'none';
    return !duplicate;
  };

  input.addEventListener('input', () => {
    const value = input.value;
    const formatted = formatCnpj(value);
    if (formatted !== value) {
      input.value = formatted;
    }
    checkDuplicate();
  });

  input.addEventListener('blur', checkDuplicate);
  input.form?.addEventListener('submit', (event) => {
    if (!checkDuplicate()) {
      event.preventDefault();
      input.focus();
    }
  });

  checkDuplicate();
})();
</script>
<?php
$content = ob_get_clean();
$title = $isEdit ? 'Editar fornecedor' : 'Incluir fornecedor';
require __DIR__ . '/layout.php';
