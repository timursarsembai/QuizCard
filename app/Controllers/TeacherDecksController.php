<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Deck;

class TeacherDecksController 
{
    private $db;
    private $user;
    private $deck;
    
    public function __construct($db) 
    {
        $this->db = $db;
        $this->user = new User($db);
        $this->deck = new Deck($db);
    }
    
    public function index() 
    {
        // Включаем отображение ошибок для диагностики
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        session_start();

        try {
            if (!$this->db) {
                throw new Exception("Database connection failed");
            }

            if (!$this->user->isLoggedIn() || $this->user->getRole() !== 'teacher') {
                header("Location: /");
                exit();
            }

            $teacher_id = $_SESSION['user_id'];
            $success = null;
            $error = null;

            // Обработка создания новой колоды
            if ($_POST && isset($_POST['create_deck'])) {
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $color = $_POST['color'] ?: '#667eea';
                $daily_word_limit = intval($_POST['daily_word_limit']) ?: 20;
                
                if ($this->deck->createDeck($teacher_id, $name, $description, $color, $daily_word_limit)) {
                    $success = "deck_created_success";
                } else {
                    $error = "deck_create_error";
                }
            }

            // Обработка удаления колоды
            if ($_GET && isset($_GET['delete_deck'])) {
                $deck_id = $_GET['delete_deck'];
                if ($this->deck->deleteDeck($deck_id, $teacher_id)) {
                    $success = "deck_deleted_success";
                } else {
                    $error = "deck_delete_error";
                }
            }

            $decks = $this->deck->getDecksByTeacher($teacher_id);
            $students = $this->user->getStudentsByTeacher($teacher_id);

            // Загружаем view
            include __DIR__ . '/../Views/teacher/decks.php';

        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }
}
