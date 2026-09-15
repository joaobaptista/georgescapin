<?php
$pageTitle = 'Meu Perfil & Segurança';
ob_start();
?>

<div class="admin-card" style="max-width: 650px;">
  <form method="POST" action="<?= url('/admin/profile/update') ?>">
    <?= csrf_field() ?>

    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin-bottom: 16px;">
      Dados Pessoais
    </h3>

    <div class="form-group">
      <label for="name">Nome Completo *</label>
      <input type="text" id="name" name="name" class="form-control" required value="<?= htmlspecialchars($userData['name'] ?? $user['name']) ?>">
    </div>

    <div class="form-group">
      <label for="email">E-mail de Acesso *</label>
      <input type="email" id="email" name="email" class="form-control" required value="<?= htmlspecialchars($userData['email'] ?? $user['email']) ?>">
    </div>

    <hr style="border: 0; border-top: 1px solid var(--border-light); margin: 28px 0 24px 0;">

    <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--text-main); margin-bottom: 6px;">
      Alterar Senha
    </h3>
    <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 16px;">Deixe os campos em branco caso não deseje alterar sua senha atual.</p>

    <div class="form-group">
      <label for="current_password">Senha Atual (obrigatória para alteração)</label>
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

    <div style="margin-top: 28px;">
      <button type="submit" class="btn-primary btn-solid" style="padding: 10px 24px;">
        <i data-lucide="shield-check" size="16"></i> Salvar Alterações
      </button>
    </div>
  </form>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
