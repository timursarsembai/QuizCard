<?php
/**
 * Временное решение для VPS где DocumentRoot нельзя изменить
 * Этот файл должен быть размещен в корне проекта (не в public/)
 * Он перенаправляет все запросы на правильную public/ директорию
 */

// Проверяем, что мы находимся в корне проекта
if (!file_exists('public/index.php')) {
    die('Ошибка: Файл public/index.php не найден. Убедитесь что структура проекта правильная.');
}

// Получаем URI запроса
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';

// Убираем начальный слеш и возможные параметры
$path = ltrim(parse_url($request_uri, PHP_URL_PATH), '/');

// Если это корневой запрос или запрос к директории
if (empty($path) || $path === 'test' || $path === 'test/') {
    // Перенаправляем на public/
    header('Location: public/', true, 301);
    exit;
}

// Для всех остальных запросов пытаемся найти файл в public/
$public_file = 'public/' . $path;

if (file_exists($public_file) && is_file($public_file)) {
    // Если это статический файл, отдаем его
    $mime_type = mime_content_type($public_file);
    header('Content-Type: ' . $mime_type);
    readfile($public_file);
    exit;
} else {
    // Если файл не найден, перенаправляем на роутер в public/
    $_SERVER['REQUEST_URI'] = '/' . $path;
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    require_once 'public/index.php';
}
?>
