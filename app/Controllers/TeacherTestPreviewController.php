<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Deck;
use App\Models\Test;

class TeacherTestPreviewController 
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
    
    public function preview() 
    {
        session_start();

        if (!$this->user->isLoggedIn() || $this->user->getRole() !== 'teacher') {
            header("Location: /");
            exit();
        }

        $teacher_id = $_SESSION['user_id'];

        // Проверяем test_id
        if (!isset($_GET['test_id'])) {
            header("Location: /teacher/decks");
            exit();
        }

        $test_id = $_GET['test_id'];
        $current_test = $this->test->getTestById($test_id);

        if (!$current_test) {
            header("Location: /teacher/decks");
            exit();
        }

        // Проверяем, что тест принадлежит преподавателю
        $current_deck = $this->deck->getDeckById($current_test['deck_id'], $teacher_id);
        if (!$current_deck) {
            header("Location: /teacher/decks");
            exit();
        }

        // Получаем вопросы теста
        $questions = $this->test->getTestQuestions($test_id);

        // Загружаем view
        include __DIR__ . '/../Views/teacher/test_preview.php';
    }
}
