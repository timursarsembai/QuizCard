<?php
// Подключение языковых файлов
$translations = [];

// Загрузка переводов для казахского языка
if (file_exists(__DIR__ . '/lang/kk.php')) {
    $kk_translations = include __DIR__ . '/lang/kk.php';
    if (is_array($kk_translations)) {
        $translations['kk'] = $kk_translations;
    }
}

// Загрузка переводов для русского языка  
if (file_exists(__DIR__ . '/lang/ru.php')) {
    $ru_translations = include __DIR__ . '/lang/ru.php';
    if (is_array($ru_translations)) {
        $translations['ru'] = $ru_translations;
    }
}

// Загрузка переводов для английского языка
if (file_exists(__DIR__ . '/lang/en.php')) {
    $en_translations = include __DIR__ . '/lang/en.php';
    if (is_array($en_translations)) {
        $translations['en'] = $en_translations;
    }
}
?>
