  <footer id="footer">
    <div class="footer-grid">
      <div class="footer-brand">
        <div class="logo-svg" style="background-image: url('<?= asset('assets/images/logo.svg') ?>'); background-position: left center;"></div>
        <p style="margin-top: 15px; font-size: 0.9rem; color: var(--text-muted); line-height: 1.6;">
          <?= nl2br(htmlspecialchars($settings['footer_tagline'] ?? "Estética facial de alta performance e sofisticação para realçar a sua melhor versão.")) ?>
        </p>
        
        <?php if (!empty($settings['social_instagram'])): ?>
          <div style="margin-top: 12px; margin-bottom: 12px;">
            <a href="<?= htmlspecialchars($settings['social_instagram']) ?>" target="_blank" rel="noopener noreferrer" style="color: var(--gold-primary); text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; font-weight: 500;">
              <i data-lucide="instagram" size="18"></i> Instagram Oficial
            </a>
          </div>
        <?php endif; ?>

        <div class="footer-social" style="justify-content: flex-start; margin-top: 10px;">
          <form id="newsletterForm" class="newsletter-form" onsubmit="handleNewsletter(event)">
            <input type="email" name="email" placeholder="Assine nossa Newsletter" required>
            <button type="submit" aria-label="Assinar"><i data-lucide="send" size="18"></i></button>
          </form>
        </div>
      </div>

      <div class="footer-links">
        <h4>Navegação</h4>
        <nav class="footer-nav">
          <?php 
            $footerMenuRepo = new \App\Infrastructure\Repositories\PDOMenuRepository();
            $footerMenuItems = $footerMenuRepo->getAll(true);
          ?>
          <?php if (!empty($footerMenuItems)): ?>
            <?php foreach ($footerMenuItems as $fm): ?>
              <a href="<?= (strpos($fm['url'], 'http') === 0) ? $fm['url'] : url($fm['url']) ?>" target="<?= htmlspecialchars($fm['target'] ?? '_self') ?>">
                <?= htmlspecialchars($fm['label']) ?>
              </a>
            <?php endforeach; ?>
          <?php else: ?>
            <a href="<?= url('/') ?>">Início</a>
            <a href="<?= url('/clinica') ?>">George Scapin</a>
            <a href="<?= url('/procedimentos') ?>">Procedimentos</a>
            <a href="<?= url('/harmonizacao-facial') ?>">Harmonização Facial</a>
            <a href="<?= url('/contato') ?>">Contato</a>
            <a href="<?= url('/blog') ?>">Blog</a>
          <?php endif; ?>
          <a href="<?= url('/admin/login') ?>" style="opacity: 0.4; font-size: 0.75rem; margin-top: 10px;">Área Restrita</a>
        </nav>
      </div>

      <div class="footer-contact">
        <h4>Atendimento</h4>
        <div class="contact-item-mini">
          <i data-lucide="map-pin" size="18"></i>
          <p><?= nl2br(htmlspecialchars($settings['contact_address'] ?? "Av. Ipiranga, 40, sala 1512 - Praia de Belas\nPorto Alegre - RS | CEP 90160-090")) ?></p>
        </div>
        <div class="contact-item-mini">
          <i data-lucide="phone" size="18"></i>
          <p><?= htmlspecialchars($settings['contact_phone'] ?? '(51) 99824-4379') ?></p>
        </div>
        <div class="contact-item-mini">
          <i data-lucide="clock" size="18"></i>
          <p><?= htmlspecialchars($settings['contact_hours_week'] ?? 'Seg - Sex: 08h às 20h') ?><br><?= htmlspecialchars($settings['contact_hours_sat'] ?? 'Sáb: 09h às 13h') ?></p>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <p><?= htmlspecialchars(str_replace('{year}', date('Y'), $settings['footer_copyright'] ?? ('© ' . date('Y') . ' Dr. George Scapin. Todos os direitos reservados.'))) ?></p>
    </div>
  </footer>

  <!-- WhatsApp Floating Button -->
  <?php if (!empty($settings['contact_whatsapp'])): ?>
    <a href="https://wa.me/<?= preg_replace('/\D/', '', $settings['contact_whatsapp']) ?>?text=Ol%C3%A1!%20Gostaria%20de%20agendar%20uma%20consulta%20com%20o%20Dr.%20George%20Scapin." target="_blank" rel="noopener noreferrer" style="position:fixed;bottom:25px;right:25px;background:#25D366;color:#fff;width:55px;height:55px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 15px rgba(0,0,0,0.3);z-index:9999;transition:transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'" aria-label="Falar no WhatsApp">
      <i data-lucide="message-circle" size="30"></i>
    </a>
  <?php endif; ?>

  <script src="<?= asset('assets/js/app.js') ?>"></script>
  <script>
    lucide.createIcons();

    // Theme Logic
    const themeToggleBtn = document.getElementById('themeToggle');
    function updateThemeIcon() {
      if (!themeToggleBtn) return;
      const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
      themeToggleBtn.innerHTML = isDark ? '<i data-lucide="sun" size="24"></i>' : '<i data-lucide="moon" size="24"></i>';
      const label = isDark ? 'Alternar para Modo Claro' : 'Alternar para Modo Escuro';
      themeToggleBtn.setAttribute('title', label);
      themeToggleBtn.setAttribute('aria-label', label);
      lucide.createIcons();
    }
    updateThemeIcon();
    
    if (themeToggleBtn) {
      themeToggleBtn.addEventListener('click', () => {
        let current = document.documentElement.getAttribute('data-theme') || 'dark';
        let next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        updateThemeIcon();
      });
    }

    // Mobile Menu
    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.getElementById('navMenu');
    if (menuToggle && navMenu) {
      menuToggle.addEventListener('click', () => {
        navMenu.classList.toggle('active');
      });
    }

    // Header Scroll Effect
    const header = document.getElementById('header');
    window.addEventListener('scroll', () => {
      if (window.scrollY > 50) header.classList.add('scrolled');
      else header.classList.remove('scrolled');
    });

    // Newsletter Ajax
    function handleNewsletter(event) {
      event.preventDefault();
      const form = event.target;
      const formData = new FormData(form);
      const btn = form.querySelector('button');
      const original = btn.innerHTML;
      btn.innerHTML = '...';

      fetch('<?= url('/newsletter/assinar') ?>', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert(data.message || 'E-mail cadastrado com sucesso!');
          form.reset();
        } else {
          alert('Erro: ' + (data.error || 'Não foi possível cadastrar.'));
        }
      })
      .catch(err => {
        console.error(err);
        alert('Erro ao enviar. Tente novamente.');
      })
      .finally(() => {
        btn.innerHTML = original;
        lucide.createIcons();
      });
    }
  </script>
</body>
</html>
