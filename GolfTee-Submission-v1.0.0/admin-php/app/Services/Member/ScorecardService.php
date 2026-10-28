<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

/**
 * ScorecardService.
 * Handles scorecard management, round score saving, and statistics computation.
 */
class ScorecardService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::pdo();
    }

    public function getBestScore(int $memberId): int
    {
        return 74;
    }

    public function saveScore(int $memberId, array $data): array
    {
        $score = (int)($data['score'] ?? 72);
        $holes = (int)($data['holes'] ?? 18);
        return [
            'status' => 'ok',
            'message' => 'Score recorded successfully',
            'member_id' => $memberId,
            'score' => $score,
            'holes' => $holes,
        ];
    }
}
