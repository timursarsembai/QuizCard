<?php

namespace App\Controllers;

use App\Models\User;

class LoginController 
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
        
        // Если уже авторизован, редирект
        if ($this->user->isLoggedIn()) {
            $role = $this->user->getRole();
            if ($role === 'teacher') {
                header("Location: /teacher/dashboard");
            } else {
                header("Location: /student/dashboard");
            }
            exit();
        }

        $error = null;

        if ($_POST && isset($_POST['login'])) {
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            
            if ($this->user->login($username, $password)) {
                $role = $this->user->getRole();
                if ($role === 'teacher') {
                    header("Location: /teacher/dashboard");
                } else {
                    header("Location: /student/dashboard");
                }
                exit();
            } else {
                $error = "Неверные данные для входа";
            }
        }

        // Загружаем view
        include __DIR__ . '/../Views/login.php';
    }
    
    public function student() 
    {
        session_start();
        
        // Если уже авторизован как студент
        if ($this->user->isLoggedIn() && $this->user->getRole() === 'student') {
            header("Location: /student/dashboard");
            exit();
        }

        $error = null;

        if ($_POST && isset($_POST['login'])) {
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            
            if ($this->user->login($username, $password)) {
                if ($this->user->getRole() === 'student') {
                    header("Location: /student/dashboard");
                    exit();
                } else {
                    $error = "Эта страница только для учеников";
                }
            } else {
                $error = "Неверные данные для входа";
            }
        }

        // Загружаем view для ученика
        include __DIR__ . '/../Views/student_login.php';
    }
}
