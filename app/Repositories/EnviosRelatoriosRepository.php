<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Repositories;

use MateriaisOpme\App\Support\Database;
use PDO;

final class EnviosRelatoriosRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function insert(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO envios_relatorios
                (fornecedor_nome, fornecedor_cnpj, data_envio,
                 email_enviado, email_destinatarios, email_conteudo,
                 created_at, updated_at)
             VALUES
                (:fornecedor_nome, :fornecedor_cnpj, :data_envio,
                 :email_enviado, :email_destinatarios, :email_conteudo,
                 NOW(), NOW())'
        );
        $stmt->execute([
            'fornecedor_nome'     => $data['fornecedor_nome'] ?? '',
            'fornecedor_cnpj'     => $data['fornecedor_cnpj'] ?? '',
            'data_envio'          => $data['data_envio'] ?? date('Y-m-d H:i:s'),
            'email_enviado'       => $data['email_enviado'] ? 1 : 0,
            'email_destinatarios' => $data['email_destinatarios'] ?? null,
            'email_conteudo'      => $data['email_conteudo'] ?? null,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function list(array $filters = []): array
    {
        $where = [];
        $params = [];

        if (!empty($filters['data_de'])) {
            $where[] = 'data_envio >= :data_de';
            $params['data_de'] = $filters['data_de'] . ' 00:00:00';
        }
        if (!empty($filters['data_ate'])) {
            $where[] = 'data_envio <= :data_ate';
            $params['data_ate'] = $filters['data_ate'] . ' 23:59:59';
        }
        if (!empty($filters['fornecedor'])) {
            $where[] = '(fornecedor_nome LIKE :fornecedor OR fornecedor_cnpj LIKE :fornecedor_cnpj)';
            $params['fornecedor'] = '%' . $filters['fornecedor'] . '%';
            $params['fornecedor_cnpj'] = '%' . $filters['fornecedor'] . '%';
        }
        $sql = 'SELECT * FROM envios_relatorios';
        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY data_envio DESC';

        $page = max(1, (int)($filters['page'] ?? 1));
        $perPage = max(1, min(200, (int)($filters['per_page'] ?? 50)));

        $countSql = 'SELECT COUNT(*) FROM envios_relatorios';
        if ($where !== []) {
            $countSql .= ' WHERE ' . implode(' AND ', $where);
        }

        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $sql .= ' LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];
    }
}
