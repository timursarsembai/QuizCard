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

/**
 * Функция для получения текущего языка
 * @return string Текущий язык
 */
function getCurrentLanguage() {
    // Приоритет: сессия -> язык браузера -> русский по умолчанию
    if (isset($_SESSION['language']) && in_array($_SESSION['language'], ['kk', 'ru', 'en'])) {
        return $_SESSION['language'];
    }
    
    // Если в сессии нет языка, попробуем определить из браузера
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        if (in_array($browser_lang, ['kk', 'ru', 'en'])) {
            $_SESSION['language'] = $browser_lang;
            return $browser_lang;
        }
    }
    
    // По умолчанию русский
    $_SESSION['language'] = 'ru';
    return 'ru';
}

/**
 * Основная функция перевода
 * @param string $key Ключ перевода
 * @return string Переведенный текст
 */
function translate($key) {
    $current_lang = getCurrentLanguage();
    return getTranslation($key, $current_lang, 'ru');
}
?>
