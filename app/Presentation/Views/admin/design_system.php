<?php
$pageTitle = 'Design System & Guia de Estilo';
$pageActions = '<a href="' . asset('assets/css/admin-design-system.css') . '" target="_blank" class="btn-admin btn-primary"><i data-lucide="file-code" size="16"></i> Ver CSS Puro</a>';
ob_start();
?>

<!-- SEÇÃO 1: PALETA DE CORES & TOKENS -->
<div class="admin-card">
  <div style="margin-bottom: 20px;">
    <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 10px;">
      <i data-lucide="palette" size="20" style="color: var(--pastel-blue-accent);"></i> 1. Cores de Ação, Ícones & Superfícies
    </h3>
    <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 2px;">Cores funcionais calibradas para foco, ergonomia visual e contraste WCAG AAA.</p>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px;">
    <div style="background: var(--color-primary); border: 1px solid var(--color-primary); color: #ffffff; padding: 18px; border-radius: var(--radius-md);">
      <strong style="display: block; font-size: 0.9rem;">Jira Blue (Botão Primário)</strong>
      <code style="font-size: 0.75rem; opacity: 0.9;">#0C66E4 • --color-primary</code>
      <div style="font-size: 0.75rem; margin-top: 8px;">Botões principais e ícones ativos</div>
    </div>

    <div style="background: var(--pastel-blue-bg); border: 1px solid var(--pastel-blue-border); color: var(--pastel-blue-text); padding: 18px; border-radius: var(--radius-md);">
      <strong style="display: block; font-size: 0.9rem;">Soft Blue (Container / Badge)</strong>
      <code style="font-size: 0.75rem;">#E9F2FF • --pastel-blue-bg</code>
      <div style="font-size: 0.75rem; margin-top: 8px;">Cards de métricas e fundos de ícones</div>
    </div>

    <div style="background: var(--pastel-green-bg); border: 1px solid var(--pastel-green-border); color: var(--pastel-green-text); padding: 18px; border-radius: var(--radius-md);">
      <strong style="display: block; font-size: 0.9rem;">Jira Green (Sucesso)</strong>
      <code style="font-size: 0.75rem;">#E3FCEF • --pastel-green-bg</code>
      <div style="font-size: 0.75rem; margin-top: 8px;">Itens publicados e novos leads</div>
    </div>

    <div style="background: var(--pastel-amber-bg); border: 1px solid var(--pastel-amber-border); color: var(--pastel-amber-text); padding: 18px; border-radius: var(--radius-md);">
      <strong style="display: block; font-size: 0.9rem;">Jira Amber (Alerta / Pendente)</strong>
      <code style="font-size: 0.75rem;">#FFFAE6 • --pastel-amber-bg</code>
      <div style="font-size: 0.75rem; margin-top: 8px;">Pendências e mensagens</div>
    </div>

    <div style="background: var(--pastel-red-bg); border: 1px solid var(--pastel-red-border); color: var(--pastel-red-text); padding: 18px; border-radius: var(--radius-md);">
      <strong style="display: block; font-size: 0.9rem;">Jira Red (Perigo / Exclusão)</strong>
      <code style="font-size: 0.75rem;">#FFEBE6 • --pastel-red-bg</code>
      <div style="font-size: 0.75rem; margin-top: 8px;">Desativados e botões de exclusão</div>
    </div>

    <div style="background: var(--bg-surface); border: 1px solid var(--border-light); padding: 18px; border-radius: var(--radius-md); box-shadow: var(--shadow-xs);">
      <strong style="display: block; font-size: 0.9rem; color: var(--text-primary);">Surface (Card Branco)</strong>
      <code style="font-size: 0.75rem; color: var(--text-muted);">#FFFFFF • --bg-surface</code>
      <div style="font-size: 0.75rem; color: var(--text-muted); margin-top: 8px;">Cards, modais e tabelas</div>
    </div>
  </div>
</div>

<!-- SEÇÃO 2: TIPOGRAFIA DE ALTA RESOLUÇÃO -->
<div class="admin-card">
  <div style="margin-bottom: 20px;">
    <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 10px;">
      <i data-lucide="type" size="20" style="color: var(--pastel-blue-accent);"></i> 2. Tipografia & Escrita em Grafite/Preto de Alta Resolução
    </h3>
    <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 2px;">Contraste profundo com preto e cinza escuro para excelente nitidez e conforto de leitura prolongada.</p>
  </div>

  <div style="display: flex; flex-direction: column; gap: 16px; background: var(--bg-canvas); padding: 24px; border-radius: var(--radius-md); border: 1px solid var(--border-light);">
    <div style="display: flex; align-items: baseline; justify-content: space-between; border-bottom: 1px solid var(--border-subtle); padding-bottom: 10px;">
      <span style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);">Título Principal (H1 - 24px / #091E42)</span>
      <code style="font-size: 0.8rem; color: var(--text-muted);">--text-primary • #091E42 (Preto Escuro)</code>
    </div>
    <div style="display: flex; align-items: baseline; justify-content: space-between; border-bottom: 1px solid var(--border-subtle); padding-bottom: 10px;">
      <span style="font-size: 1.2rem; font-weight: 700; color: var(--text-primary);">Título de Seção / Card (H2 - 19px)</span>
      <code style="font-size: 0.8rem; color: var(--text-muted);">--text-primary • #091E42 (Preto Escuro)</code>
    </div>
    <div style="display: flex; align-items: baseline; justify-content: space-between; border-bottom: 1px solid var(--border-subtle); padding-bottom: 10px;">
      <span style="font-size: 0.95rem; font-weight: 600; color: var(--text-secondary);">Labels de Formulário & Cabeçalhos de Tabela (15px)</span>
      <code style="font-size: 0.8rem; color: var(--text-muted);">--text-secondary • #172B4D (Grafite)</code>
    </div>
    <div style="display: flex; align-items: baseline; justify-content: space-between; border-bottom: 1px solid var(--border-subtle); padding-bottom: 10px;">
      <span style="font-size: 0.9rem; color: var(--text-secondary);">Texto Geral do Painel e Células de Tabela (14px)</span>
      <code style="font-size: 0.8rem; color: var(--text-muted);">--text-secondary • #172B4D (Alta Leitura)</code>
    </div>
    <div style="display: flex; align-items: baseline; justify-content: space-between;">
      <span style="font-size: 0.8rem; color: var(--text-muted);">Metadados / Legendas / Informações Auxiliares (13px)</span>
      <code style="font-size: 0.8rem; color: var(--text-muted);">--text-muted • #44546F (Cinza Escuro)</code>
    </div>
  </div>
</div>

<!-- SEÇÃO 3: BOTÕES & BADGES -->
<div class="admin-card">
  <div style="margin-bottom: 20px;">
    <h3 style="font-size: 1.2rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 10px;">
      <i data-lucide="mouse-pointer" size="20" style="color: var(--pastel-blue-accent);"></i> 3. Botões no Padrão Jira, Ações e Badges
    </h3>
    <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 2px;">Botão primário em Azul Jira (#0C66E4), botões secundários neutros e ícones em azul de destaque.</p>
  </div>

  <div style="display: flex; flex-wrap: wrap; gap: 14px; align-items: center; margin-bottom: 20px;">
    <button type="button" class="btn-primary">
      <i data-lucide="check" size="16"></i> Salvar Alterações (Azul Jira)
    </button>
    <button type="button" class="btn-secondary">
      <i data-lucide="arrow-left" size="16"></i> Cancelar / Voltar (Neutro)
    </button>
    <button type="button" class="btn-danger">
      <i data-lucide="trash-2" size="16"></i> Excluir (Perigo)
    </button>
    <button type="button" class="btn-action edit" title="Editar">
      <i data-lucide="edit-3" size="14"></i>
    </button>
    <button type="button" class="btn-action btn-action-delete" title="Excluir">
      <i data-lucide="trash-2" size="14"></i>
    </button>
  </div>

  <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
    <span class="badge badge-pastel-green"><i data-lucide="check-circle" size="12"></i> Publicado</span>
    <span class="badge badge-pastel-amber"><i data-lucide="clock" size="12"></i> Pendente</span>
    <span class="badge badge-pastel-red"><i data-lucide="x-circle" size="12"></i> Inativo</span>
    <span class="badge badge-pastel-blue"><i data-lucide="star" size="12"></i> Destaque</span>
    <span class="badge badge-pastel-purple"><i data-lucide="tag" size="12"></i> Categoria</span>
  </div>
</div>

<?php
$content = ob_get_clean();
require __DIR__ . '/../layouts/admin_layout.php';
?>
