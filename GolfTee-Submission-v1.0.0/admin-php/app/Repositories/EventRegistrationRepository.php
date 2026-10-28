<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

class EventRegistrationRepository
{
    public function getByEventId(int $eventId): array
    {
        return Database::fetchAll(
            'SELECT * FROM event_registrations WHERE event_id = ? ORDER BY registered_at ASC',
            [$eventId]
        );
    }

    public function getCount(int $eventId): int
    {
        return (int)Database::fetchColumn(
            'SELECT COUNT(*) FROM event_registrations WHERE event_id = ?',
            [$eventId]
        );
    }

    public function register(int $eventId, string $name): bool
    {
        return Database::execute(
            'INSERT INTO event_registrations (event_id, name) VALUES (?, ?)',
            [$eventId, $name]
        );
    }

    public function unregister(int $registrationId): bool
    {
        return Database::execute(
            'DELETE FROM event_registrations WHERE id = ?',
            [$registrationId]
        );
    }
}
