<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Controllers;

use MateriaisOpme\App\Middleware\AuthMiddleware;
use MateriaisOpme\App\Support\Database;
use MateriaisOpme\App\Support\View;
use MateriaisOpme\App\Support\Csrf;

final class RelatorioController
{
    private AuthMiddleware $auth;

    public function __construct()
    {
        $this->auth = new AuthMiddleware();
    }

    public function index(): void
    {
        $this->auth->requireLogin();

        $dataInicio = trim((string)($_GET['data_inicio'] ?? ''));
        $dataFim = trim((string)($_GET['data_fim'] ?? ''));
        $convenio = trim((string)($_GET['convenio'] ?? ''));
        $setor = trim((string)($_GET['setor'] ?? ''));
        $fornecedor = trim((string)($_GET['fornecedor'] ?? ''));
        $material = trim((string)($_GET['material'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(200, (int)($_GET['per_page'] ?? 50)));

        $pdo = Database::pdo();

        $sql = "
            SELECT * FROM resultado_analitico WHERE 1=1
        ";
        $params = [];

        if ($dataInicio !== '') {
            $sql .= ' AND dt_termino >= ?';
            $params[] = $dataInicio . ' 00:00:00';
        }
        if ($dataFim !== '') {
            $sql .= ' AND dt_termino <= ?';
            $params[] = $dataFim . ' 23:59:59';
        }
        if ($convenio !== '') {
            $sql .= ' AND ds_convenio LIKE ?';
            $params[] = '%' . $convenio . '%';
        }
        if ($setor !== '') {
            $sql .= ' AND ds_setor LIKE ?';
            $params[] = '%' . $setor . '%';
        }
        if ($fornecedor !== '') {
            $sql .= ' AND ds_fornecedor LIKE ?';
            $params[] = '%' . $fornecedor . '%';
        }
        if ($material !== '') {
            $sql .= ' AND (ds_material LIKE ? OR CAST(cd_material AS CHAR) LIKE ?)';
            $params[] = '%' . $material . '%';
            $params[] = '%' . $material . '%';
        }

        $countSql = 'SELECT COUNT(*) FROM (' . $sql . ') AS t';
        $total = (int)$pdo->query($countSql)->fetchColumn();

        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $sql .= ' ORDER BY dt_termino DESC, id DESC LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset;
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        $filtrosList = [
            'convenios' => [],
            'setores' => [],
            'fornecedores' => [],
        ];

        if ($filtrosList['convenios'] === []) {
            $stmt = $pdo->query('SELECT DISTINCT ds_convenio FROM resultado_analitico WHERE ds_convenio IS NOT NULL ORDER BY ds_convenio');
            $filtrosList['convenios'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        }
        if ($filtrosList['setores'] === []) {
            $stmt = $pdo->query('SELECT DISTINCT ds_setor FROM resultado_analitico WHERE ds_setor IS NOT NULL ORDER BY ds_setor');
            $filtrosList['setores'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        }
        if ($filtrosList['fornecedores'] === []) {
            $stmt = $pdo->query('SELECT DISTINCT ds_fornecedor FROM resultado_analitico WHERE ds_fornecedor IS NOT NULL ORDER BY ds_fornecedor');
            $filtrosList['fornecedores'] = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        }

        $aggregates = [
            'cirurgias' => 0,
            'itens' => 0,
            'qtdes' => 0.0,
            'vl_conta' => 0.0,
            'lucro' => 0.0,
            'ultima_compra' => 0.0,
        ];

        if ($total > 0) {
            $aggSql = "
                SELECT
                    COUNT(DISTINCT nr_cirurgia) AS cirurgias,
                    COUNT(*) AS itens,
                    COALESCE(SUM(qtde), 0) AS qtdes,
                    COALESCE(SUM(vl_conta), 0) AS vl_conta,
                    COALESCE(SUM(vl_lucro_liq), 0) AS lucro,
                    COALESCE(SUM(vl_ultima_compra), 0) AS ultima_compra
                FROM resultado_analitico WHERE 1=1
            ";
            $aggParams = [];

            if ($dataInicio !== '') {
                $aggSql .= ' AND dt_termino >= ?';
                $aggParams[] = $dataInicio . ' 00:00:00';
            }
            if ($dataFim !== '') {
                $aggSql .= ' AND dt_termino <= ?';
                $aggParams[] = $dataFim . ' 23:59:59';
            }
            if ($convenio !== '') {
                $aggSql .= ' AND ds_convenio LIKE ?';
                $aggParams[] = '%' . $convenio . '%';
            }
            if ($setor !== '') {
                $aggSql .= ' AND ds_setor LIKE ?';
                $aggParams[] = '%' . $setor . '%';
            }
            if ($fornecedor !== '') {
                $aggSql .= ' AND ds_fornecedor LIKE ?';
                $aggParams[] = '%' . $fornecedor . '%';
            }
            if ($material !== '') {
                $aggSql .= ' AND (ds_material LIKE ? OR CAST(cd_material AS CHAR) LIKE ?)';
                $aggParams[] = '%' . $material . '%';
                $aggParams[] = '%' . $material . '%';
            }

            $aggStmt = $pdo->prepare($aggSql);
            $aggStmt->execute($aggParams);
            $aggregates = $aggStmt->fetch() ?: $aggregates;
        }

        View::render('relatorio_resultado', [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
            'filtros' => [
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'convenio' => $convenio,
                'setor' => $setor,
                'fornecedor' => $fornecedor,
                'material' => $material,
            ],
            'listas' => $filtrosList,
            'aggregates' => $aggregates,
            'csrf_token' => Csrf::token(),
            'role' => $_SESSION['role'] ?? 'guest',
        ]);
    }

    public function exportCsv(): void
    {
        $this->auth->requireLogin();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="resultado_opme_' . date('Y-m-d_His') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $dataInicio = trim((string)($_GET['data_inicio'] ?? ''));
        $dataFim = trim((string)($_GET['data_fim'] ?? ''));
        $convenio = trim((string)($_GET['convenio'] ?? ''));
        $setor = trim((string)($_GET['setor'] ?? ''));
        $fornecedor = trim((string)($_GET['fornecedor'] ?? ''));
        $material = trim((string)($_GET['material'] ?? ''));

        $pdo = Database::pdo();
        $sql = "
            SELECT nr_atendimento, nr_cirurgia, cd_material, ds_material, qtde, vl_conta, vl_ultima_compra, vl_lucro_liq,
                   ds_fornecedor, situacao, dt_termino, dt_baixa, ds_convenio, ds_setor
            FROM resultado_analitico WHERE 1=1
        ";
        $params = [];

        if ($dataInicio !== '') {
            $sql .= ' AND dt_termino >= ?';
            $params[] = $dataInicio . ' 00:00:00';
        }
        if ($dataFim !== '') {
            $sql .= ' AND dt_termino <= ?';
            $params[] = $dataFim . ' 23:59:59';
        }
        if ($convenio !== '') {
            $sql .= ' AND ds_convenio LIKE ?';
            $params[] = '%' . $convenio . '%';
        }
        if ($setor !== '') {
            $sql .= ' AND ds_setor LIKE ?';
            $params[] = '%' . $setor . '%';
        }
        if ($fornecedor !== '') {
            $sql .= ' AND ds_fornecedor LIKE ?';
            $params[] = '%' . $fornecedor . '%';
        }
        if ($material !== '') {
            $sql .= ' AND (ds_material LIKE ? OR CAST(cd_material AS CHAR) LIKE ?)';
            $params[] = '%' . $material . '%';
            $params[] = '%' . $material . '%';
        }

        $sql .= ' ORDER BY dt_termino DESC, id DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $out = fopen('php://output', 'w');
        fputcsv($out, [
            'Atendimento', 'Cirurgia', 'Material', 'Descricao', 'Qtde', 'Vl Conta', 'Vl Ultima Compra', 'Lucro Limpo',
            'Fornecedor', 'Situacao', 'Termino', 'Baixa', 'Convenio', 'Setor'
        ], ';');

        foreach ($rows as $r) {
            fputcsv($out, [
                $r['nr_atendimento'] ?? '',
                $r['nr_cirurgia'] ?? '',
                $r['cd_material'] ?? '',
                $r['ds_material'] ?? '',
                $r['qtde'] ?? '',
                $r['vl_conta'] ?? '',
                $r['vl_ultima_compra'] ?? '',
                $r['vl_lucro_liq'] ?? '',
                $r['ds_fornecedor'] ?? '',
                $r['situacao'] ?? '',
                $r['dt_termino'] ?? '',
                $r['dt_baixa'] ?? '',
                $r['ds_convenio'] ?? '',
                $r['ds_setor'] ?? '',
            ], ';');
        }

        fclose($out);
        exit;
    }
}
