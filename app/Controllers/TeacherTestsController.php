<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Deck;
use App\Models\Test;

class TeacherTestsController 
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

        if (!$this->user->isLoggedIn() || $this->user->getRole() !== 'teacher') {
            header("Location: /");
            exit();
        }

        $teacher_id = $_SESSION['user_id'];
        $success = null;
        $error = null;

        // Получаем все колоды преподавателя
        $decks = $this->deck->getDecksByTeacher($teacher_id);

        // Получаем все тесты преподавателя
        $all_tests = $this->test->getTestsByTeacher($teacher_id);
        $total_tests = count($all_tests);

        // Загружаем view
        include __DIR__ . '/../Views/teacher/tests.php';
    }
}