<?php
declare(strict_types=1);
ob_start();
$values = $values ?? [];
?>
<h1>Configurações do sistema</h1>
<div class="nav nav-top">
  <a class="btn" href="/configuracoes/credenciais">Credenciais</a>
  <a class="btn btn-secondary" href="/dashboard">Voltar</a>
</div>
<p class="muted">Gerenciamento de informações do sistema. Só altere sob recomendação de um desenvolvedor.</p>
<?php if (!empty($error)): ?>
  <p class="error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<?php if (!empty($success)): ?>
  <p class="success"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></p>
<?php endif; ?>
<form method="post" action="/configuracoes/sistema" class="form-standard form-compact config-system-form">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">

  <div class="form-grid config-system-grid">
    <div class="full-width">
      <h2>Aplicação</h2>
    </div>
    <div>
      <label>Nome da aplicação</label>
      <input name="app_name" value="<?= htmlspecialchars((string)($values['app_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div>
      <label>Ambiente</label>
      <input name="environment" value="<?= htmlspecialchars((string)($values['environment'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="production ou homologacao">
    </div>
    <div>
      <label>URL base</label>
      <input name="base_url" value="<?= htmlspecialchars((string)($values['base_url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>
    <div>
      <label>Timezone</label>
      <input name="timezone" value="<?= htmlspecialchars((string)($values['timezone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="America/Sao_Paulo">
    </div>

    <div class="full-width">
      <h2>Segredos</h2>
    </div>
    <div>
      <label>Session secret</label>
      <input type="password" name="session_secret" value="" placeholder="Deixe em branco para manter o valor atual">
    </div>
    <div>
      <label>CSRF secret</label>
      <input type="password" name="csrf_secret" value="" placeholder="Deixe em branco para manter o valor atual">
    </div>

    <div class="full-width">
      <h2>Arquivos</h2>
    </div>
    <div>
      <label>Arquivo de credenciais IMAP/SMTP</label>
      <input name="compras_credentials_file" value="<?= htmlspecialchars((string)($values['compras_credentials_file'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
      <div class="help">Contém usuário e senha da conta de e-mail (compras4@donahelena.com.br).</div>
    </div>
    <div>
      <label>Diretório de logs</label>
      <input name="log_dir" value="<?= htmlspecialchars((string)($values['log_dir'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="full-width">
      <h2>Informações v2.0</h2>
    </div>
    <div class="full-width">
      <div style="background:#f0f4f8;border:1px solid #d0d7de;border-radius:8px;padding:16px;font-size:14px;line-height:1.6;">
        <p><strong>Fornecedores:</strong> armazenados no banco de dados MariaDB (tabela <code>fornecedores</code>). Gerencie pela página de Fornecedores.</p>
        <p><strong>Usuários do backend:</strong> armazenados no banco de dados MariaDB (tabela <code>users</code>). Tipos de usuário:</p>
        <ul style="margin:8px 0 8px 20px;">
          <li><strong>user</strong> — Usuário operacional: visualiza dashboard, fornecedores e envios.</li>
          <li><strong>admin</strong> — Administrador: tudo do user + gerencia fornecedores, dispara envios, altera configurações.</li>
          <li><strong>desenv</strong> — Desenvolvedor: acesso total, incluindo auditoria e logs.</li>
        </ul>
        <p><strong>Coleta de e-mails:</strong> via IMAP (pasta INBOX/CARTEIRA OPME/Relatórios automáticos).</p>
        <p><strong>Envio de e-mails:</strong> via SMTP (nodemailer) + cópia salva no IMAP (Itens Enviados).</p>
      </div>
    </div>
  </div>

  <div class="form-actions">
    <button type="submit">Salvar configurações</button>
  </div>
</form>
<?php
$content = ob_get_clean();
$title = 'Configurações do sistema';
require __DIR__ . '/layout.php';
