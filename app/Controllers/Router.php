<?php
// Простой роутер для MVC
namespace App\Controllers;

use App\Config\Database;

class Router {
    public static function route($uri) {
        // Создаем подключение к базе данных
        $database = new Database();
        $db = $database->getConnection();
        
        $path = parse_url($uri, PHP_URL_PATH);
        $path = trim($path, '/');
        
        // Специальные маршруты
        $routes = [
            'login' => ['LoginController', 'index'],
            'logout' => ['LogoutController', 'logout'], 
            'student-login' => ['LoginController', 'studentLogin'],
            'error' => ['ErrorController', 'index'],
            'verify-email' => ['VerifyEmailController', 'verify'],
            'email-verification-required' => ['EmailVerificationRequiredController', 'index'],
            'api/csrf-token' => ['ApiCsrfTokenController', 'generate'],
            'api/log-security' => ['ApiLogSecurityController', 'log'],
            'set-language' => ['LanguageSwitcherController', 'setLanguage'],
            'teacher/test-edit' => ['TeacherTestEditController', 'edit'],
            'teacher/test-preview' => ['TeacherTestPreviewController', 'preview'],
            'teacher/security-dashboard' => ['TeacherSecurityDashboardController', 'dashboard'],
            'teacher/deck-students' => ['TeacherDeckStudentsController', 'manage'],
        ];
        
        if (isset($routes[$path])) {
            list($controller, $method) = $routes[$path];
        } else {
            // Общая логика для остальных маршрутов
            $parts = explode('/', $path);
            
            // Если URI пустой или только /, используем HomeController
            if (empty($parts[0])) {
                $controller = 'HomeController';
                $method = 'index';
            } else {
                // Составные маршруты типа teacher/dashboard
                if (count($parts) >= 2) {
                    $controller = ucfirst($parts[0]) . ucfirst($parts[1]) . 'Controller';
                    $method = $parts[2] ?? 'index';
                } else {
                    $controller = ucfirst($parts[0]) . 'Controller';
                    $method = $parts[1] ?? 'index';
                }
            }
        }
        
        $controllerClass = 'App\\Controllers\\' . $controller;
        if (class_exists($controllerClass)) {
            $obj = new $controllerClass($db);
            if (method_exists($obj, $method)) {
                return $obj->$method();
            }
        }
        
        // 404
        http_response_code(404);
        echo 'Not Found';
    }
}
