<?php

return [
    'driver' => getenv('DB_CONNECTION') ?: 'mysql',
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'port' => (int)(getenv('DB_PORT') ?: 3306),
    // Deve permanecer alinhado ao .env.example e à documentação. Em todos
    // os ambientes reais, DB_DATABASE no .env tem precedência.
    'database' => getenv('DB_DATABASE') ?: 'clinica_george',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4'
];
