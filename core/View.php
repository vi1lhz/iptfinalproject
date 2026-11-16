<?php

namespace Core;

/**
 * View – renders plain-PHP templates from app/Views.
 * A page template is rendered first, then wrapped in a layout that receives it as $content.
 */
class View
{
    private static string $path = '';
    private static array $shared = [];

    public static function setPath(string $path): void
    {
        self::$path = rtrim($path, '/');
    }

    /** Data available to every template (e.g. current user, cart count). */
    public static function share(string $key, mixed $value): void
    {
        self::$shared[$key] = $value;
    }

    public static function render(string $view, array $data = [], ?string $layout = 'layouts/main'): string
    {
        $content = self::renderFile($view, $data);

        if ($layout === null) {
            return $content;
        }

        return self::renderFile($layout, array_merge($data, ['content' => $content]));
    }

    /** Renders a small reusable piece, e.g. View::partial('partials/item-card', ['item' => $item]). */
    public static function partial(string $view, array $data = []): string
    {
        return self::renderFile($view, $data);
    }

    private static function renderFile(string $view, array $data): string
    {
        $file = self::$path . '/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: $view");
        }

        extract(array_merge(self::$shared, $data), EXTR_SKIP);

        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return ob_get_clean();
    }
}
