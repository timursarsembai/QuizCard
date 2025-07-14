<?php
namespace App\Models;

/**
 * Система логирования событий безопасности
 */
class SecurityLogger {
    private static $logDir = null;
    private static $maxLogSize = 10485760; // 10MB
    private static $maxLogFiles = 10;

    /**
     * Инициализация
     */
    private static function init() {
        if (self::$logDir === null) {
            self::$logDir = dirname(__DIR__, 2) . '/logs';
            
            if (!is_dir(self::$logDir)) {
                mkdir(self::$logDir, 0755, true);
            }
        }
    }

    /**
     * Базовое логирование
     */
    private static function log($level, $event, $data = []) {
        self::init();

        require_once dirname(__DIR__) . '/Models/EnvLoader.php';
        \EnvLoader::load();
        
        if (!\EnvLoader::get('SECURITY_LOGGING', true)) {
            return;
        }

        $logEntry = [
            'timestamp' => date('c'),
            'level' => $level,
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'session_id' => session_id() ?: 'no_session',
            'user_id' => $_SESSION['user_id'] ?? null,
            'data' => $data
        ];

        $logMessage = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . PHP_EOL;
        
        $logFile = self::$logDir . '/security.log';
        self::writeToLogFile($logFile, $logMessage);
    }

    /**
     * Запись в лог файл с ротацией
     */
    private static function writeToLogFile($logFile, $message) {
        // Проверяем размер файла и ротируем при необходимости
        if (file_exists($logFile) && filesize($logFile) > self::$maxLogSize) {
            self::rotateLogFile($logFile);
        }

        error_log($message, 3, $logFile);
    }

    /**
     * Ротация лог файлов
     */
    private static function rotateLogFile($logFile) {
        $dir = dirname($logFile);
        $basename = basename($logFile, '.log');
        
        // Сдвигаем существующие файлы
        for ($i = self::$maxLogFiles - 1; $i >= 1; $i--) {
            $oldFile = $dir . '/' . $basename . '.' . $i . '.log';
            $newFile = $dir . '/' . $basename . '.' . ($i + 1) . '.log';
            
            if (file_exists($oldFile)) {
                if ($i == self::$maxLogFiles - 1) {
                    unlink($oldFile); // Удаляем самый старый
                } else {
                    rename($oldFile, $newFile);
                }
            }
        }
        
        // Переименовываем текущий файл
        if (file_exists($logFile)) {
            rename($logFile, $dir . '/' . $basename . '.1.log');
        }
    }

    /**
     * Логирование попыток входа
     */
    public static function logLogin($username, $success, $details = []) {
        $event = $success ? 'LOGIN_SUCCESS' : 'LOGIN_FAILED';
        $data = array_merge([
            'username' => $username,
            'success' => $success
        ], $details);

        self::log($success ? 'INFO' : 'WARNING', $event, $data);
    }

    /**
     * Логирование выхода из системы
     */
    public static function logLogout($username) {
        self::log('INFO', 'LOGOUT', ['username' => $username]);
    }

    /**
     * Логирование подозрительной активности
     */
    public static function logSuspiciousActivity($description, $details = []) {
        self::log('WARNING', 'SUSPICIOUS_ACTIVITY', array_merge([
            'description' => $description
        ], $details));
    }

    /**
     * Логирование ошибок безопасности
     */
    public static function logSecurityError($error, $details = []) {
        self::log('ERROR', 'SECURITY_ERROR', array_merge([
            'error' => $error
        ], $details));
    }

    /**
     * Логирование изменений паролей
     */
    public static function logPasswordChange($username, $success = true, $details = []) {
        $event = $success ? 'PASSWORD_CHANGED' : 'PASSWORD_CHANGE_FAILED';
        self::log('INFO', $event, array_merge([
            'username' => $username
        ], $details));
    }

    /**
     * Логирование создания/удаления пользователей
     */
    public static function logUserAction($action, $targetUser, $actorUser = null, $details = []) {
        self::log('INFO', 'USER_' . strtoupper($action), array_merge([
            'target_user' => $targetUser,
            'actor_user' => $actorUser ?: ($_SESSION['username'] ?? 'system')
        ], $details));
    }

    /**
     * Логирование доступа к файлам
     */
    public static function logFileAccess($filename, $action, $success = true, $details = []) {
        $event = 'FILE_' . strtoupper($action);
        self::log($success ? 'INFO' : 'WARNING', $event, array_merge([
            'filename' => $filename,
            'success' => $success
        ], $details));
    }

    /**
     * Логирование SQL инъекций
     */
    public static function logSQLInjectionAttempt($query, $details = []) {
        self::log('CRITICAL', 'SQL_INJECTION_ATTEMPT', array_merge([
            'query' => $query
        ], $details));
    }

    /**
     * Логирование XSS попыток
     */
    public static function logXSSAttempt($input, $field = null, $details = []) {
        self::log('CRITICAL', 'XSS_ATTEMPT', array_merge([
            'input' => $input,
            'field' => $field
        ], $details));
    }

    /**
     * Логирование CSRF атак
     */
    public static function logCSRFAttempt($details = []) {
        self::log('CRITICAL', 'CSRF_ATTEMPT', $details);
    }

    /**
     * Логирование превышения rate limit
     */
    public static function logRateLimitExceeded($action, $attempts, $details = []) {
        self::log('WARNING', 'RATE_LIMIT_EXCEEDED', array_merge([
            'action' => $action,
            'attempts' => $attempts
        ], $details));
    }

    /**
     * Логирование изменений прав доступа
     */
    public static function logPermissionChange($targetUser, $oldRole, $newRole, $actorUser = null) {
        self::log('INFO', 'PERMISSION_CHANGED', [
            'target_user' => $targetUser,
            'old_role' => $oldRole,
            'new_role' => $newRole,
            'actor_user' => $actorUser ?: ($_SESSION['username'] ?? 'system')
        ]);
    }

    /**
     * Логирование ошибок валидации
     */
    public static function logValidationError($field, $value, $error, $details = []) {
        self::log('WARNING', 'VALIDATION_ERROR', array_merge([
            'field' => $field,
            'value' => substr($value, 0, 100), // Ограничиваем длину
            'error' => $error
        ], $details));
    }

    /**
     * Получить последние записи лога
     */
    public static function getRecentLogs($count = 100, $level = null) {
        self::init();
        
        $logFile = self::$logDir . '/security.log';
        if (!file_exists($logFile)) {
            return [];
        }

        $logs = [];
        $handle = fopen($logFile, 'r');
        
        if ($handle) {
            // Читаем файл с конца
            $lines = [];
            while (($line = fgets($handle)) !== false) {
                $lines[] = $line;
            }
            fclose($handle);

            // Берем последние строки
            $lines = array_slice(array_reverse($lines), 0, $count);
            
            foreach ($lines as $line) {
                $logEntry = json_decode(trim($line), true);
                if ($logEntry && ($level === null || $logEntry['level'] === $level)) {
                    $logs[] = $logEntry;
                }
            }
        }

        return array_reverse($logs);
    }

    /**
     * Получить статистику безопасности
     */
    public static function getSecurityStats($hours = 24) {
        $logs = self::getRecentLogs(1000);
        $cutoff = time() - ($hours * 3600);
        
        $stats = [
            'total_events' => 0,
            'failed_logins' => 0,
            'successful_logins' => 0,
            'csrf_attempts' => 0,
            'xss_attempts' => 0,
            'sql_injection_attempts' => 0,
            'rate_limit_exceeded' => 0,
            'suspicious_activities' => 0,
            'top_ips' => [],
            'event_timeline' => []
        ];

        $ipCounts = [];
        $hourlyEvents = [];

        foreach ($logs as $log) {
            $timestamp = strtotime($log['timestamp']);
            if ($timestamp < $cutoff) {
                continue;
            }

            $stats['total_events']++;
            
            // Подсчет по типам событий
            switch ($log['event']) {
                case 'LOGIN_FAILED':
                    $stats['failed_logins']++;
                    break;
                case 'LOGIN_SUCCESS':
                    $stats['successful_logins']++;
                    break;
                case 'CSRF_ATTEMPT':
                    $stats['csrf_attempts']++;
                    break;
                case 'XSS_ATTEMPT':
                    $stats['xss_attempts']++;
                    break;
                case 'SQL_INJECTION_ATTEMPT':
                    $stats['sql_injection_attempts']++;
                    break;
                case 'RATE_LIMIT_EXCEEDED':
                    $stats['rate_limit_exceeded']++;
                    break;
                case 'SUSPICIOUS_ACTIVITY':
                    $stats['suspicious_activities']++;
                    break;
            }

            // Подсчет по IP
            $ip = $log['ip'];
            $ipCounts[$ip] = ($ipCounts[$ip] ?? 0) + 1;

            // Временная линия
            $hour = date('H', $timestamp);
            $hourlyEvents[$hour] = ($hourlyEvents[$hour] ?? 0) + 1;
        }

        // Топ IP адресов
        arsort($ipCounts);
        $stats['top_ips'] = array_slice($ipCounts, 0, 10, true);
        
        $stats['event_timeline'] = $hourlyEvents;

        return $stats;
    }

    /**
     * Очистка старых логов
     */
    public static function cleanupOldLogs($maxAge = 2592000) { // 30 дней
        self::init();
        
        $files = glob(self::$logDir . '/*.log*');
        $now = time();
        
        foreach ($files as $file) {
            if ($now - filemtime($file) > $maxAge) {
                unlink($file);
            }
        }
    }

    /**
     * Экспорт логов для анализа
     */
    public static function exportLogs($startDate, $endDate, $format = 'json') {
        $logs = self::getRecentLogs(10000);
        $filteredLogs = [];
        
        $start = strtotime($startDate);
        $end = strtotime($endDate);
        
        foreach ($logs as $log) {
            $timestamp = strtotime($log['timestamp']);
            if ($timestamp >= $start && $timestamp <= $end) {
                $filteredLogs[] = $log;
            }
        }
        
        if ($format === 'csv') {
            return self::convertToCSV($filteredLogs);
        }
        
        return json_encode($filteredLogs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Конвертация в CSV формат
     */
    private static function convertToCSV($logs) {
        if (empty($logs)) {
            return '';
        }
        
        $csv = "timestamp,level,event,ip,user_agent,request_uri,user_id,data\n";
        
        foreach ($logs as $log) {
            $row = [
                $log['timestamp'],
                $log['level'],
                $log['event'],
                $log['ip'],
                '"' . str_replace('"', '""', $log['user_agent']) . '"',
                $log['request_uri'],
                $log['user_id'] ?: '',
                '"' . str_replace('"', '""', json_encode($log['data'])) . '"'
            ];
            
            $csv .= implode(',', $row) . "\n";
        }
        
        return $csv;
    }

    /**
     * Получить логи безопасности для конкретного преподавателя и его студентов
     * @param int $teacher_id ID преподавателя
     * @param int $count Количество записей
     * @param string|null $level Уровень событий
     * @return array
     */
    public static function getTeacherSecurityLogs($teacher_id, $count = 100, $level = null) {
        require_once dirname(__DIR__) . '/Config/database.php';
        
        // Получаем список ID студентов данного преподавателя
        $database = new \Database();
        $db = $database->getConnection();
        
        if (!$db) {
            return [];
        }

        // Получаем ID всех студентов преподавателя
        $query = "SELECT DISTINCT u.id 
                  FROM users u 
                  INNER JOIN deck_assignments da ON u.id = da.student_id 
                  INNER JOIN decks d ON da.deck_id = d.id 
                  WHERE d.teacher_id = :teacher_id AND u.role = 'student'";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':teacher_id', $teacher_id);
        $stmt->execute();
        $student_ids = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        
        // Добавляем ID самого преподавателя
        $allowed_user_ids = array_merge([$teacher_id], $student_ids);
        
        // Получаем все логи
        $all_logs = self::getRecentLogs($count * 3, $level); // Берем больше, чтобы после фильтрации было достаточно
        
        // Фильтруем логи
        $filtered_logs = [];
        foreach ($all_logs as $log) {
            $include_log = false;
            
            // Включаем лог если:
            // 1. user_id принадлежит преподавателю или его студентам
            if (!empty($log['user_id']) && in_array($log['user_id'], $allowed_user_ids)) {
                $include_log = true;
            }
            // 2. Или это событие связанное с данным преподавателем по данным в логе
            elseif (!empty($log['data'])) {
                // Проверяем username в данных лога
                if (isset($log['data']['username']) && !empty($student_ids)) {
                    $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
                    $username_query = "SELECT id FROM users WHERE username = ? AND (id = ? OR id IN ($placeholders))";
                    $username_stmt = $db->prepare($username_query);
                    $params = [$log['data']['username'], $teacher_id];
                    $params = array_merge($params, $student_ids);
                    $username_stmt->execute($params);
                    if ($username_stmt->fetch()) {
                        $include_log = true;
                    }
                } elseif (isset($log['data']['username']) && empty($student_ids)) {
                    // Если нет студентов, проверяем только преподавателя
                    $username_query = "SELECT id FROM users WHERE username = ? AND id = ?";
                    $username_stmt = $db->prepare($username_query);
                    $username_stmt->execute([$log['data']['username'], $teacher_id]);
                    if ($username_stmt->fetch()) {
                        $include_log = true;
                    }
                }
                
                // Проверяем email в данных лога
                if (!$include_log && isset($log['data']['email']) && !empty($student_ids)) {
                    $placeholders = implode(',', array_fill(0, count($student_ids), '?'));
                    $email_query = "SELECT id FROM users WHERE email = ? AND (id = ? OR id IN ($placeholders))";
                    $email_stmt = $db->prepare($email_query);
                    $params = [$log['data']['email'], $teacher_id];
                    $params = array_merge($params, $student_ids);
                    $email_stmt->execute($params);
                    if ($email_stmt->fetch()) {
                        $include_log = true;
                    }
                } elseif (!$include_log && isset($log['data']['email']) && empty($student_ids)) {
                    // Если нет студентов, проверяем только преподавателя
                    $email_query = "SELECT id FROM users WHERE email = ? AND id = ?";
                    $email_stmt = $db->prepare($email_query);
                    $email_stmt->execute([$log['data']['email'], $teacher_id]);
                    if ($email_stmt->fetch()) {
                        $include_log = true;
                    }
                }
            }
            // 3. Или это общие системные события (без user_id), которые могут касаться преподавателя
            elseif (empty($log['user_id']) && in_array($log['event'], [
                'SYSTEM_ERROR', 'DATABASE_ERROR', 'CSRF_ATTACK', 'XSS_ATTEMPT', 
                'SQL_INJECTION_ATTEMPT', 'SUSPICIOUS_ACTIVITY'
            ])) {
                // Включаем только если это с того же IP, что использует преподаватель
                // или если это критическое системное событие
                if (isset($log['data']['severity']) && $log['data']['severity'] === 'critical') {
                    $include_log = true;
                }
            }
            
            if ($include_log) {
                $filtered_logs[] = $log;
                
                // Ограничиваем количество результатов
                if (count($filtered_logs) >= $count) {
                    break;
                }
            }
        }
        
        return $filtered_logs;
    }

    /**
     * Получить статистику безопасности для конкретного преподавателя
     * @param int $teacher_id ID преподавателя
     * @param int $hours Количество часов для анализа
     * @return array
     */
    public static function getTeacherSecurityStats($teacher_id, $hours = 24) {
        $logs = self::getTeacherSecurityLogs($teacher_id, 1000);
        $cutoff = time() - ($hours * 3600);
        
        $stats = [
            'total_events' => 0,
            'failed_logins' => 0,
            'successful_logins' => 0,
            'csrf_attempts' => 0,
            'xss_attempts' => 0,
            'sql_injection_attempts' => 0,
            'rate_limit_exceeded' => 0,
            'suspicious_activities' => 0,
            'top_ips' => [],
            'event_timeline' => []
        ];

        $ipCounts = [];
        $hourlyEvents = [];

        foreach ($logs as $log) {
            $logTime = strtotime($log['timestamp']);
            if ($logTime < $cutoff) continue;

            $stats['total_events']++;

            // Анализируем события аналогично getSecurityStats()
            switch ($log['event']) {
                case 'LOGIN_FAILED':
                    $stats['failed_logins']++;
                    break;
                case 'LOGIN_SUCCESS':
                    $stats['successful_logins']++;
                    break;
                case 'CSRF_ATTACK':
                    $stats['csrf_attempts']++;
                    break;
                case 'XSS_ATTEMPT':
                    $stats['xss_attempts']++;
                    break;
                case 'SQL_INJECTION_ATTEMPT':
                    $stats['sql_injection_attempts']++;
                    break;
                case 'RATE_LIMIT_EXCEEDED':
                    $stats['rate_limit_exceeded']++;
                    break;
                case 'SUSPICIOUS_ACTIVITY':
                    $stats['suspicious_activities']++;
                    break;
            }

            // Подсчет IP адресов
            $ip = $log['ip'];
            $ipCounts[$ip] = ($ipCounts[$ip] ?? 0) + 1;

            // Временная шкала
            $hour = date('H', $logTime);
            $hourlyEvents[$hour] = ($hourlyEvents[$hour] ?? 0) + 1;
        }

        // Топ IP адресов
        arsort($ipCounts);
        $stats['top_ips'] = array_slice($ipCounts, 0, 10, true);
        
        // Заполняем пропуски в временной шкале
        for ($i = 0; $i < 24; $i++) {
            $hour = sprintf('%02d', $i);
            if (!isset($hourlyEvents[$hour])) {
                $hourlyEvents[$hour] = 0;
            }
        }
        ksort($hourlyEvents);
        $stats['event_timeline'] = $hourlyEvents;

        return $stats;
    }
}
