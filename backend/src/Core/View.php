<?php

namespace App\Core;

use RuntimeException;
use Throwable;

/**
 * Minimal PHP template renderer. Templates live in backend/views/.
 */
final class View
{
    /**
     * Render a template, wrapped in a layout (pass null for a standalone page).
     */
    public static function render(string $template, array $data = [], ?string $layout = 'layouts/app'): void
    {
        $content = self::capture($template, $data);
        if ($layout === null) {
            echo $content;
            return;
        }
        echo self::capture($layout, ['content' => $content] + $data);
    }

    public static function capture(string $__template, array $__data = []): string
    {
        $__file = BASE_PATH . '/views/' . $__template . '.php';
        if (!is_file($__file)) {
            throw new RuntimeException("View not found: {$__template}");
        }

        extract($__data, EXTR_SKIP);
        ob_start();
        try {
            include $__file;
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string)ob_get_clean();
    }
}
