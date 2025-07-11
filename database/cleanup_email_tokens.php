<?php
/**
 * Скрипт для очистки истекших токенов верификации email
 * Рекомендуется запускать через cron раз в час или день
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/email_config.php';

// Логирование результатов
function logCleanup($message) {
    $logFile = __DIR__ . '/logs/email_cleanup.log';
    $logDir = dirname($logFile);
    
    if (!file_exists($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    
    @file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$database->isConnected()) {
        throw new Exception('Database connection failed: ' . $database->getError());
    }
    
    // Очищаем истекшие токены
    $result = EmailConfig::cleanupExpiredTokens($db);
    
    if ($result) {
        // Получаем статистику очистки
        $query = "SELECT COUNT(*) as expired_tokens FROM users 
                 WHERE verification_token IS NULL 
                 AND verification_token_expires IS NULL 
                 AND last_verification_sent IS NOT NULL 
                 AND last_verification_sent < DATE_SUB(NOW(), INTERVAL " . EmailConfig::$token_expiry_hours . " HOUR)";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Статистика логов
        $log_query = "SELECT 
                     COUNT(*) as total_logs,
                     SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_logs,
                     SUM(CASE WHEN status = 'verified' THEN 1 ELSE 0 END) as verified_logs,
                     SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_logs
                     FROM email_verification_logs 
                     WHERE sent_at > DATE_SUB(NOW(), INTERVAL 7 DAY)";
        
        $stmt = $db->prepare($log_query);
        $stmt->execute();
        $log_stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $message = "Cleanup completed successfully. ";
        $message .= "Processed tokens in last 7 days - ";
        $message .= "Total: {$log_stats['total_logs']}, ";
        $message .= "Verified: {$log_stats['verified_logs']}, ";
        $message .= "Expired: {$log_stats['expired_logs']}, ";
        $message .= "Failed: {$log_stats['failed_logs']}";
        
        logCleanup($message);
        
        // Если запущено из браузера, показываем результаты
        if (isset($_SERVER['HTTP_HOST'])) {
            echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Email Cleanup Results</title></head><body>";
            echo "<h1>🧹 Email Verification Cleanup Results</h1>";
            echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "<h3>✅ Cleanup completed successfully!</h3>";
            echo "<p><strong>Statistics for last 7 days:</strong></p>";
            echo "<ul>";
            echo "<li>Total verification attempts: {$log_stats['total_logs']}</li>";
            echo "<li>Successfully verified: {$log_stats['verified_logs']}</li>";
            echo "<li>Expired tokens: {$log_stats['expired_logs']}</li>";
            echo "<li>Failed attempts: {$log_stats['failed_logs']}</li>";
            echo "</ul>";
            echo "</div>";
            
            if ($log_stats['total_logs'] > 0) {
                $success_rate = round(($log_stats['verified_logs'] / $log_stats['total_logs']) * 100, 2);
                echo "<p><strong>Success rate:</strong> {$success_rate}%</p>";
                
                if ($success_rate < 50) {
                    echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
                    echo "<p>⚠️ <strong>Warning:</strong> Low verification success rate. Check email configuration.</p>";
                    echo "</div>";
                }
            }
            
            echo "<p><a href='../setup.php'>🔧 Database Setup</a></p>";
            echo "<p><a href='teacher/dashboard.php'>🏠 Teacher Dashboard</a></p>";
            echo "</body></html>";
        }
        
    } else {
        throw new Exception('Cleanup operation failed');
    }
    
} catch (Exception $e) {
    $error_message = "Cleanup failed: " . $e->getMessage();
    logCleanup($error_message);
    
    if (isset($_SERVER['HTTP_HOST'])) {
        echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Cleanup Error</title></head><body>";
        echo "<h1>❌ Email Cleanup Error</h1>";
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
        echo "<p><strong>Error:</strong> " . htmlspecialchars($error_message) . "</p>";
        echo "</div>";
        echo "</body></html>";
    } else {
        echo "Error: $error_message\n";
        exit(1);
    }
}

// Если запущено из командной строки
if (!isset($_SERVER['HTTP_HOST'])) {
    echo "Email verification cleanup completed successfully.\n";
}
?>
