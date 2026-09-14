<?php
$pageTitle = 'Conteúdos do Site & SEO';
ob_start();
?>

<form method="POST" action="<?= url('/admin/settings/update') ?>">
  <?= csrf_field() ?>

  <!-- Seção Hero -->
  <div class="admin-card">
    <div style="margin-bottom: 20px;">
      <h3 style="color: var(--gold-light); font-size: 1.3rem;">1. Seção Principal (Hero da Home)</h3>
      <p style="color: var(--text-muted); font-size: 0.85rem;">Textos e botões de destaque exibidos no topo da página inicial.</p>
    </div>

    <div class="form-group">
      <label for="hero_title">Título Principal (Hero Title)</label>
      <input type="text" id="hero_title" name="hero_title" class="form-control" value="<?= htmlspecialchars($hero['title'] ?? '') ?>">
      <small style="color: var(--text-muted);">Use &lt;br&gt; para quebra de linha se desejar.</small>
    </div>

    <div class="form-group">
      <label for="hero_subtitle">Subtítulo do Hero</label>
      <textarea id="hero_subtitle" name="hero_subtitle" class="form-control" rows="2"><?= htmlspecialchars($hero['subtitle'] ?? '') ?></textarea>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
      <div class="form-group">
        <label for="hero_button_text">Texto do Botão Principal</label>
        <input type="text" id="hero_button_text" name="hero_button_text" class="form-control" value="<?= htmlspecialchars($hero['button_text'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="hero_button_link">Link do Botão</label>
        <input type="text" id="hero_button_link" name="hero_button_link" class="form-control" value="<?= htmlspecialchars($hero['button_link'] ?? '/contato') ?>">
      </div>
    </div>
  </div>

  <!-- Seção A Clínica -->
  <div class="admin-card">
    <div style="margin-bottom: 20px;">
      <h3 style="color: var(--gold-light); font-size: 1.3rem;">2. Página "A Clínica"</h3>
      <p style="color: var(--text-muted); font-size: 0.85rem;">Conteúdo institucional, parágrafos e frase em destaque.</p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
      <div class="form-group">
        <label for="clinic_title">Título da Página</label>
        <input type="text" id="clinic_title" name="clinic_title" class="form-control" value="<?= htmlspecialchars($clinic['title'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="clinic_subtitle">Subtítulo / Chamada</label>
        <input type="text" id="clinic_subtitle" name="clinic_subtitle" class="form-control" value="<?= htmlspecialchars($clinic['subtitle'] ?? '') ?>">
      </div>
    </div>

    <div class="form-group">
      <label for="clinic_p1">Primeiro Parágrafo</label>
      <textarea id="clinic_p1" name="clinic_p1" class="form-control" rows="3"><?= htmlspecialchars($clinic['paragraph_1'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
      <label for="clinic_p2">Segundo Parágrafo</label>
      <textarea id="clinic_p2" name="clinic_p2" class="form-control" rows="3"><?= htmlspecialchars($clinic['paragraph_2'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
      <label for="clinic_quote">Citação em Destaque (Frase de Efeito)</label>
      <input type="text" id="clinic_quote" name="clinic_quote" class="form-control" value="<?= htmlspecialchars($clinic['highlight_quote'] ?? '') ?>">
    </div>
  </div>

  <!-- Contato & Atendimento -->
  <div class="admin-card">
    <div style="margin-bottom: 20px;">
      <h3 style="color: var(--gold-light); font-size: 1.3rem;">3. Dados de Contato & Rodapé</h3>
      <p style="color: var(--text-muted); font-size: 0.85rem;">Telefone, WhatsApp, endereço e horários de atendimento da clínica.</p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
      <div class="form-group">
        <label for="contact_phone">Telefone de Atendimento</label>
        <input type="text" id="contact_phone" name="contact_phone" class="form-control" value="<?= htmlspecialchars($settings['contact_phone'] ?? '(51) 99824-4379') ?>">
      </div>
      <div class="form-group">
        <label for="contact_whatsapp">WhatsApp (com DDD, somente números)</label>
        <input type="text" id="contact_whatsapp" name="contact_whatsapp" class="form-control" value="<?= htmlspecialchars($settings['contact_whatsapp'] ?? '5551998244379') ?>">
      </div>
    </div>

    <div class="form-group">
      <label for="contact_address">Endereço Completo</label>
      <input type="text" id="contact_address" name="contact_address" class="form-control" value="<?= htmlspecialchars($settings['contact_address'] ?? '') ?>">
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
      <div class="form-group">
        <label for="contact_hours_week">Horário Dias Úteis</label>
        <input type="text" id="contact_hours_week" name="contact_hours_week" class="form-control" value="<?= htmlspecialchars($settings['contact_hours_week'] ?? 'Seg - Sex: 08h às 20h') ?>">
      </div>
      <div class="form-group">
        <label for="contact_hours_sat">Horário Sábados</label>
        <input type="text" id="contact_hours_sat" name="contact_hours_sat" class="form-control" value="<?= htmlspecialchars($settings['contact_hours_sat'] ?? 'Sáb: 09h às 13h') ?>">
      </div>
    </div>
  </div>

  <!-- SEO & Metatags -->
  <div class="admin-card">
    <div style="margin-bottom: 20px;">
      <h3 style="color: var(--gold-light); font-size: 1.3rem;">4. Configurações de SEO & Google</h3>
      <p style="color: var(--text-muted); font-size: 0.85rem;">Palavras-chave e títulos indexados pelos mecanismos de busca.</p>
    </div>

    <div class="form-group">
      <label for="site_title">Título Global do Site (&lt;title&gt;)</label>
      <input type="text" id="site_title" name="site_title" class="form-control" value="<?= htmlspecialchars($settings['site_title'] ?? '') ?>">
    </div>

    <div class="form-group">
      <label for="meta_description">Meta Description (Resumo no Google)</label>
      <textarea id="meta_description" name="meta_description" class="form-control" rows="2"><?= htmlspecialchars($settings['meta_description'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
      <label for="meta_keywords">Meta Keywords (Palavras-chave separadas por vírgula)</label>
      <input type="text" id="meta_keywords" name="meta_keywords" class="form-control" value="<?= htmlspecialchars($settings['meta_keywords'] ?? '') ?>">
    </div>
  </div>

  <div style="margin-top: 20px; margin-bottom: 50px;">
    <button type="submit" class="btn-primary btn-solid" style="padding: 14px 40px; font-size: 1rem;">
      <i data-lucide="check" size="20"></i> Salvar Todas as Configurações
    </button>
  </div>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
