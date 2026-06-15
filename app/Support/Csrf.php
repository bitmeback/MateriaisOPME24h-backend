<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Support;

final class Csrf
{
    public static function token(): string
    {
        if (!isset($_SESSION['_csrf_token'])) {
            $secret = (string)Config::get('csrf_secret', '');
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32)) . hash('sha256', $secret . microtime(true));
        }

        return (string)$_SESSION['_csrf_token'];
    }

    public static function validate(?string $token): bool
    {
        if (!is_string($token) || $token === '' || !isset($_SESSION['_csrf_token'])) {
            return false;
        }

        return hash_equals((string)$_SESSION['_csrf_token'], $token);
    }
}
