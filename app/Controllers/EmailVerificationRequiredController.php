<?php

namespace App\Controllers;

use App\Models\User;

class EmailVerificationRequiredController 
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

        // Обрабатываем смену языка из URL
        if (isset($_GET['lang']) && in_array($_GET['lang'], ['kk', 'ru', 'en'])) {
            $_SESSION['language'] = $_GET['lang'];
        }

        // Проверяем, что пользователь авторизован
        if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
            header("Location: /login");
            exit();
        }

        // Проверяем, не подтвержден ли уже email
        if (isset($_SESSION['email_verified']) && $_SESSION['email_verified']) {
            header("Location: /teacher/dashboard");
            exit();
        }

        $message = '';
        $message_type = 'info';

        // Обработка повторной отправки письма
        if ($_POST && isset($_POST['resend_email'])) {
            $result = $this->user->resendVerificationEmail($_SESSION['user_id']);
            
            if ($result['success']) {
                $message = 'Письмо с подтверждением отправлено повторно';
                $message_type = 'success';
            } else {
                $message = 'Ошибка при отправке письма: ' . $result['error'];
                $message_type = 'error';
            }
        }

        // Загружаем view
        include __DIR__ . '/../Views/email_verification_required.php';
    }
}
