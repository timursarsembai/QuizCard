<?php
session_start();
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

// Проверим, что в базе данных
$teacher_id = 7; // Замените на ваш teacher_id

echo "<h1>Проверка таблицы decks для teacher_id = $teacher_id</h1>";

$query = "SELECT * FROM decks WHERE teacher_id = :teacher_id ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':teacher_id', $teacher_id);
$stmt->execute();
$decks = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Результаты SQL запроса:</h2>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Teacher ID</th><th>Name</th><th>Description</th><th>Color</th><th>Created At</th><th>Daily Word Limit</th></tr>";

foreach ($decks as $deck) {
    echo "<tr>";
    echo "<td>" . $deck['id'] . "</td>";
    echo "<td>" . $deck['teacher_id'] . "</td>";
    echo "<td>" . $deck['name'] . "</td>";
    echo "<td>" . $deck['description'] . "</td>";
    echo "<td>" . $deck['color'] . "</td>";
    echo "<td>" . $deck['created_at'] . "</td>";
    echo "<td>" . $deck['daily_word_limit'] . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<p><strong>Всего записей: " . count($decks) . "</strong></p>";

// Проверим на дубликаты по ID
$ids = array_column($decks, 'id');
$unique_ids = array_unique($ids);

if (count($ids) !== count($unique_ids)) {
    echo "<p style='color: red;'><strong>ВНИМАНИЕ: Найдены дублирующиеся ID!</strong></p>";
    $duplicates = array_diff_assoc($ids, $unique_ids);
    echo "<p>Дублирующиеся ID: " . implode(', ', $duplicates) . "</p>";
} else {
    echo "<p style='color: green;'><strong>Дубликатов по ID не найдено.</strong></p>";
}
?>
