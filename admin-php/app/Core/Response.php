<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Encapsulated HTTP Response wrapper (redirects, JSON, CSV downloads).
 * Includes security protections against CSV formula injection.
 */
class Response
{
    public static function redirect(string $url): never
    {
        header("Location: {$url}");
        exit;
    }

    public static function json(mixed $data, int $code = 200): never
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function unauthorized(string $message = 'Authentication required'): never
    {
        self::json(['status' => 'error', 'error' => $message, 'message' => $message], 401);
    }

    public static function forbidden(string $message = 'Forbidden'): never
    {
        self::json(['status' => 'error', 'error' => $message, 'message' => $message], 403);
    }

    public static function csv(string $filename, array $headers, array $rows): never
    {
        // Sanitize filename to prevent header injection or directory traversal
        $safeFilename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('X-Content-Type-Options: nosniff');

        $out = fopen('php://output', 'w');
        // Add UTF-8 BOM for Excel compatibility
        fputs($out, "\xEF\xBB\xBF");

        // Sanitize headers
        $sanitizedHeaders = array_map([self::class, 'sanitizeCsvCell'], $headers);
        fputcsv($out, $sanitizedHeaders);

        // Sanitize each row to neutralize formula injection
        foreach ($rows as $row) {
            $sanitizedRow = array_map([self::class, 'sanitizeCsvCell'], (array)$row);
            fputcsv($out, $sanitizedRow);
        }
        fclose($out);
        exit;
    }

    /**
     * Neutralize CSV formula injection (=, +, -, @, \t, \r)
     */
    public static function sanitizeCsvCell(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }
        $val = trim($value);
        if ($val !== '' && in_array($val[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $val;
        }
        return $value;
    }
}
