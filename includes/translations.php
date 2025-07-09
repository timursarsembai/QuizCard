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

/**
 * Функция для получения перевода
 * @param string $key Ключ перевода
 * @param string $lang Язык (kk, ru, en)
 * @param string $fallback_lang Резервный язык (по умолчанию 'en')
 * @return string Переведенный текст
 */
function getTranslation($key, $lang = 'en', $fallback_lang = 'en') {
    global $translations;
    
    // Проверяем, есть ли перевод для выбранного языка
    if (isset($translations[$lang][$key])) {
        return $translations[$lang][$key];
    }
    
    // Если нет, пробуем резервный язык
    if ($lang !== $fallback_lang && isset($translations[$fallback_lang][$key])) {
        return $translations[$fallback_lang][$key];
    }
    
    // Если и резервного нет, возвращаем ключ
    return $key;
}

/**
 * Короткая функция для перевода (алиас)
 * @param string $key Ключ перевода
 * @param string $lang Язык
 * @return string Переведенный текст
 */
function t($key, $lang = 'en') {
    return getTranslation($key, $lang);
}
?>
