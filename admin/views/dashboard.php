<?php
$pageTitle = 'Dashboard Geral';
ob_start();
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon"><i data-lucide="message-square" size="24"></i></div>
    <div>
      <div class="stat-val"><?= count($contacts) ?></div>
      <div class="stat-label">Mensagens de Contato</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon"><i data-lucide="bell" size="24"></i></div>
    <div>
      <div class="stat-val" style="color: #25D366;"><?= $newLeadsCount ?></div>
      <div class="stat-label">Novos Leads Pendentes</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon"><i data-lucide="sparkles" size="24"></i></div>
    <div>
      <div class="stat-val"><?= count($procedures) ?></div>
      <div class="stat-label">Procedimentos Ativos</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon"><i data-lucide="newspaper" size="24"></i></div>
    <div>
      <div class="stat-val"><?= count($posts ?? []) ?></div>
      <div class="stat-label">Artigos no Blog</div>
    </div>
  </div>

  <div class="stat-card">
    <div class="stat-icon"><i data-lucide="mail" size="24"></i></div>
    <div>
      <div class="stat-val"><?= count($subscribers) ?></div>
      <div class="stat-label">Assinantes Newsletter</div>
    </div>
  </div>
</div>

<div class="admin-card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
    <h3 style="color: var(--gold-light); font-size: 1.3rem;">Últimos Contatos Recebidos</h3>
    <a href="<?= url('/admin/leads') ?>" class="btn-primary" style="padding: 8px 18px; font-size: 0.8rem;">Ver Todos os Leads</a>
  </div>

  <?php if (empty($contacts)): ?>
    <p style="color: var(--text-muted); text-align: center; padding: 30px;">Nenhuma mensagem de contato recebida ainda.</p>
  <?php else: ?>
    <table class="admin-table">
      <thead>
        <tr>
          <th>Data</th>
          <th>Nome</th>
          <th>Telefone</th>
          <th>Mensagem</th>
          <th>Status</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach (array_slice($contacts, 0, 5) as $c): ?>
          <tr>
            <td style="color: var(--text-muted); font-size: 0.8rem;"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
            <td><strong><?= htmlspecialchars($c['nome']) ?></strong></td>
            <td><?= htmlspecialchars($c['telefone']) ?></td>
            <td style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?= htmlspecialchars($c['mensagem']) ?></td>
            <td>
              <span class="badge <?= $c['status'] === 'novo' ? 'badge-warning' : ($c['status'] === 'atendido' ? 'badge-success' : 'badge-muted') ?>">
                <?= ucfirst($c['status']) ?>
              </span>
            </td>
            <td>
              <a href="https://wa.me/55<?= preg_replace('/\D/', '', $c['telefone']) ?>?text=Olá%20<?= urlencode($c['nome']) ?>,%20recebemos%20sua%20mensagem%20no%20site%20do%20Dr.%20George%20Scapin." target="_blank" class="btn-primary" style="padding: 4px 10px; font-size: 0.75rem; background: #25D366; border-color: #25D366; color: #fff;">
                <i data-lucide="message-circle" size="14"></i> WhatsApp
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="admin-card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px;">
    <h3 style="color: var(--gold-light); font-size: 1.3rem;">Procedimentos Cadastrados</h3>
    <a href="<?= url('/admin/procedures/create') ?>" class="btn-primary" style="padding: 8px 18px; font-size: 0.8rem;">+ Novo Procedimento</a>
  </div>

  <table class="admin-table">
    <thead>
      <tr>
        <th>Ordem</th>
        <th>Imagem</th>
        <th>Título</th>
        <th>Ícone</th>
        <th>Status</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($procedures as $p): ?>
        <tr>
          <td><?= $p['sort_order'] ?></td>
          <td><img src="<?= asset($p['image_url'] ?: 'assets/images/botox.png') ?>" style="width: 50px; height: 35px; object-fit: cover; border-radius: 4px;"></td>
          <td><strong><?= htmlspecialchars($p['title']) ?></strong></td>
          <td><i data-lucide="<?= htmlspecialchars($p['icon_name'] ?: 'sparkles') ?>" size="18" style="color: var(--gold-primary);"></i></td>
          <td>
            <span class="badge <?= $p['is_active'] ? 'badge-success' : 'badge-muted' ?>">
              <?= $p['is_active'] ? 'Ativo' : 'Inativo' ?>
            </span>
          </td>
          <td>
            <a href="<?= url('/admin/procedures/edit/' . $p['id']) ?>" style="color: var(--gold-primary); margin-right: 15px; text-decoration: none;"><i data-lucide="edit" size="16"></i> Editar</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
