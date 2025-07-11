<?php
/**
 * Простой тест для страницы email_verification_required.php
 */

// Запускаем буферизацию вывода для перехвата любого JSON
ob_start();

// Устанавливаем сессию как для преподавателя
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'teacher';
$_SESSION['email'] = 'test@example.com';
$_SESSION['email_verified'] = 0; // Не подтвержден

// Симулируем смену языка
$_GET['lang'] = 'en';

// Включаем страницу
include 'email_verification_required.php';

// Получаем вывод
$output = ob_get_clean();

// Проверяем, содержит ли вывод JSON-ошибку
if (strpos($output, '{"success":false,"error":"Invalid request method"}') !== false) {
    echo "❌ ОШИБКА: Найден JSON-ответ с ошибкой 'Invalid request method'\n";
    echo "Вывод содержит:\n";
    echo substr($output, 0, 500) . "...\n";
} else {
    echo "✅ УСПЕХ: JSON-ошибка 'Invalid request method' не найдена\n";
    echo "Страница работает корректно\n";
    
    // Проверяем, что это HTML
    if (strpos($output, '<!DOCTYPE html>') !== false) {
        echo "✅ Корректный HTML-документ\n";
    }
    
    // Проверяем, что язык был установлен
    if (strpos($output, 'lang="en"') !== false) {
        echo "✅ Язык корректно установлен в HTML\n";
    }
}
?>
