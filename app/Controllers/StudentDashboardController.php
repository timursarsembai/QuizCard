<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Vocabulary;
use App\Models\Deck;

class StudentDashboardController 
{
    private $db;
    private $user;
    private $vocabulary;
    private $deck;
    
    public function __construct($db) 
    {
        $this->db = $db;
        $this->user = new User($db);
        $this->vocabulary = new Vocabulary($db);
        $this->deck = new Deck($db);
    }
    
    public function index() 
    {
        session_start();

        if (!$this->user->isLoggedIn() || $this->user->getRole() !== 'student') {
            header("Location: /student/login");
            exit();
        }

        $student_id = $_SESSION['user_id'];
        $statistics = $this->vocabulary->getStatistics($student_id);
        $words_for_review = $this->vocabulary->getWordsForReview($student_id);
        $student_decks = $this->deck->getDecksForStudent($student_id);
        $daily_limits = $this->vocabulary->getDailyLimitStatistics($student_id);

        // Получаем количество слов в процессе изучения
        $query = "SELECT COUNT(*) as studying_count FROM learning_progress WHERE student_id = :student_id AND total_attempts > 0 AND repetition_count < 3";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        $studying_result = $stmt->fetch(\PDO::FETCH_ASSOC);
        $studying_words = $studying_result['studying_count'];

        // Загружаем view
        include __DIR__ . '/../Views/student/dashboard.php';
    }
}
