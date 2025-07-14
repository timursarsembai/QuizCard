<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Deck;

class TeacherDeckStudentsController 
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
    
    public function manage() 
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

        // Обработка назначения колоды ученику
        if ($_POST && isset($_POST['assign_student'])) {
            $student_id = $_POST['student_id'];
            if ($this->deck->assignDeckToStudent($deck_id, $student_id, $teacher_id)) {
                $success = "Ученик успешно добавлен к колоде";
            } else {
                $error = "Ошибка при добавлении ученика";
            }
        }

        // Обработка отмены назначения колоды ученику
        if ($_GET && isset($_GET['unassign'])) {
            $student_id = $_GET['unassign'];
            if ($this->deck->unassignDeckFromStudent($deck_id, $student_id, $teacher_id)) {
                $success = "Ученик успешно удален из колоды";
            } else {
                $error = "Ошибка при удалении ученика";
            }
        }

        $assigned_students = $this->deck->getStudentsForDeck($deck_id, $teacher_id);
        $available_students = $this->deck->getAvailableStudents($deck_id, $teacher_id);

        // Загружаем view
        include __DIR__ . '/../Views/teacher/deck_students.php';
    }
}
