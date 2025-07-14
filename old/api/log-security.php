<?php
/**
 * API endpoint для логирования событий безопасности с клиента
 */
session_start();

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

require_once '../classes/SecurityLogger.php';
require_once '../classes/Sanitizer.php';

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

    // Санитизируем данные
    $type = Sanitizer::string($data['type'], 50);
    $details = isset($data['details']) ? Sanitizer::array($data['details']) : [];
    
    // Ограничиваем типы событий
    $allowedTypes = [
        'SUSPICIOUS_ACTIVITY',
        'VALIDATION_ERROR', 
        'CLIENT_ERROR',
        'FORM_MANIPULATION',
        'XSS_ATTEMPT'
    ];

    if (!in_array($type, $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid event type']);
        exit;
    }

    // Логируем событие
    SecurityLogger::logSuspiciousActivity('Client-side: ' . $type, $details);
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    
    error_log('Security logging API error: ' . $e->getMessage());
}
?>
