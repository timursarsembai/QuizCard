<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "Подключение к базе данных успешно!\n";
    
    // Проверим, есть ли необходимые таблицы
    $tables = ['users', 'decks', 'vocabulary', 'tests', 'test_questions', 'test_attempts'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "Таблица $table: ✓ существует\n";
            } else {
                echo "Таблица $table: ✗ НЕ существует\n";
            }
        } catch (PDOException $e) {
            echo "Ошибка при проверке таблицы $table: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "Ошибка подключения к базе данных: " . $database->getError() . "\n";
}
?>
