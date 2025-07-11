<?php
/**
 * Скрипт для автоматической очистки старых данных безопасности
 * Запускается как cron job или вручную
 */

require_once '../classes/SecurityLogger.php';
require_once '../classes/RateLimit.php';
require_once '../classes/EnvLoader.php';

echo "Начинаем очистку данных безопасности...\n";

try {
    // Очистка старых логов (старше 30 дней)
    echo "Очистка старых логов безопасности...\n";
    SecurityLogger::cleanupOldLogs(30 * 24 * 3600); // 30 дней
    
    // Очистка старых данных rate limiting (старше 1 дня)
    echo "Очистка старых данных rate limiting...\n";
    RateLimit::cleanup(24 * 3600); // 24 часа
    
    // Очистка старых токенов CSRF (старше 1 часа)
    echo "Очистка устаревших сессий...\n";
    $sessionPath = session_save_path() ?: sys_get_temp_dir();
    $files = glob($sessionPath . '/sess_*');
    $oneHourAgo = time() - 3600;
    
    $cleanedSessions = 0;
    foreach ($files as $file) {
        if (filemtime($file) < $oneHourAgo) {
            unlink($file);
            $cleanedSessions++;
        }
    }
    echo "Очищено $cleanedSessions старых сессий\n";
    
    // Очистка временных файлов загрузки (старше 1 дня)
    echo "Очистка временных файлов...\n";
    $tempDirs = [
        '../uploads/temp',
        '../uploads/audio/temp'
    ];
    
    $cleanedFiles = 0;
    foreach ($tempDirs as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '/*');
            $oneDayAgo = time() - (24 * 3600);
            
            foreach ($files as $file) {
                if (is_file($file) && filemtime($file) < $oneDayAgo) {
                    unlink($file);
                    $cleanedFiles++;
                }
            }
        }
    }
    echo "Очищено $cleanedFiles временных файлов\n";
    
    // Проверка состояния системы безопасности
    echo "Проверка состояния системы...\n";
    
    // Проверка .env файла
    if (!file_exists('../.env')) {
        echo "ПРЕДУПРЕЖДЕНИЕ: Файл .env не найден!\n";
    }
    
    // Проверка директории логов
    $logDir = '../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
        echo "Создана директория логов: $logDir\n";
    }
    
    if (!is_writable($logDir)) {
        echo "ОШИБКА: Директория логов недоступна для записи: $logDir\n";
    }
    
    // Проверка размера логов
    $logFile = $logDir . '/security.log';
    if (file_exists($logFile)) {
        $logSize = filesize($logFile);
        if ($logSize > 10 * 1024 * 1024) { // 10MB
            echo "ПРЕДУПРЕЖДЕНИЕ: Лог файл слишком большой (" . round($logSize/1024/1024, 2) . " MB)\n";
        }
    }
    
    // Логируем успешную очистку
    SecurityLogger::log('INFO', 'CLEANUP_COMPLETED', [
        'cleaned_sessions' => $cleanedSessions,
        'cleaned_files' => $cleanedFiles,
        'script_execution' => 'automated'
    ]);
    
    echo "Очистка завершена успешно!\n";
    
} catch (Exception $e) {
    echo "ОШИБКА при очистке: " . $e->getMessage() . "\n";
    
    // Логируем ошибку
    SecurityLogger::logSecurityError('Cleanup script failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    exit(1);
}

echo "Время выполнения: " . date('Y-m-d H:i:s') . "\n";
?>
