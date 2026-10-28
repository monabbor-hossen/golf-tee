<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;

/**
 * Encapsulated PDO Singleton with query and transaction utilities,
 * strict prepared statement enforcement, and SQL injection protections.
 */
class Database
{
    private static ?PDO $instance = null;

    public static function pdo(): PDO
    {
        if (self::$instance === null) {
            $host    = Config::get('DB_HOST', 'localhost');
            $db      = Config::get('DB_NAME', 'golf_admin');
            $user    = Config::get('DB_USER', 'root');
            $pass    = Config::get('DB_PASS', '');
            $charset = Config::get('DB_CHARSET', 'utf8mb4');

            $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";

            $errmode = (int)Config::get('DB_ERRMODE', PDO::ERRMODE_EXCEPTION);
            $fetchMode = (int)Config::get('DB_FETCH_MODE', PDO::FETCH_ASSOC);
            $emulatePrepares = (bool)Config::get('DB_EMULATE_PREPARES', false);

            $options = (array)Config::get('DB_OPTIONS', [
                PDO::ATTR_ERRMODE            => $errmode,
                PDO::ATTR_DEFAULT_FETCH_MODE => $fetchMode,
                PDO::ATTR_EMULATE_PREPARES   => $emulatePrepares,
            ]);

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                error_log('Database connection error: ' . $e->getMessage());

                if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
                    http_response_code(500);
                    header('Content-Type: application/json');
                    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
                }
                die('<div style="font-family:system-ui,-apple-system,sans-serif;max-width:640px;margin:40px auto;padding:24px;color:#991b1b;background:#fef2f2;border:1px solid #f87171;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.05);">' .
                    '<h2 style="margin-top:0;font-size:20px;">Database Connection Error</h2>' .
                    '<p><strong>Error details:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>' .
                    '<hr style="border:0;border-top:1px solid #fecaca;margin:16px 0;">' .
                    '<p style="color:#4b5563;font-size:14px;line-height:1.5;"><strong>Troubleshooting Checklist:</strong><br>' .
                    '1. Verify your <code>DB_HOST</code> in <code>.env</code> is your InfinityFree SQL hostname (e.g. <code>sql105.infinityfree.com</code>), <em>not</em> <code>localhost</code>.<br>' .
                    '2. Ensure <code>DB_NAME</code> starts with your prefix (e.g. <code>if0_xxxx_golf</code>).<br>' .
                    '3. Ensure <code>DB_USER</code> is your InfinityFree account username.<br>' .
                    '4. Ensure <code>DB_PASS</code> is your hosting account password.</p>' .
                    '</div>');
            }
        }

        return self::$instance;
    }

    public static function prepare(string $sql): PDOStatement
    {
        return self::pdo()->prepare($sql);
    }

    public static function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = self::pdo()->prepare($sql);
        // Bind parameters safely by 1-indexed position or associative key
        $i = 1;
        foreach ($params as $param) {
            $type = match (true) {
                is_int($param)  => PDO::PARAM_INT,
                is_bool($param) => PDO::PARAM_BOOL,
                is_null($param) => PDO::PARAM_NULL,
                default         => PDO::PARAM_STR,
            };
            $stmt->bindValue($i++, $param, $type);
        }
        $stmt->execute();
        return $stmt;
    }

    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $res = self::query($sql, $params)->fetch();
        return $res ?: null;
    }

    public static function fetchColumn(string $sql, array $params = [], int $col = 0): mixed
    {
        $res = self::query($sql, $params)->fetchColumn($col);
        return $res !== false ? $res : null;
    }

    public static function execute(string $sql, array $params = []): bool
    {
        return self::query($sql, $params)->rowCount() > 0;
    }

    public static function lastInsertId(): string
    {
        return self::pdo()->lastInsertId();
    }

    public static function transaction(callable $callback): mixed
    {
        $pdo = self::pdo();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            error_log('Transaction rolled back: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Escape user input for SQL LIKE queries to prevent wildcard injection (%, _, \)
     */
    public static function escapeLike(string $value): string
    {
        return addcslashes($value, '%_\\');
    }
}
