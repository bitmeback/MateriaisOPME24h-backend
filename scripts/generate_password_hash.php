<?php
declare(strict_types=1);

/**
 * Helper CLI para gerar um hash compatível com password_hash().
 *
 * Uso:
 *   php scripts/generate_password_hash.php 'SenhaForte'
 */

$password = $argv[1] ?? '';
if ($password === '') {
    fwrite(STDERR, "Uso: php scripts/generate_password_hash.php 'SENHA'\n");
    exit(1);
}

echo password_hash($password, PASSWORD_DEFAULT) . PHP_EOL;
