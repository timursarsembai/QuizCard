<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';
require_once '../classes/Deck.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$deck = new Deck($db);

if (!$user->isLoggedIn() || $user->getRole() !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

$teacher_id = $_SESSION['user_id'];

echo "<h1>Отладка колод для teacher_id: $teacher_id</h1>";

// Получаем колоды с помощью debug метода
echo "<h2>Сырые данные из БД:</h2>";
$raw_decks = $deck->debugGetDecksByTeacher($teacher_id);

echo "<h2>Результат метода getDecksByTeacher():</h2>";
$processed_decks = $deck->getDecksByTeacher($teacher_id);
echo "<pre>";
echo "Processed decks count: " . count($processed_decks) . "\n";
foreach ($processed_decks as $deck_item) {
    echo "ID: {$deck_item['id']}, Name: {$deck_item['name']}, Words: {$deck_item['word_count']}, Students: {$deck_item['assigned_students']}\n";
}
echo "</pre>";

echo "<h2>Прямой SQL запрос:</h2>";
$query = "SELECT * FROM decks WHERE teacher_id = :teacher_id ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':teacher_id', $teacher_id);
$stmt->execute();
$direct_result = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<pre>";
echo "Direct SQL result count: " . count($direct_result) . "\n";
foreach ($direct_result as $deck_item) {
    echo "ID: {$deck_item['id']}, Name: {$deck_item['name']}, Teacher_ID: {$deck_item['teacher_id']}, Created: {$deck_item['created_at']}\n";
}
echo "</pre>";
?>
