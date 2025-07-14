<?php

namespace App\Controllers;

use App\Models\SecurityLogger;

class ApiLogSecurityController 
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

        try {
            // Проверяем метод запроса
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }

            // Читаем JSON данные
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);

            if (!$data || !isset($data['type'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid data']);
                exit;
            }

            // Логируем событие безопасности
            $logData = [
                'user_id' => $_SESSION['user_id'] ?? null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'data' => $data['data'] ?? []
            ];

            SecurityLogger::logSecurityEvent($data['type'], $logData);

            echo json_encode([
                'success' => true,
                'message' => 'Event logged'
            ]);

        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Logging failed'
            ]);
        }
    }
}
