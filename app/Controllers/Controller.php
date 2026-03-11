<?php

declare(strict_types=1);

namespace App\Controllers;

abstract class Controller
{
    /**
     * Render a view file with the given data.
     *
     * Uses an isolated static closure so that:
     *  - Data variables cannot overwrite internal variables ($view, $viewFile).
     *  - extract() uses EXTR_SKIP to refuse overriding any pre-existing variable.
     *  - Missing views throw a RuntimeException rather than silently die().
     */
    protected function view(string $view, array $data = []): void
    {
        $__viewFile = BASE_PATH . "/views/{$view}.php";

        if (!file_exists($__viewFile)) {
            throw new \RuntimeException("View [{$view}] not found at [{$__viewFile}].");
        }

        // Isolate scope: $__file and $__data cannot be overwritten by $data keys
        (static function (string $__file, array $__data): void {
            extract($__data, EXTR_SKIP);
            require $__file;
        })($__viewFile, $data);
    }

    protected function jsonResponse(mixed $data, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data);
        exit;
    }
}
