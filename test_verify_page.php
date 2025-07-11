<?php
/**
 * Простой тест для страницы verify_email.php
 */

// Запускаем буферизацию вывода для перехвата любого JSON
ob_start();

// Симулируем переход по ссылке с токеном и языком
$_GET['token'] = 'test_token_123';
$_GET['lang'] = 'ru';

// Включаем страницу
include 'verify_email.php';

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
    if (strpos($output, 'lang="ru"') !== false) {
        echo "✅ Язык корректно установлен в HTML\n";
    }
}
?>
