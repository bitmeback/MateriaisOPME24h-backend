<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Repositories;

use MateriaisOpme\App\Support\Database;
use PDO;

final class ReportRecipientsRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function all(): array
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM report_recipients ORDER BY id ASC'
        );
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM report_recipients WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findActive(): array
    {
        $stmt = $this->pdo->query(
            'SELECT * FROM report_recipients WHERE active = 1 ORDER BY name ASC'
        );
        return $stmt->fetchAll();
    }

    public function create(array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare(
            'INSERT INTO report_recipients (name, email, phone, type, active, created_at, updated_at)
             VALUES (:name, :email, :phone, :type, :active, :created_at, :updated_at)'
        );
        $stmt->execute([
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'type' => $data['type'] ?? 'email',
            'active' => !empty($data['active']) ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE report_recipients
             SET name = :name, email = :email, phone = :phone, type = :type, active = :active, updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'] ?? '',
            'email' => $data['email'] ?? '',
            'phone' => $data['phone'] ?? '',
            'type' => $data['type'] ?? 'email',
            'active' => !empty($data['active']) ? 1 : 0,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM report_recipients WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public function count(): int
    {
        return (int)$this->pdo->query('SELECT COUNT(*) FROM report_recipients')->fetchColumn();
    }

    public function countActive(): int
    {
        return (int)$this->pdo->query('SELECT COUNT(*) FROM report_recipients WHERE active = 1')->fetchColumn();
    }
}
