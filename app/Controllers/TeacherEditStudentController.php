<?php

namespace App\Controllers;

use App\Models\User;

class TeacherEditStudentController 
{
    private $db;
    private $user;
    
    public function __construct($db) 
    {
        $this->db = $db;
        $this->user = new User($db);
    }
    
    public function index() 
    {
        session_start();

        if (!$this->user->isLoggedIn() || $this->user->getRole() !== 'teacher') {
            header("Location: /");
            exit();
        }

        $teacher_id = $_SESSION['user_id'];
        $error = '';
        $success = '';
        $student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        // Получаем данные ученика
        $student_info = $this->user->getStudentInfo($student_id, $teacher_id);
        if (!$student_info) {
            header("Location: /teacher/dashboard");
            exit();
        }

        // Обработка формы обновления
        if ($_POST && isset($_POST['update_student'])) {
            $new_username = trim($_POST['username']);
            $new_first_name = trim($_POST['first_name']);
            $new_last_name = trim($_POST['last_name']);
            $new_password = $_POST['password'];
            
            if ($new_username && $new_first_name && $new_last_name) {
                $update_result = $this->user->updateStudent($student_id, $teacher_id, [
                    'username' => $new_username,
                    'first_name' => $new_first_name,
                    'last_name' => $new_last_name,
                    'password' => $new_password
                ]);
                
                if ($update_result) {
                    $success = "Данные ученика успешно обновлены";
                    $student_info = $this->user->getStudentInfo($student_id, $teacher_id);
                } else {
                    $error = "Ошибка при обновлении данных";
                }
            } else {
                $error = "Заполните все обязательные поля";
            }
        }

        // Загружаем view
        include __DIR__ . '/../Views/teacher/edit_student.php';
    }
}
