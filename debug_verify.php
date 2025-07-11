<?php
/**
 * Простая диагностика верификации email
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Диагностика верификации email</h1>";

echo "<h2>Информация о запросе:</h2>";
echo "Метод запроса: " . $_SERVER['REQUEST_METHOD'] . "<br>";
echo "URL: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "User Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? 'не определен') . "<br>";

$token = $_GET['token'] ?? '';
echo "Токен: " . htmlspecialchars($token) . "<br>";
echo "Длина токена: " . strlen($token) . "<br>";

if (empty($token)) {
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; color: #721c24;'>";
    echo "❌ Токен не предоставлен в URL";
    echo "</div>";
    exit;
}

echo "<h2>Подключение к БД:</h2>";
try {
    require_once 'config/database.php';
    require_once 'classes/User.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$database->isConnected()) {
        throw new Exception('Не удалось подключиться к БД: ' . $database->getError());
    }
    
    echo "✅ Подключение к БД успешно<br>";
    
    $user = new User($db);
    echo "✅ Класс User загружен<br>";
    
    echo "<h2>Поиск токена в БД:</h2>";
    
    // Ищем токен
    $query = "SELECT id, email, verification_token_expires, email_verified, first_name, last_name 
              FROM users 
              WHERE verification_token = :token 
              AND role = 'teacher'";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    
    $user_record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user_record) {
        echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; color: #721c24;'>";
        echo "❌ Пользователь с таким токеном не найден";
        echo "</div>";
        
        // Проверяем, есть ли вообще такой токен в БД
        $check_query = "SELECT COUNT(*) FROM users WHERE verification_token = :token";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':token', $token);
        $check_stmt->execute();
        $token_exists = $check_stmt->fetchColumn();
        
        if ($token_exists > 0) {
            echo "<p>⚠️ Токен найден в БД, но пользователь не является преподавателем</p>";
        } else {
            echo "<p>⚠️ Токен полностью отсутствует в БД</p>";
        }
        
    } else {
        echo "✅ Пользователь найден:<br>";
        echo "ID: " . $user_record['id'] . "<br>";
        echo "Email: " . htmlspecialchars($user_record['email']) . "<br>";
        echo "Имя: " . htmlspecialchars($user_record['first_name'] . ' ' . $user_record['last_name']) . "<br>";
        echo "Email уже подтвержден: " . ($user_record['email_verified'] ? 'Да' : 'Нет') . "<br>";
        echo "Срок действия токена: " . ($user_record['verification_token_expires'] ?? 'не установлен') . "<br>";
        
        if ($user_record['email_verified']) {
            echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px; color: #856404;'>";
            echo "⚠️ Email уже подтвержден ранее";
            echo "</div>";
        } else {
            // Проверяем срок действия
            if ($user_record['verification_token_expires'] && strtotime($user_record['verification_token_expires']) < time()) {
                echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; color: #721c24;'>";
                echo "❌ Срок действия токена истек";
                echo "</div>";
            } else {
                echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; color: #155724;'>";
                echo "✅ Токен действителен, можно подтверждать email";
                echo "</div>";
                
                echo "<h2>Тест подтверждения:</h2>";
                
                // Проверяем метод верификации
                $result = $user->verifyEmail($token);
                
                if ($result['success']) {
                    echo "<div style='background: #d4edda; padding: 10px; border-radius: 5px; color: #155724;'>";
                    echo "🎉 Email успешно подтвержден!";
                    echo "</div>";
                } else {
                    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; color: #721c24;'>";
                    echo "❌ Ошибка подтверждения: " . ($result['reason'] ?? 'неизвестная ошибка');
                    echo "</div>";
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px; color: #721c24;'>";
    echo "❌ Ошибка: " . $e->getMessage();
    echo "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='verify_email.php?token=" . urlencode($token) . "'>🔄 Перейти к обычной странице верификации</a></p>";
echo "<p><a href='login.php'>🏠 Страница входа</a></p>";
?>
