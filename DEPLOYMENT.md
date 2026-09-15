# 🚀 Guia de Build e Deploy em Produção — Dr. George Scapin

Este documento contém o passo a passo completo para validar, compilar/empacotar e publicar a aplicação **Clínica Dr. George Scapin** (Site Institucional, Painel Administrativo CMS e Sistema Clínico) em ambientes de produção.

---

## 📋 1. Requisitos do Servidor

| Requisito | Versão Mínima / Recomendada | Observações |
|---|---|---|
| **PHP** | `8.2` ou superior (recomendado `8.3` ou `8.4`) | Compatível com PHP 7.4+ |
| **Extensões PHP** | `pdo_mysql`, `mbstring`, `gd`, `curl`, `json` | `gd` é obrigatório para otimização WebP de uploads |
| **Banco de Dados** | MySQL `5.7+` / `8.0+` ou MariaDB `10.4+` | Charset `utf8mb4` e Collation `utf8mb4_unicode_ci` |
| **Servidor Web** | Apache com `mod_rewrite` ou Nginx com PHP-FPM | Apontamento para a pasta `public/` |
| **Permissões** | `public/uploads/` gravável pelo usuário do PHP | `chmod 755` ou `chmod 775` |

---

## 📦 2. Geração do Pacote de Build de Produção

O projeto possui um gerador automatizado de builds que:
1. Executa a análise de sintaxe de 100% dos arquivos PHP (`php -l`).
2. Roda as 9 baterias de testes automatizados (unitários, repositórios, rotas, segurança e API E2E).
3. Executa a auditoria completa de QA e Segurança.
4. Gera um arquivo ZIP higienizado e pronto para publicação na pasta `build/` (removendo `.git`, caches, logs e `.env` de desenvolvimento).

### Comando para gerar o build:
```bash
php scripts/build-release.php
```

> **Resultado:** O pacote final será salvo em `build/georgescapin_release_YYYYMMDD_HHMMSS.zip`.

---

## 🧪 3. Baterias de Testes e Auditoria de Qualidade

Antes de qualquer publicação, você pode rodar os testes individualmente ou de forma unificada:

### 3.1 Suíte Unificada (9 Baterias de Testes)
```bash
php scripts/run-all-tests.php
```

### 3.2 Auditoria Especializada de QA, Segurança e Usabilidade (31 Verificações)
```bash
php scripts/qa-security-audit.php
```

### 3.3 Testes E2E de Rotas HTTP do Site e Painel
```bash
php scripts/http-e2e-test.php
```

### 3.4 Testes Unitários de Repositórios e ImageOptimizer
```bash
php scripts/app-test-suite.php
```

---

## 🗄️ 4. Configuração do Banco de Dados MySQL

### 4.1 Criar o Banco e Importar Tabelas
No painel do servidor (cPanel / phpMyAdmin) ou via terminal SSH:

```bash
mysql -u usuario_do_banco -p -e "CREATE DATABASE IF NOT EXISTS clinica_george CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u usuario_do_banco -p clinica_george < database/schema.sql
mysql -u usuario_do_banco -p clinica_george < database/seed.sql
```

### 4.2 Configurar o Arquivo `.env` em Produção
Crie ou edite o arquivo `.env` na raiz da hospedagem com as credenciais reais de produção:

```dotenv
APP_NAME="Clínica Dr. George Scapin"
APP_ENV=production
APP_URL=https://www.drgeorgescapin.com.br

# Conexão MySQL Principal
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=clinica_george
DB_USERNAME=usuario_producao
DB_PASSWORD=senha_forte_producao
DB_CHARSET=utf8mb4
```

> ⚠️ **Importante:** Nunca versione ou compartilhe o arquivo `.env` com dados de produção.

---

## 🌐 5. Configuração do Servidor Web

### 5.1 Apache / cPanel / Hostinger (Recomendado)

Configure o **DocumentRoot** do domínio para apontar para a pasta `public/` do projeto:

```apache
DocumentRoot /var/www/site_george/public

<Directory /var/www/site_george/public>
    AllowOverride All
    Require all granted
    Options -Indexes +FollowSymLinks
</Directory>
```

> **Hospedagens Compartilhadas:** Caso o painel não permita alterar o DocumentRoot e force a raiz do projeto, o arquivo `.htaccess` na raiz já contém o fallback automático para o Front Controller da `public/`.

### 5.2 Nginx + PHP-FPM

Para servidores Nginx, configure o bloco de servidor conforme abaixo:

```nginx
server {
    listen 80;
    listen 443 ssl http2;
    server_name drgeorgescapin.com.br www.drgeorgescapin.com.br;

    root /var/www/site_george/public;
    index index.php index.html;

    # SSL Certificado (Let's Encrypt / Certbot)
    # ssl_certificate /etc/letsencrypt/live/drgeorgescapin.com.br/fullchain.pem;
    # ssl_certificate_key /etc/letsencrypt/live/drgeorgescapin.com.br/privkey.pem;

    # Headers de Segurança
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header X-XSS-Protection "1; mode=block" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Bloqueio de arquivos ocultos e sensíveis
    location ~ /\.(?!well-known) {
        deny all;
    }

    # Proteção de upload: proíbe execução de scripts dentro de uploads
    location ~* ^/uploads/.*\.(php|phtml|phar|sh|pl|py)$ {
        deny all;
    }

    # Processamento PHP
    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param HTTP_AUTHORIZATION $http_authorization;
        fastcgi_pass unix:/run/php/php8.4-fpm.sock; # Ajustar para a sua versão do PHP
        fastcgi_intercept_errors on;
    }

    # Cache de Assets Estáticos
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|webp|woff|woff2|ttf)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }
}
```

---

## 🔒 6. Permissões de Pastas e Arquivos

Após descompactar o arquivo de release no servidor, ajuste as permissões:

```bash
# Permissões gerais
find . -type d -exec chmod 755 {} \;
find . -type f -exec chmod 644 {} \;

# Permissão de escrita para upload de imagens pelo CMS
chmod -R 775 public/uploads
chown -R www-data:www-data public/uploads
```

---

## ✅ 7. Verificação Pós-Deploy

Logo após a publicação, execute o verificador de integridade no servidor:

```bash
php scripts/deploy-check.php
```

E valide no navegador o funcionamento das seguintes rotas:
- [x] Home: `https://www.drgeorgescapin.com.br/`
- [x] A Clínica: `https://www.drgeorgescapin.com.br/clinica`
- [x] Procedimentos: `https://www.drgeorgescapin.com.br/procedimentos`
- [x] Harmonização Facial: `https://www.drgeorgescapin.com.br/harmonizacao-facial`
- [x] Blog: `https://www.drgeorgescapin.com.br/blog`
- [x] Contato: `https://www.drgeorgescapin.com.br/contato`
- [x] Sitemap XML: `https://www.drgeorgescapin.com.br/sitemap.xml`
- [x] Painel Administrativo: `https://www.drgeorgescapin.com.br/admin/login`
