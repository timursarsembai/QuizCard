<?php

namespace App\Controllers;

use App\Models\SecurityLogger;

class TeacherSecurityDashboardController 
{
    private $db;
    
    public function __construct($db) 
    {
        $this->db = $db;
    }
    
    public function dashboard() 
    {
        session_start();

        // Проверка прав доступа (только для преподавателей)
        if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
            header('Location: /login');
            exit;
        }

        // Получение ID текущего преподавателя
        $teacher_id = $_SESSION['user_id'];

        // Получение статистики безопасности для данного преподавателя и его студентов
        $timeframe = $_GET['timeframe'] ?? '24';
        $stats = SecurityLogger::getTeacherSecurityStats($teacher_id, intval($timeframe));
        $rateLimitStats = SecurityLogger::getRateLimitStats(); // Общая статистика rate limiting
        $recentLogs = SecurityLogger::getTeacherSecurityLogs($teacher_id, 50);

        // Переменные для фильтрации
        $filter = $_GET['filter'] ?? 'all';

        // Настройки для header.php
        $page_title = "Панель безопасности";
        $page_icon = "fas fa-shield-alt";

        // Загружаем view
        include __DIR__ . '/../Views/teacher/security_dashboard.php';
    }
}
