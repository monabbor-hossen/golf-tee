<?php
declare(strict_types=1);

namespace App\Core;

/**
 * View template renderer with layout and escaping support.
 */
class View
{
    private static string $viewsPath;

    public static function setViewsPath(string $path): void
    {
        self::$viewsPath = rtrim($path, '/\\') . DIRECTORY_SEPARATOR;
    }

    public static function render(string $template, array $data = [], bool $withLayout = true): void
    {
        $viewFile = self::$viewsPath . str_replace('.', DIRECTORY_SEPARATOR, $template) . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View template not found: {$template} ({$viewFile})");
        }

        // Extract variables to local scope
        extract($data, EXTR_SKIP);

        // Global layout helpers
        $appName     = Config::get('APP_NAME');
        $appUrl      = Config::get('APP_URL');
        $currentPage = $data['currentPage'] ?? basename($template);

        if ($withLayout) {
            $headerFile = self::$viewsPath . 'layouts' . DIRECTORY_SEPARATOR . 'header.php';
            if (file_exists($headerFile)) {
                require $headerFile;
            }
        }

        require $viewFile;

        if ($withLayout) {
            $footerFile = self::$viewsPath . 'layouts' . DIRECTORY_SEPARATOR . 'footer.php';
            if (file_exists($footerFile)) {
                require $footerFile;
            }
        }
    }

    public static function escape(?string $string): string
    {
        return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
    }
}
