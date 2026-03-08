<?php
namespace App\Controllers;

abstract class Controller {
    // Helper method to load views easily
    protected function view($view, $data = []) {
        extract($data);
        $viewFile = BASE_PATH . "/views/{$view}.php";
        
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            die("View {$view} not found.");
        }
    }

    // JSON response helper for AJAX operations
    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
