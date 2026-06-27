<?php
declare(strict_types=1);

return [
    'app_name' => 'Materiais Opme Backend',
    'environment' => 'production',
    'base_url' => 'https://seu-dominio.exemplo',
    'timezone' => 'America/Sao_Paulo',
    'session_name' => 'materiais_opme_sess',
    'session_cookie_secure' => true,
    'session_cookie_httponly' => true,
    'session_cookie_samesite' => 'Strict',
    'files' => [
        'fornecedores' => '/root/.materiais_opme_fornecedores.conf',
        'compras_credentials' => __DIR__ . '/generated/production.credentials.conf',
        'webmail_url' => __DIR__ . '/generated/production.webmail.conf',
        'backend' => __DIR__ . '/generated/production.backend.conf',
    ],
    'paths' => [
        'log_dir' => '/var/log/materiais_opme_backend',
        'storage_dir' => __DIR__ . '/../storage',
    ],
];
