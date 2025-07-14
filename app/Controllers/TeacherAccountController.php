<?php

namespace App\Controllers;

use App\Models\User;

class TeacherAccountController 
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

        try {
            if (!$this->db) {
                throw new Exception("Database connection failed");
            }

            if (!$this->user->isLoggedIn() || $this->user->getRole() !== 'teacher') {
                header("Location: /");
                exit();
            }

            $teacher_id = $_SESSION['user_id'];
            $teacher_info = $this->user->getTeacherInfo($teacher_id);
            
            if (!$teacher_info) {
                throw new Exception("Информация о преподавателе не найдена");
            }

            $success_message = '';
            $error_message = '';

            // Обработка формы обновления данных
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $action = $_POST['action'] ?? '';
                
                if ($action === 'update_profile') {
                    $username = trim($_POST['username'] ?? '');
                    $first_name = trim($_POST['first_name'] ?? '');
                    $last_name = trim($_POST['last_name'] ?? '');
                    
                    // Валидация
                    if (empty($username) || empty($first_name) || empty($last_name)) {
                        $error_message = "Все поля обязательны для заполнения";
                    } elseif (strlen($username) < 3) {
                        $error_message = "Логин должен содержать минимум 3 символа";
                    } elseif (strlen($first_name) < 2 || strlen($last_name) < 2) {
                        $error_message = "Имя и фамилия должны содержать минимум 2 символа";
                    } else {
                        // Обновляем профиль
                        $result = $this->user->updateTeacher($teacher_id, $username, null, $first_name, $last_name);
                        if ($result) {
                            $success_message = "Профиль успешно обновлен!";
                            $teacher_info = $this->user->getTeacherInfo($teacher_id); // Обновляем данные
                        } else {
                            $error_message = "Ошибка при обновлении профиля";
                        }
                    }
                }
                
                if ($action === 'change_password') {
                    $current_password = $_POST['current_password'] ?? '';
                    $new_password = $_POST['new_password'] ?? '';
                    $confirm_password = $_POST['confirm_password'] ?? '';
                    
                    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                        $error_message = "Все поля пароля обязательны для заполнения";
                    } elseif ($new_password !== $confirm_password) {
                        $error_message = "Новые пароли не совпадают";
                    } elseif (strlen($new_password) < 6) {
                        $error_message = "Новый пароль должен содержать минимум 6 символов";
                    } else {
                        // Проверяем текущий пароль и обновляем
                        if ($this->user->changePassword($teacher_id, $current_password, $new_password)) {
                            $success_message = "Пароль успешно изменен!";
                        } else {
                            $error_message = "Неверный текущий пароль";
                        }
                    }
                }
            }

            // Загружаем view
            include __DIR__ . '/../Views/teacher/account.php';

        } catch (Exception $e) {
            die("Error: " . $e->getMessage());
        }
    }
}
