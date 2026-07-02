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
        $filtro_uso = trim((string)($_GET['uso'] ?? 'utilizados')); // utilizados | nao_utilizados | todos
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

        // 2. Query principal — filtro de uso dinâmico
        $havingClause = match ($filtro_uso) {
            'nao_utilizados' => 'HAVING media_trimestre < 1 OR media_trimestre IS NULL',
            'todos' => '',
            default => 'HAVING media_trimestre >= 1', // utilizados (padrão)
        };

        $sql = "
            SELECT 
                s.cd_material,
                s.cd_fornec_consignado AS cnpj_fornecedor,
                COALESCE(f.name, 'Não identificado') AS ds_fornecedor,
                s.saldo,
                COALESCE(cad.descricao, 'Material Desconhecido') AS descricao,
                COALESCE(ROUND(SUM(c.consumo) / NULLIF(COUNT(DISTINCT c.mes), 0)), 0) AS media_trimestre
            FROM saldo_estoque_atual s
            LEFT JOIN consumo_materiais_cadastro cad ON s.cd_material = cad.cd_material
            LEFT JOIN consumo_materiais c ON s.cd_material = c.codigo
                AND c.ano = YEAR(CURDATE()) 
                AND c.mes >= MONTH(CURDATE()) - 3
            LEFT JOIN consumo_fornecedores f ON f.cnpj = s.cd_fornec_consignado
            LEFT JOIN consumo_fornecedor_especialidade cfe ON cfe.cnpj_fornecedor = s.cd_fornec_consignado AND cfe.id_especialidade = 1
            LEFT JOIN consumo_relacoes_inativas cri ON cri.cd_material = s.cd_material AND cri.cnpj_fornecedor = s.cd_fornec_consignado
            WHERE cfe.id_especialidade IS NULL
              AND cri.cd_material IS NULL
            GROUP BY s.cd_material, s.cd_fornec_consignado, cad.descricao, f.name, s.saldo
            {$havingClause}
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

            // Status para materiais NÃO UTILIZADOS (sem giro ou sem consumo)
            if ($media < 1 && $saldo > 0) {
                // SEM GIRO — estoque parado sem consumo recente
                $status = 'sem_giro';
                $status_desc = 'Sem Giro';
                $threshold_critico = 0;
                $threshold_warning = 0;
                $ratio = 999.0; // não prioriza na ordenação
            } elseif ($media < 1 && $saldo <= 0) {
                // Sem consumo e sem estoque — inativo
                $status = 'inativo';
                $status_desc = 'Inativo';
                $threshold_critico = 0;
                $threshold_warning = 0;
                $ratio = 999.0;
            } elseif ($media <= 3) {
                // Grupo A: saldo >= média = OK, saldo < média = CRITICAL (sem warning)
                $threshold_critico = (float)$media;
                $threshold_warning = (float)$media;
                if ($saldo < $media) {
                    $status = 'critico';
                    $status_desc = 'Crítico';
                } else {
                    $status = 'normal';
                    $status_desc = 'Saudável';
                }
                $ratio = $media > 0 ? ($saldo / $media) : 999.0;
            } else {
                // Grupo B: saldo >= média = OK, >= 90% = WARNING, < 90% = CRITICAL
                $threshold_critico = (float)round($media * 0.9);
                $threshold_warning = (float)$media;
                if ($saldo < $threshold_critico) {
                    $status = 'critico';
                    $status_desc = 'Crítico';
                } elseif ($saldo < $threshold_warning) {
                    $status = 'alerta';
                    $status_desc = 'Alerta';
                } else {
                    $status = 'normal';
                    $status_desc = 'Saudável';
                }
                $ratio = $media > 0 ? ($saldo / $media) : 999.0;
            }

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
                $statusWeights = ['critico' => 1, 'alerta' => 2, 'sem_giro' => 3, 'normal' => 4, 'inativo' => 5];
                $wa = $statusWeights[$a['status']] ?? 5;
                $wb = $statusWeights[$b['status']] ?? 5;
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
        $semGiroCount = count(array_filter($processedItems, fn($i) => $i['status'] === 'sem_giro'));
        $inativoCount = count(array_filter($processedItems, fn($i) => $i['status'] === 'inativo'));

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
            'filtro_uso' => $filtro_uso,
            'sort' => $sort,
            'pagination' => $pagination,
            'total_count' => $totalItemsCount,
            'critico_count' => $criticosCount,
            'alerta_count' => $alertasCount,
            'saudavel_count' => $saudavelCount,
            'sem_giro_count' => $semGiroCount,
            'inativo_count' => $inativoCount,
            'csrf_token' => Csrf::token(),
            'role' => $_SESSION['role'] ?? 'guest'
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

        if ($cd_material <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Código de material inválido']);
            exit;
        }

        if (strlen($cnpj_fornecedor) === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Fornecedor não informado']);
            exit;
        }

        if (strlen($cnpj_fornecedor) !== 14) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'CNPJ do fornecedor inválido']);
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
        $q = trim((string)($_GET['q'] ?? ''));
        $filtro_vinculo = trim((string)($_GET['vinculo'] ?? 'ativos'));
        $filtro_uso = trim((string)($_GET['uso'] ?? 'utilizados'));
        $sort = trim((string)($_GET['sort'] ?? 'data_desc'));

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
                COALESCE(c_desc.descricao, 'Material Desconhecido') AS descricao,
                COALESCE(f.name, 'Não identificado') AS fornecedor,
                h.status_anterior,
                h.status_novo,
                h.saldo_momento,
                h.media_momento,
                DATE_FORMAT(h.data_transicao, '%d/%m/%Y %H:%i') AS data_formatada,
                h.data_transicao
            FROM consumo_status_historico h
            LEFT JOIN (
                SELECT cd_material AS codigo, descricao 
                FROM consumo_materiais_cadastro
            ) c_desc ON h.cd_material = c_desc.codigo
            LEFT JOIN consumo_fornecedores f ON f.cnpj = h.cnpj_fornecedor
            LEFT JOIN consumo_fornecedor_especialidade cfe ON cfe.cnpj_fornecedor = h.cnpj_fornecedor AND cfe.id_especialidade = 1
            WHERE 1=1
        ";

        // Filtro de uso: utilizados (critico/alerta/normal), nao_utilizados (sem_giro/inativo), todos
        $params = [];
        $usoWhitelist = match ($filtro_uso) {
            'nao_utilizados' => ['sem_giro', 'inativo'],
            'todos' => [],
            default => ['critico', 'alerta', 'normal'], // utilizados
        };
        if (!empty($usoWhitelist)) {
            $placeholders = implode(',', array_fill(0, count($usoWhitelist), '?'));
            $sql .= " AND h.status_novo IN ({$placeholders})";
            $params = array_merge($params, $usoWhitelist);
        }

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

        // Ordenação
        $orderBy = match ($sort) {
            'data_asc' => 'h.data_transicao ASC',
            'codigo_asc' => 'h.cd_material ASC, h.data_transicao DESC',
            'codigo_desc' => 'h.cd_material DESC, h.data_transicao DESC',
            'material_asc' => 'descricao ASC, h.data_transicao DESC',
            'material_desc' => 'descricao DESC, h.data_transicao DESC',
            'fornecedor_asc' => 'fornecedor ASC, h.data_transicao DESC',
            'fornecedor_desc' => 'fornecedor DESC, h.data_transicao DESC',
            'saldo_asc' => 'h.saldo_momento ASC, h.data_transicao DESC',
            'saldo_desc' => 'h.saldo_momento DESC, h.data_transicao DESC',
            default => 'h.data_transicao DESC',
        };
        $sql .= " ORDER BY {$orderBy}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $historico = $stmt->fetchAll();

        // 3. Filtro por busca textual (código, descrição ou fornecedor)
        if ($q !== '') {
            $qLower = mb_strtolower($q);
            $historico = array_filter($historico, function ($row) use ($qLower) {
                return stripos((string)$row['cd_material'], $qLower) !== false
                    || stripos($row['descricao'], $qLower) !== false
                    || stripos($row['fornecedor'], $qLower) !== false;
            });
            $historico = array_values($historico);
        }

        // 4. Filtro de vínculos (ativos/inativos/todos)
        if ($filtro_vinculo !== 'todos') {
            $historico = array_filter($historico, function ($row) use ($blacklist, $filtro_vinculo) {
                $key = $row['cd_material'] . '_' . $row['cnpj_fornecedor'];
                $ativo = !isset($blacklist[$key]);
                if ($filtro_vinculo === 'ativos') return $ativo;
                if ($filtro_vinculo === 'inativos') return !$ativo;
                return true;
            });
            $historico = array_values($historico);
        }

        // 5. Estatísticas resumidas
        $total_transicoes = count($historico);
        $total_critico = count(array_filter($historico, fn($r) => $r['status_novo'] === 'critico'));
        $total_alerta = count(array_filter($historico, fn($r) => $r['status_novo'] === 'alerta'));
        $total_normal = count(array_filter($historico, fn($r) => $r['status_novo'] === 'normal'));
        $total_sem_giro = count(array_filter($historico, fn($r) => $r['status_novo'] === 'sem_giro'));
        $total_inativo = count(array_filter($historico, fn($r) => $r['status_novo'] === 'inativo'));

        // 6. Listas para filtros
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
            'total_sem_giro' => $total_sem_giro,
            'total_inativo' => $total_inativo,
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim,
            'status_filtro' => $status_filtro,
            'id_especialidade' => $id_especialidade,
            'id_fornecedor' => $id_fornecedor,
            'busca' => $q,
            'filtro_vinculo' => $filtro_vinculo,
            'filtro_uso' => $filtro_uso,
            'sort' => $sort,
            'especialidades' => $especialidades,
            'fornecedores' => $fornecedores,
            'csrf_token' => Csrf::token(),
            'role' => $_SESSION['role'] ?? 'guest'
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

        $data_inicio = trim((string)($_GET['data_inicio'] ?? ''));
        $data_fim = trim((string)($_GET['data_fim'] ?? ''));
        $status_filtro = trim((string)($_GET['status'] ?? ''));
        $id_especialidade = (int)($_GET['id_especialidade'] ?? 0);
        $id_fornecedor = (int)($_GET['id_fornecedor'] ?? 0);
        $q = trim((string)($_GET['q'] ?? ''));
        $filtro_vinculo = trim((string)($_GET['vinculo'] ?? 'ativos'));
        $filtro_uso = trim((string)($_GET['uso'] ?? 'utilizados'));
        $sort = trim((string)($_GET['sort'] ?? 'data_desc'));

        $pdo = Database::pdo();

        // Blacklist de vínculos inativos
        $stmtInativas = $pdo->query('SELECT cd_material, cnpj_fornecedor FROM consumo_relacoes_inativas');
        $blacklist = [];
        while ($row = $stmtInativas->fetch()) {
            $key = $row['cd_material'] . '_' . $row['cnpj_fornecedor'];
            $blacklist[$key] = true;
        }

        $sql = "
            SELECT 
                h.cd_material,
                COALESCE(c_desc.descricao, 'Material Desconhecido') AS descricao,
                h.cnpj_fornecedor,
                COALESCE(f.name, 'Não identificado') AS fornecedor,
                h.status_anterior,
                h.status_novo,
                h.saldo_momento,
                h.media_momento,
                DATE_FORMAT(h.data_transicao, '%d/%m/%Y %H:%i') AS data_transicao
            FROM consumo_status_historico h
            LEFT JOIN (
                SELECT cd_material AS codigo, descricao 
                FROM consumo_materiais_cadastro
            ) c_desc ON h.cd_material = c_desc.codigo
            LEFT JOIN consumo_fornecedores f ON f.cnpj = h.cnpj_fornecedor
            WHERE 1=1
        ";

        // Filtro de uso no CSV
        $params = [];
        $usoWhitelist = match ($filtro_uso) {
            'nao_utilizados' => ['sem_giro', 'inativo'],
            'todos' => [],
            default => ['critico', 'alerta', 'normal'],
        };
        if (!empty($usoWhitelist)) {
            $placeholders = implode(',', array_fill(0, count($usoWhitelist), '?'));
            $sql .= " AND h.status_novo IN ({$placeholders})";
            $params = array_merge($params, $usoWhitelist);
        }

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

        $orderBy = match ($sort) {
            'data_asc' => 'h.data_transicao ASC',
            'codigo_asc' => 'h.cd_material ASC, h.data_transicao DESC',
            'codigo_desc' => 'h.cd_material DESC, h.data_transicao DESC',
            'material_asc' => 'descricao ASC, h.data_transicao DESC',
            'material_desc' => 'descricao DESC, h.data_transicao DESC',
            'fornecedor_asc' => 'fornecedor ASC, h.data_transicao DESC',
            'fornecedor_desc' => 'fornecedor DESC, h.data_transicao DESC',
            'saldo_asc' => 'h.saldo_momento ASC, h.data_transicao DESC',
            'saldo_desc' => 'h.saldo_momento DESC, h.data_transicao DESC',
            default => 'h.data_transicao DESC',
        };
        $sql .= " ORDER BY {$orderBy}";

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

        // Dados com filtros de busca e vínculo aplicados
        while ($row = $stmt->fetch()) {
            // Filtro de busca textual
            if ($q !== '') {
                $qLower = mb_strtolower($q);
                if (stripos((string)$row['cd_material'], $qLower) === false
                    && stripos($row['descricao'], $qLower) === false
                    && stripos($row['fornecedor'], $qLower) === false) {
                    continue;
                }
            }
            // Filtro de vínculos
            if ($filtro_vinculo !== 'todos') {
                $key = $row['cd_material'] . '_' . $row['cnpj_fornecedor'];
                $ativo = !isset($blacklist[$key]);
                if ($filtro_vinculo === 'ativos' && !$ativo) continue;
                if ($filtro_vinculo === 'inativos' && $ativo) continue;
            }

                fputcsv($output, [
                    $row['cd_material'],
                    $row['descricao'],
                    $row['cnpj_fornecedor'],
                    $row['fornecedor'],
                    $row['status_anterior'] ?? '— Primeiro registro',
                    $row['status_novo'] === 'sem_giro' ? 'Sem Giro' : ($row['status_novo'] === 'inativo' ? 'Inativo' : $row['status_novo']),
                    $row['saldo_momento'],
                    $row['media_momento'],
                    $row['data_transicao']
                ], ';');
        }

        fclose($output);
        exit;
    }

    /**
     * Endpoint CSV do estado atual do /consumo, seguindo os mesmos filtros da tela.
     */
    public function exportCsvConsumo(): void
    {
        $this->auth->requireLogin();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="consumo_atual_' . date('Y-m-d_His') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $q = trim((string)($_GET['q'] ?? ''));
        $status_filtro = trim((string)($_GET['status'] ?? ''));
        $filtro_vinculo = trim((string)($_GET['vinculo'] ?? 'ativos'));
        $filtro_uso = trim((string)($_GET['uso'] ?? 'utilizados'));
        $sort = trim((string)($_GET['sort'] ?? 'status_ratio'));

        $pdo = Database::pdo();

        $stmtInativas = $pdo->query('SELECT cd_material, cnpj_fornecedor FROM consumo_relacoes_inativas');
        $blacklist = [];
        while ($row = $stmtInativas->fetch()) {
            $key = $row['cd_material'] . '_' . $row['cnpj_fornecedor'];
            $blacklist[$key] = true;
        }

        $havingClause = match ($filtro_uso) {
            'nao_utilizados' => 'HAVING media_trimestre < 1 OR media_trimestre IS NULL',
            'todos' => '',
            default => 'HAVING media_trimestre >= 1',
        };

        $sql = "
            SELECT 
                s.cd_material,
                s.cd_fornec_consignado AS cnpj_fornecedor,
                COALESCE(f.name, 'Não identificado') AS ds_fornecedor,
                COALESCE(cad.descricao, 'Material Desconhecido') AS descricao,
                COALESCE(ROUND(SUM(c.consumo) / NULLIF(COUNT(DISTINCT c.mes), 0)), 0) AS media_trimestre,
                MAX(s.saldo) as saldo
            FROM saldo_estoque_atual s
            LEFT JOIN consumo_materiais_cadastro cad ON s.cd_material = cad.cd_material
            LEFT JOIN consumo_materiais c ON s.cd_material = c.codigo
                AND c.ano = YEAR(CURDATE())
                AND c.mes >= MONTH(CURDATE()) - 3
            LEFT JOIN consumo_fornecedores f ON f.cnpj = s.cd_fornec_consignado
            LEFT JOIN consumo_fornecedor_especialidade cfe ON cfe.cnpj_fornecedor = s.cd_fornec_consignado AND cfe.id_especialidade = 1
            LEFT JOIN consumo_relacoes_inativas cri ON cri.cd_material = s.cd_material AND cri.cnpj_fornecedor = s.cd_fornec_consignado
            WHERE cfe.id_especialidade IS NULL
              AND cri.cd_material IS NULL
            GROUP BY s.cd_material, s.cd_fornec_consignado, cad.descricao, f.name, s.saldo
            {$havingClause}
        ";

        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['Codigo', 'Material', 'Fornecedor', 'CNPJ_Fornecedor', 'Media_90d', 'Saldo', 'Status', 'Vinculo'], ';');

        foreach ($rows as $row) {
            $codigo = (int)$row['cd_material'];
            $cnpj = (string)$row['cnpj_fornecedor'];
            $key = $codigo . '_' . $cnpj;
            $vinculo_ativo = !isset($blacklist[$key]);

            if ($filtro_vinculo === 'ativos' && !$vinculo_ativo) continue;
            if ($filtro_vinculo === 'inativos' && $vinculo_ativo) continue;

            $saldo = (float)$row['saldo'];
            $media = (float)$row['media_trimestre'];

            if ($media < 1 && $saldo > 0) $status = 'Sem Giro';
            elseif ($media < 1 && $saldo <= 0) $status = 'Inativo';
            elseif ($media <= 3) $status = $saldo < $media ? 'Critico' : 'Saudavel';
            else $status = $saldo < (round($media * 0.9)) ? 'Critico' : ($saldo < $media ? 'Alerta' : 'Saudavel');

            if ($q !== '' && stripos((string)$codigo, $q) === false && stripos((string)$row['descricao'], $q) === false && stripos((string)$row['ds_fornecedor'], $q) === false) continue;
            if ($status_filtro !== '' && strtolower($status) !== strtolower($status_filtro)) continue;

            fputcsv($output, [
                $codigo,
                (string)$row['descricao'],
                (string)$row['ds_fornecedor'],
                $cnpj,
                $media,
                $saldo,
                $status,
                $vinculo_ativo ? 'Ativo' : 'Inativo',
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

        if ($cd_material <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Código de material inválido']);
            exit;
        }

        if (strlen($cnpj_fornecedor) === 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Fornecedor não informado']);
            exit;
        }

        if (strlen($cnpj_fornecedor) !== 14) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'CNPJ do fornecedor inválido']);
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

    /**
     * Timeline — Visão diária do estado de todos os materiais via consumo_snapshot_diario.
     */
    public function timeline(): void
    {
        $this->auth->requireLogin();

        $data_inicio = trim((string)($_GET['data_inicio'] ?? ''));
        $data_fim = trim((string)($_GET['data_fim'] ?? ''));
        $status_filtro = trim((string)($_GET['status'] ?? ''));
        $q = trim((string)($_GET['q'] ?? ''));
        $filtro_vinculo = trim((string)($_GET['vinculo'] ?? 'ativos'));
        $sort = trim((string)($_GET['sort'] ?? 'data_desc'));

        $pdo = Database::pdo();

        // Blacklist de vínculos inativos
        $stmtInativas = $pdo->query('SELECT cd_material, cnpj_fornecedor FROM consumo_relacoes_inativas');
        $blacklist = [];
        while ($row = $stmtInativas->fetch()) {
            $key = $row['cd_material'] . '_' . $row['cnpj_fornecedor'];
            $blacklist[$key] = true;
        }

        // Query principal — dados do snapshot com descrição do material
        $params = [];
        $sql = "
            SELECT 
                sn.data_snapshot,
                sn.cd_material,
                sn.cnpj_fornecedor,
                COALESCE(c_desc.descricao, 'Material Desconhecido') AS descricao,
                COALESCE(f.name, 'Não identificado') AS fornecedor,
                sn.status,
                sn.saldo,
                sn.media_trimestre
            FROM consumo_snapshot_diario sn
            LEFT JOIN (
                SELECT cd_material AS codigo, descricao 
                FROM consumo_materiais_cadastro
            ) c_desc ON sn.cd_material = c_desc.codigo
            LEFT JOIN consumo_fornecedores f ON f.cnpj = sn.cnpj_fornecedor
            WHERE 1=1
        ";

        if ($data_inicio !== '') {
            $sql .= " AND sn.data_snapshot >= ?";
            $params[] = $data_inicio;
        }
        if ($data_fim !== '') {
            $sql .= " AND sn.data_snapshot <= ?";
            $params[] = $data_fim;
        }
        if ($status_filtro !== '') {
            $sql .= " AND sn.status = ?";
            $params[] = $status_filtro;
        }

        $orderBy = match ($sort) {
            'data_asc' => 'sn.data_snapshot ASC, sn.cd_material ASC',
            'codigo_asc' => 'sn.cd_material ASC, sn.data_snapshot DESC',
            'codigo_desc' => 'sn.cd_material DESC, sn.data_snapshot DESC',
            'material_asc' => 'descricao ASC, sn.data_snapshot DESC',
            'material_desc' => 'descricao DESC, sn.data_snapshot DESC',
            'fornecedor_asc' => 'fornecedor ASC, sn.data_snapshot DESC',
            'fornecedor_desc' => 'fornecedor DESC, sn.data_snapshot DESC',
            'saldo_asc' => 'sn.saldo ASC, sn.data_snapshot DESC',
            'saldo_desc' => 'sn.saldo DESC, sn.data_snapshot DESC',
            'media_asc' => 'sn.media_trimestre ASC, sn.data_snapshot DESC',
            'media_desc' => 'sn.media_trimestre DESC, sn.data_snapshot DESC',
            default => 'sn.data_snapshot DESC, sn.cd_material ASC',
        };
        $sql .= " ORDER BY {$orderBy}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $snapshots = $stmt->fetchAll();

        // Filtro textual (código, descrição, fornecedor)
        if ($q !== '') {
            $qLower = mb_strtolower($q);
            $snapshots = array_filter($snapshots, function ($row) use ($qLower) {
                return stripos((string)$row['cd_material'], $qLower) !== false
                    || stripos($row['descricao'], $qLower) !== false
                    || stripos($row['fornecedor'], $qLower) !== false;
            });
            $snapshots = array_values($snapshots);
        }

        // Filtro de vínculos (ativos/inativos/todos)
        if ($filtro_vinculo !== 'todos') {
            $snapshots = array_filter($snapshots, function ($row) use ($blacklist, $filtro_vinculo) {
                $key = $row['cd_material'] . '_' . $row['cnpj_fornecedor'];
                $ativo = !isset($blacklist[$key]);
                if ($filtro_vinculo === 'ativos') return $ativo;
                if ($filtro_vinculo === 'inativos') return !$ativo;
                return true;
            });
            $snapshots = array_values($snapshots);
        }

        // Estatísticas por dia
        $stats_por_dia = [];
        foreach ($snapshots as $row) {
            $dia = $row['data_snapshot'];
            if (!isset($stats_por_dia[$dia])) {
                $stats_por_dia[$dia] = ['total' => 0, 'critico' => 0, 'alerta' => 0, 'normal' => 0, 'sem_giro' => 0, 'inativo' => 0];
            }
            $stats_por_dia[$dia]['total']++;
            $stats_por_dia[$dia][$row['status']]++;
        }
        krsort($stats_por_dia);

        // Dias disponíveis para o filtro de período
        $stmtDias = $pdo->query('SELECT DISTINCT data_snapshot FROM consumo_snapshot_diario ORDER BY data_snapshot DESC');
        $dias_disponiveis = $stmtDias->fetchAll();

        // Lista de fornecedores para filtro
        $stmtForn = $pdo->query('SELECT id, name FROM consumo_fornecedores ORDER BY name');
        $fornecedores = $stmtForn->fetchAll();

        View::render('consumo_timeline', [
            'snapshots' => $snapshots,
            'stats_por_dia' => $stats_por_dia,
            'dias_disponiveis' => $dias_disponiveis,
            'data_inicio' => $data_inicio,
            'data_fim' => $data_fim,
            'status_filtro' => $status_filtro,
            'busca' => $q,
            'filtro_vinculo' => $filtro_vinculo,
            'sort' => $sort,
            'fornecedores' => $fornecedores,
            'csrf_token' => Csrf::token(),
            'role' => $_SESSION['role'] ?? 'guest'
        ]);
    }

    /**
     * Exportar timeline como CSV (Excel BR).
     */
    public function exportTimelineCsv(): void
    {
        $this->auth->requireLogin();

        $data_inicio = trim((string)($_GET['data_inicio'] ?? ''));
        $data_fim = trim((string)($_GET['data_fim'] ?? ''));
        $status_filtro = trim((string)($_GET['status'] ?? ''));
        $q = trim((string)($_GET['q'] ?? ''));
        $filtro_vinculo = trim((string)($_GET['vinculo'] ?? 'ativos'));
        $sort = trim((string)($_GET['sort'] ?? 'data_desc'));

        $pdo = Database::pdo();

        // Blacklist
        $stmtInativas = $pdo->query('SELECT cd_material, cnpj_fornecedor FROM consumo_relacoes_inativas');
        $blacklist = [];
        while ($row = $stmtInativas->fetch()) {
            $key = $row['cd_material'] . '_' . $row['cnpj_fornecedor'];
            $blacklist[$key] = true;
        }

        $params = [];
        $sql = "
            SELECT 
                sn.data_snapshot,
                sn.cd_material,
                COALESCE(c_desc.descricao, 'Material Desconhecido') AS descricao,
                sn.cnpj_fornecedor,
                COALESCE(f.name, 'Não identificado') AS fornecedor,
                sn.status,
                sn.saldo,
                sn.media_trimestre
            FROM consumo_snapshot_diario sn
            LEFT JOIN (
                SELECT cd_material AS codigo, descricao 
                FROM consumo_materiais_cadastro
            ) c_desc ON sn.cd_material = c_desc.codigo
            LEFT JOIN consumo_fornecedores f ON f.cnpj = sn.cnpj_fornecedor
            WHERE 1=1
        ";

        if ($data_inicio !== '') {
            $sql .= " AND sn.data_snapshot >= ?";
            $params[] = $data_inicio;
        }
        if ($data_fim !== '') {
            $sql .= " AND sn.data_snapshot <= ?";
            $params[] = $data_fim;
        }
        if ($status_filtro !== '') {
            $sql .= " AND sn.status = ?";
            $params[] = $status_filtro;
        }

        $orderBy = match ($sort) {
            'data_asc' => 'sn.data_snapshot ASC, sn.cd_material ASC',
            'codigo_asc' => 'sn.cd_material ASC, sn.data_snapshot DESC',
            'codigo_desc' => 'sn.cd_material DESC, sn.data_snapshot DESC',
            'material_asc' => 'descricao ASC, sn.data_snapshot DESC',
            'material_desc' => 'descricao DESC, sn.data_snapshot DESC',
            'fornecedor_asc' => 'fornecedor ASC, sn.data_snapshot DESC',
            'fornecedor_desc' => 'fornecedor DESC, sn.data_snapshot DESC',
            'saldo_asc' => 'sn.saldo ASC, sn.data_snapshot DESC',
            'saldo_desc' => 'sn.saldo DESC, sn.data_snapshot DESC',
            'media_asc' => 'sn.media_trimestre ASC, sn.data_snapshot DESC',
            'media_desc' => 'sn.media_trimestre DESC, sn.data_snapshot DESC',
            default => 'sn.data_snapshot DESC, sn.cd_material ASC',
        };
        $sql .= " ORDER BY {$orderBy}";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="timeline_consumo_opme_' . date('Y-m-d_His') . '.csv"');
        header('Cache-Control: no-cache');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8

        fputcsv($output, [
            'Data_Snapshot',
            'Codigo_Material',
            'Descricao',
            'CNPJ_Fornecedor',
            'Fornecedor',
            'Status',
            'Saldo',
            'Media_Trimestre'
        ], ';');

        while ($row = $stmt->fetch()) {
            // Filtro textual
            if ($q !== '') {
                $qLower = mb_strtolower($q);
                if (stripos((string)$row['cd_material'], $qLower) === false
                    && stripos($row['descricao'], $qLower) === false
                    && stripos($row['fornecedor'], $qLower) === false) {
                    continue;
                }
            }
            // Filtro de vínculos
            if ($filtro_vinculo !== 'todos') {
                $key = $row['cd_material'] . '_' . $row['cnpj_fornecedor'];
                $ativo = !isset($blacklist[$key]);
                if ($filtro_vinculo === 'ativos' && !$ativo) continue;
                if ($filtro_vinculo === 'inativos' && $ativo) continue;
            }

            $statusLabel = match ($row['status']) {
                'critico' => 'Crítico',
                'alerta' => 'Alerta',
                'normal' => 'Normal',
                'sem_giro' => 'Sem Giro',
                'inativo' => 'Inativo',
                default => $row['status'],
            };
            fputcsv($output, [
                $row['data_snapshot'],
                $row['cd_material'],
                $row['descricao'],
                $row['cnpj_fornecedor'],
                $row['fornecedor'],
                $statusLabel,
                number_format((float)$row['saldo'], 1, ',', '.'),
                number_format((float)$row['media_trimestre'], 1, ',', '.')
            ], ';');
        }

        fclose($output);
        exit;
    }
}
