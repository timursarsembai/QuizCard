<?php
// Временный файл для диагностики регистрации
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

echo "<h1>Диагностика системы регистрации</h1>";

// Тест 1: Проверка подключения к БД
echo "<h2>1. Проверка подключения к БД</h2>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($database->isConnected()) {
        echo "✅ Подключение к БД: OK<br>";
        
        // Проверяем структуру таблицы users
        $query = "SHOW COLUMNS FROM users";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $required_fields = ['email_verified', 'verification_token', 'verification_token_expires', 'last_verification_sent'];
        $existing_fields = array_column($columns, 'Field');
        
        echo "✅ Поля в таблице users: " . implode(', ', $existing_fields) . "<br>";
        
        foreach ($required_fields as $field) {
            if (in_array($field, $existing_fields)) {
                echo "✅ Поле $field: найдено<br>";
            } else {
                echo "❌ Поле $field: НЕ НАЙДЕНО<br>";
            }
        }
    } else {
        echo "❌ Ошибка БД: " . $database->getError() . "<br>";
    }
} catch (Exception $e) {
    echo "❌ Исключение БД: " . $e->getMessage() . "<br>";
}

// Тест 2: Проверка класса User
echo "<h2>2. Проверка класса User</h2>";
try {
    require_once 'classes/User.php';
    $user = new User($db);
    echo "✅ Класс User загружен<br>";
    
    // Проверяем новые методы
    $methods = get_class_methods($user);
    $required_methods = ['generateVerificationToken', 'sendVerificationEmail', 'verifyEmail', 'isEmailVerified'];
    
    foreach ($required_methods as $method) {
        if (in_array($method, $methods)) {
            echo "✅ Метод $method: найден<br>";
        } else {
            echo "❌ Метод $method: НЕ НАЙДЕН<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Ошибка класса User: " . $e->getMessage() . "<br>";
}

// Тест 3: Проверка email конфигурации
echo "<h2>3. Проверка email конфигурации</h2>";
try {
    require_once 'config/email_config.php';
    echo "✅ EmailConfig загружен<br>";
    echo "✅ From email: " . EmailConfig::$from_email . "<br>";
    echo "✅ From name: " . EmailConfig::$from_name . "<br>";
    
    // Проверка функции mail()
    if (function_exists('mail')) {
        echo "✅ Функция mail() доступна<br>";
    } else {
        echo "❌ Функция mail() недоступна<br>";
    }
} catch (Exception $e) {
    echo "❌ Ошибка EmailConfig: " . $e->getMessage() . "<br>";
}

// Тест 4: Проверка переводов
echo "<h2>4. Проверка переводов</h2>";
try {
    require_once 'includes/translations.php';
    echo "✅ Переводы загружены<br>";
    
    if (function_exists('translate')) {
        echo "✅ Функция translate() доступна<br>";
        echo "✅ Тест перевода: " . translate('success_register_email_sent') . "<br>";
    } else {
        echo "❌ Функция translate() недоступна<br>";
    }
} catch (Exception $e) {
    echo "❌ Ошибка переводов: " . $e->getMessage() . "<br>";
}

// Тест 5: Симуляция регистрации
echo "<h2>5. Тест регистрации (без отправки email)</h2>";
try {
    session_start();
    
    // Тестовые данные
    $test_username = 'testuser_' . time();
    $test_email = 'test' . time() . '@example.com';
    $test_password = 'testpass123';
    $test_first_name = 'Test';
    $test_last_name = 'User';
    
    echo "Тестовые данные:<br>";
    echo "Username: $test_username<br>";
    echo "Email: $test_email<br>";
    
    // Проверяем существование пользователя
    if ($user->isUsernameExists($test_username)) {
        echo "❌ Пользователь уже существует<br>";
    } else {
        echo "✅ Пользователь не существует - можно создавать<br>";
    }
    
    if ($user->isEmailExists($test_email)) {
        echo "❌ Email уже используется<br>";
    } else {
        echo "✅ Email свободен<br>";
    }
    
    // НЕ создаем пользователя, только тестируем методы
    echo "✅ Проверки пройдены - регистрация должна работать<br>";
    
} catch (Exception $e) {
    echo "❌ Ошибка при тесте регистрации: " . $e->getMessage() . "<br>";
    echo "Стек вызовов: " . $e->getTraceAsString() . "<br>";
}

echo "<h2>📋 Рекомендации</h2>";
echo "<p>Если видите ошибки выше, исправьте их перед тестированием регистрации.</p>";
echo "<p>Если всё ✅, то проблема может быть в:</p>";
echo "<ul>";
echo "<li>Ошибках в логах сервера</li>";
echo "<li>Недостающих правах на запись</li>";
echo "<li>Проблемах с отправкой email</li>";
echo "</ul>";

echo "<p><a href='login.php'>← Вернуться к форме входа</a></p>";
?>
