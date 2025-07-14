<?php

namespace App\Controllers;

class TeacherLanguageIntegrationGuideController 
{
    public function guide() 
    {
        session_start();

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
            header('Location: /login');
            exit;
        }

        // Загружаем view для руководства по интеграции языков
        include __DIR__ . '/../Views/teacher/language_integration_guide.php';
    }
}
