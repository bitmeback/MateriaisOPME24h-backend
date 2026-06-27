<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Controllers;

use MateriaisOpme\App\Middleware\AuthMiddleware;
use MateriaisOpme\App\Support\Database;
use MateriaisOpme\App\Support\View;
use MateriaisOpme\App\Support\Csrf;

final class ConsumoController
{
    private AuthMiddleware $auth;

    public function __construct()
    {
        $this->auth = new AuthMiddleware();
    }

    public function index(): void
    {
        $this->auth->requireLogin();

        $q = trim((string)($_GET['q'] ?? ''));
        $status_filtro = trim((string)($_GET['status'] ?? ''));
        $sort = trim((string)($_GET['sort'] ?? 'status_ratio'));
        $filtro_vinculo = trim((string)($_GET['vinculo'] ?? 'ativos')); // padrão: apenas ativos
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, min(100, (int)($_GET['per_page'] ?? 20)));

        $pdo = Database::pdo();

        // 1. Obter a lista de relações desativadas (blacklist) na memória para cruzamento veloz
        $stmtInativas = $pdo->query('SELECT cd_material, cnpj_fornecedor FROM consumo_relacoes_inativas');
        $blacklist = [];
        while ($row = $stmtInativas->fetch()) {
            $key = $row['cd_material'] . '_' . $row['cnpj_fornecedor'];
            $blacklist[$key] = true;
        }

        // 2. Query principal do fluxo analítico
        $sql = "
            SELECT 
                s.cd_material,
                s.cd_fornec_consignado AS cnpj_fornecedor,
                COALESCE(f.name, 'Não identificado') AS ds_fornecedor,
                s.saldo,
                COALESCE(c.descricao, 'Material Desconhecido') AS descricao,
                CEIL(SUM(c.consumo) / 3) AS media_trimestre
            FROM saldo_estoque_atual s
            LEFT JOIN consumo_materiais c ON s.cd_material = c.codigo
            LEFT JOIN consumo_fornecedores f ON f.cnpj = s.cd_fornec_consignado
            LEFT JOIN consumo_fornecedor_especialidade cfe ON cfe.cnpj_fornecedor = s.cd_fornec_consignado AND cfe.id_especialidade = 1
            WHERE c.ano = 2025 
              AND c.mes >= 3
              AND cfe.id_especialidade IS NULL
            GROUP BY s.cd_material, s.cd_fornec_consignado, c.descricao, f.name, s.saldo
            HAVING media_trimestre > 1
        ";

        $stmt = $pdo->query($sql);
        $rawItems = $stmt->fetchAll();

        $processedItems = [];

        foreach ($rawItems as $row) {
            $codigo = (int)$row['cd_material'];
            $cnpj = (string)$row['cnpj_fornecedor'];
            $key = $codigo . '_' . $cnpj;

            // Verificar se o vínculo está ativo ou inativo
            $vinculo_ativo = !isset($blacklist[$key]);

            // Aplicar o filtro de vínculo do select superior
            if ($filtro_vinculo === 'ativos' && !$vinculo_ativo) {
                continue; // oculta inativos
            }
            if ($filtro_vinculo === 'inativos' && $vinculo_ativo) {
                continue; // oculta ativos
            }

            $saldo = (float)$row['saldo'];
            $media = (float)$row['media_trimestre'];
            $desc = (string)$row['descricao'];
            $forn = (string)$row['ds_fornecedor'];

            $threshold_critico = (float)ceil($media * 0.95);
            $threshold_warning = (float)ceil($media * 1.05);

            $status = 'normal';
            $status_desc = 'Saudável';

            if ($saldo <= $threshold_critico) {
                $status = 'critico';
                $status_desc = 'Crítico';
            } elseif ($saldo <= $threshold_warning) {
                $status = 'alerta';
                $status_desc = 'Alerta';
            }

            // Ratio de criticidade para ordenação (menor ratio = mais crítico)
            $ratio = $media > 0 ? ($saldo / $media) : 999.0;

            $processedItems[] = [
                'codigo' => $codigo,
                'descricao' => $desc,
                'cnpj_fornecedor' => $cnpj,
                'fornecedor' => $forn,
                'saldo' => $saldo,
                'media' => $media,
                'threshold_critico' => $threshold_critico,
                'threshold_warning' => $threshold_warning,
                'status' => $status,
                'status_desc' => $status_desc,
                'ratio' => $ratio,
                'vinculo_ativo' => $vinculo_ativo
            ];
        }

        // Aplicação de filtros textuais
        if ($q !== '') {
            $processedItems = array_filter($processedItems, function ($item) use ($q) {
                return (stripos((string)$item['codigo'], $q) !== false) || 
                       (stripos($item['descricao'], $q) !== false) ||
                       (stripos($item['fornecedor'], $q) !== false);
            });
        }

        // Filtro de status de estoque
        if ($status_filtro !== '') {
            $processedItems = array_filter($processedItems, function ($item) use ($status_filtro) {
                return $item['status'] === $status_filtro;
            });
        }

        // Ordenação
        usort($processedItems, function ($a, $b) use ($sort) {
            if ($sort === 'status_ratio') {
                $statusWeights = ['critico' => 1, 'alerta' => 2, 'normal' => 3];
                $wa = $statusWeights[$a['status']] ?? 3;
                $wb = $statusWeights[$b['status']] ?? 3;
                if ($wa !== $wb) {
                    return $wa <=> $wb;
                }
                return $a['ratio'] <=> $b['ratio'];
            } elseif ($sort === 'nome_asc') {
                return strcasecmp($a['descricao'], $b['descricao']);
            } elseif ($sort === 'nome_desc') {
                return strcasecmp($b['descricao'], $a['descricao']);
            } elseif ($sort === 'codigo_asc') {
                return $a['codigo'] <=> $b['codigo'];
            } elseif ($sort === 'codigo_desc') {
                return $b['codigo'] <=> $a['codigo'];
            } elseif ($sort === 'saldo_asc') {
                return $a['saldo'] <=> $b['saldo'];
            } elseif ($sort === 'saldo_desc') {
                return $b['saldo'] <=> $a['saldo'];
            } elseif ($sort === 'media_desc') {
                return $b['media'] <=> $a['media'];
            }
            return 0;
        });

        // Contagens correspondentes aos cards superiores baseados na lista atual sob visibilidade
        $totalItemsCount = count($processedItems);
        $criticosCount = count(array_filter($processedItems, fn($i) => $i['status'] === 'critico'));
        $alertasCount = count(array_filter($processedItems, fn($i) => $i['status'] === 'alerta'));
        $saudavelCount = count(array_filter($processedItems, fn($i) => $i['status'] === 'normal'));

        // Paginação
        $total = count($processedItems);
        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;
        $itemsPaginados = array_slice($processedItems, $offset, $perPage);

        $pagination = [
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];

        View::render('consumo', [
            'items' => $itemsPaginados,
            'busca' => $q,
            'filtro_status' => $status_filtro,
            'filtro_vinculo' => $filtro_vinculo,
            'sort' => $sort,
            'pagination' => $pagination,
            'total_count' => $totalItemsCount,
            'critico_count' => $criticosCount,
            'alerta_count' => $alertasCount,
            'saudavel_count' => $saudavelCount,
            'csrf_token' => Csrf::token()
        ]);
    }

    /**
     * Endpoint silencioso AJAX que recebe o toggle de ligar/desligar vínculos
     * do estoque de consumo.
     */
    public function toggleVinculo(): void
    {
        $this->auth->requireLogin();

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }

        // Validação CSRF silencioso via cabeçalhos HTTP customizados do Javascript
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
        if (!Csrf::validate($token)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Token CSRF inválido ou expirado']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        $cd_material = (int)($input['cd_material'] ?? 0);
        $cnpj_fornecedor = preg_replace('/[^0-9]/', '', (string)($input['cnpj_fornecedor'] ?? ''));
        $ativo = filter_var($input['ativo'] ?? true, FILTER_VALIDATE_BOOL);

        if ($cd_material <= 0 || strlen($cnpj_fornecedor) !== 14) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Dados de material ou fornecedor inválidos']);
            exit;
        }

        $pdo = Database::pdo();

        try {
            if ($ativo) {
                // Se está ativo no painel, ele deve ser REMOVIDO da blacklist de inativos
                $stmt = $pdo->prepare('DELETE FROM consumo_relacoes_inativas WHERE cd_material = ? AND cnpj_fornecedor = ?');
                $stmt->execute([$cd_material, $cnpj_fornecedor]);
                $action = 'reativado';
            } else {
                // Se foi inativado no painel, deve ser INSERIDO na blacklist
                $stmt = $pdo->prepare('INSERT IGNORE INTO consumo_relacoes_inativas (cd_material, cnpj_fornecedor) VALUES (?, ?)');
                $stmt->execute([$cd_material, $cnpj_fornecedor]);
                $action = 'desativado';
            }

            echo json_encode(['success' => true, 'action' => $action]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erro interno na transação de dados: ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Página de Relatórios de Consumo com filtros e exportação CSV.
     */
    public function relatorios(): void
    {
        $this->auth->requireLogin();

        $data_inicio = trim((string)($_GET['data_inicio'] ?? ''));
        $data_fim = trim((string)($_GET['data_fim'] ?? ''));
        $status_filtro = trim((string)($_GET['status'] ?? ''));
        $id_especialidade = (int)($_GET['id_especialidade'] ?? 0);
        $id_fornecedor = (int)($_GET['id_fornecedor'] ?? 0);

        $pdo = Database::pdo();

        // 1. Blacklist de relações inativas
        $stmtInativas = $pdo->query('SELECT cd_material, cnpj_fornecedor FROM consumo_relacoes_inativas');
        $blacklist = [];
        while ($row = $stmtInativas->fetch()) {
            $key = $row['cd_material'] . '_' . $row['cnpj_fornecedor'];
            $blacklist[$key] = true;
        }

        // 2. Query base para histórico de status transacional
        $sql = "
            SELECT 
                h.cd_material,
                h.cnpj_fornecedor,
                COALESCE(c.descricao, 'Material Desconhecido') AS descricao,
                COALESCE(f.name, 'Não identificado') AS fornecedor,
                h.status_anterior,
                h.status_novo,
                h.saldo_momento,
                h.media_momento,
                DATE_FORMAT(h.data_transicao, '%d/%m/%Y %H:%i') AS data_formatada,
                h.data_transicao
            FROM consumo_status_historico h
            LEFT JOIN consumo_materiais c ON h.cd_material = c.codigo
            LEFT JOIN consumo_fornecedores f ON f.cnpj = h.cnpj_fornecedor
            LEFT JOIN consumo_fornecedor_especialidade cfe ON cfe.cnpj_fornecedor = h.cnpj_fornecedor AND cfe.id_especialidade = 1
            WHERE 1=1
        ";
        $params = [];

        if ($data_inicio !== '') {
            $sql .= " AND h.data_transicao >= ?";
            $params[] = date('Y-m-d 00:00:00', strtotime($data_inicio));
        }
        if ($data_fim !== '') {
            $sql .= " AND h.data_transicao <= ?";
            $params[] = date('Y-m-d 23:59:59', strtotime($data_fim));
        }
        if ($status_filtro !== '') {
            $sql .= " AND h.status_novo = ?";
            $params[] = $status_filtro;
        }
        if ($id_fornecedor > 0) {
            $sql .= " AND h.cnpj_fornecedor = (SELECT cnpj FROM consumo_fornecedores WHERE id = ?)";
            $params[] = $id_fornecedor;
        }
        if ($id_especialidade > 0) {
            $sql .= " AND h.cnpj_fornecedor IN (SELECT cnpj_fornecedor FROM consumo_fornecedor_especialidade WHERE id_especialidade = ?)";
            $params[] = $id_especialidade;
        }

        $sql .= " ORDER BY h.data_transicao DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $historico = $stmt->fetchAll();

        // 3. Estatísticas resumidas
        $total_transicoes = count($historico);
        $total_critico = count(array_filter($historico, fn($r) => $r['status_novo'] === 'critico'));
        $total_alerta = count(array_filter($historico, fn($r) => $r['status_novo'] === 'alerta'));
        $total_normal = count(array_filter($historico, fn($r) => $r['status_novo'] === 'normal'));

        // 4. Listas para filtros
        $stmt_especialidades = $pdo->query('SELECT id, nome FROM consumo_especialidades ORDER BY nome');
        $especialidades = $stmt_especialidades->fetchAll();

        $stmt_fornecedores = $pdo->query('SELECT id, name FROM consumo_fornecedores ORDER BY name');
        $fornecedores = $stmt_fornecedores->fetchAll();

        View::render('consumo_relatorios', [
            'historico' => $historico,
            'total_transicoes' => $total_transicoes,
            'total_critico' => $total_critico,
            'total_alerta' => $total_alerta,
            'total_normal' => $total_normal,
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim,
            'status_filtro' => $status_filtro,
            'id_especialidade' => $id_especialidade,
            'id_fornecedor' => $id_fornecedor,
            'especialidades' => $especialidades,
            'fornecedores' => $fornecedores,
            'csrf_token' => Csrf::token()
        ]);
    }

    /**
     * Endpoint que gera e baixa o CSV do histórico de transições de status.
     */
    public function exportCsv(): void
    {
        $this->auth->requireLogin();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="relatorio_consumo_' . date('Y-m-d_His') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $data_inicio = trim((string)($_GET['data_inixo'] ?? $_GET['data_inicio'] ?? ''));
        $data_fim = trim((string)($_GET['data_fim'] ?? ''));
        $status_filtro = trim((string)($_GET['status'] ?? ''));
        $id_especialidade = (int)($_GET['id_especialidade'] ?? 0);
        $id_fornecedor = (int)($_GET['id_fornecedor'] ?? 0);

        $pdo = Database::pdo();

        $sql = "
            SELECT 
                h.cd_material,
                COALESCE(c.descricao, 'Material Desconhecido') AS descricao,
                h.cnpj_fornecedor,
                COALESCE(f.name, 'Não identificado') AS fornecedor,
                h.status_anterior,
                h.status_novo,
                h.saldo_momento,
                h.media_momento,
                DATE_FORMAT(h.data_transicao, '%d/%m/%Y %H:%i') AS data_transicao
            FROM consumo_status_historico h
            LEFT JOIN consumo_materiais c ON h.cd_material = c.codigo
            LEFT JOIN consumo_fornecedores f ON f.cnpj = h.cnpj_fornecedor
            WHERE 1=1
        ";
        $params = [];

        if ($data_inicio !== '') {
            $sql .= " AND h.data_transicao >= ?";
            $params[] = date('Y-m-d 00:00:00', strtotime($data_inicio));
        }
        if ($data_fim !== '') {
            $sql .= " AND h.data_transicao <= ?";
            $params[] = date('Y-m-d 23:59:59', strtotime($data_fim));
        }
        if ($status_filtro !== '') {
            $sql .= " AND h.status_novo = ?";
            $params[] = $status_filtro;
        }
        if ($id_fornecedor > 0) {
            $sql .= " AND h.cnpj_fornecedor = (SELECT cnpj FROM consumo_fornecedores WHERE id = ?)";
            $params[] = $id_fornecedor;
        }
        if ($id_especialidade > 0) {
            $sql .= " AND h.cnpj_fornecedor IN (SELECT cnpj_fornecedor FROM consumo_fornecedor_especialidade WHERE id_especialidade = ?)";
            $params[] = $id_especialidade;
        }

        $sql .= " ORDER BY h.data_transicao DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        // Usa php://output para streaming direto do CSV
        $output = fopen('php://output', 'w');

        // Header BOM UTF-8 para Excel
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        // Cabeçalhos delimitados por ponto e vírgula
        fputcsv($output, [
            'Cod_Material',
            'Material',
            'CNPJ_Fornecedor',
            'Fornecedor',
            'Status_Anterior',
            'Status_Novo',
            'Saldo_Momento',
            'Media_Momento',
            'Data_Transacao'
        ], ';');

        // Dados
        while ($row = $stmt->fetch()) {
            fputcsv($output, [
                $row['cd_material'],
                $row['descricao'],
                $row['cnpj_fornecedor'],
                $row['fornecedor'],
                $row['status_anterior'],
                $row['status_novo'],
                $row['saldo_momento'],
                $row['media_momento'],
                $row['data_transicao']
            ], ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Endpoint API AJAX que retorna a JSON list of transition history for a given cd_material and cnpj_fornecedor.
     */
    public function getHistoricoStatus(): void
    {
        $this->auth->requireLogin();

        header('Content-Type: application/json');

        $cd_material = (int)($_GET['cd_material'] ?? 0);
        $cnpj_fornecedor = preg_replace('/[^0-9]/', '', (string)($_GET['cnpj_fornecedor'] ?? ''));

        if ($cd_material <= 0 || strlen($cnpj_fornecedor) !== 14) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Código de material ou CNPJ inválido']);
            exit;
        }

        $pdo = Database::pdo();

        try {
            $stmt = $pdo->prepare("
                SELECT 
                    status_anterior, 
                    status_novo, 
                    saldo_momento, 
                    media_momento, 
                    DATE_FORMAT(data_transicao, '%d/%m/%Y %H:%i') as data_formatada
                FROM consumo_status_historico
                WHERE cd_material = ? AND cnpj_fornecedor = ?
                ORDER BY data_transicao DESC
            ");
            $stmt->execute([$cd_material, $cnpj_fornecedor]);
            $historico = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'historico' => $historico
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Erro ao consultar histórico: ' . $e->getMessage()]);
        }
        exit;
    }
}
