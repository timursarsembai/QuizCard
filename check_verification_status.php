<?php
/**
 * AJAX endpoint для проверки статуса верификации email
 */

session_start();
header('Content-Type: application/json');

require_once 'config/database.php';
require_once 'classes/User.php';

// Проверяем, что пользователь авторизован
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    echo json_encode(['error' => 'unauthorized']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if (!$database->isConnected()) {
    echo json_encode(['error' => 'db_connection']);
    exit();
}

try {
    $user = new User($db);
    $is_verified = $user->isEmailVerified($_SESSION['user_id']);
    
    // Обновляем сессию если статус изменился
    if ($is_verified && (!isset($_SESSION['email_verified']) || !$_SESSION['email_verified'])) {
        $_SESSION['email_verified'] = 1;
    }
    
    echo json_encode([
        'verified' => $is_verified,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => 'system_error']);
}
?>
