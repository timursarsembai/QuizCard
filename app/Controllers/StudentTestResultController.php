<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Test;

class StudentTestResultController 
{
    private $db;
    private $user;
    private $test;
    
    public function __construct($db) 
    {
        $this->db = $db;
        $this->user = new User($db);
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

        // Проверяем attempt_id
        if (!isset($_GET['attempt_id'])) {
            header("Location: /student/tests");
            exit();
        }

        $attempt_id = $_GET['attempt_id'];

        // Получаем информацию о попытке
        $query = "SELECT ta.*, t.name as test_name, t.time_limit, d.name as deck_name, d.color as deck_color
                  FROM test_attempts ta
                  INNER JOIN tests t ON ta.test_id = t.id
                  INNER JOIN decks d ON t.deck_id = d.id
                  WHERE ta.id = :attempt_id AND ta.student_id = :student_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':attempt_id', $attempt_id);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();

        $attempt = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$attempt) {
            header("Location: /student/tests");
            exit();
        }

        // Получаем детали ответов
        $query = "SELECT ta.*, tq.question, tq.option_a, tq.option_b, tq.option_c, tq.option_d, tq.correct_answer
                  FROM test_answers ta
                  INNER JOIN test_questions tq ON ta.question_id = tq.id
                  WHERE ta.attempt_id = :attempt_id
                  ORDER BY tq.order_number";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':attempt_id', $attempt_id);
        $stmt->execute();

        $answers = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Считаем статистику
        $total_questions = count($answers);
        $correct_answers = 0;
        foreach ($answers as $answer) {
            if ($answer['student_answer'] === $answer['correct_answer']) {
                $correct_answers++;
            }
        }

        $score_percentage = $total_questions > 0 ? ($correct_answers / $total_questions) * 100 : 0;

        // Загружаем view
        include __DIR__ . '/../Views/student/test_result.php';
    }
}
