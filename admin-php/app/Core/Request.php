<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Encapsulated HTTP Request wrapper.
 */
class Request
{
    private array $get;
    private array $post;
    private array $server;
    private ?array $json = null;

    public function __construct(?array $get = null, ?array $post = null, ?array $server = null)
    {
        $this->get = $get ?? $_GET;
        $this->post = $post ?? $_POST;
        $this->server = $server ?? $_SERVER;
    }

    public static function createFromGlobals(): self
    {
        return new self($_GET, $_POST, $_SERVER);
    }

    public function method(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function isPost(): bool
    {
        return $this->method() === 'POST';
    }

    public function isGet(): bool
    {
        return $this->method() === 'GET';
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->get[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->get[$key] ?? $this->json()[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->get, $this->post, $this->json());
    }

    public function json(): array
    {
        if ($this->json === null) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            $this->json = is_array($decoded) ? $decoded : [];
        }
        return $this->json;
    }

    public function getString(string $key, string $default = ''): string
    {
        $val = $this->input($key, $default);
        return is_string($val) ? trim($val) : (string)$default;
    }

    public function getInt(string $key, int $default = 0): int
    {
        $val = $this->input($key, $default);
        return is_numeric($val) ? (int)$val : $default;
    }

    public function getFloat(string $key, float $default = 0.0): float
    {
        $val = $this->input($key, $default);
        return is_numeric($val) ? (float)$val : $default;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $val = $this->input($key, $default);
        if ($val === null) return $default;
        return in_array($val, [true, 1, '1', 'true', 'on', 'yes'], true);
    }
}
