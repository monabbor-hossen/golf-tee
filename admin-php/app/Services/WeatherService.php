<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\SettingRepository;

class WeatherService
{
    private SettingRepository $settingRepo;
    private string $cacheDir;

    public function __construct(?SettingRepository $settingRepo = null)
    {
        $this->settingRepo = $settingRepo ?? new SettingRepository();
        $this->cacheDir = __DIR__ . '/../../scratch';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0777, true);
        }
    }

    /**
     * Get real-time course weather and conditions for the given location or GPS coordinates.
     */
    public function getConditions(
        ?string $location = null,
        ?string $unit = null,
        bool $forceRefresh = false,
        ?float $lat = null,
        ?float $lng = null
    ): array {
        $hasGps = ($lat !== null && $lng !== null);

        if ($hasGps) {
            $queryTarget = round($lat, 4) . ',' . round($lng, 4);
            $loc = $location ?: ($queryTarget);
        } else {
            $loc = trim($location ?? $this->settingRepo->get('course_location', 'Pine Valley, NJ'));
            if (empty($loc)) {
                $loc = 'Pine Valley, NJ';
            }
            $queryTarget = $loc;
        }

        $unit = strtoupper($unit ?? $this->settingRepo->get('weather_unit', 'F'));
        $greenSpeed = $this->settingRepo->get('green_speed', '10.5');
        $speedDesc = $this->settingRepo->get('speed_desc', 'Green speed');
        $carts = $this->settingRepo->get('carts', 'Open');
        $cartsDesc = $this->settingRepo->get('carts_desc', 'Carts on fairways');

        $cacheKey = strtolower($queryTarget) . '_' . $unit;
        $cacheFile = $this->cacheDir . '/weather_' . md5($cacheKey) . '.json';
        $cacheTtl = 3600; // 1 hour (auto refresh weather after one hour)

        if (!$forceRefresh && file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTtl)) {
            $cached = @json_decode((string)file_get_contents($cacheFile), true);
            if (is_array($cached) && !empty($cached['weather'])) {
                $cached['green_speed'] = $greenSpeed;
                $cached['speed_desc']  = $speedDesc;
                $cached['carts']       = $carts;
                $cached['carts_desc']  = $cartsDesc;
                $cached['cache_expires_in'] = max(0, $cacheTtl - (time() - filemtime($cacheFile)));
                return $cached;
            }
        }

        // Fetch live from Free API (wttr.in)
        $live = $this->fetchFromWttr($queryTarget, $unit);

        if ($live !== null) {
            $displayLocation = !empty($live['resolved_location']) ? $live['resolved_location'] : $loc;
            $resLat = $lat ?? ($live['lat'] !== null ? (float)$live['lat'] : null);
            $resLng = $lng ?? ($live['lng'] !== null ? (float)$live['lng'] : null);

            $gpsFormatted = '';
            if ($resLat !== null && $resLng !== null) {
                $latDir = $resLat >= 0 ? 'N' : 'S';
                $lngDir = $resLng >= 0 ? 'E' : 'W';
                $gpsFormatted = sprintf('%.4f° %s, %.4f° %s', abs($resLat), $latDir, abs($resLng), $lngDir);
            }

            $result = [
                'location'      => $displayLocation,
                'lat'           => $resLat,
                'lng'           => $resLng,
                'gps_formatted' => $gpsFormatted,
                'weather'       => $live['temp'] . '°',
                'weather_desc'  => $live['desc'],
                'wind'          => $live['wind_speed'] . ($unit === 'C' ? ' km/h' : ' mph'),
                'wind_desc'     => $live['wind_dir'] . ' wind',
                'humidity'      => $live['humidity'] . '%',
                'feels_like'    => $live['feels_like'] . '°',
                'green_speed'   => $greenSpeed,
                'speed_desc'    => $speedDesc,
                'carts'         => $carts,
                'carts_desc'    => $cartsDesc,
                'updated_at'    => 'Updated ' . date('g:i A'),
                'updated_timestamp' => time(),
                'cache_ttl'     => $cacheTtl,
                'is_live'       => true,
                'is_gps'        => $hasGps || !empty($gpsFormatted),
                'source'        => $hasGps ? 'Live GPS · Free Weather API' : 'wttr.in Free API',
            ];

            @file_put_contents($cacheFile, json_encode($result, JSON_PRETTY_PRINT));
            $this->settingRepo->set('last_weather_update', (string)time());
            $this->settingRepo->set('last_weather_json', json_encode($result));
            return $result;
        }

        // Fallback realistic condition if network is offline
        $resLat = $lat ?? (float)$this->settingRepo->get('course_lat', '39.7898');
        $resLng = $lng ?? (float)$this->settingRepo->get('course_lng', '-74.9735');
        $gpsFormatted = sprintf('%.4f° N, %.4f° W', abs($resLat), abs($resLng));

        return [
            'location'      => $loc,
            'lat'           => $resLat,
            'lng'           => $resLng,
            'gps_formatted' => $gpsFormatted,
            'weather'       => ($unit === 'C' ? '21°' : '70°'),
            'weather_desc'  => 'Sunny',
            'wind'          => ($unit === 'C' ? '11 km/h' : '7 mph'),
            'wind_desc'     => 'NW wind',
            'humidity'      => '48%',
            'feels_like'    => ($unit === 'C' ? '21°' : '70°'),
            'green_speed'   => $greenSpeed,
            'speed_desc'    => $speedDesc,
            'carts'         => $carts,
            'carts_desc'    => $cartsDesc,
            'updated_at'    => 'Updated ' . date('g:i A'),
            'updated_timestamp' => time(),
            'cache_ttl'     => $cacheTtl,
            'is_live'       => false,
            'is_gps'        => $hasGps,
            'source'        => 'Offline default',
        ];
    }

    /**
     * Clear all cached weather JSON files so fresh weather is fetched immediately.
     */
    public function clearCache(): void
    {
        $files = glob($this->cacheDir . '/weather_*.json');
        if ($files) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    @unlink($file);
                }
            }
        }
    }

    private function fetchFromWttr(string $location, string $unit): ?array
    {
        $encoded = urlencode($location);
        $url = "https://wttr.in/{$encoded}?format=j1";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'curl/7.68.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$body || $httpCode !== 200) {
            return null;
        }

        $data = json_decode((string)$body, true);
        if (!is_array($data) || empty($data['current_condition'][0])) {
            return null;
        }

        $curr = $data['current_condition'][0];

        $temp = $unit === 'C' ? ($curr['temp_C'] ?? '20') : ($curr['temp_F'] ?? '68');
        $feels = $unit === 'C' ? ($curr['FeelsLikeC'] ?? $temp) : ($curr['FeelsLikeF'] ?? $temp);

        $desc = 'Sunny';
        if (!empty($curr['weatherDesc'][0]['value'])) {
            $desc = trim((string)$curr['weatherDesc'][0]['value']);
            if (strcasecmp($desc, 'clear') === 0) {
                $desc = 'Sunny';
            }
        }

        $windSpeed = $unit === 'C' ? ($curr['windspeedKmph'] ?? '10') : ($curr['windspeedMiles'] ?? '6');
        $windDir = trim((string)($curr['winddir16Point'] ?? 'NW'));
        if (empty($windDir)) {
            $windDir = 'NW';
        }

        $humidity = $curr['humidity'] ?? '45';

        // Extract reverse-geocoded location if available
        $resolvedLoc = '';
        $resLat = null;
        $resLng = null;
        if (!empty($data['nearest_area'][0])) {
            $area = $data['nearest_area'][0];
            $areaName = trim($area['areaName'][0]['value'] ?? '');
            $region   = trim($area['region'][0]['value'] ?? '');
            $country  = trim($area['country'][0]['value'] ?? '');
            $resLat   = isset($area['latitude']) ? (float)$area['latitude'] : null;
            $resLng   = isset($area['longitude']) ? (float)$area['longitude'] : null;

            $parts = [];
            if ($areaName !== '') $parts[] = $areaName;
            if ($region !== '' && $region !== $areaName) $parts[] = $region;
            if ($country !== '' && empty($parts)) $parts[] = $country;

            if (!empty($parts)) {
                $resolvedLoc = implode(', ', $parts);
            }
        }

        return [
            'temp'              => $temp,
            'feels_like'        => $feels,
            'desc'              => $desc,
            'wind_speed'        => $windSpeed,
            'wind_dir'          => $windDir,
            'humidity'          => $humidity,
            'resolved_location' => $resolvedLoc,
            'lat'               => $resLat,
            'lng'               => $resLng,
        ];
    }
}
