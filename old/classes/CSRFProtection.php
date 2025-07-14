<?php
/**
 * Защита от CSRF атак
 */
class CSRFProtection {
    private static $tokenName = 'csrf_token';
    private static $secretKey = null;

    /**
     * Инициализация с загрузкой секретного ключа
     */
    public static function init() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Загружаем секретный ключ из переменных окружения
        if (self::$secretKey === null) {
            require_once dirname(__DIR__) . '/classes/EnvLoader.php';
            EnvLoader::load();
            self::$secretKey = EnvLoader::get('CSRF_SECRET_KEY', 'default_secret_key_change_me');
        }
    }

    /**
     * Генерация CSRF токена
     */
    public static function generateToken() {
        self::init();

        $token = bin2hex(random_bytes(32));
        $timestamp = time();
        
        // Создаем подпись токена
        $signature = hash_hmac('sha256', $token . $timestamp, self::$secretKey);
        
        // Комбинируем токен, время и подпись
        $csrfToken = base64_encode($token . '|' . $timestamp . '|' . $signature);
        
        // Сохраняем в сессии
        $_SESSION[self::$tokenName] = $csrfToken;
        
        return $csrfToken;
    }

    /**
     * Проверка CSRF токена
     */
    public static function validateToken($token, $maxAge = 3600) {
        self::init();

        if (empty($token)) {
            return false;
        }

        // Проверяем токен из сессии
        if (!isset($_SESSION[self::$tokenName])) {
            return false;
        }

        $sessionToken = $_SESSION[self::$tokenName];

        // Токены должны совпадать
        if (!hash_equals($sessionToken, $token)) {
            return false;
        }

        // Декодируем токен
        $decoded = base64_decode($token);
        if ($decoded === false) {
            return false;
        }

        $parts = explode('|', $decoded);
        if (count($parts) !== 3) {
            return false;
        }

        list($originalToken, $timestamp, $signature) = $parts;

        // Проверяем подпись
        $expectedSignature = hash_hmac('sha256', $originalToken . $timestamp, self::$secretKey);
        if (!hash_equals($expectedSignature, $signature)) {
            return false;
        }

        // Проверяем время жизни токена
        if (time() - $timestamp > $maxAge) {
            return false;
        }

        return true;
    }

    /**
     * Проверка CSRF токена из POST данных
     */
    public static function validateRequest($maxAge = 3600) {
        $token = self::getTokenFromRequest();
        $isValid = self::validateToken($token, $maxAge);
        
        return $isValid;
        
        // Логируем неудачные попытки
        if (!$isValid) {
            self::logCSRFFailure();
        }
        
        return $isValid;
    }

    /**
     * Получение токена из запроса
     */
    private static function getTokenFromRequest() {
        // Проверяем в POST
        if (isset($_POST[self::$tokenName])) {
            return $_POST[self::$tokenName];
        }

        // Проверяем в заголовках (для AJAX)
        $headers = getallheaders();
        if ($headers && isset($headers['X-CSRF-Token'])) {
            return $headers['X-CSRF-Token'];
        }

        return null;
    }

    /**
     * Получение HTML инпута с токеном
     */
    public static function getTokenInput() {
        $token = self::generateToken();
        return '<input type="hidden" name="' . self::$tokenName . '" value="' . htmlspecialchars($token) . '">';
    }

    /**
     * Получение токена для JavaScript
     */
    public static function getTokenForJS() {
        return self::generateToken();
    }

    /**
     * Обновление токена (для длительных форм)
     */
    public static function refreshToken() {
        return self::generateToken();
    }

    /**
     * Очистка старых токенов
     */
    public static function cleanup() {
        unset($_SESSION[self::$tokenName]);
    }

    /**
     * Логирование неудачных CSRF попыток
     */
    private static function logCSRFFailure() {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown',
            'referer' => $_SERVER['HTTP_REFERER'] ?? 'unknown',
            'post_data' => !empty($_POST) ? 'present' : 'absent'
        ];

        $logMessage = '[CSRF_FAILURE] ' . json_encode($logData) . PHP_EOL;
        
        // Создаем директорию logs если не существует
        $logDir = dirname(__DIR__) . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        error_log($logMessage, 3, $logDir . '/security.log');
    }

    /**
     * Middleware для автоматической проверки CSRF
     */
    public static function middleware($excludePaths = []) {
        $currentPath = $_SERVER['REQUEST_URI'] ?? '';
        
        // Пропускаем GET запросы и исключения
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || in_array($currentPath, $excludePaths)) {
            return true;
        }

        if (!self::validateRequest()) {
            http_response_code(403);
            die('CSRF token validation failed. Refresh the page and try again.');
        }

        return true;
    }

    /**
     * Генерация мета-тега для AJAX
     */
    public static function getMetaTag() {
        $token = self::generateToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }
}
?>
