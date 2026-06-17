<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Services;

use MateriaisOpme\App\Repositories\AuditLogRepository;

final class AuditService
{
    private ?AuditLogRepository $repository = null;

    private function repository(): AuditLogRepository
    {
        if ($this->repository === null) {
            $this->repository = new AuditLogRepository();
        }

        return $this->repository;
    }

    public function record(string $action, string $entity, ?string $entityId = null, array $details = [], ?int $userId = null): void
    {
        if ($userId === null && isset($_SESSION['user_id'])) {
            $userId = (int) $_SESSION['user_id'];
        }

        $payload = $details === [] ? null : json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            $payload = null;
        }

        try {
            $this->repository()->insert($userId, $action, $entity, $entityId, $payload);
        } catch (\Throwable) {
            // Auditoria não pode derrubar o fluxo principal.
        }
    }
}
