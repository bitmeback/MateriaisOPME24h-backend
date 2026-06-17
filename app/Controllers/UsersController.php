<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Controllers;

use DateTimeImmutable;
use MateriaisOpme\App\Middleware\AuthMiddleware;
use MateriaisOpme\App\Repositories\UserRepository;
use MateriaisOpme\App\Services\AuditService;
use MateriaisOpme\App\Support\Csrf;
use MateriaisOpme\App\Support\View;

final class UsersController
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
        $this->auth->requireRole('admin');

        $selectedUser = null;
        $error = null;
        $editId = (int)($_GET['edit'] ?? 0);
        if ($editId > 0) {
            $selectedUser = $this->users->findById($editId);
            if ($selectedUser === null) {
                $error = 'Usuário não encontrado para edição.';
            } elseif (!$this->canManageUser($selectedUser)) {
                $error = 'Administradores não podem editar o usuário Desenvolvedor.';
                $selectedUser = null;
            }
        }

        $filters = $this->normalizeFilters($_GET);
        $users = $this->presentUsers($this->users->all());
        $users = $this->filterUsers($users, $filters);
        $users = $this->sortUsers($users, $filters['sort']);
        $pagination = $this->paginateUsers($users, (int)$filters['page'], (int)$filters['per_page']);

        View::render('users', [
            'csrf_token' => Csrf::token(),
            'users' => $pagination['items'],
            'error' => $error,
            'success' => null,
            'form' => $this->buildForm($selectedUser),
            'form_mode' => $selectedUser !== null ? 'edit' : 'create',
            'filters' => $filters,
            'pagination' => $pagination,
            'username_hint' => null,
            'password_hint' => null,
        ]);
    }

    public function store(): void
    {
        $this->auth->requireRole('admin');

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->renderUsers('CSRF inválido.', null, $_POST);
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $isEdit = $id > 0 || (string)($_POST['form_mode'] ?? '') === 'edit';
        $existing = $id > 0 ? $this->users->findById($id) : null;
        if ($id > 0 && $existing === null) {
            $this->renderUsers('Usuário não encontrado para edição.', null, $_POST);
            return;
        }
        if ($existing !== null && !$this->canManageUser($existing)) {
            $this->renderUsers('Administradores não podem editar o usuário Desenvolvedor.', null, $_POST);
            return;
        }

        $form = $this->normalizeForm($_POST);
        $password = trim((string)($_POST['password'] ?? ''));
        $passwordConfirm = trim((string)($_POST['password_confirm'] ?? ''));

        if ($form['username'] === '' || $form['full_name'] === '') {
            $this->renderUsers('Preencha o usuário e o nome completo.', null, $_POST);
            return;
        }

        if (strlen($form['username']) > 80) {
            $this->renderUsers('O usuário precisa ter no máximo 80 caracteres.', null, $_POST, 'O usuário precisa ter no máximo 80 caracteres.');
            return;
        }

        if (!$this->isValidUsername($form['username'])) {
            $this->renderUsers('Use apenas letras, números, ponto, underline e hífen no usuário.', null, $_POST, 'Use apenas letras, números, ponto, underline e hífen no usuário.');
            return;
        }

        if (strlen($form['full_name']) > 150) {
            $this->renderUsers('O nome completo precisa ter no máximo 150 caracteres.', null, $_POST);
            return;
        }

        if (!in_array($form['role'], ['user', 'admin', 'desenv'], true)) {
            $this->renderUsers('Perfil inválido.', null, $_POST);
            return;
        }

        $duplicate = $this->users->findByUsername($form['username']);
        if ($duplicate !== null && (int)$duplicate['id'] !== $id) {
            $this->renderUsers('Já existe um usuário com esse login.', null, $_POST, 'Já existe um usuário com esse login.');
            return;
        }

        $shouldUpdatePassword = $password !== '' || $passwordConfirm !== '';
        if (!$isEdit && !$shouldUpdatePassword) {
            $this->renderUsers('Informe uma senha para cadastrar o usuário.', null, $_POST, null, 'A senha precisa ter pelo menos 8 caracteres.');
            return;
        }

        if ($shouldUpdatePassword) {
            if ($password === '' || $passwordConfirm === '') {
                $this->renderUsers($isEdit ? 'Preencha e confirme a nova senha.' : 'Informe uma senha para cadastrar o usuário.', null, $_POST, null, 'A senha precisa ter pelo menos 8 caracteres.');
                return;
            }

            if (strlen($password) < 8) {
                $this->renderUsers('A senha precisa ter pelo menos 8 caracteres.', null, $_POST, null, 'A senha precisa ter pelo menos 8 caracteres.');
                return;
            }

            if ($password !== $passwordConfirm) {
                $this->renderUsers('A confirmação da senha não confere.', null, $_POST, null, 'A senha precisa ter pelo menos 8 caracteres.');
                return;
            }
        }

        $payload = [
            'username' => $form['username'],
            'full_name' => $form['full_name'],
            'role' => $form['role'],
            'active' => $form['active'],
        ];

        try {
            if ($isEdit) {
                $this->users->update($id, $payload);
                if ($shouldUpdatePassword) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    if ($hash === false) {
                        $this->renderUsers('Falha ao gerar hash da senha.', null, $_POST, null, 'A senha precisa ter pelo menos 8 caracteres.');
                        return;
                    }
                    $this->users->updatePassword($id, $hash);
                }

                $this->audit->record('update', 'users', (string)$id, [
                    'username' => $payload['username'],
                    'full_name' => $payload['full_name'],
                    'role' => $payload['role'],
                    'active' => (bool)$payload['active'],
                    'password_changed' => $shouldUpdatePassword,
                ]);

                $this->renderUsers(null, 'Usuário atualizado com sucesso.', []);
                return;
            }

            $hash = password_hash($password, PASSWORD_DEFAULT);
            if ($hash === false) {
                $this->renderUsers('Falha ao gerar hash da senha.', null, $_POST, null, 'A senha precisa ter pelo menos 8 caracteres.');
                return;
            }

            $newId = $this->users->create([
                'username' => $payload['username'],
                'password_hash' => $hash,
                'full_name' => $payload['full_name'],
                'role' => $payload['role'],
                'active' => $payload['active'],
            ]);

            $this->audit->record('create', 'users', (string)$newId, [
                'username' => $payload['username'],
                'full_name' => $payload['full_name'],
                'role' => $payload['role'],
                'active' => (bool)$payload['active'],
            ]);

            $this->renderUsers(null, 'Usuário criado com sucesso.', []);
        } catch (\Throwable $e) {
            $message = $e->getMessage();
            if (stripos($message, 'duplicate') !== false || stripos($message, 'uq_users_username') !== false) {
                $message = 'Já existe um usuário com esse login.';
            }
            $this->renderUsers($message, null, $_POST);
        }
    }

    public function delete(string $id = ''): void
    {
        $this->auth->requireRole('admin');

        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            $this->renderUsers('CSRF inválido.', null, []);
            return;
        }

        $userId = (int)$id;
        if ($userId <= 0) {
            $this->renderUsers('ID de usuário inválido.', null, []);
            return;
        }

        $currentUserId = $this->currentUserId();
        if ($currentUserId !== null && $currentUserId === $userId) {
            $this->renderUsers('Você não pode excluir o próprio usuário.', null, []);
            return;
        }

        $user = $this->users->findById($userId);
        if ($user === null) {
            $this->renderUsers('Usuário não encontrado.', null, []);
            return;
        }
        if (!$this->canManageUser($user)) {
            $this->renderUsers('Administradores não podem excluir o usuário Desenvolvedor.', null, []);
            return;
        }

        try {
            $this->users->delete($userId);
            $this->audit->record('delete', 'users', (string)$userId, [
                'username' => (string)($user['username'] ?? ''),
                'full_name' => (string)($user['full_name'] ?? ''),
                'role' => (string)($user['role'] ?? ''),
            ]);
            $this->renderUsers(null, 'Usuário excluído com sucesso.', []);
        } catch (\Throwable $e) {
            $this->renderUsers($e->getMessage(), null, []);
        }
    }

    private function renderUsers(?string $error, ?string $success, array $form, ?string $usernameHint = null, ?string $passwordHint = null): void
    {
        $selectedUser = null;
        $formData = $this->normalizeForm($form);
        if (($formData['id'] ?? 0) > 0) {
            $selectedUser = $this->users->findById((int)$formData['id']);
        }

        $allUsers = $this->presentUsers($this->users->all());
        $pagination = $this->paginateUsers(
            $allUsers,
            max(1, (int)($form['page'] ?? 1)),
            max(10, min(100, ((int)($form['per_page'] ?? 10) ?: 10)))
        );

        View::render('users', [
            'csrf_token' => Csrf::token(),
            'users' => $pagination['items'],
            'error' => $error,
            'success' => $success,
            'form' => $selectedUser !== null ? $this->buildForm($selectedUser, $formData) : $this->buildForm(null, $formData),
            'form_mode' => ($selectedUser !== null || (int)($formData['id'] ?? 0) > 0 || (string)($formData['form_mode'] ?? '') === 'edit') ? 'edit' : 'create',
            'pagination' => $pagination,
            'username_hint' => $usernameHint,
            'password_hint' => $passwordHint,
        ]);
    }

    private function normalizeFilters(array $query): array
    {
        $role = (string)($query['role'] ?? 'all');
        $active = (string)($query['active'] ?? 'all');
        $sort = (string)($query['sort'] ?? 'default');
        $page = max(1, (int)($query['page'] ?? 1));
        $perPage = max(10, min(100, ((int)($query['per_page'] ?? 10) ?: 10)));

        return [
            'q' => trim((string)($query['q'] ?? '')),
            'role' => in_array($role, ['all', 'user', 'admin', 'desenv'], true) ? $role : 'all',
            'active' => in_array($active, ['all', '1', '0'], true) ? $active : 'all',
            'sort' => in_array($sort, ['default', 'username_asc', 'username_desc', 'full_name_asc', 'full_name_desc', 'role_asc', 'role_desc', 'active_asc', 'active_desc'], true) ? $sort : 'default',
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    private function paginateUsers(array $users, int $page, int $perPage): array
    {
        $total = count($users);
        $totalPages = max(1, (int)ceil($total / max(1, $perPage)));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * max(1, $perPage);

        return [
            'items' => array_slice($users, $offset, $perPage),
            'total' => $total,
            'per_page' => $perPage,
            'page' => $page,
            'total_pages' => $totalPages,
        ];
    }

    private function filterUsers(array $users, array $filters): array
    {
        $query = $this->normalizeSearchText((string)($filters['q'] ?? ''));
        $role = (string)($filters['role'] ?? 'all');
        $active = (string)($filters['active'] ?? 'all');

        return array_values(array_filter($users, function (array $user) use ($query, $role, $active): bool {
            if ($role !== 'all' && (string)($user['role'] ?? '') !== $role) {
                return false;
            }

            if ($active !== 'all' && ((int)($user['active'] ?? 0) === 1) !== ($active === '1')) {
                return false;
            }

            if ($query === '') {
                return true;
            }

            $haystack = implode(' ', [
                (string)($user['username'] ?? ''),
                (string)($user['full_name'] ?? ''),
                (string)($user['role'] ?? ''),
                (string)($user['role_label'] ?? $this->roleLabel((string)($user['role'] ?? ''))),
                ((int)($user['active'] ?? 0) === 1) ? 'ativo' : 'inativo',
            ]);

            return str_contains($this->normalizeSearchText($haystack), $query);
        }));
    }

    private function sortUsers(array $users, string $sort): array
    {
        if ($sort === 'default') {
            return $users;
        }

        usort($users, function (array $left, array $right) use ($sort): int {
            return match ($sort) {
                'username_desc' => $this->compareText((string)($right['username'] ?? ''), (string)($left['username'] ?? '')),
                'full_name_asc' => $this->compareText((string)($left['full_name'] ?? ''), (string)($right['full_name'] ?? '')),
                'full_name_desc' => $this->compareText((string)($right['full_name'] ?? ''), (string)($left['full_name'] ?? '')),
                'role_asc' => $this->compareRole($left, $right),
                'role_desc' => $this->compareRole($right, $left),
                'active_asc' => $this->compareActive($left, $right),
                'active_desc' => $this->compareActive($right, $left),
                default => $this->compareText((string)($left['username'] ?? ''), (string)($right['username'] ?? '')),
            };
        });

        return $users;
    }

    private function compareRole(array $left, array $right): int
    {
        $order = ['user' => 0, 'admin' => 1, 'desenv' => 2];
        $leftRole = $order[(string)($left['role'] ?? 'user')] ?? 0;
        $rightRole = $order[(string)($right['role'] ?? 'user')] ?? 0;

        if ($leftRole === $rightRole) {
            return $this->compareText((string)($left['username'] ?? ''), (string)($right['username'] ?? ''));
        }

        return $leftRole <=> $rightRole;
    }

    private function compareActive(array $left, array $right): int
    {
        $leftActive = !empty($left['active']) ? 1 : 0;
        $rightActive = !empty($right['active']) ? 1 : 0;

        if ($leftActive === $rightActive) {
            return $this->compareText((string)($left['username'] ?? ''), (string)($right['username'] ?? ''));
        }

        return $leftActive <=> $rightActive;
    }

    private function compareText(string $left, string $right): int
    {
        return strcmp($this->normalizeSearchText($left), $this->normalizeSearchText($right));
    }

    private function normalizeSearchText(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
            if (is_string($converted) && $converted !== '') {
                $value = $converted;
            }
        }

        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }

    private function buildForm(?array $user, array $fallback = []): array
    {
        $base = [
            'id' => 0,
            'username' => '',
            'full_name' => '',
            'role' => 'user',
            'active' => true,
        ];

        if ($user !== null) {
            $base = [
                'id' => (int)($user['id'] ?? 0),
                'username' => (string)($user['username'] ?? ''),
                'full_name' => (string)($user['full_name'] ?? ''),
                'role' => (string)($user['role'] ?? 'user'),
                'active' => !empty($user['active']),
            ];
        }

        $fallbackHasActive = array_key_exists('active', $fallback);
        $fallback = $this->normalizeForm($fallback);
        if (($fallback['id'] ?? 0) > 0) {
            $base['id'] = (int)$fallback['id'];
        }

        foreach (['username', 'full_name', 'role'] as $key) {
            if (array_key_exists($key, $fallback) && $fallback[$key] !== '') {
                $base[$key] = $fallback[$key];
            }
        }

        if ($fallbackHasActive) {
            $base['active'] = (bool)$fallback['active'];
        }

        return $base;
    }

    private function normalizeForm(array $form): array
    {
        return [
            'id' => (int)($form['id'] ?? 0),
            'username' => trim((string)($form['username'] ?? '')),
            'full_name' => trim((string)($form['full_name'] ?? '')),
            'role' => in_array((string)($form['role'] ?? 'user'), ['user', 'admin', 'desenv'], true) ? (string)$form['role'] : 'user',
            'active' => array_key_exists('active', $form) ? (bool)$form['active'] : false,
            'form_mode' => (string)($form['form_mode'] ?? 'create') === 'edit' ? 'edit' : 'create',
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

    private function isValidUsername(string $username): bool
    {
        return $username !== '' && (bool)preg_match('/^[A-Za-z0-9._-]+$/', $username);
    }

    private function presentUsers(array $users): array
    {
        $items = [];
        foreach ($users as $user) {
            $items[] = [
                'id' => (int)($user['id'] ?? 0),
                'username' => (string)($user['username'] ?? ''),
                'full_name' => (string)($user['full_name'] ?? ''),
                'role' => (string)($user['role'] ?? ''),
                'role_label' => $this->roleLabel((string)($user['role'] ?? '')),
                'active' => !empty($user['active']),
                'last_login_at' => $this->formatDateTime($user['last_login_at'] ?? null),
                'created_at' => $this->formatDateTime($user['created_at'] ?? null),
                'updated_at' => $this->formatDateTime($user['updated_at'] ?? null),
                'can_manage' => $this->canManageUser($user),
            ];
        }

        return $items;
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

    private function currentUserId(): ?int
    {
        return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
    }

    private function canManageUser(array $user): bool
    {
        if ($this->currentUserRole() === 'admin' && (string)($user['role'] ?? '') === 'desenv') {
            return false;
        }

        return true;
    }

    private function currentUserRole(): string
    {
        return (string)($_SESSION['role'] ?? 'guest');
    }
}
