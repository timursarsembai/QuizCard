<?php
/**
 * Скрипт миграции для обновления существующих баз данных QuizCard
 * Добавляет недостающие поля и таблицы к существующей установке
 */

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$database->isConnected()) {
    die('Ошибка подключения к базе данных: ' . $database->getError());
}

echo "<h1>🔄 Миграция базы данных QuizCard</h1>";

$migrations = [];

try {
    // Проверяем существующие таблицы и поля
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    // Миграция 1: Добавление полей email верификации
    $users_columns = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('email_verified', $users_columns)) {
        $db->exec("ALTER TABLE users ADD COLUMN email_verified BOOLEAN DEFAULT FALSE");
        $migrations[] = "✅ Добавлено поле email_verified";
    }
    
    if (!in_array('verification_token', $users_columns)) {
        $db->exec("ALTER TABLE users ADD COLUMN verification_token VARCHAR(255) NULL");
        $migrations[] = "✅ Добавлено поле verification_token";
    }
    
    if (!in_array('verification_token_expires', $users_columns)) {
        $db->exec("ALTER TABLE users ADD COLUMN verification_token_expires DATETIME NULL");
        $migrations[] = "✅ Добавлено поле verification_token_expires";
    }
    
    if (!in_array('last_verification_sent', $users_columns)) {
        $db->exec("ALTER TABLE users ADD COLUMN last_verification_sent DATETIME NULL");
        $migrations[] = "✅ Добавлено поле last_verification_sent";
    }
    
    // Миграция 2: Добавление поддержки аудио
    $vocabulary_columns = $db->query("SHOW COLUMNS FROM vocabulary")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('audio_path', $vocabulary_columns)) {
        $db->exec("ALTER TABLE vocabulary ADD COLUMN audio_path VARCHAR(500) NULL AFTER image_path");
        $migrations[] = "✅ Добавлено поле audio_path в vocabulary";
    }
    
    // Миграция 3: Создание таблиц тестов
    if (!in_array('tests', $tables)) {
        $db->exec("
            CREATE TABLE tests (
                id INT AUTO_INCREMENT PRIMARY KEY,
                deck_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                questions_count INT DEFAULT 10,
                time_limit INT NULL COMMENT 'Ограничение времени в минутах',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (deck_id) REFERENCES decks(id) ON DELETE CASCADE
            )
        ");
        $migrations[] = "✅ Создана таблица tests";
    }
    
    if (!in_array('test_questions', $tables)) {
        $db->exec("
            CREATE TABLE test_questions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                test_id INT NOT NULL,
                question TEXT NOT NULL,
                option_a VARCHAR(255) NOT NULL,
                option_b VARCHAR(255) NOT NULL,
                option_c VARCHAR(255) NOT NULL,
                option_d VARCHAR(255) NOT NULL,
                correct_answer CHAR(1) NOT NULL CHECK (correct_answer IN ('A', 'B', 'C', 'D')),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE
            )
        ");
        $migrations[] = "✅ Создана таблица test_questions";
    }
    
    if (!in_array('test_attempts', $tables)) {
        $db->exec("
            CREATE TABLE test_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                test_id INT NOT NULL,
                student_id INT NOT NULL,
                correct_answers INT NOT NULL DEFAULT 0,
                total_questions INT NOT NULL DEFAULT 0,
                score DECIMAL(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Процент правильных ответов',
                time_spent INT NULL COMMENT 'Время в секундах',
                started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                completed_at TIMESTAMP NULL,
                FOREIGN KEY (test_id) REFERENCES tests(id) ON DELETE CASCADE,
                FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
        $migrations[] = "✅ Создана таблица test_attempts";
    }
    
    if (!in_array('test_answers', $tables)) {
        $db->exec("
            CREATE TABLE test_answers (
                id INT AUTO_INCREMENT PRIMARY KEY,
                attempt_id INT NOT NULL,
                question_id INT NOT NULL,
                selected_answer CHAR(1) NULL COMMENT 'A, B, C, D или NULL если не отвечено',
                is_correct BOOLEAN NOT NULL DEFAULT FALSE,
                answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (attempt_id) REFERENCES test_attempts(id) ON DELETE CASCADE,
                FOREIGN KEY (question_id) REFERENCES test_questions(id) ON DELETE CASCADE
            )
        ");
        $migrations[] = "✅ Создана таблица test_answers";
    }
    
    // Миграция 4: Создание индексов для производительности
    $indexes = $db->query("SHOW INDEX FROM users WHERE Key_name = 'idx_users_email'")->fetchAll();
    if (empty($indexes)) {
        $db->exec("CREATE INDEX idx_users_email ON users(email)");
        $migrations[] = "✅ Создан индекс idx_users_email";
    }
    
    $indexes = $db->query("SHOW INDEX FROM users WHERE Key_name = 'idx_users_verification_token'")->fetchAll();
    if (empty($indexes)) {
        $db->exec("CREATE INDEX idx_users_verification_token ON users(verification_token)");
        $migrations[] = "✅ Создан индекс idx_users_verification_token";
    }
    
    $indexes = $db->query("SHOW INDEX FROM vocabulary WHERE Key_name = 'idx_vocabulary_audio_path'")->fetchAll();
    if (empty($indexes)) {
        $db->exec("CREATE INDEX idx_vocabulary_audio_path ON vocabulary(audio_path)");
        $migrations[] = "✅ Создан индекс idx_vocabulary_audio_path";
    }
    
    echo "<h2>🎉 Результаты миграции:</h2>";
    if (empty($migrations)) {
        echo "<p>✅ База данных уже актуальна, миграция не требуется.</p>";
    } else {
        echo "<ul>";
        foreach ($migrations as $migration) {
            echo "<li>$migration</li>";
        }
        echo "</ul>";
    }
    
    echo "<p><strong>Миграция завершена успешно!</strong></p>";
    echo "<p><a href='../'>← Вернуться на главную</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Ошибка миграции:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Проверьте права доступа к базе данных и повторите попытку.</p>";
}
?>
