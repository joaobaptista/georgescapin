<?php
$pageTitle = 'Configurações';
$activeTab = 'settings';
ob_start();
?>

<form method="POST" action="<?= url('/admin/settings/update') ?>">
  <?= csrf_field() ?>

  <div class="settings-layout">
    <!-- Coluna Principal (Configurações) -->
    <div class="settings-main-content">

      <!-- SEO & METATAGS -->
      <div class="admin-card" id="section-seo">
        <div class="admin-card-header">
          <h3 class="admin-card-title">Configurações de SEO & Google</h3>
        </div>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 20px;">Palavras-chave e títulos indexados pelos mecanismos de busca.</p>

        <div class="form-group">
          <label for="site_title">Título Global do Site (&lt;title&gt;)</label>
          <input type="text" id="site_title" name="site_title" class="form-control" value="<?= htmlspecialchars($settings['site_title'] ?? '') ?>">
        </div>

        <div class="form-group">
          <label for="meta_description">Meta Description (Resumo exibido no Google)</label>
          <textarea id="meta_description" name="meta_description" class="form-control" rows="2"><?= htmlspecialchars($settings['meta_description'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label for="meta_keywords">Meta Keywords (Palavras-chave separadas por vírgula)</label>
          <input type="text" id="meta_keywords" name="meta_keywords" class="form-control" value="<?= htmlspecialchars($settings['meta_keywords'] ?? '') ?>">
        </div>
      </div>

      <!-- CONTATO & ATENDIMENTO -->
      <div class="admin-card" id="section-contact">
        <div class="admin-card-header">
          <h3 class="admin-card-title">Dados de Contato & Redes Sociais</h3>
        </div>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 20px;">Telefone, WhatsApp, endereço, horários de atendimento e link do Instagram.</p>

        <div class="admin-grid-2col" style="gap: 20px;">
          <div class="form-group">
            <label for="contact_phone">Telefone de Atendimento</label>
            <input type="text" id="contact_phone" name="contact_phone" class="form-control" value="<?= htmlspecialchars($settings['contact_phone'] ?? '(51) 99824-4379') ?>">
          </div>
          <div class="form-group">
            <label for="contact_whatsapp">WhatsApp (com DDD, somente números)</label>
            <input type="text" id="contact_whatsapp" name="contact_whatsapp" class="form-control" value="<?= htmlspecialchars($settings['contact_whatsapp'] ?? '5551998244379') ?>">
          </div>
        </div>

        <div class="admin-grid-2col" style="gap: 20px;">
          <div class="form-group">
            <label for="contact_address">Endereço Completo</label>
            <input type="text" id="contact_address" name="contact_address" class="form-control" value="<?= htmlspecialchars($settings['contact_address'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label for="social_instagram">Link do Instagram</label>
            <input type="text" id="social_instagram" name="social_instagram" class="form-control" placeholder="https://instagram.com/drgeorgescapin" value="<?= htmlspecialchars($settings['social_instagram'] ?? 'https://instagram.com/drgeorgescapin') ?>">
          </div>
        </div>

        <div class="admin-grid-2col" style="gap: 20px;">
          <div class="form-group">
            <label for="contact_hours_week">Horário Dias Úteis</label>
            <input type="text" id="contact_hours_week" name="contact_hours_week" class="form-control" value="<?= htmlspecialchars($settings['contact_hours_week'] ?? 'Seg - Sex: 08h às 20h') ?>">
          </div>
          <div class="form-group">
            <label for="contact_hours_sat">Horário Sábados</label>
            <input type="text" id="contact_hours_sat" name="contact_hours_sat" class="form-control" value="<?= htmlspecialchars($settings['contact_hours_sat'] ?? 'Sáb: 09h às 13h') ?>">
          </div>
        </div>

        <div class="admin-grid-2col" style="gap: 20px; margin-top: 10px;">
          <div class="form-group">
            <label for="footer_tagline">Slogan / Texto do Rodapé</label>
            <input type="text" id="footer_tagline" name="footer_tagline" class="form-control" value="<?= htmlspecialchars($settings['footer_tagline'] ?? 'Estética facial de alta performance e sofisticação para realçar a sua melhor versão.') ?>">
          </div>
          <div class="form-group">
            <label for="footer_copyright">Texto de Copyright (use {year} para o ano atual)</label>
            <input type="text" id="footer_copyright" name="footer_copyright" class="form-control" value="<?= htmlspecialchars($settings['footer_copyright'] ?? '© {year} Dr. George Scapin. Todos os direitos reservados.') ?>">
          </div>
        </div>
      </div>

      <!-- CABEÇALHOS DAS PÁGINAS PADRÃO -->
      <div class="admin-card" id="section-headers">
        <div class="admin-card-header">
          <h3 class="admin-card-title">Cabeçalhos das Páginas Padrão</h3>
        </div>
        <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 20px;">Personalize os títulos e introduções exibidos no topo de cada página pública.</p>

        <!-- Procedimentos -->
        <div style="background: var(--bg-surface-hover); padding: 18px; border-radius: var(--radius-sm); border: 1px solid var(--border-light); margin-bottom: 18px;">
          <h4 style="font-size: 0.95rem; font-weight: 600; color: var(--text-primary); margin-bottom: 12px;">Página de Procedimentos (/procedimentos)</h4>
          <div class="admin-grid-1-2" style="gap: 15px;">
            <div class="form-group" style="margin: 0;">
              <label for="procedures_page_title">Título da Página</label>
              <input type="text" id="procedures_page_title" name="procedures_page_title" class="form-control" value="<?= htmlspecialchars($settings['procedures_page_title'] ?? 'Nossos Procedimentos') ?>">
            </div>
            <div class="form-group" style="margin: 0;">
              <label for="procedures_page_subtitle">Subtítulo / Descrição</label>
              <input type="text" id="procedures_page_subtitle" name="procedures_page_subtitle" class="form-control" value="<?= htmlspecialchars($settings['procedures_page_subtitle'] ?? 'Conheça os procedimentos desenhados para realçar o que você tem de melhor, com foco absoluto em proporção geométrica e elegância.') ?>">
            </div>
          </div>
        </div>

        <!-- Blog -->
        <div style="background: var(--bg-surface-hover); padding: 18px; border-radius: var(--radius-sm); border: 1px solid var(--border-light); margin-bottom: 18px;">
          <h4 style="font-size: 0.95rem; font-weight: 600; color: var(--text-primary); margin-bottom: 12px;">Página do Blog (/blog)</h4>
          <div class="admin-grid-1-2" style="gap: 15px; margin-bottom: 12px;">
            <div class="form-group" style="margin: 0;">
              <label for="blog_page_title">Título do Blog</label>
              <input type="text" id="blog_page_title" name="blog_page_title" class="form-control" value="<?= htmlspecialchars($settings['blog_page_title'] ?? 'Blog do Dr. George') ?>">
            </div>
            <div class="form-group" style="margin: 0;">
              <label for="blog_page_subtitle">Subtítulo do Blog</label>
              <input type="text" id="blog_page_subtitle" name="blog_page_subtitle" class="form-control" value="<?= htmlspecialchars($settings['blog_page_subtitle'] ?? 'Artigos, dicas e novidades sobre Estética Avançada, Gerenciamento do Envelhecimento e Harmonização.') ?>">
            </div>
          </div>
          <div class="admin-grid-1-2" style="gap: 15px;">
            <div class="form-group" style="margin: 0;">
              <label for="blog_cta_title">Título do CTA Final dos Artigos</label>
              <input type="text" id="blog_cta_title" name="blog_cta_title" class="form-control" value="<?= htmlspecialchars($settings['blog_cta_title'] ?? 'Gostou do conteúdo?') ?>">
            </div>
            <div class="form-group" style="margin: 0;">
              <label for="blog_cta_text">Texto de Apoio do CTA nos Artigos</label>
              <input type="text" id="blog_cta_text" name="blog_cta_text" class="form-control" value="<?= htmlspecialchars($settings['blog_cta_text'] ?? 'Agende uma consulta de avaliação personalizada com o Dr. George Scapin em Porto Alegre.') ?>">
            </div>
          </div>
        </div>

        <!-- Contato -->
        <div style="background: var(--bg-surface-hover); padding: 18px; border-radius: var(--radius-sm); border: 1px solid var(--border-light);">
          <h4 style="font-size: 0.95rem; font-weight: 600; color: var(--text-primary); margin-bottom: 12px;">Página de Contato (/contato)</h4>
          <div class="admin-grid-1-2" style="gap: 15px;">
            <div class="form-group" style="margin: 0;">
              <label for="contact_page_title">Título da Página de Contato</label>
              <input type="text" id="contact_page_title" name="contact_page_title" class="form-control" value="<?= htmlspecialchars($settings['contact_page_title'] ?? 'Agende sua Consulta') ?>">
            </div>
            <div class="form-group" style="margin: 0;">
              <label for="contact_page_subtitle">Subtítulo / Mensagem Introdutória</label>
              <input type="text" id="contact_page_subtitle" name="contact_page_subtitle" class="form-control" value="<?= htmlspecialchars($settings['contact_page_subtitle'] ?? 'Dê o primeiro passo para elevar sua autoestima. Preencha o formulário ou entre em contato diretamente conosco.') ?>">
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Sidebar Direita -->
    <aside class="settings-sidebar">
      <!-- Card de Ação / Salvar -->
      <div class="admin-card">
        <div class="admin-card-header">
          <h3 class="admin-card-title" style="font-size: 1rem;"><i data-lucide="save" size="18" style="color: var(--pastel-blue-accent);"></i> Publicação</h3>
        </div>
        <p style="font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 16px; line-height: 1.4;">
          Salve as configurações globais, tags de SEO e dados de atendimento.
        </p>
        <button type="submit" class="btn-admin btn-primary" style="width: 100%; justify-content: center; padding: 12px 18px; font-size: 0.92rem;">
          <i data-lucide="check" size="18"></i> Salvar Alterações
        </button>
        <div style="margin-top: 14px; font-size: 0.78rem; color: var(--text-muted); display: flex; align-items: flex-start; gap: 6px; line-height: 1.4;">
          <i data-lucide="info" size="14" style="flex-shrink: 0; margin-top: 2px; color: var(--pastel-blue-accent);"></i>
          <span>As alterações entram em vigor imediatamente no site público.</span>
        </div>
      </div>

      <!-- Card de Navegação Rápida -->
      <div class="admin-card">
        <div class="admin-card-header">
          <h3 class="admin-card-title" style="font-size: 0.95rem;"><i data-lucide="compass" size="16" style="color: var(--pastel-blue-accent);"></i> Nesta Página</h3>
        </div>
        <nav class="settings-nav-list">
          <a href="#section-seo" class="settings-nav-item">
            <i data-lucide="search" size="16"></i>
            <span>SEO & Google</span>
          </a>
          <a href="#section-contact" class="settings-nav-item">
            <i data-lucide="phone" size="16"></i>
            <span>Contato & Redes</span>
          </a>
          <a href="#section-headers" class="settings-nav-item">
            <i data-lucide="layout-template" size="16"></i>
            <span>Cabeçalhos Padrão</span>
          </a>
        </nav>
      </div>

      <!-- Card de Status / Dicas -->
      <div class="admin-card" style="background: var(--bg-surface-hover); border: 1px dashed var(--border-light);">
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px; font-weight: 600; font-size: 0.88rem; color: var(--text-primary);">
          <i data-lucide="lightbulb" size="16" style="color: #f59e0b;"></i> Dica de SEO
        </div>
        <p style="font-size: 0.8rem; color: var(--text-secondary); line-height: 1.5; margin: 0;">
          Mantenha o título com até <strong>60 caracteres</strong> e a descrição com até <strong>160 caracteres</strong> para melhor relevância e indexação no Google.
        </p>
      </div>
    </aside>
  </div>
</form>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
