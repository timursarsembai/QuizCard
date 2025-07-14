<?php
namespace App\Controllers;

class HomeController {
    private $db;
    
    public function __construct($db = null) {
        $this->db = $db;
    }
    
    public function index() {
        // Проверяем, авторизован ли пользователь
        if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
            // Редиректим в зависимости от роли
            if ($_SESSION['role'] === 'teacher') {
                header('Location: /teacher/dashboard');
                exit();
            } elseif ($_SESSION['role'] === 'student') {
                header('Location: /student/dashboard');
                exit();
            }
        }
        
        // Загружаем главную страницу
        include __DIR__ . '/../Views/home.php';
    }
}
