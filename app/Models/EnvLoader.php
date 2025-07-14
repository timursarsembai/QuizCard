<?php
/**
 * Простой загрузчик переменных окружения
 * Совместимый с классическим хостингом (без Composer)
 */
namespace App\Models;

class EnvLoader {
    private static $loaded = false;
    private static $variables = [];

    /**
     * Загрузить переменные из .env файла
     */
    public static function load($envPath = null) {
        if (self::$loaded) {
            return;
        }

        if ($envPath === null) {
            $envPath = dirname(dirname(__DIR__)) . '/.env';
        }

        if (!file_exists($envPath)) {
            // В production среде файл .env может отсутствовать
            // если переменные заданы на уровне сервера
            self::$loaded = true;
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Пропускаем комментарии
            if (strpos($line, '#') === 0 || empty($line)) {
                continue;
            }

            // Парсим переменную
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Удаляем кавычки если есть
                $value = trim($value, '"\'');

                // Устанавливаем переменную окружения
                if (!getenv($key)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }

                self::$variables[$key] = $value;
            }
        }

        self::$loaded = true;
    }

    /**
     * Получить переменную окружения
     */
    public static function get($key, $default = null) {
        self::load();
        
        // Пробуем разные источники
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }

        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }

        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }

        if (isset(self::$variables[$key])) {
            return self::$variables[$key];
        }

        return $default;
    }

    /**
     * Проверить, загружены ли переменные
     */
    public static function isLoaded() {
        return self::$loaded;
    }

    /**
     * Получить все загруженные переменные
     */
    public static function getAll() {
        self::load();
        return self::$variables;
    }

    /**
     * Принудительно перезагрузить переменные
     */
    public static function reload($envPath = null) {
        self::$loaded = false;
        self::$variables = [];
        self::load($envPath);
    }

    /**
     * Проверить обязательные переменные
     */
    public static function requireVars($vars) {
        $missing = [];
        
        foreach ($vars as $var) {
            if (self::get($var) === null) {
                $missing[] = $var;
            }
        }

        if (!empty($missing)) {
            throw new \RuntimeException('Отсутствуют обязательные переменные окружения: ' . implode(', ', $missing));
        }
    }
}
