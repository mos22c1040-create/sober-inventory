<?php
namespace App\Controllers;

use App\Helpers\AuthHelper;

class HomeController extends Controller {
    public function index(): void {
        AuthHelper::startSession();
        // Redirect to dashboard if logged in, otherwise to login
        if (!empty($_SESSION['user_id'])) {
            header('Location: /dashboard');
            exit;
        }
        header('Location: /login');
        exit;
    }
}
