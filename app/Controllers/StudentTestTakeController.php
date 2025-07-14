<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Deck;
use App\Models\Test;

class StudentTestTakeController 
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

        // Проверяем test_id
        if (!isset($_GET['test_id'])) {
            header("Location: /student/tests");
            exit();
        }

        $test_id = $_GET['test_id'];
        $current_test = $this->test->getTestById($test_id);

        if (!$current_test) {
            header("Location: /student/tests");
            exit();
        }

        // Проверяем, что ученик имеет доступ к колоде этого теста
        $student_decks = $this->deck->getDecksForStudent($student_id);
        $has_access = false;
        foreach ($student_decks as $student_deck) {
            if ($student_deck['id'] == $current_test['deck_id']) {
                $has_access = true;
                $current_deck = $student_deck;
                break;
            }
        }

        if (!$has_access) {
            header("Location: /student/tests");
            exit();
        }

        // Обработка отправки теста
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_test'])) {
            $answers = $_POST['answers'] ?? [];
            
            // Создаем попытку теста
            $attempt_id = $this->test->createTestAttempt($test_id, $student_id);
            
            if ($attempt_id) {
                // Сохраняем ответы
                $score = $this->test->saveTestAnswers($attempt_id, $answers);
                
                // Перенаправляем на результаты
                header("Location: /student/testresult?attempt_id=$attempt_id");
                exit();
            } else {
                $error = "Ошибка при сохранении теста";
            }
        }

        // Получаем вопросы теста
        $questions = $this->test->getTestQuestions($test_id);

        // Загружаем view
        include __DIR__ . '/../Views/student/test_take.php';
    }
}
