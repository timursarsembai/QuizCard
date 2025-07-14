<?php

namespace App\Controllers;

class ApiCsrfTokenController 
{
    private $db;
    
    public function __construct($db) 
    {
        $this->db = $db;
    }
    
    public function index() 
    {
        session_start();

        header('Content-Type: application/json');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        try {
            // Проверяем метод запроса
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }

            // Генерируем новый токен (упрощенная версия)
            $newToken = bin2hex(random_bytes(32));
            $_SESSION['csrf_token'] = $newToken;
            
            echo json_encode([
                'success' => true,
                'token' => $newToken,
                'expires_in' => 3600 // 1 час
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Token generation failed'
            ]);
        }
    }
}
