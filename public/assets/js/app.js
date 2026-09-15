// Funcionalidade da Newsletter
function handleNewsletter(event) {
  event.preventDefault();
  const form = event.target;
  const formData = new FormData(form);
  formData.append('action', 'newsletter');
  
  const btn = form.querySelector('button');
  const originalHtml = btn.innerHTML;
  btn.innerHTML = '...';

  fetch('/newsletter/assinar', {
    method: 'POST',
    body: formData,
    headers: {
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert("E-mail cadastrado com sucesso!");
      form.reset();
    } else {
      alert("Ocorreu um erro: " + data.error);
    }
  })
  .catch(err => {
    console.error(err);
    alert("Erro ao cadastrar. Tente novamente.");
  })
  .finally(() => {
    btn.innerHTML = originalHtml;
  });
}

// Formulário de Contato Principal
const contactForm = document.getElementById('mainContactForm');
if (contactForm) {
  contactForm.addEventListener('submit', function(event) {
    event.preventDefault();
    const formData = new FormData(this);
    formData.append('action', 'contact');
    
    const btn = this.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.innerHTML = 'Enviando...';
    btn.disabled = true;

    fetch(this.action || '/contato/enviar', {
      method: 'POST',
      body: formData,
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert("Mensagem enviada com sucesso! Em breve entraremos em contato.");
        this.reset();
        // Atualiza para um novo captcha após envio
        document.getElementById('btnRefreshCaptcha')?.click();
      } else {
        alert("Atenção: " + (data.error || "Erro ao processar o formulário."));
        if (data.new_captcha) {
          const qElem = document.getElementById('captchaQuestion');
          if (qElem) qElem.textContent = data.new_captcha;
          const cInput = document.getElementById('captcha');
          if (cInput) {
            cInput.value = '';
            cInput.focus();
          }
        }
      }
    })
    .catch(err => {
      console.error(err);
      alert("Erro ao enviar mensagem. Por favor, tente novamente.");
    })
    .finally(() => {
      btn.innerHTML = originalText;
      btn.disabled = false;
    });
  });
}
