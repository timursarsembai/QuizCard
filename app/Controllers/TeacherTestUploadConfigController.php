<?php

namespace App\Controllers;

class TeacherTestUploadConfigController 
{
    public function config() 
    {
        session_start();

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
            header('Location: /login');
            exit;
        }

        // Загружаем view для конфигурации загрузки тестов
        include __DIR__ . '/../Views/teacher/test_upload_config.php';
    }
}
