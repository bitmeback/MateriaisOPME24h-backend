<?php
declare(strict_types=1);
ob_start();
?>
<div class="landing-shell">
  <section class="landing-grid">
    <div class="landing-left landing-panel">
      <div class="landing-brand-row">
        <img class="landing-brand-logo" src="/assets/logo-bitmeback.jpg" alt="Bitmeback">
        <div>
          <div class="landing-kicker">Materiais OPME</div>
          <div class="landing-brand-name">Bitmeback Data Analysis</div>
        </div>
      </div>

      <h1 class="landing-title">Portal de administração e notificações de Materiais OPME</h1>
      <p class="landing-subtitle">Central de acesso para gestão, acompanhamento e envio de informações operacionais do setor.</p>

      <div class="landing-copy-box">
        <p>Use este ambiente para consultar fornecedores, administrar acessos e organizar as rotinas de notificação com uma experiência mais clara e direta.</p>
      </div>
    </div>

    <div class="landing-right landing-panel">
      <div class="landing-login-card">
        <div class="landing-login-shell">
          <h2>Acesso ao sistema</h2>
          <p class="muted">Entre com suas credenciais para continuar.</p>
          <?php require __DIR__ . '/partials/login_form.php'; ?>
        </div>
      </div>
    </div>
  </section>
</div>
<?php
$content = ob_get_clean();
$title = 'Materiais Opme';
require __DIR__ . '/layout.php';
