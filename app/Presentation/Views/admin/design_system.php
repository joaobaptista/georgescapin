<?php
$pageTitle = 'Design System & Guia de Estilo';
ob_start();
?>

<!-- CABEÇALHO DO DESIGN SYSTEM -->
<div class="admin-card" style="background: linear-gradient(135deg, var(--bg-surface) 0%, var(--bg-surface-container) 100%); border-left: 4px solid var(--color-primary);">
  <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
    <div>
      <span class="badge" style="background: var(--color-primary-container); color: var(--color-primary); margin-bottom: 8px;">
        Material Design 3 • Clean Architecture
      </span>
      <h2 style="font-size: 1.6rem; color: var(--text-main); font-weight: 700; margin-bottom: 6px;">
        Design System do Painel Administrativo
      </h2>
      <p style="color: var(--text-muted); font-size: 0.9rem; max-width: 750px;">
        Arquitetura visual padronizada, moderna e minimalista para este CMS e projetos futuros. Cores neutras, tipografia elegante, sombras suaves e suporte nativo a temas Claro e Escuro.
      </p>
    </div>
    <a href="<?= asset('assets/css/admin-design-system.css') ?>" target="_blank" class="btn btn-solid" style="padding: 10px 20px;">
      <i data-lucide="file-code" size="16"></i> Ver CSS Puro
    </a>
  </div>
</div>

<!-- SEÇÃO 1: PALETA DE CORES NEUTRA E FUNCIONAL -->
<div class="admin-card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
      <h3 style="font-size: 1.25rem; font-weight: 600; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
        <i data-lucide="palette" size="20" style="color: var(--color-primary);"></i> 1. Tokens de Cores e Superfícies
      </h3>
      <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 2px;">Paleta neutra sofisticada com contraste calibrado para acessibilidade.</p>
    </div>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
    <div style="background: var(--color-primary); color: var(--color-on-primary); padding: 18px; border-radius: var(--radius-md); box-shadow: var(--elevation-1);">
      <strong style="display: block; font-size: 0.9rem;">Primary Brand</strong>
      <code style="font-size: 0.75rem; opacity: 0.9;">--color-primary</code>
      <div style="font-size: 0.75rem; margin-top: 8px;">Gold / Acento Principal</div>
    </div>

    <div style="background: var(--bg-surface); border: 1px solid var(--border-strong); padding: 18px; border-radius: var(--radius-md); box-shadow: var(--elevation-1);">
      <strong style="display: block; font-size: 0.9rem; color: var(--text-main);">Surface</strong>
      <code style="font-size: 0.75rem; color: var(--text-muted);">--bg-surface</code>
      <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 8px;">Cards e Modais</div>
    </div>

    <div style="background: var(--bg-surface-container); border: 1px solid var(--border-subtle); padding: 18px; border-radius: var(--radius-md);">
      <strong style="display: block; font-size: 0.9rem; color: var(--text-main);">Surface Container</strong>
      <code style="font-size: 0.75rem; color: var(--text-muted);">--bg-surface-container</code>
      <div style="font-size: 0.75rem; color: var(--text-secondary); margin-top: 8px;">Inputs e Fundos</div>
    </div>

    <div style="background: var(--color-success-container); border: 1px solid var(--color-success); color: var(--color-success); padding: 18px; border-radius: var(--radius-md);">
      <strong style="display: block; font-size: 0.9rem;">Success (Emerald)</strong>
      <code style="font-size: 0.75rem;">--color-success</code>
      <div style="font-size: 0.75rem; margin-top: 8px;">Confirmações e Ativos</div>
    </div>

    <div style="background: var(--color-error-container); border: 1px solid var(--color-error); color: var(--color-error); padding: 18px; border-radius: var(--radius-md);">
      <strong style="display: block; font-size: 0.9rem;">Danger (Rose)</strong>
      <code style="font-size: 0.75rem;">--color-error</code>
      <div style="font-size: 0.75rem; margin-top: 8px;">Exclusões e Erros</div>
    </div>

    <div style="background: var(--color-warning-container); border: 1px solid var(--color-warning); color: var(--color-warning); padding: 18px; border-radius: var(--radius-md);">
      <strong style="display: block; font-size: 0.9rem;">Warning (Amber)</strong>
      <code style="font-size: 0.75rem;">--color-warning</code>
      <div style="font-size: 0.75rem; margin-top: 8px;">Pendências e Avisos</div>
    </div>
  </div>
</div>

<!-- SEÇÃO 2: TIPOGRAFIA -->
<div class="admin-card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
      <h3 style="font-size: 1.25rem; font-weight: 600; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
        <i data-lucide="type" size="20" style="color: var(--color-primary);"></i> 2. Escala Tipográfica
      </h3>
      <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 2px;">Plus Jakarta Sans para interface limpa e Playfair Display para títulos nobres.</p>
    </div>
  </div>

  <div style="display: flex; flex-direction: column; gap: 16px; background: var(--bg-surface-container); padding: 24px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle);">
    <div style="display: flex; align-items: baseline; justify-content: space-between; border-bottom: 1px solid var(--border-subtle); padding-bottom: 10px;">
      <span style="font-family: var(--font-serif); font-size: 2rem; color: var(--color-primary);">Título Serif (Playfair Display)</span>
      <code style="font-size: 0.8rem; color: var(--text-muted);">2rem / 32px • Serif</code>
    </div>
    <div style="display: flex; align-items: baseline; justify-content: space-between; border-bottom: 1px solid var(--border-subtle); padding-bottom: 10px;">
      <span style="font-size: 1.4rem; font-weight: 700; color: var(--text-main);">Headline 1 (Plus Jakarta Sans)</span>
      <code style="font-size: 0.8rem; color: var(--text-muted);">1.4rem / 22px • Bold</code>
    </div>
    <div style="display: flex; align-items: baseline; justify-content: space-between; border-bottom: 1px solid var(--border-subtle); padding-bottom: 10px;">
      <span style="font-size: 1.1rem; font-weight: 600; color: var(--text-main);">Headline 2 (Subtitle)</span>
      <code style="font-size: 0.8rem; color: var(--text-muted);">1.1rem / 18px • SemiBold</code>
    </div>
    <div style="display: flex; align-items: baseline; justify-content: space-between; border-bottom: 1px solid var(--border-subtle); padding-bottom: 10px;">
      <span style="font-size: 0.95rem; color: var(--text-secondary);">Body Text (Texto Corrido do Sistema)</span>
      <code style="font-size: 0.8rem; color: var(--text-muted);">0.95rem / 15px • Regular</code>
    </div>
    <div style="display: flex; align-items: baseline; justify-content: space-between;">
      <span style="font-size: 0.8rem; color: var(--text-muted);">Caption / Subtext / Labels</span>
      <code style="font-size: 0.8rem; color: var(--text-muted);">0.8rem / 13px • Medium</code>
    </div>
  </div>
</div>

<!-- SEÇÃO 3: BOTÕES & INTERAÇÕES -->
<div class="admin-card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <div>
      <h3 style="font-size: 1.25rem; font-weight: 600; color: var(--text-main); display: flex; align-items: center; gap: 10px;">
        <i data-lucide="mouse-pointer" size="20" style="color: var(--color-primary);"></i> 3. Botões & Ações
      </h3>
      <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 2px;">Variantes: Filled, Outlined, Danger e Tamanhos.</p>
    </div>
  </div>

  <div style="display: flex; flex-wrap: wrap; gap: 14px; align-items: center;">
    <button type="button" class="btn btn-solid">
      <i data-lucide="check" size="16"></i> Botão Filled (Primary)
    </button>
    <button type="button" class="btn">
      <i data-lucide="edit" size="16"></i> Botão Outlined (Neutral)
    </button>
    <button type="button" class="btn btn-danger">
      <i data-lucide="trash-2" size="16"></i> Botão Danger (Excluir)
    </button>
  </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
