<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Deck;
use App\Models\Test;

class StudentTestsController 
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

        if (!$this->user->isLoggedIn() || $this->user->getRole() !== 'student') {
            header("Location: /login/student");
            exit();
        }

        $student_id = $_SESSION['user_id'];

        // Получаем колоды ученика
        $student_decks = $this->deck->getDecksForStudent($student_id);

        // Получаем тесты для каждой колоды
        $available_tests = [];
        foreach ($student_decks as $deck_item) {
            $deck_tests = $this->test->getTestsByDeck($deck_item['id']);
            if (!empty($deck_tests)) {
                for ($i = 0; $i < count($deck_tests); $i++) {
                    // Получаем статистику ученика по этому тесту
                    $deck_tests[$i]['student_stats'] = $this->test->getStudentTestStats($deck_tests[$i]['id'], $student_id);
                }
                $available_tests[$deck_item['id']] = [
                    'deck' => $deck_item,
                    'tests' => $deck_tests
                ];
            }
        }

        // Получаем последние результаты ученика
        $recent_attempts = $this->test->getStudentRecentAttempts($student_id, 5);

        // Загружаем view
        include __DIR__ . '/../Views/student/tests.php';
    }
}
