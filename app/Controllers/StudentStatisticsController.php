<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Deck;
use App\Models\Vocabulary;
use App\Models\Test;

class StudentStatisticsController 
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

        if (!$this->user->isLoggedIn() || $this->user->getRole() !== 'student') {
            header("Location: /login/student");
            exit();
        }

        $student_id = $_SESSION['user_id'];

        // Получаем статистику по тестам
        $test_statistics = $this->test->getStudentTestStatistics($student_id);

        // Получаем последние результаты тестов
        $query = "SELECT ta.*, t.name as test_name, d.name as deck_name, d.color as deck_color
                  FROM test_attempts ta 
                  JOIN tests t ON ta.test_id = t.id 
                  JOIN decks d ON t.deck_id = d.id 
                  WHERE ta.student_id = :student_id 
                  ORDER BY ta.completed_at DESC 
                  LIMIT 5";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        $recent_test_results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Получаем статистику по словарю
        $vocabulary_stats = $this->vocabulary->getStatistics($student_id);
        
        // Получаем статистику по колодам
        $deck_stats = $this->deck->getStudentDeckStatistics($student_id);

        // Загружаем view
        include __DIR__ . '/../Views/student/statistics.php';
    }
}
