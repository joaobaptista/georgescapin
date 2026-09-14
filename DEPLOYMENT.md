# Deploy em produção

O site exige PHP 7.4 ou superior, as extensões `pdo_mysql`, `mbstring` e
`gd`, e acesso a um banco MySQL. Antes de publicar, crie um `.env` a partir
de `.env.example` com as credenciais reais. Não publique o `.env` no controle
de versão.

## Banco de dados

Defina no `.env` o mesmo nome de banco que será usado na importação:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=clinica_george
DB_USERNAME=usuario_do_banco
DB_PASSWORD=senha_do_banco
```

Depois importe, uma única vez, a estrutura e a carga inicial:

```bash
mysql -u usuario_do_banco -p -e "CREATE DATABASE IF NOT EXISTS clinica_george CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u usuario_do_banco -p clinica_george < database/schema.sql
mysql -u usuario_do_banco -p clinica_george < database/seed.sql
```

O diretório `public/uploads/` deve ter permissão de escrita pelo usuário do
PHP para que o CMS possa receber imagens.

## Apache e Hostinger

Configure o **DocumentRoot** para a pasta `public` do projeto e deixe
`mod_rewrite` ativo. Em um VirtualHost, a configuração essencial é:

```apache
DocumentRoot /caminho/para/site_george/public

<Directory /caminho/para/site_george/public>
    AllowOverride All
    Require all granted
</Directory>
```

Em hospedagens compartilhadas nas quais não é possível alterar o
DocumentRoot, envie também o `.htaccess` da raiz: ele encaminha arquivos
públicos e rotas ao front controller sem o loop `public/public/...` que
provoca HTTP 500. Não remova os arquivos `.htaccess` do projeto.

Em um Apache administrado no qual a raiz do projeto precisa ser o
DocumentRoot, libere a leitura do `.htaccess` nesse mesmo diretório:

```apache
DocumentRoot /caminho/para/site_george

<Directory /caminho/para/site_george>
    AllowOverride All
    Require all granted
</Directory>
```

## Checagem antes de liberar

Se a hospedagem oferece terminal/SSH, rode o verificador incluído depois de
enviar os arquivos e importar o banco:

```bash
php scripts/deploy-check.php
```

Ele confirma versão e extensões do PHP, presença do `.env`, conexão MySQL,
tabelas obrigatórias e permissões de `public/uploads/`. O comando não mostra
credenciais e encerra com código diferente de zero se algo estiver pendente.

## Nginx

Use `public` como raiz e encaminhe apenas URLs que não correspondam a um
arquivo real para o front controller:

```nginx
root /caminho/para/site_george/public;
index index.php;

location / {
    try_files $uri $uri/ /index.php?$query_string;
}

location ~ \.php$ {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_pass unix:/run/php/php-fpm.sock;
}
```

Adapte o socket `php-fpm.sock` ao servidor. Em Nginx, arquivos `.htaccess`
não são lidos, portanto esse bloco é obrigatório para as rotas amigáveis.

## Verificação após publicar

Confirme no navegador ou por HTTP que estas URLs respondem sem erro:

- `/`
- `/clinica`
- `/procedimentos`
- `/harmonizacao-facial`
- `/contato`
- `/blog`
- `/admin/login`

Se uma página retornar 500, consulte o log de erros PHP/Apache ou PHP-FPM da
hospedagem. As causas mais comuns são credenciais/tabelas do MySQL, extensão
`pdo_mysql` ausente, versão de PHP incompatível ou permissões em
`public/uploads/`.
