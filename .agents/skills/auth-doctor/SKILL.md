---
name: auth-doctor
description: Especialista em diagnóstico, reparo, testes e adaptação de login, sessões e senhas da aplicação em ambientes locais e de produção.
---

# 🩺 Skill: Auth Doctor — Diagnóstico e Solução de Problemas de Login

Esta skill orienta o agente a diagnosticar, testar e corrigir imediatamente qualquer falha de autenticação, cookies, sessões ou divergência de senhas no painel da Clínica Dr. George Scapin.

---

## 🚀 Quando Ativar Esta Skill
- O usuário relatar erro de login, "senha incorreta", "credenciais inválidas" ou "sessão expirada" em produção ou localmente.
- O login funcionar localmente mas falhar no servidor (cPanel, Nginx, Apache, Hostinger).
- Necessidade de redefinir ou verificar senhas do administrador.
- Atualizações ou migrações de banco de dados.

---

## 🛠️ Procedimento Operacional Passo a Passo

### Passo 1: Executar o Diagnóstico Automatizado
Execute sempre o script do Auth Doctor no terminal para inspecionar o estado atual:

```bash
php scripts/auth-doctor.php
```

O relatório exibirá:
1. **Ambiente PHP e Sessões:** Validação da versão do PHP e se o diretório de sessões está gravável (com fallback para `storage/sessions/`).
2. **Conexão MySQL:** Status de conexão com o banco configurado no `.env`.
3. **Tabela de Usuários:** Lista de usuários, e-mails e validação das senhas `123` e `admin123`.
4. **Bateria de Testes Adaptativos:** 6 testes simulando logins reais.

---

### Passo 2: Sincronizar Credenciais de Fábrica (Se Necessário)
Se algum usuário de administração estiver ausente ou com senha corrompida:

```bash
php scripts/auth-doctor.php --sync-seeds
```

Isso garante que:
- `admin@drgeorgescapin.com.br` tenha a senha `123` (com fallback aceitando `admin123`).
- `admin@admin.com` tenha a senha `123` (com fallback aceitando `admin123`).

---

### Passo 3: Redefinir Senha Específica
Caso o administrador solicite uma nova senha personalizada:

```bash
# Para todos os administradores padrão:
php scripts/auth-doctor.php --reset-password=NovaSenhaForte2026!

# Para um e-mail específico:
php scripts/auth-doctor.php --reset-password=NovaSenhaForte2026! --email=admin@drgeorgescapin.com.br
```

---

### Passo 4: Resolução de Problemas de Sessão no Servidor

Se o usuário relatar que após digitar a senha a página apenas recarrega ou diz "Sessão expirada":
1. **Verificar permissões de escrita:**
   ```bash
   chmod -R 775 storage/sessions
   ```
2. **Verificar se o site está rodando em HTTPS ou atrás de proxy:**
   - O helper `is_https_request()` em `app/helpers.php` detecta automaticamente cabeçalhos de Cloudflare (`CF-Visitor`), Nginx (`X-Forwarded-Proto`) e AWS.
   - O cookie de sessão é configurado como `SameSite=Lax`, `HttpOnly=true` e sem parâmetro `domain` vazio.
3. **Verificar se as credenciais submetidas conferem:**
   - A camada `Auth::attempt()` aceita o login mesmo se o token CSRF tiver sido perdido por delay de cookie no proxy reverso, evitando o bloqueio falso de administradores autênticos.
