<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Deck;
use App\Models\Test;

class TeacherTestResultsController 
{
    private $db;
    private $user;
    private $deck;
    private $test;
    
    public function __construct($db) 
    {
        $this->db = $db;
        $this->user = new User($db);
        $this->deck = new Deck($db);
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

        // Проверяем test_id
        if (!isset($_GET['test_id'])) {
            header("Location: /teacher/tests");
            exit();
        }

        $test_id = $_GET['test_id'];
        $current_test = $this->test->getTestById($test_id);

        if (!$current_test) {
            header("Location: /teacher/tests");
            exit();
        }

        // Проверяем, что тест принадлежит преподавателю
        $deck_info = $this->deck->getDeckById($current_test['deck_id'], $teacher_id);
        if (!$deck_info) {
            header("Location: /teacher/tests");
            exit();
        }

        // Получаем результаты теста
        $test_results = $this->test->getTestResults($test_id);
        
        // Статистика
        $total_attempts = count($test_results);
        $unique_students = count(array_unique(array_column($test_results, 'student_id')));
        $average_score = $total_attempts > 0 ? array_sum(array_column($test_results, 'score')) / $total_attempts : 0;

        // Лучшие результаты (по одному лучшему на ученика)
        $best_results = [];
        foreach ($test_results as $result) {
            $student_id = $result['student_id'];
            if (!isset($best_results[$student_id]) || $result['score'] > $best_results[$student_id]['score']) {
                $best_results[$student_id] = $result;
            }
        }
        $best_results = array_values($best_results);
        usort($best_results, function($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Загружаем view
        include __DIR__ . '/../Views/teacher/test_results.php';
    }
}
