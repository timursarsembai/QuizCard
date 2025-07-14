<?php
/**
 * Упрощенная временная CSRF защита для отладки
 */
class SimpleCSRF {
    private static $tokenName = 'csrf_token';
    
    public static function generateToken() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Простой токен без сложной криптографии
        $token = bin2hex(random_bytes(16));
        $_SESSION[self::$tokenName] = $token;
        
        return $token;
    }
    
    public static function getToken() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION[self::$tokenName])) {
            return self::generateToken();
        }
        
        return $_SESSION[self::$tokenName];
    }
    
    public static function validateRequest() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        $sessionToken = $_SESSION[self::$tokenName] ?? null;
        $requestToken = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
        
        if (!$sessionToken || !$requestToken) {
            return false;
        }
        
        return hash_equals($sessionToken, $requestToken);
    }
    
    public static function getTokenInput() {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }
    
    public static function getTokenMeta() {
        $token = self::getToken();
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }
}
?>
