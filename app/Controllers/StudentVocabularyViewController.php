<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Vocabulary;
use App\Models\Deck;

class StudentVocabularyViewController 
{
    private $db;
    private $user;
    private $vocabulary;
    private $deck_class;
    
    public function __construct($db) 
    {
        $this->db = $db;
        $this->user = new User($db);
        $this->vocabulary = new Vocabulary($db);
        $this->deck_class = new Deck($db);
    }
    
    public function index() 
    {
        session_start();

        if (!$this->user->isLoggedIn() || $this->user->getRole() !== 'student') {
            header("Location: /login/student");
            exit();
        }

        $student_id = $_SESSION['user_id'];

        // Получаем параметры фильтрации и сортировки
        $selected_deck = isset($_GET['deck_id']) ? (int)$_GET['deck_id'] : 0;
        $sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'date';

        // Получаем все колоды студента для фильтра
        $student_decks = $this->deck_class->getDecksForStudent($student_id);

        // Получаем слова
        $words = $this->vocabulary->getVocabularyByStudent($student_id);

        // Фильтрация по колоде
        if ($selected_deck > 0) {
            $words = array_filter($words, function($word) use ($selected_deck) {
                return $word['deck_id'] == $selected_deck;
            });
        }

        // Сортировка
        switch ($sort_by) {
            case 'easy_first':
                usort($words, function($a, $b) {
                    $ease_a = $a['ease_factor'] ?: 2.5;
                    $ease_b = $b['ease_factor'] ?: 2.5;
                    return $ease_b <=> $ease_a;
                });
                break;
            case 'hard_first':
                usort($words, function($a, $b) {
                    $ease_a = $a['ease_factor'] ?: 2.5;
                    $ease_b = $b['ease_factor'] ?: 2.5;
                    return $ease_a <=> $ease_b;
                });
                break;
            case 'alphabetical':
                usort($words, function($a, $b) {
                    return strcasecmp($a['foreign_word'], $b['foreign_word']);
                });
                break;
            default: // date
                usort($words, function($a, $b) {
                    return strtotime($b['created_at']) <=> strtotime($a['created_at']);
                });
        }

        // Загружаем view
        include __DIR__ . '/../Views/student/vocabulary_view.php';
    }
}
