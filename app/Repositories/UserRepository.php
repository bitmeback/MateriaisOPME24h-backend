<?php
declare(strict_types=1);

namespace MateriaisOpme\App\Repositories;

use MateriaisOpme\App\Support\Database;
use PDO;

final class UserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::pdo();
    }

    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT id, username, password_hash, full_name, role, active, last_login_at, created_at, updated_at FROM users ORDER BY role DESC, active DESC, username ASC');
        return $stmt->fetchAll();
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute(['username' => $username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, password_hash, full_name, role, active, created_at, updated_at)
             VALUES (:username, :password_hash, :full_name, :role, :active, NOW(), NOW())'
        );
        $stmt->execute([
            'username' => $data['username'],
            'password_hash' => $data['password_hash'],
            'full_name' => $data['full_name'],
            'role' => $data['role'],
            'active' => (int) $data['active'],
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users
             SET username = :username,
                 full_name = :full_name,
                 role = :role,
                 active = :active,
                 updated_at = NOW()
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'username' => $data['username'],
            'full_name' => $data['full_name'],
            'role' => $data['role'],
            'active' => (int) $data['active'],
        ]);
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = :password_hash, updated_at = NOW() WHERE id = :id');
        $stmt->execute([
            'id' => $id,
            'password_hash' => $passwordHash,
        ]);
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function updateLastLogin(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET last_login_at = NOW(), updated_at = NOW() WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
