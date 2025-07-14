<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Deck;
use App\Models\Vocabulary;
use App\Models\Test;

class TeacherStudentProgressController 
{
    private $db;
    private $user;
    private $deck;
    private $vocabulary;
    private $test;
    
    public function __construct($db) 
    {
        $this->db = $db;
        $this->user = new User($db);
        $this->deck = new Deck($db);
        $this->vocabulary = new Vocabulary($db);
        $this->test = new Test($db);
    }
    
    public function index() 
    {
        session_start();

        if (!$this->user->isLoggedIn() || $this->user->getRole() !== 'teacher') {
            header("Location: /");
            exit();
        }

        $teacher_id = $_SESSION['user_id'];

        // Проверяем, что student_id принадлежит данному преподавателю
        if (!isset($_GET['student_id'])) {
            header("Location: /teacher/dashboard");
            exit();
        }

        $student_id = $_GET['student_id'];

        // Проверяем, что ученик принадлежит преподавателю
        $student_info = $this->user->getStudentInfo($student_id, $teacher_id);
        if (!$student_info) {
            header("Location: /teacher/students");
            exit();
        }

        // Получаем прогресс по колодам
        $decks_progress = $this->deck->getStudentDeckStats($student_id, $teacher_id);
        
        // Получаем прогресс по тестам
        $test_progress = $this->test->getStudentTestStatistics($student_id);
        
        // Получаем недавнюю активность
        $recent_activity = $this->vocabulary->getStudentRecentActivity($student_id, 10);

        // Общая статистика
        $total_words = 0;
        $learned_words = 0;
        $words_to_review = 0;
        
        foreach ($decks_progress as $deck_stats) {
            $total_words += $deck_stats['total_words'];
            $learned_words += $deck_stats['learned_words'];
            $words_to_review += $deck_stats['words_to_review'];
        }

        $overall_progress = $total_words > 0 ? round(($learned_words / $total_words) * 100, 1) : 0;

        // Загружаем view
        include __DIR__ . '/../Views/teacher/student_progress.php';
    }
}
