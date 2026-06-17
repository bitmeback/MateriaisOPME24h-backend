<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Services;

use MateriaisOpme\App\Repositories\FornecedoresRepository;
use MateriaisOpme\App\Support\Cnpj;

/**
 * Serviço de fornecedores — substitui VendorFileService.
 * Opera sobre a tabela `fornecedores` no MariaDB.
 */
final class VendorService
{
    private FornecedoresRepository $repo;

    public function __construct()
    {
        $this->repo = new FornecedoresRepository();
    }

    /**
     * Lista fornecedores com paginação, busca e filtros.
     */
    public function list(array $filters = []): array
    {
        $page    = max(1, (int)($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int)($filters['per_page'] ?? 10)));
        $query   = trim((string)($filters['query'] ?? ''));
        $status  = trim((string)($filters['status'] ?? ''));

        $where = [];
        $params = [];

        if ($query !== '') {
            $normalized = Cnpj::normalize($query);
            $where[] = '(name LIKE :q OR cnpj_formatted LIKE :qf OR contacts LIKE :q2 OR status LIKE :q3 OR notes LIKE :q4)';
            $params[':q']  = '%' . $query . '%';
            $params[':qf'] = '%' . $query . '%';
            $params[':q2'] = '%' . $query . '%';
            $params[':q3'] = '%' . $query . '%';
            $params[':q4'] = '%' . $query . '%';
            if ($normalized !== '') {
                $where[] = 'cnpj LIKE :qc';
                $params[':qc'] = '%' . $normalized . '%';
            }
        }

        if ($status !== '' && strcasecmp($status, 'all') !== 0) {
            $where[] = 'status = :status';
            $params[':status'] = $status;
        }

        $whereStr = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Mapeamento de sort → ORDER BY SQL
        $sort = trim((string)($filters['sort'] ?? 'cnpj_asc'));
        $orderByMap = [
            'cnpj_asc'        => 'cnpj ASC',
            'cnpj_desc'       => 'cnpj DESC',
            'name_asc'        => 'name ASC',
            'name_desc'       => 'name DESC',
            'pendencias_asc'  => 'pendencias ASC, name ASC',
            'pendencias_desc' => 'pendencias DESC, name ASC',
        ];
        $orderBy = $orderByMap[$sort] ?? 'name ASC';

        // Total
        $total = $this->repo->count($whereStr, $params);

        // Items
        $offset = ($page - 1) * $perPage;
        $items = $this->repo->list($whereStr, $params, $orderBy, $perPage, $offset);

        $totalPages = (int)ceil($total / $perPage);

        return [
            'items'       => $items,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    /**
     * Retorna todos os fornecedores (sem paginação) — compatibilidade com código legado.
     * @return array<int, array>
     */
    public function all(): array
    {
        return $this->repo->list('', [], 'name ASC', 10000, 0);
    }

    /**
     * Busca um fornecedor pelo CNPJ normalizado.
     */
    public function findByCnpj(string $cnpj): ?array
    {
        $normalized = Cnpj::normalize($cnpj);
        if ($normalized === '') return null;
        return $this->repo->findByCnpj($normalized);
    }

    /**
     * Cria ou atualiza um fornecedor.
     */
    public function save(array $data): array
    {
        $normalized = Cnpj::normalize($data['cnpj'] ?? '');
        if ($normalized === '') {
            throw new \RuntimeException('CNPJ inválido.');
        }

        $payload = [
            'cnpj'            => $normalized,
            'cnpj_formatted'  => Cnpj::format($data['cnpj'] ?? ''),
            'name'            => trim((string)($data['name'] ?? '')),
            'contacts'        => trim((string)($data['contacts'] ?? '')),
            'status'          => trim((string)($data['status'] ?? 'pendente')),
            'notes'           => trim((string)($data['notes'] ?? '')),
            'pendencias'      => max(0, (int)($data['pendencias'] ?? 0)),
            'send_report'     => (bool)($data['send_report'] ?? true),
        ];

        $existing = $this->repo->findByCnpj($normalized);
        if ($existing) {
            $this->repo->update($normalized, $payload);
        } else {
            $this->repo->create($payload);
        }

        return $payload;
    }

    /**
     * Remove um fornecedor pelo CNPJ.
     */
    public function delete(string $cnpj): void
    {
        $normalized = Cnpj::normalize($cnpj);
        if ($normalized === '') {
            throw new \RuntimeException('CNPJ inválido para remoção.');
        }
        $this->repo->delete($normalized);
    }

    /**
     * Verifica se o fornecedor pode receber envio de pendências.
     */
    public function canSendPending(array $vendor): bool
    {
        $statusOk = in_array($vendor['status'] ?? '', ['validado', 'envio teste'], true);
        $hasContacts = trim((string)($vendor['contacts'] ?? '')) !== '';
        $hasPendencias = (int)($vendor['pendencias'] ?? 0) > 0;

        return $statusOk && $hasContacts && $hasPendencias;
    }

    /**
     * Retorna a quantidade de pendências para exibição.
     */
    public function displayPendingCount(array $vendor): int
    {
        return (int)($vendor['pendencias'] ?? 0);
    }

    /**
     * Normaliza e valida uma string de contatos (e-mails separados por vírgula).
     * Retorna array de contatos válidos ou array vazio se nenhum for válido.
     */
    public function normalizeContacts(string $contacts): array
    {
        $parts = array_map('trim', explode(',', $contacts));
        $valid = array_filter($parts, static function (string $part): bool {
            // Aceita e-mails válidos
            return filter_var($part, FILTER_VALIDATE_EMAIL) !== false;
        });

        return array_values($valid);
    }

    /**
     * Alias para save() — compatibilidade com código legado.
     */
    public function upsert(array $data): array
    {
        return $this->save($data);
    }
}
