<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Dedicated .env file loader and environment parser.
 * Single responsibility: parses key-value pairs from .env files into $_ENV and putenv.
 */
class EnvLoader
{
    /**
     * Load environment variables from the specified file path or directory.
     *
     * @param string $path Path to the .env file or directory containing .env
     * @return array<string, string> Parsed environment key-value pairs
     */
    public static function load(string $path): array
    {
        $items = [];

        if (is_dir($path)) {
            $envFile = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . '.env';
            if (file_exists($envFile)) {
                $path = $envFile;
            } else {
                $exampleFile = rtrim($path, '/\\') . DIRECTORY_SEPARATOR . '.env.example';
                if (file_exists($exampleFile)) {
                    $path = $exampleFile;
                } else {
                    return $items;
                }
            }
        }

        if (!file_exists($path) || is_dir($path)) {
            return $items;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return $items;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (!str_contains($line, '=')) {
                continue;
            }
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val);

            if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                $val = substr($val, 1, -1);
            }

            $_ENV[$key] = $val;
            putenv("$key=$val");
            $items[$key] = $val;
        }

        return $items;
    }
}
