<?php
declare(strict_types=1);

use MateriaisOpme\App\Support\FileConfig;

$backendFile = __DIR__ . '/generated/production.backend.conf';
$flat = FileConfig::parse($backendFile);

return [
    'app_name' => $flat['app_name'] ?? 'Materiais Opme Backend',
    'environment' => $flat['environment'] ?? 'production',
    'base_url' => $flat['base_url'] ?? 'https://seu-dominio.exemplo',
    'timezone' => $flat['timezone'] ?? 'America/Sao_Paulo',
    'session_name' => $flat['session_name'] ?? 'materiais_opme_sess',
    'session_cookie_secure' => filter_var($flat['session_cookie_secure'] ?? '1', FILTER_VALIDATE_BOOL),
    'session_cookie_httponly' => filter_var($flat['session_cookie_httponly'] ?? '1', FILTER_VALIDATE_BOOL),
    'session_cookie_samesite' => $flat['session_cookie_samesite'] ?? 'Strict',
    'session_secret' => $flat['session_secret'] ?? '',
    'csrf_secret' => $flat['csrf_secret'] ?? '',
    'db' => [
        'host' => $flat['db_host'] ?? '127.0.0.1',
        'port' => (int)($flat['db_port'] ?? 3306),
        'name' => $flat['db_name'] ?? 'materiais_opme',
        'user' => $flat['db_user'] ?? 'materiais_opme_user',
        'pass' => $flat['db_pass'] ?? '',
        'charset' => $flat['db_charset'] ?? 'utf8mb4',
    ],
    'files' => [
        'fornecedores' => $flat['fornecedores_file'] ?? '/root/.materiais_opme_fornecedores.conf',
        'compras_credentials' => $flat['compras_credentials_file'] ?? '/root/.credentials_compras4.conf',
        'webmail_url' => $flat['webmail_url_file'] ?? __DIR__ . '/generated/production.webmail.conf',
        'backend' => $backendFile,
    ],
    'paths' => [
        'log_dir' => $flat['log_dir'] ?? '/var/log/materiais_opme_backend',
    ],
];
