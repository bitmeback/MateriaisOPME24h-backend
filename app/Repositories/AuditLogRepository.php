<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Repositories;

use MateriaisOpme\App\Support\Database;
use PDO;

final class AuditLogRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function insert(?int $userId, string $action, string $entity, ?string $entityId = null, ?string $details = null): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO audit_log (user_id, action, entity, entity_id, details, created_at)
             VALUES (:user_id, :action, :entity, :entity_id, :details, NOW())'
        );
        $stmt->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'details' => $details,
        ]);
    }
}
