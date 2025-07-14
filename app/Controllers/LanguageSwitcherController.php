<?php
namespace App\Controllers;

class LanguageSwitcherController {
    public function setLanguage() {
        session_start();
        
        $language = $_POST['language'] ?? 'ru';
        
        // Проверка корректности языка
        if (!in_array($language, ['kk', 'ru', 'en'])) {
            $language = 'ru';
        }
        
        $_SESSION['language'] = $language;
        
        // Ответ для AJAX запроса
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'language' => $language]);
            exit;
        }
        
        // Редирект для обычных запросов
        $redirect = $_POST['redirect'] ?? $_SERVER['HTTP_REFERER'] ?? '/';
        header('Location: ' . $redirect);
        exit;
    }
}
