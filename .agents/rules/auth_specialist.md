---
description: Diretrizes e padrões arquiteturais do Especialista em Autenticação, Sessões e Senhas
globs: ["app/Infrastructure/Security/**", "app/Presentation/Controllers/AuthController.php", "admin/views/login.php", "scripts/auth-doctor.php", "public/index.php"]
always_on: true
---

# 🛡️ Especialista em Autenticação, Sessões e Senhas (CMS & Servidor)

Este documento define os padrões mandatórios de autenticação, compatibilidade entre servidores de produção (cPanel, Nginx, Apache, Hostinger) e resolução de problemas de login na Clínica Dr. George Scapin.

---

## 1. Credenciais Padrão do Sistema

| Usuário / Perfil | E-mail | Senha Padrão | Senha Fallback |
|---|---|---|---|
| **Dr. George Scapin** | `admin@drgeorgescapin.com.br` | `123` | `admin123` |
| **Administrador Geral** | `admin@admin.com` | `123` | `admin123` |
| **Atalho de Usuário** | `admin` (reconhecido no campo de login) | `123` | `admin123` |

---

## 2. Pilares de Resiliência no Servidor

### 2.1 Cookies e Detecção de HTTPS / Proxies Reversos
- Em hospedagens com terminação SSL externa (Cloudflare, Nginx proxy, cPanel EA-Nginx, Load Balancers), a comunicação interna com o Apache/PHP ocorre via HTTP porta 80.
- A aplicação utiliza `is_https_request()` em `app/helpers.php` verificando `HTTPS`, `HTTP_X_FORWARDED_PROTO`, `HTTP_X_FORWARDED_SSL` e `HTTP_CF_VISITOR`.
- **Regra dos Cookies:** NUNCA definir `'domain' => ''` em `session_set_cookie_params()`. Parâmetros vazios de domínio violam a RFC 6265 e causam descarte imediato do cookie de sessão no Google Chrome e Safari.
- Parâmetros padrão: `lifetime = 0`, `path = '/'`, `secure = is_https_request()`, `httponly = true`, `samesite = 'Lax'`.

### 2.2 Fallback de Diretório de Sessões
- Quando o diretório padrão `session.save_path` do servidor estiver sem permissão de escrita (`/var/lib/php/sessions` ou `/tmp` bloqueado), a função `init_session()` redireciona automaticamente para `storage/sessions/` (permissão `0700` e protegido via `.htaccess`).

### 2.3 Camada de Autenticação Adaptativa (`Auth::attempt`)
- **Busca Flexível:** `LOWER(email) = LOWER(?) OR LOWER(name) = LOWER(?)`.
- **Auto-Rehash:** Se a senha no banco foi inserida em texto puro por edição manual (ex: phpMyAdmin), o sistema valida, aceita e atualiza imediatamente para hash bcrypt padrão.
- **Suporte Dual 123 / admin123:** Para os administradores padrão, o sistema aceita tanto a senha nova `123` quanto a antiga `admin123`, atualizando o hash no banco automaticamente para a senha digitada.
- **Self-Healing:** Se o banco de produção for importado vazio (sem seeds), a primeira tentativa legítima de login com as credenciais padrão cria o usuário administrador no MySQL e autentica sem falhas.

### 2.4 Resiliência contra Loops de CSRF
- Em casos de troca de protocolo HTTP -> HTTPS ou latência no proxy reverso, se as credenciais submetidas no formulário de login forem autênticas e válidas, a autenticação é aceita graciosamente em vez de travar o usuário com erro permanente de sessão expirada.

---

## 3. Comandos de Diagnóstico e Reparo Rápido

```bash
# Diagnóstico completo de ambiente, banco e testes de login
php scripts/auth-doctor.php

# Sincroniza e garante os dois administradores com senha 123
php scripts/auth-doctor.php --sync-seeds

# Redefine a senha de um usuário específico
php scripts/auth-doctor.php --reset-password=minhasenha --email=admin@drgeorgescapin.com.br

# Testa todas as variações de login
php scripts/auth-doctor.php --test-auth
```
