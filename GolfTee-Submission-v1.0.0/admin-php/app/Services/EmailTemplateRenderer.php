<?php
declare(strict_types=1);

namespace App\Services;

/**
 * EmailTemplateRenderer
 * Centralized template rendering service that safely loads and renders templates with context variables.
 */
class EmailTemplateRenderer
{
    /**
     * Render a PHP template file with an associative array of context variables.
     */
    public static function render(string $templatePath, array $context = []): string
    {
        if (!file_exists($templatePath)) {
            return '';
        }

        extract($context, EXTR_SKIP);

        ob_start();
        include $templatePath;
        return (string)ob_get_clean();
    }
}
