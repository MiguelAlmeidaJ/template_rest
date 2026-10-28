<?php
declare(strict_types=1);

require __DIR__ . '/includes/cms-boot.php';

use App\Db;

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    cms_csrf();

    $current = (string) ($_POST['current_password'] ?? '');
    $new = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    try {
        if (strlen($new) < 10 || strlen($new) > 128) {
            throw new DomainException('A nova senha deve ter entre 10 e 128 caracteres.');
        }
        if (!hash_equals($new, $confirm)) {
            throw new DomainException('A confirmação não corresponde à nova senha.');
        }

        $db = Db::pdo();
        $db->beginTransaction();

        try {
            $query = $db->prepare('SELECT id, password_hash, customer_id FROM admins WHERE id = ? FOR UPDATE');
            $query->execute([(int) $staff['id']]);
            $account = $query->fetch();

            if (!$account || !password_verify($current, $account['password_hash'])) {
                throw new DomainException('Senha atual incorreta.');
            }
            if (password_verify($new, $account['password_hash'])) {
                throw new DomainException('Escolha uma senha diferente da atual.');
            }

            $hash = password_hash($new, PASSWORD_DEFAULT);
            $query = $db->prepare('UPDATE admins SET password_hash = ? WHERE id = ?');
            $query->execute([$hash, (int) $staff['id']]);

            if ($account['customer_id']) {
                $query = $db->prepare('UPDATE customer_accounts SET password_hash = ? WHERE id = ?');
                $query->execute([$hash, (int) $account['customer_id']]);
            }

            $db->commit();
            $success = true;
        } catch (Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    } catch (DomainException $exception) {
        $error = $exception->getMessage();
    } catch (Throwable $exception) {
        error_log((string) $exception);
        $error = 'Não foi possível alterar a senha.';
    }
}

adm_start('Alterar minha senha', 'profile');
?>
<div class="admin-profile-page">
  <div class="mb-4">
    <span class="page-kicker">SEGURANÇA DA CONTA</span>
    <h1 class="page-title">Alterar minha senha</h1>
    <p class="page-subtitle">Atualize sua senha de acesso ao painel administrativo.</p>
  </div>
  <section class="content-card admin-profile-card">
    <div class="admin-profile-heading">
      <span class="admin-profile-icon"><i class="bi bi-shield-lock" aria-hidden="true"></i></span>
      <div><h2>Senha de acesso</h2><p><?=adm_h($staff['email'])?></p></div>
    </div>
    <?php if ($success): ?><div class="alert alert-success" role="status">Senha alterada com sucesso.</div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-danger" role="alert"><?=adm_h($error)?></div><?php endif; ?>
    <form method="post" class="admin-profile-form" autocomplete="on">
      <input type="hidden" name="csrf" value="<?=adm_h($csrf)?>">
      <label for="current-password">Senha atual</label>
      <input id="current-password" class="form-control" type="password" name="current_password" autocomplete="current-password" required>
      <label for="new-password">Nova senha</label>
      <input id="new-password" class="form-control" type="password" name="new_password" autocomplete="new-password" minlength="10" maxlength="128" required>
      <small>Use pelo menos 10 caracteres.</small>
      <label for="confirm-password">Confirmar nova senha</label>
      <input id="confirm-password" class="form-control" type="password" name="confirm_password" autocomplete="new-password" minlength="10" maxlength="128" required>
      <div class="admin-profile-actions">
        <a class="btn btn-outline-secondary" href="/admin/">Cancelar</a>
        <button class="btn btn-bia" type="submit">Salvar nova senha</button>
      </div>
    </form>
  </section>
</div>
<?php adm_end(); ?>
