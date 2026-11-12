# Deploy — PHP puro + HTML/CSS/JavaScript nativos

O Nüva não requer Node.js, Vite, Vue, npm nem etapa de compilação. O runtime é
PHP 8.2+ com as dependências instaladas pelo Composer; a interface é servida de
`public/index.html`, `public/assets/css/app.css` e `public/assets/js/app.js`.

## Requisitos

- PHP 8.2+ com PDO e o driver do banco escolhido (`pdo_sqlite` ou `pdo_mysql`)
- Composer para instalar as dependências PHP
- Variáveis de ambiente em `.env`, fora do diretório público
- Servidor apontado preferencialmente para `public/`

Instale apenas as dependências do backend:

```bash
composer install --no-dev --optimize-autoloader
```

Não execute `npm install` nem `npm run build`.

## Atualizar um servidor existente

1. Faça um backup do banco antes de atualizar.
2. O histórico do repositório foi reescrito em 11/11/2026 (remoção de dados
   sensíveis): `git pull` não funciona em clones antigos. Copie para fora do
   projeto qualquer `database/database.sqlite` ou dump que exista no servidor
   e então rode `git fetch origin && git reset --hard origin/main`.
3. Rode **`composer migrate`**. A migração é incremental e segura de repetir;
   ela cria as tabelas de auditoria e de limite de tentativas e as colunas
   `users.activated_at` e `personal_access_tokens.last_used_at`. Na primeira
   execução, as sessões antigas (sem validade) são encerradas e todos fazem
   login de novo.
4. Revise o `.env` com base no `.env.example`: `APP_ENV=production`,
   `APP_TIMEZONE`, `SESSION_LIFETIME_HOURS`, `SESSION_IDLE_MINUTES` e, se houver
   proxy ou balanceador na frente, `TRUSTED_PROXIES` com o IP dele (sem isso, o
   limite de tentativas e a auditoria veem o IP do proxy).
5. Nunca rode `composer seed` em produção: ele apaga todas as tabelas
   (bloqueado com `APP_ENV=production`).

## Contas e senhas

O sistema não envia e-mails nem mensagens.

- Profissionais são criados pela clínica:
  `php scripts/create-professional.php --name="..." --email=...`
- Senha esquecida (qualquer conta): `php scripts/password-reset-link.php
  --email=... --base-url=https://seu-dominio` gera um link de uso único,
  válido por 72 horas, entregue pela clínica depois de confirmar a identidade.
- Pacientes cadastrados pelo profissional recebem o link de ativação gerado na
  tela do prontuário (*Acesso ao portal*).

## Apache

Configure o `DocumentRoot` para a pasta `public/` e mantenha `AllowOverride
FileInfo Options AuthConfig` habilitado. O arquivo `public/.htaccess` preserva o header
`Authorization`, bloqueia PHP em assets e encaminha URLs visuais para
`index.php`.

Se a hospedagem obrigar o diretório raiz do projeto como `DocumentRoot`, use o
`.htaccess` da raiz: ele expõe apenas arquivos de `public/` e bloqueia `app/`,
`database/`, `routes/`, `vendor/` e outros diretórios internos. Ainda assim,
usar `public/` diretamente é a configuração recomendada.

## Nginx

Use um bloco equivalente, ajustando os caminhos e a versão do PHP-FPM:

```nginx
root /var/www/nuva/public;
index index.php index.html;

location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ /\.(?!well-known) {
    deny all;
}

location ~* ^/(?:assets|images)/.*\.(?:php[0-9]?|phtml|phar)$ {
    deny all;
}

location ~ \.php$ {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_param HTTP_AUTHORIZATION $http_authorization;
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
}
```

O encaminhamento de `HTTP_AUTHORIZATION` é obrigatório: a API usa tokens
Bearer.

## Verificação antes do deploy

```bash
php scripts/deploy-check.php
php -l public/index.php
php -l routes/api.php
```

Se o Node.js já estiver disponível na máquina de desenvolvimento, `node --check
public/assets/js/app.js` é uma checagem adicional de sintaxe; ele não é
necessário para rodar ou publicar o sistema.

Para exercitar rotas, APIs e a interface sem tocar no banco da clínica, crie
um SQLite temporário, migre e popule-o, inicie o servidor PHP e use os testes
indicados no `README.md`.

## Segurança do navegador

`public/index.php` e `public/.htaccess` enviam a mesma Content-Security-Policy:
scripts apenas do próprio domínio (sem inline e sem CDN), estilos e fontes do
Google Fonts e do unpkg (ícones). Ao adicionar um recurso externo, atualize os
dois arquivos; `scripts/route-smoke-test.php` falha se `script-src` deixar de
ser `'self'`.
