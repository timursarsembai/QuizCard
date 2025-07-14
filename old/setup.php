<?php
// Настройка и тестирование базы данных
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<!DOCTYPE html>
<html lang='ru'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>QuizCard - Настройка базы данных</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 2rem auto; padding: 2rem; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        .section { margin: 2rem 0; padding: 1rem; border: 1px solid #ddd; border-radius: 5px; }
        .btn { padding: 0.5rem 1rem; background: #007bff; color: white; text-decoration: none; border-radius: 3px; display: inline-block; margin: 0.5rem 0; }
        .btn-danger { background: #dc3545; }
        pre { background: #f8f9fa; padding: 1rem; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>";

echo "<h1>🔧 Настройка базы данных QuizCard</h1>";

// Параметр для принудительного пересоздания
$force_recreate = isset($_GET['force']) && $_GET['force'] === 'true';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        echo "<div class='section'>";
        echo "<h2 class='error'>❌ Ошибка подключения к базе данных</h2>";
        echo "<p>Проверьте настройки в файле config/database.php:</p>";
        echo "<ul>";
        echo "<li>Хост: localhost</li>";
        echo "<li>База данных: ramazang_quiz</li>";
        echo "<li>Пользователь: ramazang_qusr</li>";
        echo "<li>Убедитесь, что MySQL сервер запущен</li>";
        echo "</ul>";
        echo "</div>";
        exit;
    }
    
    echo "<div class='section'>";
    echo "<h2 class='success'>✅ Подключение к базе данных успешно!</h2>";
    echo "</div>";
    
    // Проверяем текущую структуру
    echo "<div class='section'>";
    echo "<h2>📋 Проверка структуры базы данных</h2>";
    
    $tables_to_check = ['users', 'decks', 'deck_assignments', 'vocabulary', 'learning_progress'];
    $existing_tables = [];
    $missing_tables = [];
    
    foreach ($tables_to_check as $table) {
        $query = "SHOW TABLES LIKE '$table'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $existing_tables[] = $table;
            echo "<span class='success'>✅ Таблица '$table' существует</span><br>";
        } else {
            $missing_tables[] = $table;
            echo "<span class='error'>❌ Таблица '$table' отсутствует</span><br>";
        }
    }
    
    // Проверяем структуру таблицы users для поддержки email
    if (in_array('users', $existing_tables)) {
        $query = "SHOW COLUMNS FROM users LIKE 'email'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            echo "<span class='warning'>⚠️ В таблице 'users' отсутствует поле 'email'</span><br>";
            $missing_tables[] = 'users_update';
        } else {
            echo "<span class='success'>✅ Таблица 'users' содержит поле 'email'</span><br>";
        }
    }
    
    echo "</div>";
    
    // Если есть недостающие таблицы или нужно пересоздать
    if (!empty($missing_tables) || $force_recreate) {
        echo "<div class='section'>";
        echo "<h2>🔄 Обновление структуры базы данных</h2>";
        
        if ($force_recreate) {
            echo "<p class='warning'>⚠️ Принудительное пересоздание всех таблиц...</p>";
            
            // Удаляем все таблицы в правильном порядке
            $drop_tables = ['learning_progress', 'vocabulary', 'deck_assignments', 'decks', 'users'];
            foreach ($drop_tables as $table) {
                try {
                    $db->exec("DROP TABLE IF EXISTS $table");
                    echo "<span class='info'>🗑️ Таблица '$table' удалена</span><br>";
                } catch (PDOException $e) {
                    echo "<span class='warning'>⚠️ Не удалось удалить таблицу '$table': " . $e->getMessage() . "</span><br>";
                }
            }
        } else {
            // Обновляем таблицу users если нужно
            if (in_array('users_update', $missing_tables)) {
                try {
                    $db->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) NULL AFTER last_name");
                    echo "<span class='success'>✅ Добавлено поле 'email' в таблицу 'users'</span><br>";
                } catch (PDOException $e) {
                    echo "<span class='error'>❌ Ошибка при добавлении поля 'email': " . $e->getMessage() . "</span><br>";
                }
            }
        }
        
        // Создаем недостающие таблицы
        $sql_commands = [
            "users" => "CREATE TABLE IF NOT EXISTS users (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role ENUM('teacher', 'student') NOT NULL,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL,
                email VARCHAR(255) NULL,
                teacher_id INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            
            "decks" => "CREATE TABLE IF NOT EXISTS decks (
                id INT AUTO_INCREMENT PRIMARY KEY,
                teacher_id INT NOT NULL,
                name VARCHAR(200) NOT NULL,
                description TEXT NULL,
                color VARCHAR(7) DEFAULT '#667eea',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE
            )",
            
            "deck_assignments" => "CREATE TABLE IF NOT EXISTS deck_assignments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                deck_id INT NOT NULL,
                student_id INT NOT NULL,
                assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (deck_id) REFERENCES decks(id) ON DELETE CASCADE,
                FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_deck_student (deck_id, student_id)
            )",
            
            "vocabulary" => "CREATE TABLE IF NOT EXISTS vocabulary (
                id INT AUTO_INCREMENT PRIMARY KEY,
                deck_id INT NOT NULL,
                foreign_word VARCHAR(255) NOT NULL,
                translation VARCHAR(255) NOT NULL,
                image_path VARCHAR(500) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (deck_id) REFERENCES decks(id) ON DELETE CASCADE
            )",
            
            "learning_progress" => "CREATE TABLE IF NOT EXISTS learning_progress (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                vocabulary_id INT NOT NULL,
                ease_factor DECIMAL(3,2) DEFAULT 2.50,
                interval_days INT DEFAULT 1,
                repetition_count INT DEFAULT 0,
                next_review_date DATE NOT NULL,
                last_review_date TIMESTAMP NULL,
                difficulty_rating ENUM('easy', 'hard') NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (vocabulary_id) REFERENCES vocabulary(id) ON DELETE CASCADE,
                UNIQUE KEY unique_student_vocabulary (student_id, vocabulary_id)
            )"
        ];
        
        foreach ($sql_commands as $table_name => $sql) {
            if (in_array($table_name, $missing_tables) || $force_recreate) {
                try {
                    $db->exec($sql);
                    echo "<span class='success'>✅ Таблица '$table_name' создана</span><br>";
                } catch (PDOException $e) {
                    echo "<span class='error'>❌ Ошибка при создании таблицы '$table_name': " . $e->getMessage() . "</span><br>";
                }
            }
        }
        
        echo "</div>";
    }
    
    // Проверяем и создаем тестового пользователя
    echo "<div class='section'>";
    echo "<h2>👤 Проверка тестовых пользователей</h2>";
    
    $query = "SELECT COUNT(*) as count FROM users WHERE username = 'teacher'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        echo "<span class='warning'>⚠️ Тестовый преподаватель не найден. Создаю...</span><br>";
        
        $hashed_password = password_hash('password', PASSWORD_DEFAULT);
        $query = "INSERT INTO users (username, password, role, first_name, last_name, email) 
                  VALUES ('teacher', :password, 'teacher', 'Тестовый', 'Преподаватель', 'teacher@example.com')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':password', $hashed_password);
        
        if ($stmt->execute()) {
            echo "<span class='success'>✅ Тестовый преподаватель создан (логин: teacher, пароль: password)</span><br>";
        } else {
            echo "<span class='error'>❌ Ошибка при создании тестового преподавателя</span><br>";
        }
    } else {
        echo "<span class='success'>✅ Тестовый преподаватель существует (логин: teacher, пароль: password)</span><br>";
    }
    
    // Создаем тестового ученика если его нет
    $query = "SELECT COUNT(*) as count FROM users WHERE username = 'student1'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        // Получаем ID тестового преподавателя
        $query = "SELECT id FROM users WHERE username = 'teacher'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $teacher = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($teacher) {
            $hashed_password = password_hash('password', PASSWORD_DEFAULT);
            $query = "INSERT INTO users (username, password, role, first_name, last_name, teacher_id) 
                      VALUES ('student1', :password, 'student', 'Тестовый', 'Ученик', :teacher_id)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':password', $hashed_password);
            $stmt->bindParam(':teacher_id', $teacher['id']);
            
            if ($stmt->execute()) {
                echo "<span class='success'>✅ Тестовый ученик создан (логин: student1, пароль: password)</span><br>";
            } else {
                echo "<span class='error'>❌ Ошибка при создании тестового ученика</span><br>";
            }
        }
    } else {
        echo "<span class='success'>✅ Тестовый ученик существует (логин: student1, пароль: password)</span><br>";
    }
    
    echo "</div>";
    
    // Итоговый статус
    echo "<div class='section'>";
    echo "<h2>📊 Итоговый статус</h2>";
    
    $final_check = true;
    foreach ($tables_to_check as $table) {
        $query = "SHOW TABLES LIKE '$table'";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            $final_check = false;
            break;
        }
    }
    
    if ($final_check) {
        echo "<h3 class='success'>🎉 База данных успешно настроена!</h3>";
        echo "<p>Все таблицы созданы и готовы к работе.</p>";
        echo "<h4>Тестовые аккаунты:</h4>";
        echo "<ul>";
        echo "<li><strong>Преподаватель:</strong> логин 'teacher', пароль 'password'</li>";
        echo "<li><strong>Ученик:</strong> логин 'student1', пароль 'password'</li>";
        echo "</ul>";
        echo "<a href='index.php' class='btn'>🏠 Перейти на главную страницу</a>";
        echo "<a href='student_login.php' class='btn'>👨‍🎓 Вход для учеников</a>";
    } else {
        echo "<h3 class='error'>❌ Обнаружены проблемы с базой данных</h3>";
        echo "<p>Попробуйте пересоздать все таблицы:</p>";
        echo "<a href='setup.php?force=true' class='btn btn-danger'>🔄 Пересоздать все таблицы</a>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<h2 class='error'>❌ Критическая ошибка</h2>";
    echo "<p>Ошибка: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Проверьте:</p>";
    echo "<ul>";
    echo "<li>Запущен ли MySQL сервер</li>";
    echo "<li>Правильность настроек в config/database.php</li>";
    echo "<li>Существует ли база данных 'ramazang_quiz'</li>";
    echo "<li>Есть ли права у пользователя 'ramazang_qusr'</li>";
    echo "</ul>";
    echo "</div>";
}

echo "</body></html>";
?>
