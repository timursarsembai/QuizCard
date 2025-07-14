<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Vocabulary;
use App\Models\Deck;

class TeacherVocabularyController 
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

        if (!$this->user->isLoggedIn() || $this->user->getRole() !== 'teacher') {
            header("Location: /");
            exit();
        }

        $teacher_id = $_SESSION['user_id'];
        $success = null;
        $error = null;

        // Проверяем, что deck_id принадлежит данному преподавателю
        if (!isset($_GET['deck_id'])) {
            header("Location: /teacher/decks");
            exit();
        }

        $deck_id = $_GET['deck_id'];
        $current_deck = $this->deck->getDeckById($deck_id, $teacher_id);

        if (!$current_deck) {
            header("Location: /teacher/decks");
            exit();
        }

        // Обработка редактирования колоды
        if ($_POST && isset($_POST['edit_deck'])) {
            $name = trim($_POST['deck_name']);
            $description = trim($_POST['deck_description']);
            $color = $_POST['deck_color'] ?: '#667eea';
            $daily_word_limit = intval($_POST['daily_word_limit']) ?: 20;
            
            if ($this->deck->updateDeck($deck_id, $teacher_id, $name, $description, $color, $daily_word_limit)) {
                $success = "Колода успешно обновлена!";
                $current_deck = $this->deck->getDeckById($deck_id, $teacher_id);
            } else {
                $error = "Ошибка при обновлении колоды";
            }
        }

        // Обработка назначения/удаления учеников для колоды
        if ($_POST && isset($_POST['update_students'])) {
            $selected_students = $_POST['students'] ?? [];
            
            // Получаем текущих назначенных учеников
            $current_students = $this->deck->getAssignedStudents($deck_id);
            $current_student_ids = array_column($current_students, 'id');
            
            // Удаляем учеников, которые больше не выбраны
            foreach ($current_student_ids as $student_id) {
                if (!in_array($student_id, $selected_students)) {
                    $this->deck->removeStudentFromDeck($deck_id, $student_id);
                }
            }
            
            // Добавляем новых учеников
            foreach ($selected_students as $student_id) {
                if (!in_array($student_id, $current_student_ids)) {
                    $this->deck->assignStudentToDeck($deck_id, $student_id);
                }
            }
            
            $success = "Список учеников успешно обновлен!";
        }

        // Обработка добавления нового слова (упрощенная версия)
        if ($_POST && isset($_POST['add_word'])) {
            $foreign_word = trim($_POST['foreign_word']);
            $translation = trim($_POST['translation']);
            
            if ($this->vocabulary->addWord($deck_id, $foreign_word, $translation)) {
                $success = "Слово успешно добавлено!";
            } else {
                $error = "Ошибка при добавлении слова";
            }
        }

        // Обработка удаления слова
        if ($_GET && isset($_GET['delete_word'])) {
            $word_id = $_GET['delete_word'];
            if ($this->vocabulary->deleteWord($word_id, $deck_id)) {
                $success = "Слово успешно удалено!";
            } else {
                $error = "Ошибка при удалении слова";
            }
        }

        // Получаем данные для отображения
        $words = $this->vocabulary->getWordsByDeck($deck_id);
        $assigned_students = $this->deck->getAssignedStudents($deck_id);
        $all_students = $this->user->getStudentsByTeacher($teacher_id);

        // Загружаем view
        include __DIR__ . '/../Views/teacher/vocabulary.php';
    }
}
