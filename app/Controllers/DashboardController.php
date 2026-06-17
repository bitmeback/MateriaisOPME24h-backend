<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Controllers;

use MateriaisOpme\App\Repositories\UserRepository;
use MateriaisOpme\App\Support\View;

final class DashboardController
{
    public function index(): void
    {
        $user = null;
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
        if ($userId > 0) {
            $user = (new UserRepository())->findById($userId);
        }

        View::render('dashboard', [
            'full_name' => (string)($user['full_name'] ?? $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'usuário'),
            'role' => (string)($_SESSION['role'] ?? 'guest'),
        ]);
    }
}
