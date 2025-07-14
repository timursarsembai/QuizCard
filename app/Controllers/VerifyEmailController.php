<?php

namespace App\Controllers;

use App\Models\User;

class VerifyEmailController 
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
        // Принудительно устанавливаем HTML заголовки
        header('Content-Type: text/html; charset=UTF-8');

        session_start();

        // Обрабатываем смену языка из URL
        if (isset($_GET['lang']) && in_array($_GET['lang'], ['kk', 'ru', 'en'])) {
            $_SESSION['language'] = $_GET['lang'];
        }

        // Получаем токен из URL
        $token = $_GET['token'] ?? '';
        $message = '';
        $message_type = 'error';
        $show_login_link = false;
        $show_resend_link = false;
        $user_data = null;

        if (!empty($token)) {
            $result = $this->user->verifyEmail($token);
            
            if ($result['success']) {
                $message = 'Email успешно подтвержден!';
                $message_type = 'success';
                $show_login_link = true;
                
                // Получаем информацию о пользователе
                $query = "SELECT first_name, last_name, email FROM users WHERE id = :user_id";
                $stmt = $this->db->prepare($query);
                $stmt->bindParam(':user_id', $result['user_id']);
                $stmt->execute();
                $user_data = $stmt->fetch(\PDO::FETCH_ASSOC);
            } else {
                switch ($result['reason'] ?? '') {
                    case 'token_expired':
                        $message = 'Токен подтверждения истек';
                        $show_resend_link = true;
                        break;
                    case 'already_verified':
                        $message = 'Email уже подтвержден';
                        $show_login_link = true;
                        break;
                    default:
                        $message = 'Неверный токен подтверждения';
                }
            }
        } else {
            $message = 'Токен не указан';
        }

        // Загружаем view
        include __DIR__ . '/../Views/verify_email.php';
    }
}
