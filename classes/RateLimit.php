<?php
/**
 * Система ограничения частоты запросов (Rate Limiting)
 */
class RateLimit {
    private static $storageDir = null;

    /**
     * Инициализация с проверкой директории
     */
    private static function init() {
        if (self::$storageDir === null) {
            self::$storageDir = dirname(__DIR__) . '/logs/rate_limits';
            
            if (!is_dir(self::$storageDir)) {
                mkdir(self::$storageDir, 0755, true);
            }
        }
    }

    /**
     * Получить уникальный идентификатор клиента
     */
    private static function getClientId() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Создаем хеш на основе IP и User-Agent
        return hash('sha256', $ip . '|' . $userAgent);
    }

    /**
     * Получить путь к файлу лимитов для действия
     */
    private static function getLimitFile($action, $clientId) {
        return self::$storageDir . '/' . $action . '_' . $clientId . '.json';
    }

    /**
     * Проверить лимит запросов
     */
    public static function check($action, $maxAttempts = 5, $windowMinutes = 15) {
        self::init();

        // Проверяем, включено ли ограничение
        require_once dirname(__DIR__) . '/classes/EnvLoader.php';
        EnvLoader::load();
        
        if (!EnvLoader::get('RATE_LIMIT_ENABLED', true)) {
            return true;
        }

        $clientId = self::getClientId();
        $limitFile = self::getLimitFile($action, $clientId);
        
        $now = time();
        $windowStart = $now - ($windowMinutes * 60);
        
        // Загружаем существующие попытки
        $attempts = [];
        if (file_exists($limitFile)) {
            $data = json_decode(file_get_contents($limitFile), true);
            if ($data && isset($data['attempts'])) {
                $attempts = $data['attempts'];
            }
        }

        // Удаляем старые попытки
        $attempts = array_filter($attempts, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });

        // Проверяем лимит
        if (count($attempts) >= $maxAttempts) {
            self::logRateLimitExceeded($action, $clientId, count($attempts));
            return false;
        }

        return true;
    }

    /**
     * Записать попытку
     */
    public static function record($action, $windowMinutes = 15) {
        self::init();

        require_once dirname(__DIR__) . '/classes/EnvLoader.php';
        EnvLoader::load();
        
        if (!EnvLoader::get('RATE_LIMIT_ENABLED', true)) {
            return;
        }

        $clientId = self::getClientId();
        $limitFile = self::getLimitFile($action, $clientId);
        
        $now = time();
        $windowStart = $now - ($windowMinutes * 60);
        
        // Загружаем существующие попытки
        $attempts = [];
        if (file_exists($limitFile)) {
            $data = json_decode(file_get_contents($limitFile), true);
            if ($data && isset($data['attempts'])) {
                $attempts = $data['attempts'];
            }
        }

        // Удаляем старые попытки
        $attempts = array_filter($attempts, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });

        // Добавляем новую попытку
        $attempts[] = $now;

        // Сохраняем данные
        $data = [
            'attempts' => array_values($attempts),
            'last_attempt' => $now,
            'client_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];

        file_put_contents($limitFile, json_encode($data));
    }

    /**
     * Получить оставшееся время до сброса лимита
     */
    public static function getResetTime($action, $windowMinutes = 15) {
        self::init();

        $clientId = self::getClientId();
        $limitFile = self::getLimitFile($action, $clientId);
        
        if (!file_exists($limitFile)) {
            return 0;
        }

        $data = json_decode(file_get_contents($limitFile), true);
        if (!$data || !isset($data['attempts']) || empty($data['attempts'])) {
            return 0;
        }

        $oldestAttempt = min($data['attempts']);
        $resetTime = $oldestAttempt + ($windowMinutes * 60);
        
        return max(0, $resetTime - time());
    }

    /**
     * Получить количество оставшихся попыток
     */
    public static function getRemainingAttempts($action, $maxAttempts = 5, $windowMinutes = 15) {
        self::init();

        $clientId = self::getClientId();
        $limitFile = self::getLimitFile($action, $clientId);
        
        $now = time();
        $windowStart = $now - ($windowMinutes * 60);
        
        $attempts = [];
        if (file_exists($limitFile)) {
            $data = json_decode(file_get_contents($limitFile), true);
            if ($data && isset($data['attempts'])) {
                $attempts = $data['attempts'];
            }
        }

        // Удаляем старые попытки
        $attempts = array_filter($attempts, function($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });

        return max(0, $maxAttempts - count($attempts));
    }

    /**
     * Очистить лимиты для клиента
     */
    public static function clear($action = null) {
        self::init();

        $clientId = self::getClientId();
        
        if ($action) {
            $limitFile = self::getLimitFile($action, $clientId);
            if (file_exists($limitFile)) {
                unlink($limitFile);
            }
        } else {
            // Очищаем все лимиты для клиента
            $files = glob(self::$storageDir . '/*_' . $clientId . '.json');
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }

    /**
     * Очистка старых файлов лимитов
     */
    public static function cleanup($maxAge = 86400) { // 24 часа по умолчанию
        self::init();

        $files = glob(self::$storageDir . '/*.json');
        $now = time();
        
        foreach ($files as $file) {
            if ($now - filemtime($file) > $maxAge) {
                unlink($file);
            }
        }
    }

    /**
     * Логирование превышения лимитов
     */
    private static function logRateLimitExceeded($action, $clientId, $attemptCount) {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $action,
            'client_id' => $clientId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'attempt_count' => $attemptCount,
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ];

        $logMessage = '[RATE_LIMIT_EXCEEDED] ' . json_encode($logData) . PHP_EOL;
        
        $logDir = dirname(self::$storageDir);
        error_log($logMessage, 3, $logDir . '/security.log');
    }

    /**
     * Middleware для автоматической проверки лимитов
     */
    public static function middleware($action, $maxAttempts = 5, $windowMinutes = 15) {
        if (!self::check($action, $maxAttempts, $windowMinutes)) {
            $resetTime = self::getResetTime($action, $windowMinutes);
            
            http_response_code(429);
            header('Retry-After: ' . $resetTime);
            
            $message = "Слишком много попыток. Попробуйте снова через " . 
                      ceil($resetTime / 60) . " мин.";
            
            die(json_encode(['error' => $message, 'retry_after' => $resetTime]));
        }
    }

    /**
     * Проверка и запись для форм входа
     */
    public static function checkLogin($identifier = null) {
        $action = 'login';
        
        // Используем email/username если предоставлен
        if ($identifier) {
            $action .= '_' . hash('sha256', $identifier);
        }
        
        return self::check($action, 5, 15); // 5 попыток за 15 минут
    }

    /**
     * Запись неудачной попытки входа
     */
    public static function recordFailedLogin($identifier = null) {
        $action = 'login';
        
        if ($identifier) {
            $action .= '_' . hash('sha256', $identifier);
        }
        
        self::record($action, 15);
    }

    /**
     * Получить статистику лимитов для админ панели
     */
    public static function getStats() {
        self::init();

        $stats = [
            'total_files' => 0,
            'active_limits' => 0,
            'top_ips' => []
        ];

        $files = glob(self::$storageDir . '/*.json');
        $stats['total_files'] = count($files);

        $ipCounts = [];
        $now = time();

        foreach ($files as $file) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && isset($data['client_ip'])) {
                $ip = $data['client_ip'];
                $ipCounts[$ip] = ($ipCounts[$ip] ?? 0) + 1;

                // Проверяем активные лимиты (последняя попытка < 24 часов)
                if (isset($data['last_attempt']) && ($now - $data['last_attempt']) < 86400) {
                    $stats['active_limits']++;
                }
            }
        }

        // Сортируем IP по количеству
        arsort($ipCounts);
        $stats['top_ips'] = array_slice($ipCounts, 0, 10, true);

        return $stats;
    }
}
?>
