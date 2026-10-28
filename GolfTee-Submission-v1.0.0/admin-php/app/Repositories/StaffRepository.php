<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class StaffRepository
{
    public function findByEmail(string $email): ?array
    {
        return Database::fetchOne('SELECT * FROM staff WHERE email = ? LIMIT 1', [$email]);
    }

    public function findById(int $id): ?array
    {
        return Database::fetchOne('SELECT * FROM staff WHERE id = ? LIMIT 1', [$id]);
    }

    public function getAll(): array
    {
        return Database::fetchAll('SELECT * FROM staff ORDER BY role, name');
    }

    public function create(string $name, string $email, string $role, string $hashedPassword): bool
    {
        return Database::execute(
            'INSERT INTO staff (name, email, role, password) VALUES (?, ?, ?, ?)',
            [$name, $email, $role, $hashedPassword]
        );
    }

    public function delete(int $id): bool
    {
        return Database::execute('DELETE FROM staff WHERE id = ?', [$id]);
    }
}
