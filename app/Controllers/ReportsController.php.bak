<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Controllers;

use MateriaisOpme\App\Repositories\ReportRecipientsRepository;
use MateriaisOpme\App\Support\Csrf;
use MateriaisOpme\App\Support\View;

final class ReportsController
{
    private ReportRecipientsRepository $repo;

    public function __construct()
    {
        $this->repo = new ReportRecipientsRepository();
    }

    public function index(): void
    {
        $items = $this->repo->all();
        $success = (string)($_SESSION['flash_success'] ?? '');
        $error = (string)($_SESSION['flash_error'] ?? '');
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        View::render('reports', [
            'items' => $items,
            'csrf_token' => Csrf::token(),
            'success' => $success,
            'error' => $error,
        ]);
    }

    public function store(): void
    {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido.';
            return;
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $type = trim((string)($_POST['type'] ?? 'email'));
        $active = isset($_POST['active']) ? 1 : 0;

        if ($name === '') {
            $_SESSION['flash_error'] = 'Nome é obrigatório.';
            header('Location: /reports');
            exit;
        }

        if ($email === '' && $phone === '') {
            $_SESSION['flash_error'] = 'Informe pelo menos um e-mail ou telefone.';
            header('Location: /reports');
            exit;
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'E-mail inválido.';
            header('Location: /reports');
            exit;
        }

        if (!in_array($type, ['email', 'whatsapp', 'both'], true)) {
            $type = 'email';
        }

        try {
            $this->repo->create([
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'type' => $type,
                'active' => $active,
            ]);
            $_SESSION['flash_success'] = 'Destinatário cadastrado com sucesso.';
            header('Location: /reports');
            exit;
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Erro ao cadastrar: ' . $e->getMessage();
            header('Location: /reports');
            exit;
        }
    }

    public function delete(string $id = ''): void
    {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido.';
            return;
        }

        $id = (int)$id;
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'ID inválido.';
            header('Location: /reports');
            exit;
        }

        try {
            $this->repo->delete($id);
            $_SESSION['flash_success'] = 'Destinatário removido com sucesso.';
            header('Location: /reports');
            exit;
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Erro ao remover: ' . $e->getMessage();
            header('Location: /reports');
            exit;
        }
    }

    public function edit(string $id = ''): void
    {
        $id = (int)$id;
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'ID inválido.';
            header('Location: /reports');
            exit;
        }

        $recipient = $this->repo->findById($id);
        if ($recipient === null) {
            $_SESSION['flash_error'] = 'Destinatário não encontrado.';
            header('Location: /reports');
            exit;
        }

        $success = (string)($_SESSION['flash_success'] ?? '');
        $error = (string)($_SESSION['flash_error'] ?? '');
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        View::render('reports', [
            'items' => $this->repo->all(),
            'csrf_token' => Csrf::token(),
            'success' => $success,
            'error' => $error,
            'edit' => $recipient,
        ]);
    }

    public function update(string $id = ''): void
    {
        if (!Csrf::validate($_POST['csrf_token'] ?? null)) {
            http_response_code(400);
            echo 'CSRF inválido.';
            return;
        }

        $id = (int)$id;
        if ($id <= 0) {
            $_SESSION['flash_error'] = 'ID inválido.';
            header('Location: /reports');
            exit;
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $type = trim((string)($_POST['type'] ?? 'email'));
        $active = isset($_POST['active']) ? 1 : 0;

        if ($name === '') {
            $_SESSION['flash_error'] = 'Nome é obrigatório.';
            header("Location: /reports/editar/{$id}");
            exit;
        }

        if ($email === '' && $phone === '') {
            $_SESSION['flash_error'] = 'Informe pelo menos um e-mail ou telefone.';
            header("Location: /reports/editar/{$id}");
            exit;
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash_error'] = 'E-mail inválido.';
            header("Location: /reports/editar/{$id}");
            exit;
        }

        if (!in_array($type, ['email', 'whatsapp', 'both'], true)) {
            $type = 'email';
        }

        try {
            $this->repo->update($id, [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'type' => $type,
                'active' => $active,
            ]);
            $_SESSION['flash_success'] = 'Destinatário atualizado com sucesso.';
            header('Location: /reports');
            exit;
        } catch (\Throwable $e) {
            $_SESSION['flash_error'] = 'Erro ao atualizar: ' . $e->getMessage();
            header("Location: /reports/editar/{$id}");
            exit;
        }
    }
}
