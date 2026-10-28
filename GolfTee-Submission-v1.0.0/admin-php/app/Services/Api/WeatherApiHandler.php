<?php
declare(strict_types=1);

namespace App\Services\Api;

use App\Services\WeatherService;

/**
 * Dedicated REST API Weather Handler in App\Services\Api namespace.
 */
class WeatherApiHandler
{
    private WeatherService $weatherService;

    public function __construct(?WeatherService $weatherService = null)
    {
        $this->weatherService = $weatherService ?? new WeatherService();
    }

    public function handleWeather(): array
    {
        return [
            'status' => 'ok',
            'conditions' => $this->weatherService->getConditions()
        ];
    }
}
