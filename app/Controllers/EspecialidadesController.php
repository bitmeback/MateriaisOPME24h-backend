<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Controllers;

use MateriaisOpme\App\Middleware\AuthMiddleware;
use MateriaisOpme\App\Services\EspecialidadesService;
use MateriaisOpme\App\Support\Csrf;
use MateriaisOpme\App\Support\View;

final class EspecialidadesController
{
    private EspecialidadesService $service;
    private AuthMiddleware $auth;

    public function __construct()
    {
        $this->service = new EspecialidadesService();
        $this->auth = new AuthMiddleware();
    }

    public function index(): void
    {
        $this->auth->requireLogin();

        $busca = trim((string)($_GET['q'] ?? ''));
        $filtro_status = trim((string)($_GET['status'] ?? ''));
        $sort = trim((string)($_GET['sort'] ?? 'nome_asc'));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 10)));

        $especialidades = $this->service->listAll();
        $fornecedores = $this->getFornecedoresList();

        // Aplicar filtro de busca
        if ($busca !== '') {
            $especialidades = array_filter($especialidades, function ($esp) use ($busca) {
                return stripos($esp['nome'], $busca) !== false;
            });
            $especialidades = array_values($especialidades);
        }

        // Aplicar filtro de status
        if ($filtro_status !== '') {
            $especialidades = array_filter($especialidades, function ($esp) use ($filtro_status) {
                return (int)$esp['ativo'] === (int)$filtro_status;
            });
            $especialidades = array_values($especialidades);
        }

        // Aplicar ordenação
        $sortMap = [
            'nome_asc' => fn($a, $b) => strcasecmp($a['nome'], $b['nome']),
            'nome_desc' => fn($a, $b) => strcasecmp($b['nome'], $a['nome']),
            'id_asc' => fn($a, $b) => $a['id'] <=> $b['id'],
            'id_desc' => fn($a, $b) => $b['id'] <=> $a['id'],
        ];
        if (isset($sortMap[$sort])) {
            usort($especialidades, $sortMap[$sort]);
        }

        // Paginação
        $total = count($especialidades);
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;
        $especialidadesPaginadas = array_slice($especialidades, $offset, $perPage);

        $pagination = [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];

        View::render('especialidades', [
            'especialidades' => $especialidadesPaginadas,
            'fornecedores' => $fornecedores,
            'csrf_token' => Csrf::token(),
            'busca' => $busca,
            'filtro_status' => $filtro_status,
            'sort' => $sort,
            'pagination' => $pagination,
        ]);
    }

    public function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /especialidades');
            exit;
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo 'Token CSRF inválido.';
            exit;
        }

        $nome = trim($_POST['nome'] ?? '');
        if ($nome === '') {
            $_SESSION['error'] = 'Nome da especialidade é obrigatório.';
            header('Location: /especialidades');
            exit;
        }

        $this->service->create($nome);
        $_SESSION['success'] = 'Especialidade cadastrada com sucesso.';
        header('Location: /especialidades');
        exit;
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /especialidades');
            exit;
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo 'Token CSRF inválido.';
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $ativo = isset($_POST['ativo']) ? 1 : 0;

        if ($id === 0 || $nome === '') {
            $_SESSION['error'] = 'Dados inválidos.';
            header('Location: /especialidades');
            exit;
        }

        $this->service->update($id, $nome, (bool)$ativo);
        $_SESSION['success'] = 'Especialidade atualizada.';
        header('Location: /especialidades');
        exit;
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /especialidades');
            exit;
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo 'Token CSRF inválido.';
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $this->service->deactivate($id);
            $_SESSION['success'] = 'Especialidade desativada.';
        }

        header('Location: /especialidades');
        exit;
    }

    public function syncFornecedor(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /especialidades');
            exit;
        }

        if (!Csrf::validate($_POST['csrf_token'] ?? '')) {
            http_response_code(403);
            echo 'Token CSRF inválido.';
            exit;
        }

        $espId = (int)($_POST['especialidade_id'] ?? 0);
        $cnpjs = array_map(function($c) { return preg_replace('/[^0-9]/', '', $c); }, $_POST['fornecedores'] ?? []);

        if ($espId <= 0) {
            $_SESSION['error'] = 'Especialidade inválida.';
            header('Location: /especialidades');
            exit;
        }

        $pdo = \MateriaisOpme\App\Support\Database::pdo();
        $pdo->beginTransaction();

        try {
            // Remove associações existentes para esta especialidade
            $stmt = $pdo->prepare('DELETE FROM consumo_fornecedor_especialidade WHERE id_especialidade = ?');
            $stmt->execute([$espId]);

            // Insere novas
            if (!empty($cnpjs)) {
                $placeholders = implode(',', array_fill(0, count($cnpjs), '(?, ?)'));
                $sql = "INSERT INTO consumo_fornecedor_especialidade (cnpj_fornecedor, id_especialidade) VALUES $placeholders";
                $stmt = $pdo->prepare($sql);

                $params = [];
                foreach ($cnpjs as $cnpj) {
                    if (strlen($cnpj) === 14) {
                        $params[] = $cnpj;
                        $params[] = $espId;
                    }
                }
                if (!empty($params)) {
                    $stmt->execute($params);
                }
            }

            $pdo->commit();
            $_SESSION['success'] = 'Associações salvas com sucesso (' . count($cnpjs) . ' fornecedor(es).';
        } catch (\Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Erro ao salvar: ' . $e->getMessage();
        }

        header('Location: /especialidades');
        exit;
    }

    public function getFornecedorEspecialidades(): void
    {
        $cnpj = preg_replace('/[^0-9]/', '', $_GET['cnpj'] ?? '');
        if (strlen($cnpj) !== 14) {
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }

        $associadas = $this->service->getEspecialidadesByFornecedor($cnpj);
        $naoAssociadas = $this->service->getEspecialidadesNaoAssociadas($cnpj);

        header('Content-Type: application/json');
        echo json_encode([
            'associadas' => $associadas,
            'nao_associadas' => $naoAssociadas,
        ]);
        exit;
    }

    public function getEspecialidadeFornecedores(): void
    {
        $espId = (int)($_GET['id'] ?? 0);
        if ($espId <= 0) {
            header('Content-Type: application/json');
            echo json_encode([]);
            exit;
        }

        $associados = $this->service->getFornecedoresByEspecialidade($espId);
        $naoAssociados = $this->service->getFornecedoresNaoByEspecialidade($espId);

        header('Content-Type: application/json');
        echo json_encode([
            'associados' => $associados,
            'nao_associados' => $naoAssociados,
        ]);
        exit;
    }

    private function getFornecedoresList(): array
    {
        $pdo = \MateriaisOpme\App\Support\Database::pdo();
        $stmt = $pdo->query('SELECT cnpj, name, status FROM consumo_fornecedores ORDER BY name LIMIT 500');
        return $stmt->fetchAll();
    }
}
