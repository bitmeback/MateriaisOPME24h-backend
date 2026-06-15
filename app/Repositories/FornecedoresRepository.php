<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Repositories;

use MateriaisOpme\App\Support\Database;
use PDO;

final class FornecedoresRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function count(string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) FROM fornecedores {$where}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * @return array<int, array>
     */
    public function list(string $where = '', array $params = [], string $orderBy = 'name ASC', int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT id, cnpj, cnpj_formatted, name, contacts, status, notes, pendencias, send_report, created_at, updated_at FROM fornecedores {$where} ORDER BY {$orderBy} LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findByCnpj(string $cnpj): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM fornecedores WHERE cnpj = :cnpj LIMIT 1');
        $stmt->execute([':cnpj' => $cnpj]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO fornecedores (cnpj, cnpj_formatted, name, contacts, status, notes, pendencias, send_report, created_at, updated_at)
             VALUES (:cnpj, :cnpj_formatted, :name, :contacts, :status, :notes, :pendencias, :send_report, NOW(), NOW())'
        );
        $stmt->execute([
            ':cnpj'           => $data['cnpj'],
            ':cnpj_formatted' => $data['cnpj_formatted'] ?? '',
            ':name'           => $data['name'],
            ':contacts'       => $data['contacts'] ?? '',
            ':status'         => $data['status'] ?? 'pendente',
            ':notes'          => $data['notes'] ?? '',
            ':pendencias'     => $data['pendencias'] ?? 0,
            ':send_report'    => (int)($data['send_report'] ?? 1),
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(string $cnpj, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE fornecedores
             SET name = :name, contacts = :contacts, status = :status, notes = :notes,
                 pendencias = :pendencias, send_report = :send_report, updated_at = NOW()
             WHERE cnpj = :cnpj'
        );
        $stmt->execute([
            ':cnpj'        => $cnpj,
            ':name'        => $data['name'] ?? '',
            ':contacts'    => $data['contacts'] ?? '',
            ':status'      => $data['status'] ?? 'pendente',
            ':notes'       => $data['notes'] ?? '',
            ':pendencias'  => $data['pendencias'] ?? 0,
            ':send_report' => (int)($data['send_report'] ?? 1),
        ]);
    }

    public function delete(string $cnpj): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM fornecedores WHERE cnpj = :cnpj');
        $stmt->execute([':cnpj' => $cnpj]);
    }

    /**
     * Fornecedores elegíveis para envio (send_report=1, com pendentes, status ativo).
     * @return array<int, array>
     */
    public function findEligible(): array
    {
        $stmt = $this->pdo->query(
            "SELECT * FROM fornecedores WHERE send_report = 1 AND pendencias > 0 AND status IN ('validado','envio teste') ORDER BY name ASC"
        );
        return $stmt->fetchAll();
    }
}
