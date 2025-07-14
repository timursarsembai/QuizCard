<?php
/**
 * Конфигурация для отправки email
 * Настройки для функции mail() PHP
 */

class EmailConfig {
    
    // Основные настройки отправителя
    public static $from_email = 'test@ramazango.kz';
    public static $from_name = 'QuizCard';
    public static $reply_to = 'test@ramazango.kz';
    
    // Настройки для подтверждения email
    public static $verification_subject = [
        'ru' => 'Подтвердите ваш email адрес - QuizCard',
        'en' => 'Verify your email address - QuizCard', 
        'kk' => 'Email мекенжайыңызды растаңыз - QuizCard'
    ];
    
    // Время действия токена в часах
    public static $token_expiry_hours = 24;
    
    // Лимиты для защиты от спама
    public static $resend_limit_minutes = 5; // минимальный интервал между отправками
    public static $max_attempts_per_day = 5; // максимум попыток в день
    
    // Настройки SMTP заголовков для mail()
    public static function getHeaders($language = 'ru') {
        $charset = 'UTF-8';
        $from_name = self::$from_name;
        $from_email = self::$from_email;
        $reply_to = self::$reply_to;
        
        // Формируем заголовки для предотвращения попадания в спам
        $headers = [
            "MIME-Version: 1.0",
            "Content-Type: text/html; charset=$charset",
            "Content-Transfer-Encoding: 8bit",
            "From: =?$charset?B?" . base64_encode($from_name) . "?= <$from_email>",
            "Reply-To: $reply_to",
            "Return-Path: $from_email",
            "X-Mailer: PHP/" . phpversion(),
            "X-Priority: 3",
            "X-MSMail-Priority: Normal",
            "Message-ID: <" . time() . '.' . uniqid() . "@" . $_SERVER['HTTP_HOST'] . ">",
            "Date: " . date('r')
        ];
        
        return implode("\r\n", $headers);
    }
    
    /**
     * Получить тему письма на нужном языке
     */
    public static function getSubject($language = 'ru') {
        return self::$verification_subject[$language] ?? self::$verification_subject['ru'];
    }
    
    /**
     * Проверить, можно ли отправить письмо (защита от спама)
     */
    public static function canSendEmail($user_id, $db) {
        try {
            // Проверяем последнюю отправку
            $query = "SELECT last_verification_sent FROM users WHERE id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && $row['last_verification_sent']) {
                $last_sent = new DateTime($row['last_verification_sent']);
                $now = new DateTime();
                $diff = $now->diff($last_sent);
                $minutes_passed = ($diff->h * 60) + $diff->i;
                
                if ($minutes_passed < self::$resend_limit_minutes) {
                    return [
                        'allowed' => false,
                        'reason' => 'rate_limit',
                        'wait_minutes' => self::$resend_limit_minutes - $minutes_passed
                    ];
                }
            }
            
            // Проверяем количество попыток за день
            $query = "SELECT COUNT(*) FROM email_verification_logs 
                     WHERE user_id = :user_id 
                     AND sent_at > DATE_SUB(NOW(), INTERVAL 1 DAY)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            $attempts_today = $stmt->fetchColumn();
            
            if ($attempts_today >= self::$max_attempts_per_day) {
                return [
                    'allowed' => false,
                    'reason' => 'daily_limit',
                    'max_attempts' => self::$max_attempts_per_day
                ];
            }
            
            return ['allowed' => true];
            
        } catch (Exception $e) {
            error_log("Email rate limit check error: " . $e->getMessage());
            return ['allowed' => false, 'reason' => 'system_error'];
        }
    }
    
    /**
     * Очистка истекших токенов (вызывать периодически)
     */
    public static function cleanupExpiredTokens($db) {
        try {
            // Очищаем токены пользователей
            $query = "UPDATE users SET 
                     verification_token = NULL, 
                     verification_token_expires = NULL 
                     WHERE verification_token_expires < NOW()";
            $stmt = $db->prepare($query);
            $stmt->execute();
            
            // Обновляем статус в логах
            $query = "UPDATE email_verification_logs SET status = 'expired' 
                     WHERE status = 'sent' 
                     AND sent_at < DATE_SUB(NOW(), INTERVAL :hours HOUR)";
            $stmt = $db->prepare($query);
            
            $token_expiry_hours = self::$token_expiry_hours;
            $stmt->bindParam(':hours', $token_expiry_hours);
            $stmt->execute();
            
            return true;
            
        } catch (Exception $e) {
            error_log("Token cleanup error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Логирование отправки email
     */
    public static function logEmailSent($user_id, $email, $token, $db, $status = 'sent') {
        try {
            $query = "INSERT INTO email_verification_logs 
                     (user_id, email, token, ip_address, user_agent, status) 
                     VALUES (:user_id, :email, :token, :ip, :user_agent, :status)";
            
            $stmt = $db->prepare($query);
            
            // Создаем переменные для bindParam (требует передачи по ссылке)
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':token', $token);
            $stmt->bindParam(':ip', $ip_address);
            $stmt->bindParam(':user_agent', $user_agent);
            $stmt->bindParam(':status', $status);
            
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Email logging error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Получить базовый URL сайта
     */
    public static function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return "$protocol://$host";
    }
    
    /**
     * Получить URL для подтверждения email
     */
    public static function getVerificationUrl($token) {
        $base_url = self::getBaseUrl();
        $current_dir = dirname($_SERVER['SCRIPT_NAME']);
        
        // Убираем лишние слеши и формируем правильный путь
        $path = rtrim($current_dir, '/');
        if (strpos($path, '/teacher') !== false || strpos($path, '/student') !== false) {
            $path = dirname($path);
        }
        
        return $base_url . $path . "/verify_email.php?token=" . urlencode($token);
    }
}
?>
