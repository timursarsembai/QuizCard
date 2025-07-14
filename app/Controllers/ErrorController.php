<?php
namespace App\Controllers;

class ErrorController {
    public function index() {
        // Определяем код ошибки из переменных сервера
        $error_code = $_SERVER['REDIRECT_STATUS'] ?? '404';
        
        // Устанавливаем соответствующий HTTP статус
        http_response_code($error_code);
        
        // Подключаем view для отображения ошибки
        require_once __DIR__ . '/../Views/error.php';
    }
}
