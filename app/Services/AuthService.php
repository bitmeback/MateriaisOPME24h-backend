<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Services;

use MateriaisOpme\App\Repositories\UserRepository;

final class AuthService
{
    private ?UserRepository $users = null;
    private ?AuditService $audit = null;

    private function users(): UserRepository
    {
        if ($this->users === null) {
            $this->users = new UserRepository();
        }

        return $this->users;
    }

    private function audit(): AuditService
    {
        if ($this->audit === null) {
            $this->audit = new AuditService();
        }

        return $this->audit;
    }

    public function login(string $username, string $password): bool
    {
        $user = $this->users()->findByUsername($username);
        if (!$user || !(bool)$user['active']) {
            return false;
        }

        if (!password_verify($password, (string)$user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = (string)$user['username'];
        $_SESSION['full_name'] = (string)($user['full_name'] ?? '');
        $_SESSION['role'] = (string)$user['role'];
        $_SESSION['logged_in_at'] = time();

        $this->users()->updateLastLogin((int)$user['id']);
        $this->audit()->record('login', 'users', (string)$user['id'], [
            'username' => (string)$user['username'],
            'role' => (string)$user['role'],
        ], (int)$user['id']);

        return true;
    }

    public function logout(): void
    {
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
        $username = (string)($_SESSION['username'] ?? '');

        if ($userId !== null) {
            $this->audit()->record('logout', 'users', (string)$userId, [
                'username' => $username,
            ], $userId);
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool)$params['secure'], (bool)$params['httponly']);
        }
        session_destroy();
    }

    public function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public function userId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    public function role(): string
    {
        return (string)($_SESSION['role'] ?? 'guest');
    }

    public function roleRank(): int
    {
        return $this->rankFor($this->role());
    }

    public function hasMinimumRole(string $role): bool
    {
        return $this->roleRank() >= $this->rankFor($role);
    }

    private function rankFor(string $role): int
    {
        return match ($role) {
            'user' => 1,
            'admin' => 2,
            'desenv' => 3,
            default => 0,
        };
    }
}
