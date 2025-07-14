<?php
// Инициализация языка при первом посещении
if (!isset($_SESSION['language'])) {
    // Пытаемся определить язык из заголовков браузера
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $browser_lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
        if (in_array($browser_lang, ['kk', 'ru', 'en'])) {
            $_SESSION['language'] = $browser_lang;
        } else {
            $_SESSION['language'] = 'ru'; // По умолчанию русский
        }
    } else {
        $_SESSION['language'] = 'ru'; // По умолчанию русский
    }
}
?>
