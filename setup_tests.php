<?php
/**
 * Скрипт для создания таблиц тестов в базе данных
 * Откройте этот файл в браузере для выполнения
 */

session_start();
require_once 'config/database.php';

// Проверяем, что пользователь авторизован как преподаватель
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    die('<h1>Ошибка доступа</h1><p>Этот скрипт может выполнять только преподаватель.</p>');
}

$database = new Database();
$db = $database->getConnection();

// Включаем отображение ошибок
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo '<h1>Создание таблиц для системы тестов</h1>';
echo '<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { color: green; background: #d4edda; padding: 10px; margin: 10px 0; border-radius: 5px; }
.error { color: red; background: #f8d7da; padding: 10px; margin: 10px 0; border-radius: 5px; }
.info { color: blue; background: #d1ecf1; padding: 10px; margin: 10px 0; border-radius: 5px; }
pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>';

$errors = [];
$success = [];

// SQL для создания таблиц
$tables = [
    'tests' => "
        CREATE TABLE IF NOT EXISTS tests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            deck_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            questions_count INT DEFAULT 10,
            time_limit INT NULL COMMENT 'Время в минутах, NULL = без ограничений',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (deck_id) REFERENCES decks(id) ON DELETE CASCADE,
            INDEX idx_deck_id (deck_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'test_questions' => "
        CREATE TABLE IF NOT EXISTS test_questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            test_id INT NOT NULL,
            question TEXT NOT NULL,
            option_a VARCHAR(500) NOT NULL,
            option_b VARCHAR(500) NOT NULL,
            option_c VARCHAR(500) NOT NULL,
            option_d VARCHAR(500) NOT NULL,
            correct_answer ENUM('A', 'B', 'C', 'D') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE,
            INDEX idx_test_id (test_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'test_attempts' => "
        CREATE TABLE IF NOT EXISTS test_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            test_id INT NOT NULL,
            student_id INT NOT NULL,
            score INT NOT NULL DEFAULT 0 COMMENT 'Оценка в процентах (0-100)',
            correct_answers INT NOT NULL DEFAULT 0,
            total_questions INT NOT NULL DEFAULT 0,
            time_spent INT NOT NULL DEFAULT 0 COMMENT 'Время в секундах',
            completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_test_student (test_id, student_id),
            INDEX idx_student_id (student_id),
            INDEX idx_completed_at (completed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ",
    
    'test_answers' => "
        CREATE TABLE IF NOT EXISTS test_answers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            attempt_id INT NOT NULL,
            question_id INT NOT NULL,
            selected_answer ENUM('A', 'B', 'C', 'D') NOT NULL,
            is_correct BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (attempt_id) REFERENCES test_attempts(id) ON DELETE CASCADE,
            FOREIGN KEY (question_id) REFERENCES test_questions(id) ON DELETE CASCADE,
            UNIQUE KEY unique_attempt_question (attempt_id, question_id),
            INDEX idx_attempt_id (attempt_id),
            INDEX idx_question_id (question_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    "
];

echo '<div class="info">Начинаем создание таблиц...</div>';

try {
    $db->beginTransaction();

    foreach ($tables as $table_name => $sql) {
        echo "<h3>Создание таблицы: $table_name</h3>";
        echo "<pre>" . htmlspecialchars($sql) . "</pre>";
        
        try {
            $stmt = $db->prepare($sql);
            $result = $stmt->execute();
            
            if ($result) {
                echo "<div class='success'>✅ Таблица '$table_name' успешно создана или уже существует</div>";
                $success[] = $table_name;
            } else {
                $error_info = $stmt->errorInfo();
                throw new Exception("Ошибка при создании таблицы '$table_name': " . $error_info[2]);
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Ошибка при создании таблицы '$table_name': " . $e->getMessage() . "</div>";
            $errors[] = $table_name . ': ' . $e->getMessage();
        }
    }

    if (empty($errors)) {
        $db->commit();
        echo '<div class="success"><h2>🎉 Все таблицы успешно созданы!</h2></div>';
        
        // Проверяем создание таблиц
        echo '<h3>Проверка созданных таблиц:</h3>';
        $check_query = "SHOW TABLES LIKE 'test%'";
        $stmt = $db->prepare($check_query);
        $stmt->execute();
        $tables_created = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo '<ul>';
        foreach ($tables_created as $table) {
            echo "<li>✅ $table</li>";
        }
        echo '</ul>';
        
        // Показываем статистику
        echo '<h3>Статистика таблиц:</h3>';
        foreach (array_keys($tables) as $table_name) {
            try {
                $count_query = "SELECT COUNT(*) as count FROM $table_name";
                $stmt = $db->prepare($count_query);
                $stmt->execute();
                $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                echo "<div class='info'>Таблица '$table_name': $count записей</div>";
            } catch (Exception $e) {
                echo "<div class='error'>Ошибка при подсчете записей в '$table_name': " . $e->getMessage() . "</div>";
            }
        }
        
    } else {
        $db->rollback();
        echo '<div class="error"><h2>❌ Ошибки при создании таблиц</h2>';
        echo '<ul>';
        foreach ($errors as $error) {
            echo "<li>$error</li>";
        }
        echo '</ul></div>';
    }

} catch (Exception $e) {
    $db->rollback();
    echo '<div class="error"><h2>❌ Критическая ошибка</h2>';
    echo '<p>' . $e->getMessage() . '</p></div>';
}

echo '<hr>';
echo '<div class="info">';
echo '<h3>Инструкции:</h3>';
echo '<ol>';
echo '<li>Если все таблицы созданы успешно, система тестов готова к использованию</li>';
echo '<li>Теперь преподаватели могут создавать тесты в разделе "Управление тестами"</li>';
echo '<li>Ученики смогут проходить тесты в разделе "Тесты"</li>';
echo '<li>Этот скрипт можно запускать повторно - он не повредит существующие данные</li>';
echo '</ol>';
echo '</div>';

echo '<div class="info">';
echo '<h3>Структура системы тестов:</h3>';
echo '<ul>';
echo '<li><strong>tests</strong> - основная таблица тестов</li>';
echo '<li><strong>test_questions</strong> - вопросы тестов с вариантами ответов</li>';
echo '<li><strong>test_attempts</strong> - попытки прохождения тестов учениками</li>';
echo '<li><strong>test_answers</strong> - ответы учеников на конкретные вопросы</li>';
echo '</ul>';
echo '</div>';

echo '<p><a href="teacher/decks.php">← Вернуться к управлению колодами</a></p>';
?>
