<?php require __DIR__ . '/../layouts/site_header.php'; ?>

  <div class="page-header">
    <h1>Agende sua Consulta</h1>
    <p>Dê o primeiro passo para elevar sua autoestima. Preencha o formulário ou entre em contato diretamente conosco.</p>
  </div>

  <section>
    <div class="contact-container">
      
      <?php if ($msg = flash('success')): ?>
        <div style="background: rgba(37, 211, 102, 0.15); border: 1px solid #25D366; color: #25D366; padding: 15px; border-radius: 6px; margin-bottom: 25px; text-align: center;">
          <?= htmlspecialchars($msg) ?>
        </div>
      <?php endif; ?>

      <?php if ($err = flash('error')): ?>
        <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #ef4444; color: #ef4444; padding: 15px; border-radius: 6px; margin-bottom: 25px; text-align: center;">
          <?= htmlspecialchars($err) ?>
        </div>
      <?php endif; ?>

      <div class="contact-form">
        <form id="mainContactForm" method="POST" action="<?= url('/contato/enviar') ?>">
          <?= csrf_field() ?>
          
          <div class="form-group">
            <label for="nome">Nome Completo</label>
            <input type="text" id="nome" name="nome" class="form-control" required placeholder="Ex: Maria Silva">
          </div>

          <div class="form-group">
            <label for="telefone">Telefone / WhatsApp</label>
            <input type="tel" id="telefone" name="telefone" class="form-control" required placeholder="(51) 99999-9999">
          </div>

          <!-- Honeypot Anti-Bot (invisível para humanos) -->
          <div style="display:none !important; visibility:hidden !important; opacity:0; position:absolute; left:-9999px;">
            <input type="text" name="website" tabindex="-1" autocomplete="off">
            <input type="text" name="b_address" tabindex="-1" autocomplete="off">
          </div>

          <div class="form-group">
            <label for="mensagem">Como podemos ajudar?</label>
            <textarea id="mensagem" name="mensagem" class="form-control" required placeholder="Gostaria de agendar uma avaliação para..."></textarea>
          </div>

          <!-- Verificação Anti-Spam / Captcha -->
          <div class="form-group" style="background: rgba(197, 160, 89, 0.08); border: 1px solid var(--card-border); border-radius: 8px; padding: 16px 20px; margin-top: 15px; margin-bottom: 25px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
              <label for="captcha" style="font-weight: 600; color: var(--gold-light); display: flex; align-items: center; gap: 8px; margin: 0; font-size: 0.95rem;">
                <i data-lucide="shield-check" size="18" style="color: var(--gold-primary);"></i>
                Verificação de Segurança: 
                <span id="captchaQuestion" style="background: var(--bg-card, rgba(0,0,0,0.4)); border: 1px solid var(--gold-primary); color: var(--text-main); font-weight: 700; padding: 3px 12px; border-radius: 6px; letter-spacing: 0.5px; font-size: 1.05rem;">
                  <?= $captcha['question'] ?? 'Quanto é 3 + 4?' ?>
                </span>
              </label>
              <button type="button" id="btnRefreshCaptcha" title="Gerar novo cálculo" style="background: none; border: none; color: var(--gold-primary); cursor: pointer; display: flex; align-items: center; gap: 4px; font-size: 0.8rem; font-weight: 600; padding: 4px 8px; border-radius: 4px; transition: opacity 0.2s;">
                <i data-lucide="refresh-cw" size="14"></i> Novo
              </button>
            </div>
            <input type="number" id="captcha" name="captcha" class="form-control" required placeholder="Digite o resultado numérico (Ex: 7)" style="max-width: 100%; font-size: 1rem;" min="1" max="25" autocomplete="off">
          </div>

          <button type="submit" class="btn-primary btn-solid" style="width: 100%; justify-content: center;">
            Enviar Mensagem <i data-lucide="send" size="18"></i>
          </button>
        </form>

        <script>
          // Atualização dinâmica do Captcha sem recarregar a página
          document.getElementById('btnRefreshCaptcha')?.addEventListener('click', async function() {
            const icon = this.querySelector('i');
            if (icon) icon.style.transform = 'rotate(180deg)';
            try {
              const res = await fetch('<?= url('/captcha/refresh') ?>');
              const data = await res.json();
              if (data.question) {
                document.getElementById('captchaQuestion').textContent = data.question;
                document.getElementById('captcha').value = '';
                document.getElementById('captcha').focus();
              }
            } catch (e) {
              console.error(e);
            } finally {
              setTimeout(() => { if (icon) icon.style.transform = 'none'; }, 300);
            }
          });
        </script>
        </form>
      </div>
    </div>
  </section>

<?php require __DIR__ . '/../layouts/site_footer.php'; ?>
