<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Vocabulary;
use App\Models\Deck;

class StudentFlashcardsController 
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
            header("Location: /login/student");
            exit();
        }

        $student_id = $_SESSION['user_id'];
        $deck_id = isset($_GET['deck_id']) ? (int)$_GET['deck_id'] : null;
        $review_mode = isset($_GET['review_mode']) ? $_GET['review_mode'] : 'normal';

        // Получаем информацию о колоде, если выбрана конкретная
        $deck_info = null;
        if ($deck_id) {
            $student_decks = $this->deck->getDecksForStudent($student_id);
            foreach ($student_decks as $deck) {
                if ($deck['id'] == $deck_id) {
                    $deck_info = $deck;
                    break;
                }
            }
        }

        // Обработка AJAX запросов для обновления прогресса
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
            header('Content-Type: application/json');
            
            if ($_POST['action'] === 'update_progress') {
                $vocabulary_id = $_POST['vocabulary_id'];
                $difficulty = $_POST['difficulty'];
                
                if ($this->vocabulary->updateProgress($student_id, $vocabulary_id, $difficulty)) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false]);
                }
                exit();
            }
        }

        // Получаем слова для изучения в зависимости от режима
        if ($review_mode === 'review') {
            $words = $this->vocabulary->getWordsForReview($student_id, $deck_id);
            $mode_title = "Повторение слов";
        } else {
            $words = $this->vocabulary->getWordsForLearning($student_id, $deck_id);
            $mode_title = "Изучение новых слов";
        }

        // Получаем все колоды для выбора
        $student_decks = $this->deck->getDecksForStudent($student_id);

        // Загружаем view
        include __DIR__ . '/../Views/student/flashcards.php';
    }
}
