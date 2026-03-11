<?php

declare(strict_types=1);

namespace App\Controllers;

abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data);
        $viewFile = BASE_PATH . "/views/{$view}.php";

        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            die("View {$view} not found.");
        }
    }

    protected function jsonResponse(mixed $data, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data);
        exit;
    }
}
