<?php
declare(strict_types=1);

/**
 * Login e cadastro públicos. Incluído por index.php somente para visitantes.
 * $error e $next são definidos pelo controlador.
 */
$logo = \App\Cms::setting('logo');
$favicon = \App\Cms::setting('favicon');
$attemptedAction = (string) ($_POST['action'] ?? '');
$registerActive = $attemptedAction === 'register';
$enteredEmail = (string) ($_POST['email'] ?? '');
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#f98017">
  <title>Entrar ou criar conta | Restaurante da Bia</title>
  <?php if ($favicon): ?><link rel="icon" href="<?=bia_esc($favicon)?>"><?php endif; ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Open+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="/assets/css/admin-login.css?v=2" rel="stylesheet">
  <link href="/assets/css/customer-login.css?v=1" rel="stylesheet">
</head>
<body class="auth-body customer-auth-body">
  <main class="bia-login" aria-labelledby="customer-login-title">
    <section class="bia-login-card customer-login-card">
      <a class="bia-login-brand" href="/" aria-label="Voltar ao site do Restaurante da Bia">
        <?php if ($logo): ?>
          <img src="<?=bia_esc($logo)?>" alt="Restaurante da Bia">
        <?php else: ?>
          <span class="bia-login-brand-fallback">Restaurante <strong>da Bia</strong></span>
        <?php endif; ?>
      </a>

      <header class="bia-login-form-heading customer-login-heading">
        <span class="bia-login-kicker">ÁREA DO CLIENTE</span>
        <h1 id="customer-login-title">Minha conta</h1>
        <p>Acompanhe seus pedidos, converse com a equipe e salve seus endereços.</p>
      </header>

      <?php if ($error): ?>
        <div class="bia-login-error" role="alert">
          <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
          <span><?=bia_esc($error)?></span>
        </div>
      <?php endif; ?>

      <div class="customer-login-tabs" role="tablist" aria-label="Acesso do cliente">
        <button type="button" id="customer-login-tab" role="tab"
          aria-controls="customer-login-panel"
          aria-selected="<?=$registerActive ? 'false' : 'true'?>"
          class="<?=$registerActive ? '' : 'active'?>"
          tabindex="<?=$registerActive ? '-1' : '0'?>">Entrar</button>
        <button type="button" id="customer-register-tab" role="tab"
          aria-controls="customer-register-panel"
          aria-selected="<?=$registerActive ? 'true' : 'false'?>"
          class="<?=$registerActive ? 'active' : ''?>"
          tabindex="<?=$registerActive ? '0' : '-1'?>">Criar conta</button>
      </div>

      <section id="customer-login-panel" role="tabpanel" aria-labelledby="customer-login-tab" <?=$registerActive ? 'hidden' : ''?>>
        <form method="post" class="bia-login-form customer-login-form">
          <input type="hidden" name="csrf" value="<?=bia_esc(\App\Customer::csrf())?>">
          <input type="hidden" name="action" value="login">
          <div class="bia-login-field">
            <label for="customer-login-email">E-mail</label>
            <div class="bia-login-input-wrap">
              <i class="bi bi-envelope" aria-hidden="true"></i>
              <input type="email" name="email" id="customer-login-email" autocomplete="username"
                maxlength="190" placeholder="seuemail@exemplo.com"
                value="<?=!$registerActive ? bia_esc($enteredEmail) : ''?>" required>
            </div>
          </div>
          <div class="bia-login-field">
            <label for="customer-login-password">Senha</label>
            <div class="bia-login-input-wrap bia-login-password-wrap">
              <i class="bi bi-lock" aria-hidden="true"></i>
              <input type="password" name="password" id="customer-login-password"
                autocomplete="current-password" placeholder="Digite sua senha" required>
              <button type="button" class="bia-login-password-toggle"
                data-customer-password="customer-login-password"
                aria-label="Mostrar senha" aria-pressed="false">
                <i class="bi bi-eye" aria-hidden="true"></i>
              </button>
            </div>
          </div>
          <button type="submit" class="bia-login-submit"><span>Entrar na minha conta</span><i class="bi bi-arrow-right" aria-hidden="true"></i></button>
        </form>
      </section>

      <section id="customer-register-panel" role="tabpanel" aria-labelledby="customer-register-tab" <?=$registerActive ? '' : 'hidden'?>>
        <form method="post" class="bia-login-form customer-login-form">
          <input type="hidden" name="csrf" value="<?=bia_esc(\App\Customer::csrf())?>">
          <input type="hidden" name="action" value="register">
          <div class="bia-login-field">
            <label for="customer-register-email">E-mail</label>
            <div class="bia-login-input-wrap">
              <i class="bi bi-envelope" aria-hidden="true"></i>
              <input type="email" name="email" id="customer-register-email" autocomplete="email"
                maxlength="190" placeholder="seuemail@exemplo.com"
                value="<?=$registerActive ? bia_esc($enteredEmail) : ''?>" required>
            </div>
          </div>
          <div class="bia-login-field">
            <label for="customer-register-password">Crie sua senha</label>
            <div class="bia-login-input-wrap bia-login-password-wrap">
              <i class="bi bi-lock" aria-hidden="true"></i>
              <input type="password" name="password" id="customer-register-password"
                autocomplete="new-password" minlength="10" maxlength="128"
                placeholder="Mínimo de 10 caracteres" required>
              <button type="button" class="bia-login-password-toggle"
                data-customer-password="customer-register-password"
                aria-label="Mostrar senha" aria-pressed="false">
                <i class="bi bi-eye" aria-hidden="true"></i>
              </button>
            </div>
            <small class="customer-password-hint">Use uma senha entre 10 e 128 caracteres.</small>
          </div>
          <button type="submit" class="bia-login-submit"><span>Criar minha conta</span><i class="bi bi-arrow-right" aria-hidden="true"></i></button>
        </form>
      </section>

      <a class="bia-login-back" href="/"><i class="bi bi-arrow-left" aria-hidden="true"></i> Voltar ao site</a>
    </section>
  </main>
  <script src="/assets/js/customer-access.js?v=2" defer></script>
</body>
</html>
