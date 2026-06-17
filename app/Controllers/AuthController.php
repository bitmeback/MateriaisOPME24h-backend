<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Controllers;

use MateriaisOpme\App\Support\Csrf;
use MateriaisOpme\App\Support\View;

final class AuthController
{
    public function showLogin(): void
    {
        View::render('login', [
            'csrf_token' => Csrf::token(),
            'error' => null,
        ]);
    }

    public function login(): void
    {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            View::render('login', [
                'csrf_token' => Csrf::token(),
                'error' => 'CSRF inválido.',
            ]);
            return;
        }

        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            View::render('login', [
                'csrf_token' => Csrf::token(),
                'error' => 'Preencha usuário e senha.',
            ]);
            return;
        }

        $auth = new \MateriaisOpme\App\Services\AuthService();
        if (!$auth->login($username, $password)) {
            View::render('login', [
                'csrf_token' => Csrf::token(),
                'error' => 'Credenciais inválidas.',
            ]);
            return;
        }

        header('Location: /dashboard');
        exit;
    }

    public function logout(): void
    {
        (new \MateriaisOpme\App\Services\AuthService())->logout();
        header('Location: /');
        exit;
    }
}
