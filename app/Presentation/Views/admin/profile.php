<?php
$pageTitle = 'Meu Perfil & Segurança';
ob_start();
?>

<div class="admin-card" style="max-width: 600px;">
  <div style="margin-bottom: 25px;">
    <h3 style="color: var(--gold-light); font-size: 1.3rem;">Dados do Administrador</h3>
    <p style="color: var(--text-muted); font-size: 0.85rem;">Gerencie seu nome, e-mail de acesso e altere sua senha.</p>
  </div>

  <form method="POST" action="<?= url('/admin/profile/update') ?>">
    <?= csrf_field() ?>

    <div class="form-group">
      <label for="name">Nome Completo</label>
      <input type="text" id="name" name="name" class="form-control" required value="<?= htmlspecialchars($userData['name'] ?? $user['name']) ?>">
    </div>

    <div class="form-group">
      <label for="email">E-mail de Login</label>
      <input type="email" id="email" name="email" class="form-control" required value="<?= htmlspecialchars($userData['email'] ?? $user['email']) ?>">
    </div>

    <hr style="border: 0; border-top: 1px solid var(--card-border); margin: 30px 0;">

    <h4 style="color: var(--gold-light); margin-bottom: 15px; font-family: var(--font-serif);">Alterar Senha</h4>
    <p style="color: var(--text-muted); font-size: 0.8rem; margin-bottom: 15px;">Deixe em branco caso não deseje alterar sua senha atual.</p>

    <div class="form-group">
      <label for="current_password">Senha Atual (necessária para alterar a senha)</label>
      <input type="password" id="current_password" name="current_password" class="form-control" placeholder="Digite sua senha atual">
    </div>

    <div class="form-group">
      <label for="new_password">Nova Senha</label>
      <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Mínimo 6 caracteres">
    </div>

    <div class="form-group">
      <label for="confirm_password">Confirmar Nova Senha</label>
      <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Repita a nova senha">
    </div>

    <div style="margin-top: 30px;">
      <button type="submit" class="btn-primary btn-solid" style="padding: 12px 30px;">
        <i data-lucide="shield-check" size="18"></i> Salvar Alterações
      </button>
    </div>
  </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
