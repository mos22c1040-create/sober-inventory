<?php
namespace App\Controllers;

class HomeController extends Controller {
    public function index() {
        // Here we could render the landing or login page
        // For now, we'll just demonstrate the custom framework is working
        echo "<h1>Welcome to the Next-Gen POS System!</h1>";
        echo "<p>Core Controller and Router are perfectly synchronized.</p>";
        echo "<a href='/dashboard'>Go to Dashboard</a>";
    }
}
