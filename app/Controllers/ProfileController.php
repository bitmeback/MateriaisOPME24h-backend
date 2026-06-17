<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Controllers;

use DateTimeImmutable;
use MateriaisOpme\App\Middleware\AuthMiddleware;
use MateriaisOpme\App\Repositories\AuditLogRepository;
use MateriaisOpme\App\Repositories\UserRepository;
use MateriaisOpme\App\Services\AuditService;
use MateriaisOpme\App\Support\Csrf;
use MateriaisOpme\App\Support\View;

final class ProfileController
{
    private AuthMiddleware $auth;
    private UserRepository $users;
    private AuditService $audit;

    public function __construct()
    {
        $this->auth = new AuthMiddleware();
        $this->users = new UserRepository();
        $this->audit = new AuditService();
    }

    public function index(): void
    {
        $this->auth->requireLogin();
        $user = $this->currentUser();
        if ($user === null) {
            http_response_code(404);
            echo 'Usuário não encontrado.';
            return;
        }

        View::render('profile', [
            'csrf_token' => Csrf::token(),
            'user' => $this->presentUser($user),
            'error' => null,
            'success' => null,
        ]);
    }

    public function updatePassword(): void
    {
        $this->auth->requireLogin();
        $user = $this->currentUser();
        if ($user === null) {
            http_response_code(404);
            echo 'Usuário não encontrado.';
            return;
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->renderProfile($user, 'CSRF inválido.');
            return;
        }

        $currentPassword = (string)($_POST['current_password'] ?? '');
        $newPassword = (string)($_POST['new_password'] ?? '');
        $newPasswordConfirm = (string)($_POST['new_password_confirm'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $newPasswordConfirm === '') {
            $this->renderProfile($user, 'Preencha a senha atual e a nova senha duas vezes.');
            return;
        }

        if (strlen($newPassword) < 8) {
            $this->renderProfile($user, 'A nova senha precisa ter pelo menos 8 caracteres.');
            return;
        }

        if ($newPassword !== $newPasswordConfirm) {
            $this->renderProfile($user, 'A confirmação da nova senha não confere.');
            return;
        }

        if (!password_verify($currentPassword, (string)$user['password_hash'])) {
            $this->renderProfile($user, 'Senha atual incorreta.');
            return;
        }

        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($newHash === false) {
            $this->renderProfile($user, 'Falha ao gerar hash da nova senha.');
            return;
        }

        $this->users->updatePassword((int)$user['id'], $newHash);
        session_regenerate_id(true);
        $this->audit->record('update', 'users', (string)$user['id'], [
            'action' => 'password_change',
            'username' => (string)$user['username'],
            'role' => (string)$user['role'],
        ], (int)$user['id']);

        $freshUser = $this->currentUser() ?? $user;
        $this->renderProfile($freshUser, null, 'Senha atualizada com sucesso.');
    }

    private function currentUser(): ?array
    {
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
        if ($userId <= 0) {
            return null;
        }

        $user = $this->users->findById($userId);
        if ($user === null) {
            return null;
        }

        return $user;
    }

    private function presentUser(array $user): array
    {
        return [
            'id' => (int)($user['id'] ?? 0),
            'username' => (string)($user['username'] ?? ''),
            'full_name' => (string)($user['full_name'] ?? ''),
            'role' => (string)($user['role'] ?? ''),
            'role_label' => $this->roleLabel((string)($user['role'] ?? '')),
            'active' => !empty($user['active']),
            'last_login_at' => $this->formatDateTime($user['last_login_at'] ?? null),
            'created_at' => $this->formatDateTime($user['created_at'] ?? null),
            'updated_at' => $this->formatDateTime($user['updated_at'] ?? null),
        ];
    }

    private function roleLabel(string $role): string
    {
        return match ($role) {
            'admin' => 'Administrador',
            'desenv' => 'Desenvolvedor',
            default => 'Usuário',
        };
    }

    private function formatDateTime(mixed $value): string
    {
        $value = is_string($value) ? trim($value) : '';
        if ($value === '') {
            return '-';
        }

        try {
            return (new DateTimeImmutable($value))->format('d/m/Y H:i');
        } catch (\Throwable) {
            return $value;
        }
    }

    private function renderProfile(array $user, ?string $error = null, ?string $success = null): void
    {
        View::render('profile', [
            'csrf_token' => Csrf::token(),
            'user' => $this->presentUser($user),
            'error' => $error,
            'success' => $success,
        ]);
    }
}
