<?php
declare(strict_types=1);

$backend = require __DIR__ . '/backend.php';
$db = $backend['db'] ?? [];

return [
    'host' => $db['host'] ?? '127.0.0.1',
    'port' => (int)($db['port'] ?? 3306),
    'name' => $db['name'] ?? 'materiais_opme',
    'user' => $db['user'] ?? 'materiais_opme_user',
    'pass' => $db['pass'] ?? '',
    'charset' => $db['charset'] ?? 'utf8mb4',
];
