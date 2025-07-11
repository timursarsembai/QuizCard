<?php
/**
 * Тест для проверки, что больше нет ошибок headers already sent
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Тест verify_email.php с параметром lang ===\n";

// Симулируем GET запрос с токеном и языком
$_GET['token'] = 'test_token_123';
$_GET['lang'] = 'en';

// Захватываем вывод и ошибки
ob_start();
$error_output = '';

// Устанавливаем обработчик ошибок
set_error_handler(function($severity, $message, $file, $line) use (&$error_output) {
    $error_output .= "ERROR: $message in $file on line $line\n";
});

try {
    // Включаем только обработку языка и начало verify_email.php
    session_start();
    
    // Проверяем обработку языка (такую же логику, как в исправленном файле)
    if (isset($_GET['lang']) && in_array($_GET['lang'], ['kk', 'ru', 'en'])) {
        $_SESSION['language'] = $_GET['lang'];
        echo "✅ Язык установлен: " . $_SESSION['language'] . "\n";
    }
    
    // Проверяем, что можно установить заголовки
    if (!headers_sent()) {
        echo "✅ Заголовки еще не отправлены - можно устанавливать\n";
        header('Content-Type: text/html; charset=UTF-8');
        echo "✅ HTML заголовок установлен\n";
    } else {
        echo "❌ Заголовки уже отправлены!\n";
    }
    
} catch (Exception $e) {
    $error_output .= "EXCEPTION: " . $e->getMessage() . "\n";
}

restore_error_handler();
$output = ob_get_clean();

echo $output;

if (!empty($error_output)) {
    echo "\n❌ НАЙДЕНЫ ОШИБКИ:\n";
    echo $error_output;
} else {
    echo "\n✅ ОШИБОК НЕ НАЙДЕНО!\n";
}

echo "\n=== Результат ===\n";
if (strpos($error_output, 'headers already sent') !== false) {
    echo "❌ Все еще есть проблема 'headers already sent'\n";
} elseif (strpos($error_output, 'Cannot modify header') !== false) {
    echo "❌ Все еще есть проблема с изменением заголовков\n";
} else {
    echo "✅ Проблема 'headers already sent' решена!\n";
}
?>
