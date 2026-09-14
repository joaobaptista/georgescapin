// Funcionalidade da Newsletter
function handleNewsletter(event) {
  event.preventDefault();
  const form = event.target;
  const formData = new FormData(form);
  formData.append('action', 'newsletter');
  
  const btn = form.querySelector('button');
  const originalHtml = btn.innerHTML;
  btn.innerHTML = '...';

  fetch('backend/api.php', {
    method: 'POST',
    body: formData
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

    fetch('backend/api.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        alert("Mensagem enviada com sucesso!");
        this.reset();
      } else {
        alert("Ocorreu um erro: " + data.error);
      }
    })
    .catch(err => {
      console.error(err);
      alert("Erro ao enviar. Tente novamente.");
    })
    .finally(() => {
      btn.innerHTML = originalText;
    });
  });
}
