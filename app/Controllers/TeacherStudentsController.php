<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Vocabulary;
use App\Models\Deck;
use App\Models\Test;

class TeacherStudentsController 
{
    private $db;
    private $user;
    private $vocabulary;
    private $deck;
    private $test;
    
    public function __construct($db) 
    {
        $this->db = $db;
        $this->user = new User($db);
        $this->vocabulary = new Vocabulary($db);
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
        $students = $this->user->getStudentsByTeacher($teacher_id);
        $success = null;
        $error = null;

        // Обработка добавления нового ученика
        if ($_POST && isset($_POST['add_student'])) {
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            $first_name = trim($_POST['first_name']);
            $last_name = trim($_POST['last_name']);
            
            if ($this->user->createStudent($username, $password, $first_name, $last_name, $teacher_id)) {
                $success = "student_added_success";
                $students = $this->user->getStudentsByTeacher($teacher_id); // Обновляем список
            } else {
                $error = "student_add_error";
            }
        }

        // Обработка удаления ученика
        if ($_GET && isset($_GET['delete_student'])) {
            $student_id = $_GET['delete_student'];
            if ($this->user->deleteStudent($student_id, $teacher_id)) {
                $success = "student_deleted_success";
                $students = $this->user->getStudentsByTeacher($teacher_id);
            } else {
                $error = "student_delete_error";
            }
        }

        // Обработка сброса прогресса ученика
        if ($_POST && isset($_POST['reset_progress'])) {
            $student_id = $_POST['student_id'];
            
            // Сбрасываем прогресс по словам
            $vocabulary_reset = $this->vocabulary->resetStudentProgress($student_id, $teacher_id);
            
            // Сбрасываем прогресс по тестам
            $tests_reset = $this->test->resetStudentTestProgress($student_id, $teacher_id);
            
            if ($vocabulary_reset && $tests_reset) {
                $success = "student_progress_reset_success";
            } else {
                $error = "progress_reset_error";
            }
        }

        $sortable_fields = [
            'last_name' => 'sort_surname',
            'avg_deck_progress' => 'sort_deck_progress',
            'avg_test_score' => 'sort_test_progress',
            'learned_words' => 'sort_learned_words',
            'words_to_review' => 'sort_words_to_review',
            'deck_count' => 'sort_deck_count'
        ];

        $sort_by = $_GET['sort_by'] ?? 'last_name';
        $sort_order = $_GET['sort_order'] ?? 'asc';

        // Сбор данных для сортировки
        $students_with_stats = [];
        foreach ($students as $student) {
            $student_decks = $this->deck->getStudentDeckStats($student['id'], $teacher_id);
            $total_words = 0;
            $words_to_review = 0;
            $learned_words = 0;
            foreach ($student_decks as $deck_stat) {
                $total_words += $deck_stat['total_words'];
                $words_to_review += $deck_stat['words_to_review'];
                $learned_words += $deck_stat['learned_words'];
            }
            
            $student['deck_count'] = count($student_decks);
            $student['total_words'] = $total_words;
            $student['words_to_review'] = $words_to_review;
            $student['learned_words'] = $learned_words;
            $student['avg_deck_progress'] = $this->vocabulary->getStudentAverageDeckProgress($student['id']);
            $student['avg_test_score'] = $this->test->getStudentAverageTestScore($student['id']);
            $students_with_stats[] = $student;
        }

        // Сортировка
        usort($students_with_stats, function($a, $b) use ($sort_by, $sort_order) {
            $val_a = $a[$sort_by];
            $val_b = $b[$sort_by];

            if ($val_a == $val_b) {
                return 0;
            }

            if ($sort_order === 'asc') {
                return $val_a < $val_b ? -1 : 1;
            } else {
                return $val_a > $val_b ? -1 : 1;
            }
        });

        // Загружаем view
        include __DIR__ . '/../Views/teacher/students.php';
    }
}
