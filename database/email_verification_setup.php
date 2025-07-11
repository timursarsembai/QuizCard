<?php
/**
 * Скрипт для настройки системы подтверждения email
 * Добавляет необходимые поля в таблицу users
 * 
 * Запустите этот скрипт в браузере для применения изменений в БД
 */

session_start();
require_once '../config/database.php';

// Защита от несанкционированного доступа
$allowed_ips = ['127.0.0.1', '::1', 'localhost'];
$user_ip = $_SERVER['REMOTE_ADDR'] ?? '';

if (!in_array($user_ip, $allowed_ips) && !isset($_GET['force'])) {
    die('Доступ запрещен. Запустите скрипт с локального сервера или добавьте ?force=1');
}

$database = new Database();
$db = $database->getConnection();

if (!$database->isConnected()) {
    die('Ошибка подключения к базе данных: ' . $database->getError());
}

$success_messages = [];
$error_messages = [];
$rollback_needed = false;

try {
    // Начинаем транзакцию
    $db->beginTransaction();
    
    echo "<!DOCTYPE html>
    <html lang='ru'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Настройка системы подтверждения email</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
            .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
            .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; }
            .info { background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 5px; margin: 10px 0; }
            .warning { background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin: 10px 0; }
            pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
            .button { background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 10px 5px 10px 0; }
            .button.danger { background: #dc3545; }
        </style>
    </head>
    <body>
        <h1>🔧 Настройка системы подтверждения email</h1>
        <p><strong>Дата выполнения:</strong> " . date('Y-m-d H:i:s') . "</p>";

    // Проверяем существование полей
    echo "<h2>📋 Проверка существующей структуры таблицы</h2>";
    
    $query = "SHOW COLUMNS FROM users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $existing_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $column_names = array_column($existing_columns, 'Field');
    
    echo "<div class='info'>";
    echo "<h3>Текущие поля таблицы users:</h3>";
    echo "<pre>" . implode(', ', $column_names) . "</pre>";
    echo "</div>";
    
    // Поля для добавления
    $fields_to_add = [
        'email_verified' => [
            'definition' => 'TINYINT(1) DEFAULT 0',
            'description' => 'Статус подтверждения email (0 - не подтвержден, 1 - подтвержден)'
        ],
        'verification_token' => [
            'definition' => 'VARCHAR(255) NULL',
            'description' => 'Токен для подтверждения email'
        ],
        'verification_token_expires' => [
            'definition' => 'DATETIME NULL',
            'description' => 'Срок действия токена подтверждения'
        ],
        'last_verification_sent' => [
            'definition' => 'DATETIME NULL',
            'description' => 'Время последней отправки письма подтверждения'
        ]
    ];
    
    echo "<h2>🔄 Добавление новых полей</h2>";
    
    foreach ($fields_to_add as $field_name => $field_info) {
        if (in_array($field_name, $column_names)) {
            echo "<div class='warning'>⚠️ Поле <strong>$field_name</strong> уже существует - пропускаем</div>";
            continue;
        }
        
        try {
            $alter_query = "ALTER TABLE users ADD COLUMN $field_name {$field_info['definition']}";
            $stmt = $db->prepare($alter_query);
            $stmt->execute();
            
            echo "<div class='success'>✅ Поле <strong>$field_name</strong> успешно добавлено<br>";
            echo "<small>{$field_info['description']}</small></div>";
            $success_messages[] = "Добавлено поле: $field_name";
            
        } catch (PDOException $e) {
            $error_message = "Ошибка при добавлении поля $field_name: " . $e->getMessage();
            echo "<div class='error'>❌ $error_message</div>";
            $error_messages[] = $error_message;
            $rollback_needed = true;
            break;
        }
    }
    
    // Добавляем индексы для оптимизации
    echo "<h2>📊 Добавление индексов</h2>";
    
    $indexes_to_add = [
        'idx_verification_token' => 'verification_token',
        'idx_email_verified' => 'email_verified'
    ];
    
    foreach ($indexes_to_add as $index_name => $column) {
        try {
            // Проверяем, существует ли индекс
            $check_index = "SHOW INDEX FROM users WHERE Key_name = '$index_name'";
            $stmt = $db->prepare($check_index);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                echo "<div class='warning'>⚠️ Индекс <strong>$index_name</strong> уже существует - пропускаем</div>";
                continue;
            }
            
            $create_index = "ALTER TABLE users ADD INDEX $index_name ($column)";
            $stmt = $db->prepare($create_index);
            $stmt->execute();
            
            echo "<div class='success'>✅ Индекс <strong>$index_name</strong> успешно создан</div>";
            $success_messages[] = "Создан индекс: $index_name";
            
        } catch (PDOException $e) {
            $error_message = "Ошибка при создании индекса $index_name: " . $e->getMessage();
            echo "<div class='error'>❌ $error_message</div>";
            $error_messages[] = $error_message;
            // Индексы не критичны, продолжаем
        }
    }
    
    // Создаем таблицу для логирования email отправок
    echo "<h2>📧 Создание таблицы логов email</h2>";
    
    try {
        $create_log_table = "
        CREATE TABLE IF NOT EXISTS email_verification_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            email VARCHAR(255) NOT NULL,
            token VARCHAR(255) NOT NULL,
            sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            verified_at DATETIME NULL,
            ip_address VARCHAR(45) NULL,
            user_agent TEXT NULL,
            status ENUM('sent', 'verified', 'expired', 'failed') DEFAULT 'sent',
            INDEX idx_user_id (user_id),
            INDEX idx_token (token),
            INDEX idx_status (status),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $stmt = $db->prepare($create_log_table);
        $stmt->execute();
        
        echo "<div class='success'>✅ Таблица <strong>email_verification_logs</strong> успешно создана</div>";
        $success_messages[] = "Создана таблица логов email";
        
    } catch (PDOException $e) {
        $error_message = "Ошибка при создании таблицы логов: " . $e->getMessage();
        echo "<div class='error'>❌ $error_message</div>";
        $error_messages[] = $error_message;
        // Таблица логов не критична, продолжаем
    }
    
    if ($rollback_needed) {
        $db->rollback();
        echo "<div class='error'>";
        echo "<h2>🔄 Выполнен откат изменений</h2>";
        echo "<p>Из-за критических ошибок все изменения были отменены.</p>";
        echo "</div>";
    } else {
        $db->commit();
        echo "<div class='success'>";
        echo "<h2>🎉 Настройка завершена успешно!</h2>";
        echo "<p>Все необходимые изменения в базе данных применены.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    $db->rollback();
    echo "<div class='error'>";
    echo "<h2>❌ Критическая ошибка</h2>";
    echo "<p>Произошла неожиданная ошибка: " . $e->getMessage() . "</p>";
    echo "<p>Все изменения отменены.</p>";
    echo "</div>";
}

// Показываем итоговую информацию
echo "<h2>📊 Итоги выполнения</h2>";

if (!empty($success_messages)) {
    echo "<div class='success'>";
    echo "<h3>✅ Успешно выполнено:</h3>";
    echo "<ul>";
    foreach ($success_messages as $message) {
        echo "<li>$message</li>";
    }
    echo "</ul>";
    echo "</div>";
}

if (!empty($error_messages)) {
    echo "<div class='error'>";
    echo "<h3>❌ Ошибки:</h3>";
    echo "<ul>";
    foreach ($error_messages as $message) {
        echo "<li>$message</li>";
    }
    echo "</ul>";
    echo "</div>";
}

// Проверяем финальную структуру
echo "<h2>🔍 Финальная проверка структуры</h2>";

try {
    $query = "SHOW COLUMNS FROM users";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $final_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div class='info'>";
    echo "<h3>Текущие поля таблицы users после изменений:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Поле</th><th>Тип</th><th>Null</th><th>Ключ</th><th>По умолчанию</th></tr>";
    
    foreach ($final_columns as $column) {
        $highlight = in_array($column['Field'], array_keys($fields_to_add)) ? 'background: #d4edda;' : '';
        echo "<tr style='$highlight'>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='error'>Ошибка при проверке структуры: " . $e->getMessage() . "</div>";
}

echo "<h2>🚀 Следующие шаги</h2>";
echo "<div class='info'>";
echo "<ol>";
echo "<li>Убедитесь, что все поля добавлены корректно</li>";
echo "<li>Проверьте работу системы подтверждения email</li>";
echo "<li>Настройте конфигурацию email в <code>config/email_config.php</code></li>";
echo "<li>Протестируйте регистрацию нового преподавателя</li>";
echo "</ol>";
echo "</div>";

// Кнопки для управления
echo "<div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd;'>";
echo "<a href='../teacher/dashboard.php' class='button'>🏠 Вернуться в панель управления</a>";
echo "<a href='rollback_email_verification.php' class='button danger' onclick='return confirm(\"Вы уверены, что хотите удалить все изменения?\")'>🗑️ Откатить изменения</a>";
echo "</div>";

// Создаем скрипт отката
$rollback_script = '<?php
/**
 * Скрипт для отката изменений системы подтверждения email
 */

session_start();
require_once "../config/database.php";

$database = new Database();
$db = $database->getConnection();

if (!$database->isConnected()) {
    die("Ошибка подключения к базе данных: " . $database->getError());
}

try {
    $db->beginTransaction();
    
    echo "<!DOCTYPE html><html><head><meta charset=\"UTF-8\"><title>Откат изменений</title></head><body>";
    echo "<h1>Откат изменений системы подтверждения email</h1>";
    
    // Удаляем добавленные поля
    $fields_to_remove = ["email_verified", "verification_token", "verification_token_expires", "last_verification_sent"];
    
    foreach ($fields_to_remove as $field) {
        try {
            $query = "ALTER TABLE users DROP COLUMN $field";
            $stmt = $db->prepare($query);
            $stmt->execute();
            echo "<p>✅ Поле $field удалено</p>";
        } catch (Exception $e) {
            echo "<p>⚠️ Поле $field не найдено или уже удалено</p>";
        }
    }
    
    // Удаляем таблицу логов
    try {
        $query = "DROP TABLE IF EXISTS email_verification_logs";
        $stmt = $db->prepare($query);
        $stmt->execute();
        echo "<p>✅ Таблица email_verification_logs удалена</p>";
    } catch (Exception $e) {
        echo "<p>❌ Ошибка при удалении таблицы логов: " . $e->getMessage() . "</p>";
    }
    
    $db->commit();
    echo "<p><strong>Откат завершен успешно!</strong></p>";
    echo "<a href=\"email_verification_setup.php\">Повторить настройку</a>";
    
} catch (Exception $e) {
    $db->rollback();
    echo "<p>❌ Ошибка отката: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>';

file_put_contents('rollback_email_verification.php', $rollback_script);

echo "<p style='margin-top: 20px; color: #666; font-size: 0.9em;'>";
echo "📝 Скрипт отката создан: <code>rollback_email_verification.php</code><br>";
echo "🕒 Время выполнения: " . date('Y-m-d H:i:s') . "<br>";
echo "🏷️ Версия скрипта: 1.0";
echo "</p>";

echo "</body></html>";
?>
