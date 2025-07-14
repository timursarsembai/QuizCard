<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Deck;
use App\Models\Test;
use App\Models\Vocabulary;

class TeacherTestManagerController 
{
    private $db;
    private $user;
    private $deck;
    private $test;
    private $vocabulary;
    
    public function __construct($db) 
    {
        $this->db = $db;
        $this->user = new User($db);
        $this->deck = new Deck($db);
        $this->test = new Test($db);
        $this->vocabulary = new Vocabulary($db);
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

        // Обработка создания нового теста
        if ($_POST && isset($_POST['create_test'])) {
            $test_name = trim($_POST['test_name']);
            $questions_count = intval($_POST['questions_count']);
            $time_limit = intval($_POST['time_limit']) ?: null;
            
            if ($test_name && $questions_count > 0) {
                $test_id = $this->test->createTest($deck_id, $test_name, $questions_count, $time_limit);
                if ($test_id) {
                    $success = "Тест успешно создан!";
                    // Перенаправляем на редактирование теста
                    header("Location: /teacher/testedit?test_id=$test_id");
                    exit();
                } else {
                    $error = "Ошибка при создании теста";
                }
            } else {
                $error = "Заполните все обязательные поля";
            }
        }

        // Обработка удаления теста
        if ($_GET && isset($_GET['delete_test'])) {
            $test_id = $_GET['delete_test'];
            if ($this->test->deleteTest($test_id, $teacher_id)) {
                $success = "Тест успешно удален!";
            } else {
                $error = "Ошибка при удалении теста";
            }
        }

        // Получаем все тесты для данной колоды
        $tests = $this->test->getTestsByDeck($deck_id);

        // Загружаем view
        include __DIR__ . '/../Views/teacher/test_manager.php';
    }
}
