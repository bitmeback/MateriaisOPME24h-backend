<?php
declare(strict_types=1);

/**
 * Gera um INSERT seguro para criar ou atualizar um usuário com role user/admin/desenv.
 *
 * Exemplos:
 *   php scripts/create_admin_user.php --username=admin --password='SenhaForte' --full-name='Administrador do Sistema' --role=admin > /tmp/admin.sql
 *   php scripts/create_admin_user.php --username=usuario --password='SenhaForte' --full-name='Usuário Operacional' --role=user > /tmp/user.sql
 *   php scripts/create_admin_user.php --username=desenvolvedor --password='SenhaForte' --full-name='Desenvolvedor do Sistema' --role=desenv > /tmp/desenv.sql
 *   mysql < /tmp/admin.sql
 */

$options = getopt('', ['username:', 'password:', 'full-name:', 'role::', 'active::']);

$username = trim((string)($options['username'] ?? ''));
$password = (string)($options['password'] ?? '');
$fullName = trim((string)($options['full-name'] ?? ''));
$role = trim((string)($options['role'] ?? 'admin'));
$active = (int)($options['active'] ?? 1);

if ($username === '' || $password === '' || $fullName === '') {
    fwrite(STDERR, "Uso: php scripts/create_admin_user.php --username=... --password=... --full-name=... [--role=admin] [--active=1]\n");
    exit(1);
}

if (!in_array($role, ['user', 'admin', 'desenv'], true)) {
    fwrite(STDERR, "Perfil inválido. Use user, admin ou desenv.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
if ($hash === false) {
    fwrite(STDERR, "Falha ao gerar hash da senha.\n");
    exit(1);
}

$sql = "INSERT INTO users (username, password_hash, full_name, role, active, created_at, updated_at)\n"
    . "VALUES ('" . addslashes($username) . "', '" . addslashes($hash) . "', '" . addslashes($fullName) . "', '" . addslashes($role) . "', " . ($active ? 1 : 0) . ", NOW(), NOW())\n"
    . "ON DUPLICATE KEY UPDATE\n"
    . "  password_hash = VALUES(password_hash),\n"
    . "  full_name = VALUES(full_name),\n"
    . "  role = VALUES(role),\n"
    . "  active = VALUES(active),\n"
    . "  updated_at = NOW();\n";

echo $sql;
