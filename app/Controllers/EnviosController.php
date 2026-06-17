<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Controllers;

use MateriaisOpme\App\Repositories\EnviosRelatoriosRepository;
use MateriaisOpme\App\Support\View;

final class EnviosController
{
    public function index(): void
    {
        $filters = [
            'data_de'     => trim((string)($_GET['data_de'] ?? '')),
            'data_ate'    => trim((string)($_GET['data_ate'] ?? '')),
            'fornecedor'  => trim((string)($_GET['fornecedor'] ?? '')),
            'tipo_envio'  => trim((string)($_GET['tipo_envio'] ?? '')),
            'page'        => max(1, (int)($_GET['page'] ?? 1)),
            'per_page'    => max(1, min(200, (int)($_GET['per_page'] ?? 50))),
        ];

        $repo = new EnviosRelatoriosRepository();
        $result = $repo->list($filters);

        $success = (string)($_SESSION['flash_success'] ?? '');
        $error = (string)($_SESSION['flash_error'] ?? '');
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        View::render('enviados', [
            'items'      => $result['items'],
            'total'      => $result['total'],
            'page'       => $result['page'],
            'per_page'   => $result['per_page'],
            'total_pages' => $result['total_pages'],
            'filters'    => $filters,
            'success'    => $success,
            'error'      => $error,
        ]);
    }
}
