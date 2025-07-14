<?php

namespace App\Controllers;

class TeacherSecurityDashboardNewController 
{
    public function dashboard() 
    {
        session_start();

        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
            header('Location: /login');
            exit;
        }

        // Перенаправляем на основную панель безопасности
        header('Location: /teacher/security-dashboard');
        exit;
    }
}
