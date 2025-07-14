<?php
/**
 * API endpoint для обновления CSRF токенов
 */
session_start();

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once '../classes/CSRFProtection.php';
require_once '../classes/SecurityLogger.php';

try {
    // Проверяем метод запроса
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit;
    }

    // Генерируем новый токен
    $newToken = CSRFProtection::generateToken();
    
    if ($newToken) {
        echo json_encode([
            'success' => true,
            'token' => $newToken,
            'expires_in' => 3600 // 1 час
        ]);
        
        SecurityLogger::log('INFO', 'CSRF_TOKEN_REFRESHED', [
            'session_id' => session_id()
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to generate token']);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error']);
    
    SecurityLogger::logSecurityError('CSRF token refresh failed', [
        'exception' => $e->getMessage()
    ]);
}
?>
