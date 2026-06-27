<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Services;

use MateriaisOpme\App\Support\Database;

final class EspecialidadesService
{
    public function listAll(): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query('SELECT id, nome, ativo FROM consumo_especialidades WHERE ativo = 1 ORDER BY nome');
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('SELECT id, nome, ativo FROM consumo_especialidades WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(string $nome): int
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('INSERT INTO consumo_especialidades (nome) VALUES (?)');
        $stmt->execute([trim($nome)]);
        return (int)$pdo->lastInsertId();
    }

    public function update(int $id, string $nome, bool $ativo): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('UPDATE consumo_especialidades SET nome = ?, ativo = ? WHERE id = ?');
        $stmt->execute([trim($nome), $ativo ? 1 : 0, $id]);
    }

    public function deactivate(int $id): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('UPDATE consumo_especialidades SET ativo = 0 WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function getEspecialidadesByFornecedor(string $cnpj): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT e.id, e.nome
            FROM consumo_especialidades e
            INNER JOIN consumo_fornecedor_especialidade fe ON fe.id_especialidade = e.id
            WHERE fe.cnpj_fornecedor = ? AND e.ativo = 1
            ORDER BY e.nome
        ');
        $stmt->execute([$cnpj]);
        return $stmt->fetchAll();
    }

    public function syncFornecedorEspecialidades(string $cnpj, array $especialidadeIds): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            // Remove associações existentes
            $stmt = $pdo->prepare('DELETE FROM consumo_fornecedor_especialidade WHERE cnpj_fornecedor = ?');
            $stmt->execute([$cnpj]);

            // Insere novas
            if (!empty($especialidadeIds)) {
                $placeholders = implode(',', array_fill(0, count($especialidadeIds), '(?, ?)'));
                $sql = "INSERT INTO consumo_fornecedor_especialidade (cnpj_fornecedor, id_especialidade) VALUES $placeholders";
                $stmt = $pdo->prepare($sql);

                $params = [];
                foreach ($especialidadeIds as $espId) {
                    $params[] = $cnpj;
                    $params[] = (int)$espId;
                }
                $stmt->execute($params);
            }

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function getFornecedoresByEspecialidade(int $espId): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT f.cnpj, f.name, f.status
            FROM consumo_fornecedores f
            INNER JOIN consumo_fornecedor_especialidade fe ON fe.cnpj_fornecedor = f.cnpj
            WHERE fe.id_especialidade = ?
            ORDER BY f.name
        ');
        $stmt->execute([$espId]);
        return $stmt->fetchAll();
    }

    public function getFornecedoresNaoByEspecialidade(int $espId): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT f.cnpj, f.name, f.status
            FROM consumo_fornecedores f
            WHERE f.cnpj NOT IN (
                SELECT cnpj_fornecedor FROM consumo_fornecedor_especialidade WHERE id_especialidade = ?
            )
            ORDER BY f.name
        ');
        $stmt->execute([$espId]);
        return $stmt->fetchAll();
    }

    public function getEspecialidadesNaoAssociadas(string $cnpj): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare('
            SELECT e.id, e.nome
            FROM consumo_especialidades e
            WHERE e.ativo = 1
            AND e.id NOT IN (
                SELECT id_especialidade FROM consumo_fornecedor_especialidade WHERE cnpj_fornecedor = ?
            )
            ORDER BY e.nome
        ');
        $stmt->execute([$cnpj]);
        return $stmt->fetchAll();
    }
}
