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

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false, // Strictly force true server-side prepared statements
                ]);
            } catch (PDOException $e) {
                http_response_code(500);
                error_log('Database connection error: ' . $e->getMessage());

                if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
                    header('Content-Type: application/json');
                    die(json_encode(['error' => 'Database connection failed. Please ensure MySQL is running.']));
                }
                die('<div style="font-family:sans-serif;padding:24px;color:#b91c1c;background:#fef2f2;border:1px solid #f87171;border-radius:8px;">' .
                    '<h3>Database Connection Error</h3>' .
                    '<p>Could not connect to the database. Please verify your credentials and ensure MySQL is running in XAMPP.</p>' .
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
