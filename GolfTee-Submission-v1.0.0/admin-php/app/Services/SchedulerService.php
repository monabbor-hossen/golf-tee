<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Services\WeatherService;

/**
 * Scheduled cron and background task orchestrator for GolfTee.
 */
class SchedulerService
{
    private WeatherService $weatherService;

    public function __construct(?WeatherService $weatherService = null)
    {
        $this->weatherService = $weatherService ?? new WeatherService();
    }

    /**
     * Run the hourly weather synchronization job.
     *
     * @param bool $force Force cache refresh ignoring TTL
     * @return array Result metadata
     */
    public function runWeatherSync(bool $force = true): array
    {
        $startTime = microtime(true);
        $conditions = $this->weatherService->getConditions(forceRefresh: $force);
        $duration = round(microtime(true) - $startTime, 4);

        return [
            'status'     => 'ok',
            'task'       => 'weather_sync',
            'updated_at' => $conditions['updated_at'] ?? date('Y-m-d H:i:s'),
            'duration_s' => $duration,
            'conditions' => $conditions,
        ];
    }

    /**
     * Run system maintenance tasks (e.g. clean expired setup tokens).
     */
    public function runDailyMaintenance(): array
    {
        // Expire unactivated setup tokens older than 14 days
        $cleaned = Database::execute(
            'UPDATE members SET setup_token = NULL, setup_token_expires = NULL WHERE setup_token_expires < DATE_SUB(NOW(), INTERVAL 14 DAY)'
        );

        return [
            'status'           => 'ok',
            'task'             => 'daily_maintenance',
            'tokens_expired'   => $cleaned,
            'executed_at'      => date('Y-m-d H:i:s'),
        ];
    }
}
