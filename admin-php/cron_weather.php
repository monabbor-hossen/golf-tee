<?php
declare(strict_types=1);

/**
 * Weather Auto-Refresh Endpoint / CLI Script
 *
 * Runs hourly to keep course conditions & weather cache up-to-date.
 * Usage:
 *   CLI: php cron_weather.php
 *   Web: GET /admin-php/cron_weather.php?key=auto
 */

require_once __DIR__ . '/bootstrap.php';

use App\Services\SchedulerService;

header('Content-Type: application/json; charset=utf-8');

try {
    $scheduler = new SchedulerService();
    $force = isset($_GET['force']) ? ((int)$_GET['force'] === 1) : true;
    $result = $scheduler->runWeatherSync(force: $force);

    echo json_encode([
        'status' => 'ok',
        'message' => 'Weather refreshed successfully (1-hour cycle via SchedulerService)',
        'result' => $result,
    ], JSON_PRETTY_PRINT);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
    ]);
}
