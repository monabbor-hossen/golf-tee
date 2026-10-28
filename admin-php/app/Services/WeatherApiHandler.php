<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Dedicated handler for mobile weather and course condition requests.
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
