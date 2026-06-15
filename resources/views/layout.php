<?php
declare(strict_types=1);
?><!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($title ?? 'Materiais Opme Backend', ENT_QUOTES, 'UTF-8') ?></title>
  <style>
    body { font-family: Arial, sans-serif; margin: 24px; background: #f7f7f7; color: #222; }
    .card { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 1px 6px rgba(0,0,0,.08); max-width: 1440px; }
    input, button, select, textarea { font: inherit; padding: 8px; margin: 4px 0 12px; width: 100%; box-sizing: border-box; }
    button, .btn { display: inline-block; width: auto; background: #0b5fff; color: #fff; border: 0; padding: 10px 14px; border-radius: 8px; text-decoration: none; cursor: pointer; white-space: nowrap; }
    .btn-secondary { background: #666; }
    .btn-edit { background: #0b5fff; }
    .btn-pending { background: #198754; }
    .btn-danger { background: #b00020; }
    .btn:disabled { background: #9ca3af; cursor: not-allowed; opacity: 0.8; }
    .grid { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }
    table { width: 100%; border-collapse: collapse; background: #fff; }
    th, td { border: 1px solid #ddd; padding: 8px; vertical-align: top; text-align: left; }
    th { background: #f0f0f0; }
    .muted { color: #666; }
    .nav a { margin-right: 10px; }
    .nav form { display: inline; margin-right: 10px; }
    .nav .btn { margin: 0; }
    .error { color: #b00020; }
    .success { color: #0a7a2f; }
    .warning { color: #8a5b00; background: #fff4cc; border: 1px solid #f0d36a; padding: 10px 12px; border-radius: 8px; font-weight: 700; }
    .search-bar { display: flex; gap: 8px; align-items: center; margin: 12px 0 16px; flex-wrap: wrap; }
    .search-bar input { flex: 1; min-width: 280px; margin: 0; }
    .search-bar select { width: auto; min-width: 180px; margin: 0; }
    .search-bar .btn { margin: 0; }
    .pagination { display: flex; gap: 10px; align-items: center; margin-top: 16px; flex-wrap: wrap; }
    .pagination .btn { margin: 0; }
    .actions { display: flex; gap: 8px; align-items: center; justify-content: center; flex-wrap: wrap; min-width: 360px; }
    .actions form { margin: 0; }
    .actions .btn { margin: 0; padding: 8px 12px; font-size: 14px; line-height: 1.1; }
    .contacts { overflow-wrap: anywhere; word-break: break-word; white-space: normal; }
    th.actions-col, td.actions { width: 360px; min-width: 360px; text-align: center; }
    .pending-count { font-weight: 700; text-align: center; }
    .pending-count.zero { color: #666; }
    .list-table tbody td.pending-count:not(.zero) { color: #b45309; }
    .profile-layout { display: grid; grid-template-columns: minmax(0, 2.18fr) minmax(260px, 0.62fr); gap: 18px; align-items: start; }
    .users-layout > .users-panel:last-child { justify-self: end; width: 100%; max-width: 280px; }
    .users-layout > .users-panel:first-child { max-width: 1200px; }
    .panel { background: #fafafa; border: 1px solid #e5e7eb; border-radius: 12px; padding: 16px; }
    .panel h2 { margin-top: 0; }
    .panel-head { display: flex; justify-content: space-between; gap: 12px; align-items: flex-start; margin-bottom: 12px; }
    .profile-list { list-style: none; padding: 0; margin: 0; }
    .profile-list li { padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
    .profile-list li:last-child { border-bottom: 0; }
    .profile-label { display: block; font-size: 12px; color: #6b7280; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px; }
    .profile-value { font-weight: 600; }
    .table-wrap { overflow-x: auto; }
    .list-panel { max-width: 100%; }
    .list-panel .panel-head { margin-bottom: 12px; }
    .list-table { width: 100%; table-layout: fixed; }
    .list-table th, .list-table td { font-size: 13px; line-height: 1.25; padding: 6px 8px; vertical-align: top; overflow: hidden; text-overflow: ellipsis; }
    .list-table .actions-col, .list-table td.actions { width: 82px; min-width: 82px; max-width: 82px; white-space: nowrap; text-align: center; }
    .list-table .status-cell, .list-table .pending-count { text-align: center; }
    .list-table .cnpj-cell { white-space: nowrap; font-variant-numeric: tabular-nums; }
    .list-table .contacts { overflow-wrap: normal; word-break: normal; white-space: normal; }
    .list-table td.actions { display: table-cell; }
    .list-table .table-actions { display: inline-flex; gap: 2px; align-items: center; justify-content: center; flex-wrap: nowrap; }
    .list-table .table-actions form { margin: 0; }
    .list-table .table-actions .btn-sm { padding: 4px 6px; font-size: 11px; line-height: 1.05; }
    .fornecedores-table .actions-col, .fornecedores-table td.actions { width: 190px; min-width: 190px; max-width: 190px; }
    .users-table { width: 100%; table-layout: fixed; }
    .users-table th, .users-table td { font-size: 13px; line-height: 1.25; padding: 6px 8px; vertical-align: top; overflow: hidden; text-overflow: ellipsis; }
    .users-table td:nth-child(1), .users-table td:nth-child(4), .users-table td:nth-child(5), .users-table td:nth-child(6), .users-table td:nth-child(7), .users-table td:nth-child(8) { white-space: nowrap; }
    .users-table .user-col, .users-table .name-col { white-space: normal; word-break: break-word; }
    .users-table .actions-col, .users-table td.actions { width: 82px; min-width: 82px; max-width: 82px; white-space: nowrap; text-align: center; }
    .users-table td.actions { display: table-cell; }
    .table-actions { display: inline-flex; gap: 2px; align-items: center; justify-content: center; flex-wrap: nowrap; }
    .table-actions form { margin: 0; }
    .table-actions .btn-sm { padding: 4px 6px; font-size: 11px; line-height: 1.05; }
    .table-actions .btn-danger { background: #b00020; }
    .table-actions .btn-edit { background: #0b5fff; }
    .form-standard { font-size: 13px; }
    .form-standard label { display: block; font-size: 12px; font-weight: 700; color: #374151; margin: 0 0 4px; }
    .form-standard input, .form-standard select, .form-standard textarea { font-size: 13px; line-height: 1.25; padding: 7px 8px; margin: 0 0 10px; }
    .form-standard button { font-size: 13px; padding: 9px 12px; }
    .form-standard .help { color: #6b7280; font-size: 12px; margin: -6px 0 8px; }
    .form-compact { max-width: 920px; margin: 0 auto; }
    .form-compact.config-system-form { max-width: none; width: 100%; }
    .form-grid { display: grid; gap: 12px; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); align-items: start; }
    .config-system-grid { grid-template-columns: repeat(6, minmax(0, 1fr)); }
    .form-grid .full-width { grid-column: 1 / -1; }
    .password-hint { font-size: 12px; margin: -6px 0 10px; }
    .password-hint.muted { color: #6b7280; }
    .password-hint.error { color: #b00020; font-weight: 700; }
    .username-hint { font-size: 12px; margin: -6px 0 10px; }
    .username-hint.muted { color: #6b7280; }
    .username-hint.error { color: #b00020; font-weight: 700; }
    .form-actions { display: flex; gap: 8px; flex-wrap: nowrap; justify-content: center; align-items: center; width: 100%; }
    .form-actions .btn,
    .form-actions button { display: inline-flex; align-items: center; justify-content: center; margin: 0; padding: 9px 12px; min-height: 0; line-height: 1.25; }
    .login-panel { max-width: 440px; margin: 24px auto 0; }
    .login-form-actions { margin-top: 4px; }
    .landing-shell { padding: 4px 0 2px; }
    .landing-grid { display: grid; grid-template-columns: minmax(0, 1.08fr) minmax(340px, 0.92fr); gap: 22px; align-items: stretch; }
    .landing-panel { border-radius: 20px; overflow: hidden; min-height: 520px; }
    .landing-left { position: relative; padding: 40px 24px 24px 44px; color: #fff; background: radial-gradient(circle at 50% calc(100% + 300px), rgba(88, 88, 88, 0.92) 0, rgba(26, 26, 26, 0.98) 34%, #000 68%); box-shadow: 0 14px 28px rgba(0, 0, 0, 0.16); }
    .landing-left::after { content: ''; position: absolute; inset: 0; background: radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.06), transparent 40%); pointer-events: none; }
    .landing-brand-row { position: relative; z-index: 1; display: flex; align-items: center; gap: 14px; margin-bottom: 22px; }
    .landing-brand-logo { width: 92px; max-width: 100%; height: auto; flex: 0 0 auto; filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.35)); }
    .landing-kicker { font-size: 12px; text-transform: uppercase; letter-spacing: .12em; color: rgba(255, 255, 255, 0.78); margin-bottom: 4px; }
    .landing-brand-name { font-size: 14px; font-weight: 700; color: rgba(255, 255, 255, 0.92); }
    .landing-title { position: relative; z-index: 1; font-size: clamp(1.55rem, 2.46vw, 2.82rem); line-height: 1.05; margin: 0 0 12px; max-width: 12ch; }
    .landing-subtitle { position: relative; z-index: 1; font-size: 16px; line-height: 1.45; color: #e5e7eb; margin: 0 0 18px; max-width: 52ch; }
    .landing-copy-box { position: relative; z-index: 1; max-width: 580px; background: rgba(255, 255, 255, 0.08); border: 1px solid rgba(255, 255, 255, 0.14); border-radius: 16px; padding: 18px 18px 20px; color: #f3f4f6; line-height: 1.6; box-shadow: inset 0 1px 0 rgba(255,255,255,.05); }
    .landing-copy-box p { margin: 0; }
    .landing-right { min-height: 520px; display: flex; }
    .landing-login-card { position: relative; flex: 1; width: 100%; align-self: stretch; display: flex; align-items: center; justify-content: center; min-height: 100%; padding: 24px; background: linear-gradient(135deg, rgba(0,0,0,.82), rgba(11,95,255,.16)), url('/assets/landing-placeholder.svg') center/cover no-repeat; box-shadow: 0 14px 28px rgba(0,0,0,.12); }
    .landing-login-shell { position: relative; z-index: 1; width: min(350px, 100%); margin: 0 auto; background: rgba(255, 255, 255, 0.90); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,.58); border-radius: 16px; padding: 20px; box-shadow: 0 12px 24px rgba(0,0,0,.16); }
    .landing-login-shell h2 { margin: 0 0 4px; }
    .landing-login-shell .muted { margin-top: 0; }
    .field-error { color: #b00020; font-size: 12px; margin-top: -6px; margin-bottom: 10px; }
    .help { color: #6b7280; font-size: 13px; margin-top: -6px; margin-bottom: 12px; }
    .checkbox-line { display: flex; gap: 10px; align-items: center; margin: 0 0 12px; }
    .checkbox-line input { width: auto; margin: 0; }
    .checkbox-line span { font-weight: 600; }
    .nav-top { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 16px; }
    .nav-top .btn, .nav-top form { margin: 0; }
    .page-topbar { display: flex; justify-content: space-between; gap: 12px; align-items: center; margin-bottom: 16px; flex-wrap: wrap; }
    .page-topbar .greeting { font-weight: 700; color: #111827; }
    .page-topbar-actions { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; margin-left: auto; }
    .page-topbar-actions .btn { margin: 0; }
    .signature-box { margin-top: 18px; background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px 16px; color: #374151; font-size: 13px; line-height: 1.5; text-align: center; }
    .signature-content { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
    .signature-text { flex: 1 1 320px; min-width: 0; }
    .signature-brand { flex: 0 0 auto; display: flex; align-items: flex-end; justify-content: flex-end; margin-left: auto; }
    .signature-logo { display: block; width: 62px; max-width: 62px; height: auto; opacity: 0.95; }
    .signature-box strong { color: #111827; }
    .signature-link { color: #0b5fff; text-decoration: none; font-weight: 700; }
    .signature-link:hover { text-decoration: underline; }
    @media (max-width: 900px) { .profile-layout { grid-template-columns: 1fr; } .users-layout > .users-panel:last-child { justify-self: stretch; max-width: none; } .config-system-grid { grid-template-columns: 1fr; } .page-topbar { align-items: flex-start; } .page-topbar-actions { margin-left: 0; } .landing-grid { grid-template-columns: 1fr; } .landing-panel, .landing-right { min-height: auto; } .landing-login-card { padding: 16px; } .landing-login-shell { margin-left: 0; max-width: none; } }
  </style>
</head>
<body>
  <div class="card">
    <?php if (!empty($_SESSION['user_id'])): ?>
      <?php
      $displayName = trim((string)($_SESSION['full_name'] ?? $_SESSION['username'] ?? ''));
      $firstName = $displayName !== '' ? preg_split('/\s+/', $displayName)[0] : 'usuário';
      $firstName = $firstName !== '' ? $firstName : 'usuário';
      ?>
      <div class="page-topbar">
        <div class="greeting">Olá <?= htmlspecialchars($firstName, ENT_QUOTES, 'UTF-8') ?>,</div>
        <div class="page-topbar-actions">
          <a class="btn" href="/perfil">Perfil</a>
          <a class="btn btn-danger" href="/logout">Sair</a>
        </div>
      </div>
    <?php endif; ?>
    <?= $content ?? '' ?>
    <div class="signature-box">
      <div class="signature-content">
        <div class="signature-text">
          Joinville - Santa Catarina - Brasil | Contato <a class="signature-link" href="https://wa.me/554797618699" target="_blank" rel="noopener">+55 47 9761-8699</a> | E-mail <a class="signature-link" href="mailto:joao@bitmeback.com.br?subject=Contato%20Bitmeback&body=Ol%C3%A1%20Jo%C3%A3o" target="_blank" rel="noopener">joao@bitmeback.com.br</a> | © 2024-2026 Bitmeback Data Analysis | <a class="signature-link" href="https://bitmeback.com.br" target="_blank" rel="noopener">Bitmeback.com.br</a>
        </div>
        <div class="signature-brand">
          <img class="signature-logo" src="/assets/logo-bitmeback.jpg" alt="Bitmeback">
        </div>
      </div>
    </div>
  </div>
</body>
</html>
