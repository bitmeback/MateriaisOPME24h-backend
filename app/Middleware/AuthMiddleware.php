<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Middleware;

use MateriaisOpme\App\Services\AuthService;

final class AuthMiddleware
{
    public function __construct(private readonly AuthService $auth = new AuthService())
    {
    }

    public function requireLogin(): void
    {
        if (!$this->auth->check()) {
            header('Location: /login');
            exit;
        }
    }

    public function requireRole(string $role): void
    {
        $this->requireLogin();
        if (!$this->auth->hasMinimumRole($role)) {
            http_response_code(403);
            echo 'Acesso negado.';
            exit;
        }
    }
}
