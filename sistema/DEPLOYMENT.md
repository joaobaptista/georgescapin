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

Para exercitar rotas e APIs sem tocar no banco da clínica, crie um SQLite
temporário, migre e popule-o, inicie o servidor PHP e use os testes indicados
no `README.md`.
